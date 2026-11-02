<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\FulfillmentSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AnalyticsService;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private AnalyticsService $analytics,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->resolveCart();
        $cart->load(['items.variant.product']);

        $subtotal = $this->cartService->subtotal($cart);

        $this->analytics->queue('view_cart', [
            'currency' => 'USD',
            'value' => round($subtotal / 100, 2),
            'items' => $this->analytics->cartItems($cart),
        ]);

        return view('cart.index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'itemCount' => $this->cartService->itemCount($cart),
            'fulfillmentSettings' => FulfillmentSetting::current(),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));
        $expectedProduct = $request->filled('product_id')
            ? Product::find($request->integer('product_id'))
            : null;

        $quantity = $request->integer('quantity');
        $cart = $this->cartService->addVariant($variant, $quantity, $expectedProduct);
        $cartItem = $cart->items()->with('variant.product')->where('product_variant_id', $variant->id)->first();

        if ($cartItem) {
            $this->queueItemEvent('add_to_cart', $this->analytics->cartItem($cartItem), $quantity);
        }

        return back()
            ->with('status', 'Added to your cart.')
            ->with('cart_added', true);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $oldQuantity = (int) $cartItem->quantity;
        $quantity = $request->integer('quantity');
        $cartItem->load('variant.product');
        $itemData = $this->analytics->cartItem($cartItem);

        $this->cartService->updateItem($cart, $cartItem, $quantity);

        if ($quantity === 0) {
            $this->queueItemEvent('remove_from_cart', $itemData, $oldQuantity);
        } elseif ($quantity > $oldQuantity) {
            $this->queueItemEvent('add_to_cart', $itemData, $quantity - $oldQuantity);
        } elseif ($quantity < $oldQuantity) {
            $this->queueItemEvent('remove_from_cart', $itemData, $oldQuantity - $quantity);
        }

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $cartItem->load('variant.product');
        $itemData = $this->analytics->cartItem($cartItem);
        $quantity = (int) $cartItem->quantity;

        $this->cartService->removeItem($cart, $cartItem);
        $this->queueItemEvent('remove_from_cart', $itemData, $quantity);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Item removed from your cart.');
    }

    public function clear(): RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $cart->load('items.variant.product');

        foreach ($cart->items as $item) {
            $this->queueItemEvent('remove_from_cart', $this->analytics->cartItem($item), (int) $item->quantity);
        }

        $this->cartService->clear($cart);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart cleared.');
    }

    private function queueItemEvent(string $event, array $item, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $item['quantity'] = $quantity;

        $this->analytics->queue($event, [
            'currency' => 'USD',
            'value' => round($item['price'] * $quantity, 2),
            'items' => [$item],
        ]);
    }
}
