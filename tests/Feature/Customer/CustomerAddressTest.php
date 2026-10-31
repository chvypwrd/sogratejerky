<?php

namespace Tests\Feature\Customer;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    private function addressData(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'recipient_name' => 'Customer Name',
            'company' => null,
            'address_line_1' => '100 Main Street',
            'address_line_2' => null,
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'phone' => '555-0100',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('account.addresses.index'))->assertRedirect(route('customer.login'));
    }

    public function test_first_address_becomes_default_shipping_and_billing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.addresses.store'), $this->addressData())
            ->assertRedirect(route('account.addresses.index'));

        $address = CustomerAddress::firstOrFail();

        $this->assertTrue($address->is_default_shipping);
        $this->assertTrue($address->is_default_billing);
    }

    public function test_default_shipping_and_billing_are_unique_per_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.addresses.store'), $this->addressData([
            'label' => 'First',
            'is_default_shipping' => 1,
            'is_default_billing' => 1,
        ]));

        $this->actingAs($user)->post(route('account.addresses.store'), $this->addressData([
            'label' => 'Second',
            'is_default_shipping' => 1,
        ]));

        $first = CustomerAddress::where('label', 'First')->firstOrFail();
        $second = CustomerAddress::where('label', 'Second')->firstOrFail();

        $this->assertFalse($first->is_default_shipping);
        $this->assertTrue($first->is_default_billing);
        $this->assertTrue($second->is_default_shipping);
        $this->assertFalse($second->is_default_billing);
    }

    public function test_customer_cannot_access_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = CustomerAddress::create($this->addressData(['user_id' => $owner->id]));

        $this->actingAs($other)->get(route('account.addresses.edit', $address))->assertNotFound();
        $this->actingAs($other)->put(route('account.addresses.update', $address), $this->addressData())->assertNotFound();
        $this->actingAs($other)->delete(route('account.addresses.destroy', $address))->assertNotFound();

        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
    }

    public function test_deleting_default_address_promotes_remaining_address(): void
    {
        $user = User::factory()->create();

        $default = CustomerAddress::create($this->addressData([
            'user_id' => $user->id,
            'label' => 'Default',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]));

        $remaining = CustomerAddress::create($this->addressData([
            'user_id' => $user->id,
            'label' => 'Remaining',
        ]));

        $this->actingAs($user)->delete(route('account.addresses.destroy', $default))
            ->assertRedirect(route('account.addresses.index'));

        $remaining->refresh();

        $this->assertTrue($remaining->is_default_shipping);
        $this->assertTrue($remaining->is_default_billing);
    }
}
