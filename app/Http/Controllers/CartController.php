<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->resolveCart();
        $cart->load(['items.variant.product']);

        return view('cart.index', [
            'cart' => $cart,
            'subtotal' => $this->cartService->subtotal($cart),
            'itemCount' => $this->cartService->itemCount($cart),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));
        $expectedProduct = $request->filled('product_id')
            ? Product::find($request->integer('product_id'))
            : null;

        $this->cartService->addVariant($variant, $request->integer('quantity'), $expectedProduct);

        return back()
            ->with('status', 'Added to your cart.')
            ->with('cart_added', true);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $this->cartService->updateItem($cart, $cartItem, $request->integer('quantity'));

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $this->cartService->removeItem($cart, $cartItem);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Item removed from your cart.');
    }

    public function clear(): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $this->cartService->clear($cart);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart cleared.');
    }
}
