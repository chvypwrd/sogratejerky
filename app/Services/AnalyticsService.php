<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

class AnalyticsService
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    public function enabled(): bool
    {
        return $this->measurementId() !== null
            && filter_var($this->settings->get('analytics.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function measurementId(): ?string
    {
        $id = strtoupper(trim((string) $this->settings->get('analytics.measurement_id', '')));

        return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) === 1 ? $id : null;
    }

    public function queue(string $event, array $params): void
    {
        if (! $this->enabled()) {
            return;
        }

        session()->push('ga4_events', [
            'event' => $event,
            'params' => $params,
        ]);
    }

    public function pullEvents(): array
    {
        return session()->pull('ga4_events', []);
    }

    public function cartItem(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant?->product;

        return [
            'item_id' => $variant?->sku ?: (string) $variant?->id,
            'item_name' => $product?->name ?? 'SOGRATE product',
            'item_variant' => $variant?->name,
            'item_brand' => 'SOGRATE',
            'price' => round(((int) ($variant?->price ?? 0)) / 100, 2),
            'quantity' => (int) $item->quantity,
        ];
    }

    public function orderItem(OrderItem $item): array
    {
        return [
            'item_id' => $item->sku ?: (string) $item->product_variant_id,
            'item_name' => $item->product_name,
            'item_variant' => $item->variant_name,
            'item_brand' => 'SOGRATE',
            'price' => round(((int) $item->unit_price_cents) / 100, 2),
            'quantity' => (int) $item->quantity,
        ];
    }

    public function productItem(Product $product, ?ProductVariant $variant): array
    {
        return [
            'item_id' => $variant?->sku ?: (string) $variant?->id,
            'item_name' => $product->name,
            'item_variant' => $variant?->name,
            'item_brand' => 'SOGRATE',
            'price' => round(((int) ($variant?->price ?? 0)) / 100, 2),
            'quantity' => 1,
        ];
    }

    public function variantItem(ProductVariant $variant, int $quantity): array
    {
        $variant->loadMissing('product');

        return [
            'item_id' => $variant->sku ?: (string) $variant->id,
            'item_name' => $variant->product?->name ?? 'SOGRATE product',
            'item_variant' => $variant->name,
            'item_brand' => 'SOGRATE',
            'price' => round($variant->price / 100, 2),
            'quantity' => $quantity,
        ];
    }

    public function cartItems(Cart $cart): array
    {
        $cart->loadMissing('items.variant.product');

        return $cart->items->map(fn (CartItem $item) => $this->cartItem($item))->values()->all();
    }

    public function orderItems(Order $order): array
    {
        $order->loadMissing('items');

        return $order->items->map(fn (OrderItem $item) => $this->orderItem($item))->values()->all();
    }

    public function purchaseParams(Order $order): array
    {
        return [
            'transaction_id' => $order->order_number,
            'value' => round($order->total_cents / 100, 2),
            'tax' => round($order->tax_cents / 100, 2),
            'shipping' => round($order->fulfillment_fee_cents / 100, 2),
            'currency' => strtoupper($order->currency),
            'items' => $this->orderItems($order),
        ];
    }
}
