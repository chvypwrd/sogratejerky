<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class TestOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function orderWithPayment(bool $livemode = false, bool $isTestOrder = false): Order
    {
        $order = Order::factory()->paid()->shipping()->create([
            'is_test_order' => $isTestOrder,
        ]);

        $intentId = 'pi_admin_'.Str::lower(Str::random(10));

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => $intentId,
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->stripe->intents[$intentId] = (object) [
            'id' => $intentId,
            'livemode' => $livemode,
        ];

        return $order;
    }

    public function test_admin_can_mark_verified_stripe_test_order_and_activity_is_recorded(): void
    {
        $admin = $this->admin();
        $order = $this->orderWithPayment(false);

        $this->actingAs($admin)
            ->post(route('admin.orders.test-status', $order), ['is_test_order' => '1'])
            ->assertRedirect();

        $this->assertTrue($order->fresh()->is_test_order);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'event' => OrderEvent::TestOrderMarked->value,
            'actor_id' => $admin->id,
            'new_status' => 'test',
        ]);
        $this->assertCount(0, $this->stripe->refunds);
        $this->assertSame(PaymentStatus::Paid, $order->payments()->firstOrFail()->status);
    }

    public function test_historical_test_order_can_be_marked_using_read_only_test_key_while_live_gateway_is_configured(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->paid()->shipping()->create();
        $intentId = 'pi_hist_'.Str::lower(Str::random(10));

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => $intentId,
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_livemode' => null,
        ]);

        $this->stripe->hasTestVerificationKey = true;
        $this->stripe->testModeIntents[$intentId] = (object) [
            'id' => $intentId,
            'livemode' => false,
        ];

        $this->actingAs($admin)
            ->post(route('admin.orders.test-status', $order), ['is_test_order' => '1'])
            ->assertRedirect();

        $this->assertTrue($order->fresh()->is_test_order);
        $this->assertFalse($payment->fresh()->stripe_livemode);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'event' => OrderEvent::TestOrderMarked->value,
        ]);
    }

    public function test_restricted_test_key_is_accepted_for_read_only_verification(): void
    {
        config(['services.stripe.test_secret' => 'rk_test_example']);

        $gateway = new StripeGateway;

        $this->assertTrue($gateway->testModeVerificationConfigured());
    }

    public function test_live_stripe_order_cannot_be_marked_as_test(): void
    {
        $order = $this->orderWithPayment(true);

        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.test-status', $order), ['is_test_order' => '1'])
            ->assertSessionHasErrors('is_test_order');

        $this->assertFalse($order->fresh()->is_test_order);
    }

    public function test_order_without_stripe_payment_cannot_be_marked_as_test(): void
    {
        $order = Order::factory()->paid()->shipping()->create();

        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.test-status', $order), ['is_test_order' => '1'])
            ->assertSessionHasErrors('is_test_order');

        $this->assertFalse($order->fresh()->is_test_order);
    }

    public function test_admin_can_remove_test_order_flag_without_stripe_verification(): void
    {
        $admin = $this->admin();
        $order = $this->orderWithPayment(false, true);

        $this->actingAs($admin)
            ->post(route('admin.orders.test-status', $order), ['is_test_order' => '0'])
            ->assertRedirect();

        $this->assertFalse($order->fresh()->is_test_order);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'event' => OrderEvent::TestOrderUnmarked->value,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_order_list_defaults_to_regular_orders_and_can_filter_test_orders(): void
    {
        $regular = Order::factory()->paid()->shipping()->create(['order_number' => 'SGR-REGULAR-001']);
        $test = Order::factory()->paid()->shipping()->create([
            'order_number' => 'SGR-TEST-001',
            'is_test_order' => true,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($regular->order_number)
            ->assertDontSee($test->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['order_type' => 'test']))
            ->assertOk()
            ->assertSee($test->order_number)
            ->assertSee('badge bg-warning text-dark ms-1', false)
            ->assertSee('Test Order')
            ->assertDontSee($regular->order_number);

        $summary = $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->viewData('summary');

        $this->assertSame(1, $summary['paid_awaiting_fulfillment']);
    }
}
