<?php

namespace Tests\Feature\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function orderWithItem(array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes);

        $order->items()->create([
            'product_name' => 'Snapshot Brisket',
            'variant_name' => 'Original 8 oz',
            'sku' => 'SGR-SNAP-001',
            'quantity' => 2,
            'unit_price_cents' => 1250,
            'line_total_cents' => 2500,
            'weight_grams' => 227,
        ]);

        return $order;
    }

    public function test_order_administration_requires_order_permissions(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.index'))
            ->assertForbidden();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('orders.view');

        $this->actingAs($viewer)
            ->get(route('admin.orders.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('admin.orders.ready-for-pickup', $order))
            ->assertForbidden();
    }

    public function test_admin_can_search_and_filter_orders(): void
    {
        $pickup = $this->orderWithItem([
            'order_number' => 'SGR-M09-ALICE',
            'customer_name' => 'Alice Pickup',
            'customer_email' => 'alice@example.com',
            'fulfillment_method' => FulfillmentMethod::Pickup,
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Placed,
        ]);

        $shipping = $this->orderWithItem([
            'order_number' => 'SGR-M09-BOB',
            'customer_name' => 'Bob Shipping',
            'customer_email' => 'bob@example.com',
            'fulfillment_method' => FulfillmentMethod::Shipping,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['search' => 'Alice']))
            ->assertOk()
            ->assertSee($pickup->order_number)
            ->assertDontSee($shipping->order_number);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['payment_status' => 'pending']))
            ->assertOk()
            ->assertSee($shipping->order_number)
            ->assertDontSee($pickup->order_number);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['fulfillment_method' => 'pickup']))
            ->assertOk()
            ->assertSee($pickup->order_number)
            ->assertDontSee($shipping->order_number);
    }

    public function test_order_detail_uses_historical_snapshots(): void
    {
        $order = $this->orderWithItem([
            'customer_name' => 'Snapshot Customer',
            'fulfillment_address_snapshot' => [
                'recipient_name' => 'Snapshot Customer',
                'company' => null,
                'address_line_1' => '123 Snapshot Way',
                'address_line_2' => null,
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'United States',
                'phone' => null,
            ],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Snapshot Brisket')
            ->assertSee('SGR-SNAP-001')
            ->assertSee('123 Snapshot Way')
            ->assertSee('$12.50');
    }

    public function test_pickup_fulfillment_transitions_record_activity_and_notifications(): void
    {
        Notification::fake();

        $order = Order::factory()->paid()->pickup()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.ready-for-pickup', $order), ['note' => 'Packed and waiting.'])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(FulfillmentStatus::ReadyForPickup, $order->fulfillment_status);
        $this->assertNotNull($order->ready_for_pickup_at);
        $this->assertSame(1, $order->activities()->count());
        $activity = $order->activities()->first();
        $this->assertSame(OrderEvent::ReadyForPickup, $activity->event);
        $this->assertSame($admin->id, $activity->actor_id);
        $this->assertSame('Packed and waiting.', $activity->note);
        Notification::assertSentOnDemand(
            OrderStatusNotification::class,
            fn ($notification, $channels, $notifiable) => $notification->order->is($order)
                && $notifiable->routes['mail'] === $order->customer_email
        );
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);

        $this->actingAs($admin)
            ->post(route('admin.orders.ready-for-pickup', $order), ['note' => 'Duplicate action.'])
            ->assertRedirect();

        $this->assertSame(1, $order->activities()->count());
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);

        $this->actingAs($admin)
            ->post(route('admin.orders.picked-up', $order), ['note' => 'Collected at counter.'])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::PickedUp, $order->fulfillment_status);
        $this->assertNotNull($order->picked_up_at);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertSame(2, $order->activities()->count());
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 2);
    }

    public function test_local_delivery_transitions_record_progress(): void
    {
        Notification::fake();

        $order = Order::factory()->paid()->localDelivery()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.orders.out-for-delivery', $order))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::OutForDelivery, $order->fulfillment_status);
        $this->assertNotNull($order->out_for_delivery_at);

        $this->actingAs($admin)
            ->post(route('admin.orders.delivered', $order))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::Delivered, $order->fulfillment_status);
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertSame(2, $order->activities()->count());
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 2);
    }

    public function test_shipping_requires_tracking_or_untracked_exception(): void
    {
        Notification::fake();

        $order = Order::factory()->paid()->shipping()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.shipped', $order), [
                'shipping_carrier' => 'ups',
            ])
            ->assertSessionHasErrors('tracking_number');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $order->fresh()->fulfillment_status);
        Notification::assertNothingSent();

        $this->actingAs($admin)
            ->post(route('admin.orders.shipped', $order), [
                'shipping_carrier' => 'ups',
                'tracking_number' => '1Z999AA10123456784',
                'note' => 'Left the dock.',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::Shipped, $order->fulfillment_status);
        $this->assertSame(ShippingCarrier::Ups, $order->shipping_carrier);
        $this->assertSame('1Z999AA10123456784', $order->tracking_number);
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertStringContainsString('ups.com/track', $order->tracking_url);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_untracked_shipping_is_allowed(): void
    {
        Notification::fake();

        $order = Order::factory()->paid()->shipping()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.shipped', $order), [
                'shipping_carrier' => 'other',
                'untracked' => '1',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::Shipped, $order->fulfillment_status);
        $this->assertNull($order->tracking_number);
        $this->assertSame(ShippingCarrier::Other, $order->shipping_carrier);
    }

    public function test_fulfillment_rejects_wrong_method_and_unpaid_orders(): void
    {
        Notification::fake();

        $pickup = Order::factory()->paid()->pickup()->create();
        $unpaid = Order::factory()->pickup()->create();

        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $pickup))
            ->post(route('admin.orders.out-for-delivery', $pickup))
            ->assertSessionHasErrors('order');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $pickup->fresh()->fulfillment_status);

        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $unpaid))
            ->post(route('admin.orders.ready-for-pickup', $unpaid))
            ->assertSessionHasErrors('order');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $unpaid->fresh()->fulfillment_status);
        Notification::assertNothingSent();
    }

    public function test_cancellation_is_limited_to_unpaid_unfulfilled_orders(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $unpaid = Order::factory()->pickup()->create();
        $paid = Order::factory()->paid()->pickup()->create();
        $fulfilled = Order::factory()->paid()->shipping()->create([
            'fulfillment_status' => FulfillmentStatus::Shipped,
            'fulfilled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.cancel', $unpaid), ['reason' => 'Customer request.'])
            ->assertRedirect();

        $unpaid->refresh();

        $this->assertSame(OrderStatus::Cancelled, $unpaid->order_status);
        $this->assertNotNull($unpaid->cancelled_at);
        $this->assertSame('Customer request.', $unpaid->cancellation_reason);
        $this->assertSame(1, $unpaid->activities()->count());
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $unpaid))
            ->post(route('admin.orders.ready-for-pickup', $unpaid))
            ->assertSessionHasErrors('order');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $unpaid->fresh()->fulfillment_status);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $paid))
            ->post(route('admin.orders.cancel', $paid), ['reason' => 'Cannot cancel paid.'])
            ->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Placed, $paid->fresh()->order_status);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $fulfilled))
            ->post(route('admin.orders.cancel', $fulfilled), ['reason' => 'Cannot cancel fulfilled.'])
            ->assertSessionHasErrors('order');

        $this->assertSame(FulfillmentStatus::Shipped, $fulfilled->fresh()->fulfillment_status);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_shipped_notification_contains_carrier_and_tracking_details(): void
    {
        $order = Order::factory()->paid()->shipping()->create([
            'shipping_carrier' => ShippingCarrier::Usps,
            'tracking_number' => '9400111899223856928499',
        ]);

        $mail = (new OrderStatusNotification($order, OrderEvent::Shipped))->toMail(new AnonymousNotifiable);

        $this->assertSame('Order '.$order->order_number.' has shipped', $mail->subject);
        $this->assertStringContainsString('USPS', implode(' ', $mail->introLines));
        $this->assertStringContainsString('9400111899223856928499', implode(' ', $mail->introLines));
        $this->assertStringContainsString('tools.usps.com', $mail->actionUrl);
    }

    public function test_customer_order_detail_shows_shipping_progress(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->shipping()->create([
            'user_id' => $user->id,
            'shipping_carrier' => ShippingCarrier::Fedex,
            'tracking_number' => '123456789012',
            'fulfillment_status' => FulfillmentStatus::Shipped,
            'shipped_at' => now(),
            'fulfilled_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('FedEx')
            ->assertSee('123456789012')
            ->assertSee('Shipped');
    }
}
