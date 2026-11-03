<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetiredOrderActionsTest extends TestCase
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

    private function paidOrder(array $overrides = []): Order
    {
        $order = Order::factory()->paid()->shipping()->create(array_merge([
            'customer_name' => 'Retired Actions Customer',
            'customer_email' => 'retired@example.com',
            'subtotal_cents' => 2500,
            'fulfillment_fee_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
        ], $overrides));

        $order->items()->create([
            'product_name' => 'Snapshot Jerky',
            'variant_name' => '8 oz',
            'sku' => 'SGR-RETIRED-001',
            'quantity' => 2,
            'unit_price_cents' => 1250,
            'line_total_cents' => 2500,
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

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_retired_'.$order->id,
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        return $order;
    }

    public function test_retired_test_order_actions_do_not_appear_on_order_detail(): void
    {
        $order = $this->paidOrder(['is_test_order' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Print Work Order')
            ->assertSee('Print Invoice / Receipt')
            ->assertDontSee('Mark as Test Order')
            ->assertDontSee('Remove Test Order Flag')
            ->assertDontSee('Delete Order')
            ->assertDontSee('Legacy Test Order Deletion');
    }

    public function test_retired_endpoints_cannot_modify_orders(): void
    {
        $order = $this->paidOrder(['is_test_order' => true]);
        $admin = $this->admin();

        $testStatus = $this->actingAs($admin)->post('/admin/orders/'.$order->order_number.'/test-status', [
            'is_test_order' => '0',
        ]);
        $this->assertContains($testStatus->getStatusCode(), [404, 405]);

        $delete = $this->actingAs($admin)->delete('/admin/orders/'.$order->order_number);
        $this->assertContains($delete->getStatusCode(), [404, 405]);

        $legacyDelete = $this->actingAs($admin)->delete('/admin/orders/'.$order->order_number.'/legacy-delete', [
            'confirm_order_number' => $order->order_number,
            'legacy_override' => '1',
            'override_reason' => 'Retired endpoint check.',
        ]);
        $this->assertContains($legacyDelete->getStatusCode(), [404, 405]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'is_test_order' => true,
        ]);
    }

    public function test_normal_order_administration_and_printing_remain_available(): void
    {
        $order = $this->paidOrder();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Refund')
            ->assertSee('Mark Shipped')
            ->assertSee('Order Activity');

        $this->actingAs($admin)
            ->get(route('admin.orders.work-order', $order))
            ->assertOk()
            ->assertSee('Work Order');

        $this->actingAs($admin)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Receipt');
    }

    public function test_order_list_excludes_test_orders_and_removes_test_order_filter(): void
    {
        $regular = $this->paidOrder(['order_number' => 'SGR-ACTIVE-001']);
        $test = $this->paidOrder([
            'order_number' => 'SGR-TEST-RETIRED',
            'is_test_order' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($regular->order_number)
            ->assertDontSee($test->order_number)
            ->assertDontSee('Test Orders')
            ->assertDontSee('Order type');
    }
}
