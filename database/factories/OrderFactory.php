<?php

namespace Database\Factories;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        $subtotal = 2500;
        $fee = 500;

        return [
            'order_number' => 'SGR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'checkout_token' => Str::random(64),
            'user_id' => null,
            'customer_name' => $faker->name(),
            'customer_email' => $faker->safeEmail(),
            'customer_phone' => $faker->phoneNumber(),
            'currency' => 'USD',
            'subtotal_cents' => $subtotal,
            'fulfillment_fee_cents' => $fee,
            'tax_cents' => 0,
            'total_cents' => $subtotal + $fee,
            'fulfillment_method' => FulfillmentMethod::Shipping,
            'order_status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'billing_address_snapshot' => [
                'recipient_name' => $faker->name(),
                'company' => null,
                'address_line_1' => $faker->streetAddress(),
                'address_line_2' => null,
                'city' => $faker->city(),
                'state' => $faker->stateAbbr(),
                'postal_code' => $faker->postcode(),
                'country' => 'United States',
                'phone' => null,
            ],
            'fulfillment_address_snapshot' => [
                'recipient_name' => $faker->name(),
                'company' => null,
                'address_line_1' => $faker->streetAddress(),
                'address_line_2' => null,
                'city' => $faker->city(),
                'state' => $faker->stateAbbr(),
                'postal_code' => $faker->postcode(),
                'country' => 'United States',
                'phone' => null,
            ],
            'pickup_location_snapshot' => null,
            'fulfillment_details_snapshot' => [
                'method' => FulfillmentMethod::Shipping->value,
                'label' => FulfillmentMethod::Shipping->label(),
                'fee_cents' => $fee,
                'tax_cents' => 0,
            ],
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'order_status' => OrderStatus::Placed,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }

    public function pickup(): static
    {
        return $this->state(fn () => [
            'fulfillment_method' => FulfillmentMethod::Pickup,
            'fulfillment_fee_cents' => 0,
            'total_cents' => 2500,
            'fulfillment_address_snapshot' => null,
            'pickup_location_snapshot' => [
                'id' => 1,
                'name' => 'Sograte Pickup',
                'address' => '100 Main Street, Austin, TX 78701',
                'contact_details' => 'Call 555-0100',
                'instructions' => 'Ask for your order number at the counter.',
                'fee_cents' => 0,
            ],
        ]);
    }

    public function localDelivery(): static
    {
        return $this->state(fn () => [
            'fulfillment_method' => FulfillmentMethod::LocalDelivery,
        ]);
    }

    public function shipping(): static
    {
        return $this->state(fn () => [
            'fulfillment_method' => FulfillmentMethod::Shipping,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'order_status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
