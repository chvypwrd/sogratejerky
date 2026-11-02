<?php

namespace Tests\Feature\Customer;

use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRateRule;
use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class CheckoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);

        FulfillmentSetting::current()->update([
            'shipping_enabled' => true,
            'local_delivery_enabled' => true,
            'pickup_enabled' => true,
            'shipping_basis' => 'subtotal',
        ]);

        ShippingRateRule::create([
            'min_value' => 0,
            'max_value' => 100000,
            'fee_cents' => 500,
            'is_active' => true,
        ]);
    }

    private function guestCart(): ProductVariant
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->withSession(['sograte_cart_token' => Str::random(64)]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        return $variant;
    }

    private function checkoutToken(): string
    {
        $token = Str::random(64);
        $this->withSession(['checkout_token' => $token]);

        return $token;
    }

    private function checkoutPage(): void
    {
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Your Account')
            ->assertSee('Continue as Guest')
            ->assertSee('Sign In')
            ->assertSee('Create an Account');
    }

    public function test_guest_can_see_optional_account_options_without_creating_an_account(): void
    {
        $this->guestCart();
        $this->checkoutPage();

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_customer_can_sign_in_during_checkout_and_keep_cart(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);
        $variant = $this->guestCart();
        $this->checkoutToken();

        $this->post(route('customer.login.store'), [
            'email' => 'customer@example.com',
            'password' => 'password123456',
            'return_to' => 'checkout',
        ])->assertRedirect(route('checkout.index'));

        $this->assertAuthenticatedAs($user);

        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_can_register_during_checkout_and_keep_cart(): void
    {
        Notification::fake();

        $variant = $this->guestCart();
        $this->checkoutToken();

        $this->post(route('customer.register.store'), [
            'name' => 'Checkout Customer',
            'email' => 'checkout@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
            'return_to' => 'checkout',
        ])->assertRedirect(route('checkout.index'));

        $user = User::where('email', 'checkout@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, CustomerVerifyEmail::class);

        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_duplicate_email_during_checkout_registration_requires_sign_in(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post(route('customer.register.store'), [
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
            'return_to' => 'checkout',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    public function test_checkout_registration_uses_existing_password_validation(): void
    {
        $this->post(route('customer.register.store'), [
            'name' => 'Weak Password',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
            'return_to' => 'checkout',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_signed_guest_confirmation_can_claim_only_its_own_paid_order(): void
    {
        Notification::fake();

        $token = Str::random(64);
        $order = Order::factory()->paid()->shipping()->create([
            'checkout_token' => $token,
            'customer_name' => 'Guest Buyer',
            'customer_email' => 'guest@example.com',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_claim_test',
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );
        $accountAction = URL::temporarySignedRoute(
            'checkout.confirmation.account',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->withSession(['checkout_token' => $token])
            ->get($confirmationUrl)
            ->assertOk()
            ->assertSee('Create Your Account');

        $this->post($accountAction, [
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ])->assertRedirect(route('account.orders.show', $order));

        $user = User::where('email', 'guest@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, $order->fresh()->user_id);
        Notification::assertSentTo($user, CustomerVerifyEmail::class);
    }

    public function test_guest_order_claim_requires_matching_session_and_paid_order(): void
    {
        $order = Order::factory()->paid()->shipping()->create([
            'checkout_token' => Str::random(64),
            'customer_email' => 'unclaimed@example.com',
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );
        $accountAction = URL::temporarySignedRoute(
            'checkout.confirmation.account',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->get($confirmationUrl)
            ->assertOk()
            ->assertDontSee('Create Your Account');

        $this->post($accountAction, [
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unclaimed@example.com']);
    }

    public function test_authenticated_user_is_not_offered_guest_order_account_creation(): void
    {
        $user = User::factory()->create();

        $token = Str::random(64);
        $order = Order::factory()->paid()->shipping()->create([
            'checkout_token' => $token,
            'customer_email' => 'guest-confirmation@example.com',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_claim_auth_user',
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->actingAs($user)
            ->withSession(['checkout_token' => $token])
            ->get($confirmationUrl)
            ->assertOk()
            ->assertDontSee('Create Your Account');
    }

    public function test_confirmation_offers_sign_in_when_guest_email_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'existing-guest@example.com']);

        $token = Str::random(64);
        $order = Order::factory()->paid()->shipping()->create([
            'checkout_token' => $token,
            'customer_email' => 'existing-guest@example.com',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_claim_existing',
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->withSession(['checkout_token' => $token])
            ->get($confirmationUrl)
            ->assertOk()
            ->assertSee('An account already exists')
            ->assertSee('Sign in');
    }
}
