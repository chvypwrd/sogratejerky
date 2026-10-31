<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickOrderRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuickOrderController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function index(): View
    {
        $products = Product::catalog()
            ->with('availableVariants')
            ->ordered()
            ->get();

        return view('quick-order.index', compact('products'));
    }

    public function store(QuickOrderRequest $request): RedirectResponse
    {
        $submitted = $request->input('items', []);
        $selection = [];
        $max = $this->cartService->maxQuantity();

        foreach ($submitted as $variantId => $quantity) {
            if (! ctype_digit((string) $variantId)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected items are invalid.',
                ]);
            }

            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

            if ($quantity === false || $quantity < 0 || $quantity > $max) {
                throw ValidationException::withMessages([
                    'items' => 'Quantities must be between 0 and '.$max.'.',
                ]);
            }

            if ($quantity > 0) {
                $selection[(int) $variantId] = $quantity;
            }
        }

        if (empty($selection)) {
            return back()->with('error', 'Select at least one item before adding to your cart.');
        }

        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($selection))
            ->get()
            ->keyBy('id');

        foreach ($selection as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant || ! $variant->is_active || $variant->price <= 0 || ! $variant->product || ! $variant->product->is_active) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected items are no longer available.',
                ]);
            }
        }

        $cart = $this->cartService->resolveCart();

        DB::transaction(function () use ($cart, $selection, $variants) {
            foreach ($selection as $variantId => $quantity) {
                $this->cartService->addVariantToCart($cart, $variants->get($variantId), $quantity, 'items');
            }
        });

        return redirect()
            ->route('cart.index')
            ->with('status', 'Selected items added to your cart.');
    }
}
