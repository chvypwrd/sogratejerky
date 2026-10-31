<?php

namespace App\Services;

use App\Enums\OrderEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function createFromCheckout(Cart $cart, array $data, array $summary, ?User $user, string $checkoutToken): Order
    {
        $existing = Order::where('checkout_token', $checkoutToken)->first();

        if ($existing) {
            return $existing;
        }

        $cart->loadMissing('items.variant.product');

        return DB::transaction(function () use ($cart, $data, $summary, $user, $checkoutToken) {
            $placedAt = now();

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'checkout_token' => $checkoutToken,
                'user_id' => $user?->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'currency' => 'USD',
                'subtotal_cents' => $summary['subtotal_cents'],
                'fulfillment_fee_cents' => $summary['fulfillment_fee_cents'],
                'tax_cents' => $summary['tax_cents'],
                'total_cents' => $summary['total_cents'],
                'fulfillment_method' => $summary['fulfillment_method']->value,
                'order_status' => 'pending_payment',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'billing_address_snapshot' => $summary['billing_address'],
                'fulfillment_address_snapshot' => $summary['fulfillment_address'],
                'pickup_location_snapshot' => $summary['pickup_location'],
                'fulfillment_details_snapshot' => [
                    'method' => $summary['fulfillment_method']->value,
                    'label' => $summary['fulfillment_method']->label(),
                    'fee_cents' => $summary['fulfillment_fee_cents'],
                    'tax_cents' => $summary['tax_cents'],
                ],
                'placed_at' => $placedAt,
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->variant;
                $product = $variant->product;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $variant->price,
                    'line_total_cents' => $variant->price * $item->quantity,
                    'weight_grams' => $variant->weight,
                ]);
            }

            $order->activities()->create([
                'event' => OrderEvent::OrderCreated->value,
                'previous_status' => null,
                'new_status' => $order->order_status->value,
                'actor_id' => $user?->id,
                'actor_name' => $user?->name ?? 'Customer',
                'note' => 'Order created at checkout.',
                'occurred_at' => $placedAt,
            ]);

            return $order;
        });
    }

    private function orderNumber(): string
    {
        do {
            $number = 'SGR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
