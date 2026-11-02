<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class CustomerPaymentMethodTest extends TestCase
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

    public function test_customer_without_saved_methods_sees_setup_form_and_gets_stripe_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.payment-methods.index'))
            ->assertOk()
            ->assertSee('Add a payment method')
            ->assertSee('Save Payment Method')
            ->assertSee('payment-element', false);

        $this->assertNotNull($user->fresh()->stripe_customer_id);
        $this->assertCount(1, $this->stripe->setupIntents);
        $this->assertSame($user->fresh()->stripe_customer_id, $this->stripe->setupIntents[0]['customer']);
    }

    public function test_customer_with_saved_methods_does_not_create_another_setup_intent(): void
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_existing']);
        $this->stripe->savedMethods = [
            ['id' => 'pm_saved', 'brand' => 'Visa', 'last4' => '4242', 'exp_month' => '12', 'exp_year' => '2030'],
        ];

        $this->actingAs($user)
            ->get(route('account.payment-methods.index'))
            ->assertOk()
            ->assertSee('4242')
            ->assertDontSee('Add a payment method');

        $this->assertCount(0, $this->stripe->setupIntents);
    }

    public function test_payment_method_setup_is_unavailable_when_stripe_is_not_configured(): void
    {
        $this->stripe->isConfigured = false;

        $this->actingAs(User::factory()->create())
            ->get(route('account.payment-methods.index'))
            ->assertOk()
            ->assertSee('Saved payment methods are not available')
            ->assertDontSee('Save Payment Method');

        $this->assertCount(0, $this->stripe->setupIntents);
    }
}
