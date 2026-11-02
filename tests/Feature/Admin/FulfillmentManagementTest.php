<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\PickupLocation;
use App\Models\ShippingRateRule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FulfillmentManagementTest extends TestCase
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

    public function test_fulfillment_configuration_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.fulfillment.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.fulfillment.index'))
            ->assertOk()
            ->assertSee('Fulfillment Configuration');
    }

    public function test_admin_can_update_fulfillment_settings(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.fulfillment.settings.update'), [
                'shipping_enabled' => '1',
                'local_delivery_enabled' => '1',
                'pickup_enabled' => '1',
                'shipping_basis' => 'quantity',
            ])
            ->assertRedirect(route('admin.fulfillment.index'));

        $setting = FulfillmentSetting::current();

        $this->assertTrue($setting->shipping_enabled);
        $this->assertTrue($setting->local_delivery_enabled);
        $this->assertTrue($setting->pickup_enabled);
        $this->assertSame('quantity', $setting->shipping_basis->value);
    }

    public function test_admin_can_configure_local_delivery_threshold_pricing(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.fulfillment.settings.update'), [
                'shipping_enabled' => '1',
                'local_delivery_enabled' => '1',
                'pickup_enabled' => '1',
                'shipping_basis' => 'subtotal',
                'local_delivery_threshold_enabled' => '1',
                'local_delivery_threshold' => '50.00',
                'local_delivery_flat_fee' => '5.00',
            ])
            ->assertRedirect(route('admin.fulfillment.index'));

        $setting = FulfillmentSetting::current();

        $this->assertTrue($setting->local_delivery_threshold_enabled);
        $this->assertSame(5000, $setting->local_delivery_threshold_cents);
        $this->assertSame(500, $setting->local_delivery_flat_fee_cents);
    }

    public function test_local_delivery_threshold_rejects_negative_amounts(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.fulfillment.index'))
            ->put(route('admin.fulfillment.settings.update'), [
                'shipping_basis' => 'subtotal',
                'local_delivery_threshold_enabled' => '1',
                'local_delivery_threshold' => '-1',
                'local_delivery_flat_fee' => '-5',
            ])
            ->assertSessionHasErrors(['local_delivery_threshold', 'local_delivery_flat_fee']);
    }

    public function test_shipping_basis_cannot_change_while_active_rules_exist(): void
    {
        ShippingRateRule::create([
            'min_value' => 0,
            'max_value' => 5000,
            'fee_cents' => 500,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.fulfillment.index'))
            ->put(route('admin.fulfillment.settings.update'), [
                'shipping_enabled' => '1',
                'shipping_basis' => 'quantity',
            ])
            ->assertSessionHasErrors('shipping_basis');
    }

    public function test_shipping_rules_convert_dollars_to_cents_and_reject_overlaps(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.fulfillment.shipping-rules.store'), [
                'min_value' => '0',
                'max_value' => '50',
                'fee' => '5.25',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.fulfillment.index'));

        $this->assertDatabaseHas('shipping_rate_rules', [
            'min_value' => 0,
            'max_value' => 5000,
            'fee_cents' => 525,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.fulfillment.index'))
            ->post(route('admin.fulfillment.shipping-rules.store'), [
                'min_value' => '25',
                'max_value' => '75',
                'fee' => '7.00',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('min_value');

        $this->assertSame(1, ShippingRateRule::count());
    }

    public function test_admin_can_manage_delivery_zones_and_pickup_locations(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.fulfillment.delivery-zones.store'), [
                'name' => 'Central Austin',
                'zip_code' => ' 78701 ',
                'fee' => '4.50',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.fulfillment.index'));

        $this->assertDatabaseHas('delivery_zones', [
            'zip_code' => '78701',
            'fee_cents' => 450,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fulfillment.pickup-locations.store'), [
                'name' => 'North Market',
                'address' => '100 Main Street',
                'fee' => '0',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.fulfillment.index'));

        $this->assertDatabaseHas('pickup_locations', [
            'name' => 'North Market',
            'fee_cents' => 0,
            'is_active' => true,
        ]);

        $this->assertSame(1, DeliveryZone::count());
        $this->assertSame(1, PickupLocation::count());
    }
}
