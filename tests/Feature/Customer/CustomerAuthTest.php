<?php

namespace Tests\Feature\Customer;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\CustomerVerifyEmail;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_register_without_administrative_role(): void
    {
        Notification::fake();

        $this->post(route('customer.register.store'), [
            'name' => 'Customer One',
            'email' => 'customer@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'customer@example.com')->firstOrFail();

        $this->assertSame(0, $user->roles()->count());
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, CustomerVerifyEmail::class);
    }

    public function test_duplicate_email_registration_is_rejected(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $this->post(route('customer.register.store'), [
            'name' => 'Customer Two',
            'email' => 'customer@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $this->post(route('customer.login.store'), [
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('customer.logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_customer_without_roles_cannot_access_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_customer_password_reset_flow_works(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'oldpassword123456',
        ]);

        $this->post(route('customer.password.email'), [
            'email' => 'customer@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, CustomerResetPassword::class);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('customer.password.store'), [
            'token' => $token,
            'email' => 'customer@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ])->assertRedirect(route('customer.login'));

        $this->assertTrue(Hash::check('newpassword123456', $user->fresh()->password));
    }

    public function test_profile_update_requires_current_password_for_sensitive_changes(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Customer Updated',
            'email' => 'updated@example.com',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame('customer@example.com', $user->fresh()->email);
    }

    public function test_profile_can_be_updated_and_email_verification_restarts(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Customer Updated',
            'email' => 'updated@example.com',
            'current_password' => 'password123456',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ])->assertRedirect(route('account.profile.edit'));

        $user->refresh();

        $this->assertSame('Customer Updated', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('newpassword123456', $user->password));
        Notification::assertSentTo($user, CustomerVerifyEmail::class);
    }

    public function test_signed_email_verification_link_marks_email_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('account.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_guest_cart_merges_after_actual_customer_login(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->withSession(['sograte_cart_token' => Str::random(64)]);

        $guestCart = app(CartService::class)->resolveCart();
        app(CartService::class)->addVariantToCart($guestCart, $variant, 2);

        $this->post(route('customer.login.store'), [
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }
}
