<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\ShippingBasis;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\PickupLocation;
use App\Models\ShippingRateRule;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CheckoutCalculator
{
    public function __construct(
        private CartService $cartService,
        private TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Cart $cart, string $methodValue, array $data, ?User $user): array
    {
        $this->cartService->assertCheckoutable($cart);

        $method = FulfillmentMethod::tryFrom($methodValue);

        if (! $method) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'Select a valid fulfillment method.',
            ]);
        }

        $setting = FulfillmentSetting::current();
        $enabled = match ($method) {
            FulfillmentMethod::Shipping => $setting->shipping_enabled,
            FulfillmentMethod::LocalDelivery => $setting->local_delivery_enabled,
            FulfillmentMethod::Pickup => $setting->pickup_enabled,
        };

        if (! $enabled) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'The selected fulfillment method is not available.',
            ]);
        }

        $subtotal = $this->cartService->subtotal($cart);
        $itemCount = $this->cartService->itemCount($cart);

        $fulfillmentAddress = null;
        $billingAddress = null;
        $pickupLocation = null;
        $fee = 0;

        if ($method !== FulfillmentMethod::Pickup) {
            $fulfillmentAddress = $this->resolveFulfillmentAddress($data, $user);
        }

        if ($method === FulfillmentMethod::Shipping) {
            $fee = $this->shippingFee($setting, $subtotal, $itemCount);
        } elseif ($method === FulfillmentMethod::LocalDelivery) {
            $fee = $this->deliveryFee($fulfillmentAddress, $subtotal, $setting);
        } else {
            $pickupLocation = $this->pickupLocation($data['pickup_location_id'] ?? null);
            $fee = $pickupLocation->fee_cents;
        }

        $billingSame = $method !== FulfillmentMethod::Pickup && (bool) ($data['billing_same_as_fulfillment'] ?? false);

        if ($billingSame) {
            $billingAddress = $fulfillmentAddress;
        } else {
            $billingAddress = $this->resolveBillingAddress($data, $user);
        }

        $tax = $this->taxCalculator->calculate([
            'destination' => $fulfillmentAddress,
            'items' => $cart->items,
            'subtotal_cents' => $subtotal,
            'fulfillment_fee_cents' => $fee,
        ]);

        return [
            'subtotal_cents' => $subtotal,
            'item_count' => $itemCount,
            'fulfillment_method' => $method,
            'fulfillment_fee_cents' => $fee,
            'tax_cents' => $tax,
            'total_cents' => $subtotal + $fee + $tax,
            'fulfillment_address' => $fulfillmentAddress,
            'billing_address' => $billingAddress,
            'pickup_location' => $pickupLocation?->only([
                'id', 'name', 'address', 'contact_details', 'instructions', 'fee_cents',
            ]),
        ];
    }

    private function shippingFee(FulfillmentSetting $setting, int $subtotal, int $itemCount): int
    {
        $basis = $setting->shipping_basis;
        $value = $basis === ShippingBasis::Subtotal ? $subtotal : $itemCount;

        $rule = ShippingRateRule::active()
            ->where('min_value', '<=', $value)
            ->where('max_value', '>=', $value)
            ->ordered()
            ->first();

        if (! $rule) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'Shipping is not available for this order total.',
            ]);
        }

        return $rule->fee_cents;
    }

    private function deliveryFee(?array $address, int $subtotal, FulfillmentSetting $setting): int
    {
        $zip = strtoupper(trim((string) ($address['postal_code'] ?? '')));

        $zone = DeliveryZone::active()->where('zip_code', $zip)->first();

        if (! $zone) {
            throw ValidationException::withMessages([
                'fulfillment_postal_code' => 'Local delivery is not available for this ZIP code.',
            ]);
        }

        if ($setting->local_delivery_threshold_enabled && $subtotal <= $setting->local_delivery_threshold_cents) {
            return $setting->local_delivery_flat_fee_cents;
        }

        return $zone->fee_cents;
    }

    private function pickupLocation(mixed $id): PickupLocation
    {
        $location = PickupLocation::active()->find((int) $id);

        if (! $location) {
            throw ValidationException::withMessages([
                'pickup_location_id' => 'Select a valid pickup location.',
            ]);
        }

        return $location;
    }

    private function resolveFulfillmentAddress(array $data, ?User $user): array
    {
        if (! empty($data['fulfillment_address_id'])) {
            return $this->savedAddress($data['fulfillment_address_id'], $user, 'fulfillment_address_id')->toSnapshot();
        }

        return $this->addressFromData($data, 'fulfillment');
    }

    private function resolveBillingAddress(array $data, ?User $user): array
    {
        if (! empty($data['billing_address_id'])) {
            return $this->savedAddress($data['billing_address_id'], $user, 'billing_address_id')->toSnapshot();
        }

        return $this->addressFromData($data, 'billing');
    }

    private function savedAddress(mixed $id, ?User $user, string $errorKey): CustomerAddress
    {
        $address = $user?->addresses()->find((int) $id);

        if (! $address) {
            throw ValidationException::withMessages([
                $errorKey => 'The selected address is not available.',
            ]);
        }

        return $address;
    }

    private function addressFromData(array $data, string $prefix): array
    {
        return [
            'recipient_name' => $data[$prefix.'_recipient_name'] ?? null,
            'company' => $data[$prefix.'_company'] ?? null,
            'address_line_1' => $data[$prefix.'_address_line_1'] ?? null,
            'address_line_2' => $data[$prefix.'_address_line_2'] ?? null,
            'city' => $data[$prefix.'_city'] ?? null,
            'state' => $data[$prefix.'_state'] ?? null,
            'postal_code' => $data[$prefix.'_postal_code'] ?? null,
            'country' => $data[$prefix.'_country'] ?? null,
            'phone' => $data[$prefix.'_phone'] ?? null,
        ];
    }
}
