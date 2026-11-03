<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\DeletedTestOrder;
use App\Models\DeletedTestOrderPaymentIntent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\User;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $stripe = Mockery::mock(StripeGateway::class);
        $stripe->shouldNotReceive('retrievePaymentIntent');
        $stripe->shouldNotReceive('retrieveTestModePaymentIntent');
        $stripe->shouldNotReceive('createRefund');
        $stripe->shouldNotReceive('cancelPaymentIntent');
        $this->app->instance(StripeGateway::class, $stripe);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function orderForDeletion(bool $isTestOrder = true, ?bool $livemode = false): array
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->paid()->shipping()->create([
            'user_id' => $customer->id,
            'is_test_order' => $isTestOrder,
            'order_number' => 'SGR-DELETE-'.str()->upper(str()->random(6)),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variant_name' => '8 oz',
            'sku' => 'SGR-DELETE-ITEM',
            'quantity' => 2,
            'unit_price_cents' => 1000,
            'line_total_cents' => 2000,
            'weight_grams' => 227,
        ]);

        $order->activities()->create([
            'event' => OrderEvent::OrderCreated->value,
            'previous_status' => null,
            'new_status' => 'placed',
            'actor_id' => null,
            'actor_name' => 'Customer',
            'note' => 'Order created at checkout.',
            'occurred_at' => now(),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_delete_'.$order->id,
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_livemode' => $livemode,
        ]);

        PaymentRefund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'stripe_refund_id' => 're_delete_'.$order->id,
            'request_key' => 'delete-refund-'.$order->id,
            'amount_cents' => 500,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
            'refunded_at' => now(),
        ]);

        return [$order, $customer, $product, $payment];
    }

    public function test_authorized_admin_can_delete_a_test_order_without_contacting_stripe(): void
    {
        [$order, $customer, $product, $payment] = $this->orderForDeletion();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Delete Order')
            ->assertSee('Permanent deletion')
            ->assertSee($order->order_number);

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('payment_refunds', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_activities', ['order_id' => $order->id]);
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $audit = DeletedTestOrder::firstOrFail();

        $this->assertSame($order->order_number, $audit->order_number);
        $this->assertSame($order->id, $audit->original_order_id);
        $this->assertSame($admin->id, $audit->deleted_by);
        $this->assertSame($admin->name, $audit->deleted_by_name);
        $this->assertSame($order->total_cents, $audit->original_total_cents);
        $this->assertSame('administrator_confirmed_local_test_order', $audit->verification_basis);
        $this->assertFalse($audit->local_payment_evidence[0]['stripe_livemode']);
        $this->assertNull($audit->override_reason);
        $this->assertSame(1, $audit->record_counts['order_items']);
        $this->assertSame(1, $audit->record_counts['payments']);
        $this->assertSame(1, $audit->record_counts['refunds']);
        $this->assertSame(1, $audit->record_counts['activities']);
        $this->assertSame(
            $payment->payment_intent_id,
            DeletedTestOrderPaymentIntent::firstOrFail()->payment_intent_id,
        );
    }

    public function test_exact_order_number_is_required_to_delete(): void
    {
        [$order] = $this->orderForDeletion();

        $this->actingAs($this->admin())
            ->delete(route('admin.orders.destroy', $order), [
                'confirm_order_number' => 'WRONG-ORDER',
            ])
            ->assertSessionHasErrors('confirm_order_number');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_live_mode_payment_order_cannot_be_deleted(): void
    {
        [$order] = $this->orderForDeletion(true, true);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Deletion unavailable')
            ->assertDontSee('Delete Order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_unclassified_legacy_order_with_local_test_evidence_can_be_deleted(): void
    {
        [$order, $customer, $product] = $this->orderForDeletion(false, false);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Legacy Test Order Deletion')
            ->assertSee('Delete Legacy Test Order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $audit = DeletedTestOrder::firstOrFail();

        $this->assertSame('local_stripe_livemode_false', $audit->verification_basis);
        $this->assertFalse($audit->local_payment_evidence[0]['stripe_livemode']);
        $this->assertNull($audit->override_reason);
    }

    public function test_ambiguous_legacy_order_requires_explicit_override_and_reason(): void
    {
        [$order] = $this->orderForDeletion(false, null);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Legacy Test Order Deletion')
            ->assertSee('cannot independently verify')
            ->assertSee('Delete Legacy Test Order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertSessionHasErrors('legacy_override');

        $this->actingAs($admin)
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => $order->order_number,
                'legacy_override' => '1',
            ])
            ->assertSessionHasErrors('override_reason');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);

        $this->actingAs($admin)
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => $order->order_number,
                'legacy_override' => '1',
                'override_reason' => 'Verified against the historical development checklist.',
            ])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        $audit = DeletedTestOrder::firstOrFail();

        $this->assertSame('administrator_override_unverified_payment_mode', $audit->verification_basis);
        $this->assertSame('Verified against the historical development checklist.', $audit->override_reason);
        $this->assertNull($audit->local_payment_evidence[0]['stripe_livemode']);
    }

    public function test_live_payment_legacy_order_cannot_be_deleted(): void
    {
        [$order] = $this->orderForDeletion(false, true);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Legacy Deletion Unavailable')
            ->assertDontSee('Delete Legacy Test Order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => $order->order_number,
                'legacy_override' => '1',
                'override_reason' => 'Should not be accepted for live payments.',
            ])
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_legacy_deletion_requires_the_exact_order_number(): void
    {
        [$order] = $this->orderForDeletion(false, false);

        $this->actingAs($this->admin())
            ->delete(route('admin.orders.legacy-destroy', $order), [
                'confirm_order_number' => 'WRONG-LEGACY-ORDER',
            ])
            ->assertSessionHasErrors('confirm_order_number');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_order_deletion_requires_administrative_permission(): void
    {
        [$order] = $this->orderForDeletion();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.orders.destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
