<?php

namespace Tests\Feature\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('orders.view');

        return $user;
    }

    private function orderWithSnapshots(array $overrides = []): Order
    {
        $order = Order::factory()->paid()->shipping()->create(array_merge([
            'customer_name' => 'Print Customer',
            'customer_email' => 'print@example.com',
            'customer_phone' => '555-0199',
            'subtotal_cents' => 2000,
            'fulfillment_fee_cents' => 500,
            'tax_cents' => 100,
            'total_cents' => 2600,
            'fulfillment_address_snapshot' => [
                'recipient_name' => 'Print Customer',
                'company' => null,
                'address_line_1' => '123 Print Street',
                'address_line_2' => null,
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'United States',
            ],
        ], $overrides));

        $order->items()->create([
            'product_name' => 'Historical Original Jerky',
            'variant_name' => '8 oz Bag',
            'sku' => 'SGR-PRINT-001',
            'quantity' => 2,
            'unit_price_cents' => 1000,
            'line_total_cents' => 2000,
            'weight_grams' => 227,
        ]);

        return $order;
    }

    private function paidPayment(Order $order, int $amount = 2600): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_print_'.$order->id,
            'amount_cents' => $amount,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function test_authorized_admin_can_print_work_order_with_historical_items_and_fulfillment_details(): void
    {
        $order = $this->orderWithSnapshots([
            'fulfillment_method' => FulfillmentMethod::Pickup,
            'fulfillment_address_snapshot' => null,
            'pickup_location_snapshot' => [
                'id' => 1,
                'name' => 'North Market',
                'address' => '100 Main Street',
                'contact_details' => 'Call 555-0100',
                'instructions' => 'Bring the order number to the counter.',
                'fee_cents' => 0,
            ],
        ]);

        $this->actingAs($this->viewer())
            ->get(route('admin.orders.work-order', $order))
            ->assertOk()
            ->assertSee('Work Order')
            ->assertSee($order->order_number)
            ->assertSee('Print Customer')
            ->assertSee('Historical Original Jerky')
            ->assertSee('8 oz Bag')
            ->assertSee('SGR-PRINT-001')
            ->assertSee('<td class="num">2</td>', false)
            ->assertSee('North Market')
            ->assertSee('Bring the order number to the counter.')
            ->assertSee('Packed by')
            ->assertSee('Date');
    }

    public function test_print_documents_use_historical_product_names_not_current_catalog_names(): void
    {
        $product = Product::factory()->create(['name' => 'Current Catalog Product']);
        $order = $this->orderWithSnapshots();
        $order->items()->delete();
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Historical Order Snapshot',
            'variant_name' => '4 oz Bag',
            'sku' => 'SGR-PRINT-HIST',
            'quantity' => 1,
            'unit_price_cents' => 2000,
            'line_total_cents' => 2000,
            'weight_grams' => 113,
        ]);

        $product->update(['name' => 'Renamed Catalog Product']);

        $this->actingAs($this->viewer())
            ->get(route('admin.orders.work-order', $order))
            ->assertOk()
            ->assertSee('Historical Order Snapshot')
            ->assertDontSee('Renamed Catalog Product');
    }

    public function test_receipt_shows_stored_totals_and_refund_information(): void
    {
        $order = $this->orderWithSnapshots();
        $payment = $this->paidPayment($order);

        PaymentRefund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'stripe_refund_id' => 're_print_partial',
            'request_key' => 'print-partial-refund',
            'amount_cents' => 500,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
            'reason' => 'Customer request',
            'refunded_at' => now(),
        ]);

        $payment->update(['status' => PaymentStatus::PartiallyRefunded]);
        $order->update(['payment_status' => PaymentStatus::PartiallyRefunded]);

        $this->actingAs($this->viewer())
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Partial Refund Receipt')
            ->assertSee('Historical Original Jerky')
            ->assertSee('$20.00')
            ->assertSee('$5.00')
            ->assertSee('$1.00')
            ->assertSee('$26.00')
            ->assertSee('Total charged')
            ->assertSee('Refund Information')
            ->assertSee('Customer request')
            ->assertSee('Partially Refunded');
    }

    public function test_unpaid_order_is_labelled_order_summary_not_receipt(): void
    {
        $order = $this->orderWithSnapshots([
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::PendingPayment,
        ]);

        $this->actingAs($this->viewer())
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Order Summary')
            ->assertSee('Payment has not been confirmed as paid.')
            ->assertDontSee('Partial Refund Receipt');
    }

    public function test_print_documents_require_order_view_permission(): void
    {
        $order = $this->orderWithSnapshots();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.work-order', $order))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.invoice', $order))
            ->assertForbidden();
    }
}
