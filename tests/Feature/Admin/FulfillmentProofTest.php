<?php

namespace Tests\Feature\Admin;

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FulfillmentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function pickupOrder(FulfillmentStatus $status = FulfillmentStatus::ReadyForPickup): Order
    {
        return Order::factory()->paid()->pickup()->create([
            'fulfillment_status' => $status,
        ]);
    }

    private function deliveryOrder(FulfillmentStatus $status = FulfillmentStatus::OutForDelivery): Order
    {
        return Order::factory()->paid()->localDelivery()->create([
            'fulfillment_status' => $status,
        ]);
    }

    public function test_pickup_completion_with_photo_and_note(): void
    {
        $order = $this->pickupOrder();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.orders.picked-up', $order), [
                'note' => 'Handed to customer at the counter.',
                'proof_photo' => UploadedFile::fake()->image('pickup-proof.jpg', 800, 600),
            ])
            ->assertRedirect();

        $order->refresh();
        $media = $order->getMedia('pickup_proof')->firstOrFail();

        $this->assertSame(FulfillmentStatus::PickedUp, $order->fulfillment_status);
        $this->assertSame('Handed to customer at the counter.', $media->getCustomProperty('note'));
        $this->assertSame($admin->name, $media->getCustomProperty('uploaded_by_name'));
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'event' => 'picked_up',
            'actor_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.proof', [$order, $media]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.orders.proof', [$order, $media, 'preview' => 1]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Proof of Pickup')
            ->assertSee('Handed to customer at the counter.')
            ->assertSee($admin->name)
            ->assertSee(route('admin.orders.proof', [$order, $media]), false);

        $this->actingAs($admin)
            ->get(route('admin.orders.work-order', $order))
            ->assertOk()
            ->assertDontSee(route('admin.orders.proof', [$order, $media]), false);
    }

    public function test_pickup_completion_without_photo_remains_supported(): void
    {
        $order = $this->pickupOrder();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.picked-up', $order), [
                'note' => 'Customer picked up.',
            ])
            ->assertRedirect();

        $this->assertSame(FulfillmentStatus::PickedUp, $order->fresh()->fulfillment_status);
        $this->assertCount(0, $order->getMedia('pickup_proof'));
    }

    public function test_delivery_completion_with_photo_and_note(): void
    {
        $order = $this->deliveryOrder();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.delivered', $order), [
                'note' => 'Left with recipient.',
                'proof_photo' => UploadedFile::fake()->image('delivery-proof.png', 900, 700),
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(FulfillmentStatus::Delivered, $order->fulfillment_status);
        $this->assertCount(1, $order->getMedia('delivery_proof'));
        $this->assertSame('Left with recipient.', $order->getMedia('delivery_proof')->first()->getCustomProperty('note'));
    }

    public function test_delivery_completion_without_photo_remains_supported(): void
    {
        $order = $this->deliveryOrder();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.delivered', $order), [
                'note' => 'Delivered without photo.',
            ])
            ->assertRedirect();

        $this->assertSame(FulfillmentStatus::Delivered, $order->fresh()->fulfillment_status);
        $this->assertCount(0, $order->getMedia('delivery_proof'));
    }

    public function test_invalid_or_oversized_photo_does_not_complete_delivery(): void
    {
        $order = $this->deliveryOrder();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.orders.delivered', $order), [
                'proof_photo' => UploadedFile::fake()->create('proof.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('proof_photo');

        $this->assertSame(FulfillmentStatus::OutForDelivery, $order->fresh()->fulfillment_status);
        $this->assertCount(0, $order->getMedia('delivery_proof'));

        $this->actingAs($admin)
            ->post(route('admin.orders.delivered', $order), [
                'proof_photo' => UploadedFile::fake()->image('large.jpg', 900, 700)->size(6000),
            ])
            ->assertSessionHasErrors('proof_photo');

        $this->assertSame(FulfillmentStatus::OutForDelivery, $order->fresh()->fulfillment_status);
        $this->assertCount(0, $order->getMedia('delivery_proof'));
    }

    public function test_failed_state_transition_removes_uploaded_proof(): void
    {
        $order = $this->pickupOrder(FulfillmentStatus::Unfulfilled);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.picked-up', $order), [
                'note' => 'Should not complete.',
                'proof_photo' => UploadedFile::fake()->image('pickup.jpg', 800, 600),
            ])
            ->assertSessionHasErrors('order');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $order->fresh()->fulfillment_status);
        $this->assertCount(0, $order->getMedia('pickup_proof'));
    }

    public function test_duplicate_completion_does_not_replace_existing_proof(): void
    {
        $order = $this->pickupOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.orders.picked-up', $order), [
            'proof_photo' => UploadedFile::fake()->image('first.jpg', 800, 600),
        ])->assertRedirect();

        $firstMediaId = $order->getMedia('pickup_proof')->firstOrFail()->id;

        $this->actingAs($admin)
            ->post(route('admin.orders.picked-up', $order), [
                'proof_photo' => UploadedFile::fake()->image('second.jpg', 800, 600),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Order is already marked picked up.');

        $this->assertCount(1, $order->refresh()->getMedia('pickup_proof'));
        $this->assertSame($firstMediaId, $order->getMedia('pickup_proof')->first()->id);
    }

    public function test_proof_photo_access_requires_order_view_permission(): void
    {
        $order = $this->pickupOrder();

        $this->actingAs($this->admin())->post(route('admin.orders.picked-up', $order), [
            'proof_photo' => UploadedFile::fake()->image('private.jpg', 800, 600),
        ])->assertRedirect();

        $media = $order->getMedia('pickup_proof')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.proof', [$order, $media]))
            ->assertForbidden();

        Auth::logout();

        $this->get(route('admin.orders.proof', [$order, $media]))
            ->assertRedirect(route('login'));
    }

    public function test_proof_photo_cannot_be_requested_through_another_order(): void
    {
        $order = $this->pickupOrder();
        $otherOrder = $this->pickupOrder();

        $this->actingAs($this->admin())->post(route('admin.orders.picked-up', $order), [
            'proof_photo' => UploadedFile::fake()->image('private.jpg', 800, 600),
        ])->assertRedirect();

        $media = $order->getMedia('pickup_proof')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.proof', [$otherOrder, $media]))
            ->assertNotFound();
    }
}
