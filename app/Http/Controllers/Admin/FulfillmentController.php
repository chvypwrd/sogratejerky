<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingBasis;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliveryZoneRequest;
use App\Http\Requests\Admin\StorePickupLocationRequest;
use App\Http\Requests\Admin\StoreShippingRateRuleRequest;
use App\Http\Requests\Admin\UpdateDeliveryZoneRequest;
use App\Http\Requests\Admin\UpdateFulfillmentSettingsRequest;
use App\Http\Requests\Admin\UpdatePickupLocationRequest;
use App\Http\Requests\Admin\UpdateShippingRateRuleRequest;
use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\PickupLocation;
use App\Models\ShippingRateRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FulfillmentController extends Controller
{
    public function index(): View
    {
        $setting = FulfillmentSetting::current();

        return view('admin.fulfillment.index', [
            'setting' => $setting,
            'shippingRules' => ShippingRateRule::ordered()->get(),
            'deliveryZones' => DeliveryZone::ordered()->get(),
            'pickupLocations' => PickupLocation::ordered()->get(),
            'basisOptions' => ShippingBasis::cases(),
        ]);
    }

    public function updateSettings(UpdateFulfillmentSettingsRequest $request): RedirectResponse
    {
        $setting = FulfillmentSetting::current();
        $data = $request->validated();

        if ($setting->shipping_basis->value !== $data['shipping_basis'] && ShippingRateRule::where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'shipping_basis' => 'Disable or remove active shipping rules before changing the calculation basis.',
            ]);
        }

        $setting->update([
            'shipping_enabled' => $request->boolean('shipping_enabled'),
            'local_delivery_enabled' => $request->boolean('local_delivery_enabled'),
            'pickup_enabled' => $request->boolean('pickup_enabled'),
            'shipping_basis' => $data['shipping_basis'],
            'local_delivery_threshold_enabled' => $request->boolean('local_delivery_threshold_enabled'),
            'local_delivery_threshold_cents' => (int) round(((float) ($data['local_delivery_threshold'] ?? 0)) * 100),
            'local_delivery_flat_fee_cents' => (int) round(((float) ($data['local_delivery_flat_fee'] ?? 0)) * 100),
        ]);

        return redirect()->route('admin.fulfillment.index')->with('status', 'Fulfillment settings updated.');
    }

    public function storeShippingRule(StoreShippingRateRuleRequest $request): RedirectResponse
    {
        $data = $this->shippingRuleData($request->validated());
        $this->assertNoShippingOverlap($data['min_value'], $data['max_value'], $data['is_active']);

        ShippingRateRule::create($data);

        return redirect()->route('admin.fulfillment.index')->with('status', 'Shipping rule created.');
    }

    public function updateShippingRule(UpdateShippingRateRuleRequest $request, ShippingRateRule $shippingRate): RedirectResponse
    {
        $data = $this->shippingRuleData($request->validated());
        $this->assertNoShippingOverlap($data['min_value'], $data['max_value'], $data['is_active'], $shippingRate->id);

        $shippingRate->update($data);

        return redirect()->route('admin.fulfillment.index')->with('status', 'Shipping rule updated.');
    }

    public function destroyShippingRule(ShippingRateRule $shippingRate): RedirectResponse
    {
        $shippingRate->delete();

        return redirect()->route('admin.fulfillment.index')->with('status', 'Shipping rule deleted.');
    }

    public function storeDeliveryZone(StoreDeliveryZoneRequest $request): RedirectResponse
    {
        DeliveryZone::create($this->deliveryZoneData($request->validated()));

        return redirect()->route('admin.fulfillment.index')->with('status', 'Delivery zone created.');
    }

    public function updateDeliveryZone(UpdateDeliveryZoneRequest $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->update($this->deliveryZoneData($request->validated()));

        return redirect()->route('admin.fulfillment.index')->with('status', 'Delivery zone updated.');
    }

    public function destroyDeliveryZone(DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->delete();

        return redirect()->route('admin.fulfillment.index')->with('status', 'Delivery zone deleted.');
    }

    public function storePickupLocation(StorePickupLocationRequest $request): RedirectResponse
    {
        PickupLocation::create($this->pickupData($request->validated()));

        return redirect()->route('admin.fulfillment.index')->with('status', 'Pickup location created.');
    }

    public function updatePickupLocation(UpdatePickupLocationRequest $request, PickupLocation $pickupLocation): RedirectResponse
    {
        $pickupLocation->update($this->pickupData($request->validated()));

        return redirect()->route('admin.fulfillment.index')->with('status', 'Pickup location updated.');
    }

    public function destroyPickupLocation(PickupLocation $pickupLocation): RedirectResponse
    {
        $pickupLocation->delete();

        return redirect()->route('admin.fulfillment.index')->with('status', 'Pickup location deleted.');
    }

    private function shippingRuleData(array $data): array
    {
        $basis = FulfillmentSetting::current()->shipping_basis;

        return [
            'min_value' => $basis === ShippingBasis::Subtotal
                ? (int) round($data['min_value'] * 100)
                : (int) $data['min_value'],
            'max_value' => $basis === ShippingBasis::Subtotal
                ? (int) round($data['max_value'] * 100)
                : (int) $data['max_value'],
            'fee_cents' => (int) round($data['fee'] * 100),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function deliveryZoneData(array $data): array
    {
        return [
            'name' => $data['name'] ?? null,
            'zip_code' => strtoupper(trim($data['zip_code'])),
            'fee_cents' => (int) round($data['fee'] * 100),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function pickupData(array $data): array
    {
        return [
            'name' => $data['name'],
            'address' => $data['address'],
            'contact_details' => $data['contact_details'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'fee_cents' => (int) round($data['fee'] * 100),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function assertNoShippingOverlap(int $min, int $max, bool $active, ?int $ignoreId = null): void
    {
        if (! $active) {
            return;
        }

        $overlaps = ShippingRateRule::query()
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($min, $max) {
                $query->whereBetween('min_value', [$min, $max])
                    ->orWhereBetween('max_value', [$min, $max])
                    ->orWhere(function ($query) use ($min, $max) {
                        $query->where('min_value', '<=', $min)
                            ->where('max_value', '>=', $max);
                    });
            })
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'min_value' => 'This range overlaps an active shipping rule.',
            ]);
        }
    }
}
