<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user()
                ->orders()
                ->with(['items' => fn ($query) => $query->orderBy('id')])
                ->withCount('items')
                ->latest('placed_at')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('account.orders.show', [
            'order' => $order->load('items'),
        ]);
    }

    public function reorder(Request $request, Order $order, CartService $cartService): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        $order->loadMissing('items.variant.product');
        $cart = $cartService->resolveCart();
        $added = 0;
        $skipped = [];

        DB::transaction(function () use ($cartService, $cart, $order, &$added, &$skipped) {
            foreach ($order->items as $item) {
                $variant = $item->variant;

                if (
                    ! $variant
                    || ! $variant->is_active
                    || $variant->price <= 0
                    || ! $variant->product
                    || ! $variant->product->is_active
                ) {
                    $skipped[] = $item->product_name;

                    continue;
                }

                try {
                    $cartService->addVariantToCart($cart, $variant, max(1, (int) $item->quantity));
                    $added++;
                } catch (ValidationException) {
                    $skipped[] = $item->product_name;
                }
            }
        });

        $skipped = array_values(array_unique(array_filter($skipped)));

        if ($added === 0) {
            return redirect()
                ->route('account.orders.index')
                ->with('error', 'None of the items from that order are currently available to reorder.');
        }

        $response = redirect()
            ->route('cart.index')
            ->with('status', $added === 1 ? 'Item added to your cart.' : $added.' items added to your cart.');

        if ($skipped !== []) {
            $response->with('error', 'Some unavailable items were skipped: '.implode(', ', $skipped).'.');
        }

        return $response;
    }
}
