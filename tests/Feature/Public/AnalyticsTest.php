<?php

namespace Tests\Feature\Public;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function enableAnalytics(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('analytics.enabled', '1');
        $settings->set('analytics.measurement_id', 'G-ABC123XYZ');
    }

    public function test_ga4_is_not_loaded_when_disabled(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    public function test_ga4_loads_on_public_pages_but_not_admin_pages(): void
    {
        $this->enableAnalytics();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
            ->assertSee("gtag('config', \"G-ABC123XYZ\"", false);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    public function test_view_item_event_uses_product_snapshot_without_customer_data(): void
    {
        $this->enableAnalytics();

        $product = Product::factory()->create(['name' => 'Honey BBQ Jerky', 'slug' => 'honey-bbq-jerky']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '8 oz',
            'sku' => 'SGR-GA4-HONEY',
            'price' => 1299,
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.show', $product->slug));

        $response->assertOk();
        $response->assertSee('view_item', false);
        $response->assertSee('SGR-GA4-HONEY', false);
        $response->assertSee('Honey BBQ Jerky', false);
        $response->assertDontSee('buyer@example.com', false);
    }

    public function test_add_to_cart_and_view_cart_events_are_queued(): void
    {
        $this->enableAnalytics();

        $product = Product::factory()->create(['name' => 'Original Jerky']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SGR-GA4-ORIGINAL',
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('add_to_cart', false)
            ->assertSee('view_cart', false)
            ->assertSee('SGR-GA4-ORIGINAL', false);

        $cartItem = CartItem::firstOrFail();

        $this->patch(route('cart.items.update', $cartItem), ['quantity' => 0])
            ->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('remove_from_cart', false);
    }

    public function test_checkout_page_queues_begin_checkout_and_includes_shipping_info_payload(): void
    {
        $this->enableAnalytics();

        $product = Product::factory()->create(['name' => 'Checkout Jerky']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SGR-GA4-CHECKOUT',
            'price' => 1500,
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('begin_checkout', false)
            ->assertSee('add_shipping_info', false)
            ->assertSee('SGR-GA4-CHECKOUT', false);
    }

    public function test_purchase_event_fires_once_for_a_paid_order(): void
    {
        $this->enableAnalytics();

        $order = Order::factory()->paid()->shipping()->create([
            'customer_name' => 'Private Buyer',
            'customer_email' => 'private@example.com',
            'total_cents' => 2500,
            'subtotal_cents' => 2500,
            'fulfillment_fee_cents' => 0,
            'tax_cents' => 0,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_ga4_purchase',
            'amount_cents' => 2500,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $first = $this->get($url)->assertOk();
        $firstHtml = $first->getContent();

        $this->assertStringContainsString('"event":"purchase"', $firstHtml);
        $this->assertStringContainsString($order->order_number, $firstHtml);
        $this->assertNotNull($order->fresh()->ga4_purchase_tracked_at);

        $params = app(AnalyticsService::class)->purchaseParams($order->fresh('items'));
        $this->assertArrayNotHasKey('customer_name', $params);
        $this->assertArrayNotHasKey('customer_email', $params);

        $order->update(['payment_status' => PaymentStatus::PartiallyRefunded]);

        $this->get($url)
            ->assertOk()
            ->assertDontSee('"event":"purchase"', false);
    }

    public function test_purchase_event_does_not_fire_for_an_unpaid_order(): void
    {
        $this->enableAnalytics();

        $order = Order::factory()->create([
            'order_status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $url = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->get($url)
            ->assertOk()
            ->assertDontSee('"event":"purchase"', false);

        $this->assertNull($order->fresh()->ga4_purchase_tracked_at);
    }
}
