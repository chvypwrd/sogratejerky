<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\AdminDateTime;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailPresentationTest extends TestCase
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

    public function test_admin_date_time_formatting_uses_central_time_across_dst(): void
    {
        $this->assertSame(
            'Jul 15, 2026 7:00 AM',
            AdminDateTime::format(Carbon::parse('2026-07-15 12:00:00', 'UTC'))
        );

        $this->assertSame(
            'Jan 15, 2026 12:00 PM',
            AdminDateTime::format(Carbon::parse('2026-01-15 18:00:00', 'UTC'))
        );

        $this->assertSame(
            'Mar 8, 2026 3:30 AM',
            AdminDateTime::format(Carbon::parse('2026-03-08 08:30:00', 'UTC'))
        );

        $this->assertSame(
            'Nov 1, 2026 1:30 AM',
            AdminDateTime::format(Carbon::parse('2026-11-01 07:30:00', 'UTC'))
        );

        $this->assertSame(AdminDateTime::PLACEHOLDER, AdminDateTime::format(null));
        $this->assertSame('—', AdminDateTime::PLACEHOLDER);
    }

    public function test_admin_date_time_formatting_does_not_mutate_timestamp_instances(): void
    {
        $stored = Carbon::parse('2026-07-15 12:00:00', 'UTC');

        $this->assertSame('Jul 15, 2026 7:00 AM', AdminDateTime::format($stored));
        $this->assertSame('2026-07-15 12:00:00', $stored->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $stored->timezoneName);
    }

    public function test_order_detail_renders_central_time_clean_placeholders_and_customer_account(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['name' => 'Jamie Customer']);

        $order = Order::factory()->create([
            'order_number' => 'SGR-20261119-PRESENT',
            'user_id' => $customer->id,
            'customer_phone' => null,
            'placed_at' => Carbon::parse('2026-07-15 12:00:00', 'UTC'),
            'payment_status' => PaymentStatus::Failed,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_3UdZPALIGfH6TAeN0vXRpjXM',
            'amount_cents' => 2400,
            'currency' => 'USD',
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
        ]);

        $order->activities()->create([
            'event' => OrderEvent::PaymentFailed->value,
            'previous_status' => null,
            'new_status' => PaymentStatus::Failed->value,
            'actor_name' => null,
            'note' => null,
            'occurred_at' => Carbon::parse('2026-03-08 08:30:00', 'UTC'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Placed July 15, 2026 7:00 AM')
            ->assertSee('Account #'.$customer->id.' — Jamie Customer')
            ->assertSee('Mar 8, 2026 3:30 AM')
            ->assertSee('Failed')
            ->assertSee('$0.00')
            ->assertDontSee('Request Refund')
            ->assertDontSee('Ã¢â‚¬â€');
    }
}
