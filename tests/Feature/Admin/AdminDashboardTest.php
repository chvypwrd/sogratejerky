<?php

namespace Tests\Feature\Admin;

use App\Enums\ContactMessageStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');
    }

    private function paidOrder(int $totalCents, string $placedAt, string $productName = 'Original Beef Jerky', bool $isTest = false): Order
    {
        $order = Order::factory()->paid()->shipping()->create([
            'total_cents' => $totalCents,
            'subtotal_cents' => $totalCents,
            'fulfillment_fee_cents' => 0,
            'placed_at' => $placedAt,
            'is_test_order' => $isTest,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_dashboard_'.$order->id,
            'amount_cents' => $totalCents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => $placedAt,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => $productName,
            'variant_name' => '8 oz',
            'sku' => 'SGR-DASH-'.$order->id,
            'quantity' => 2,
            'unit_price_cents' => intdiv($totalCents, 2),
            'line_total_cents' => $totalCents,
            'weight_grams' => 226,
        ]);

        return $order;
    }

    public function test_dashboard_renders_kpis_and_financial_totals_from_paid_payments(): void
    {
        $todayOrder = $this->paidOrder(2500, now()->format('Y-m-d H:i:s'));
        $this->paidOrder(5000, now()->subDays(10)->format('Y-m-d H:i:s'), 'Peppered Beef Jerky');

        Order::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::PendingPayment,
            'total_cents' => 9900,
            'placed_at' => now(),
        ]);

        PaymentRefund::create([
            'payment_id' => $todayOrder->payments()->firstOrFail()->id,
            'order_id' => $todayOrder->id,
            'request_key' => 'dashboard-refund',
            'amount_cents' => 500,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
            'refunded_at' => now(),
        ]);

        ContactMessage::factory()->count(2)->create(['status' => ContactMessageStatus::New]);
        ContactMessage::factory()->create(['status' => ContactMessageStatus::Read]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();

        $report = $response->viewData('report');

        $this->assertSame(2, $report['ordersToday']);
        $this->assertSame(3, $report['ordersMonth']);
        $this->assertSame(2500, $report['grossToday']);
        $this->assertSame(7500, $report['grossMonth']);
        $this->assertSame(500, $report['refundsMonth']);
        $this->assertSame(7000, $report['netMonth']);
        $this->assertSame(2, $report['unreadMessages']);
        $this->assertCount(30, $report['salesTrend']);
        $peppered = $report['bestSellers']->firstWhere('product_name', 'Peppered Beef Jerky');
        $this->assertNotNull($peppered);
        $this->assertSame(2, $peppered->units);
    }

    public function test_dashboard_excludes_test_orders_from_financial_and_operational_reports(): void
    {
        $regular = $this->paidOrder(2500, now()->format('Y-m-d H:i:s'));
        $this->paidOrder(9000, now()->format('Y-m-d H:i:s'), 'Test-Only Product', true);

        PaymentRefund::create([
            'payment_id' => $regular->payments()->firstOrFail()->id,
            'order_id' => $regular->id,
            'request_key' => 'dashboard-regular-refund',
            'amount_cents' => 500,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
            'refunded_at' => now(),
        ]);

        $report = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->viewData('report');

        $this->assertSame(1, $report['ordersToday']);
        $this->assertSame(1, $report['ordersMonth']);
        $this->assertSame(2500, $report['grossToday']);
        $this->assertSame(2500, $report['grossMonth']);
        $this->assertSame(500, $report['refundsMonth']);
        $this->assertSame(2000, $report['netMonth']);
        $this->assertSame(1, $report['awaitingFulfillment']);
        $this->assertNull($report['bestSellers']->firstWhere('product_name', 'Test-Only Product'));
    }

    public function test_dashboard_handles_an_empty_database_and_month_filter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', ['month' => '2026-10']));

        $response->assertOk();
        $response->assertSee('No orders yet.');

        $report = $response->viewData('report');

        $this->assertSame('2026-10', $report['month']);
        $this->assertSame(0, $report['grossMonth']);
        $this->assertSame(0, $report['refundsMonth']);
        $this->assertSame(0, $report['netMonth']);
        $this->assertCount(30, $report['salesTrend']);
    }
}
