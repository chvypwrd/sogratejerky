<?php

namespace App\Http\Requests;

use App\Enums\FulfillmentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = $this->input('fulfillment_method');
        $needsFulfillmentAddress = in_array($method, [
            FulfillmentMethod::Shipping->value,
            FulfillmentMethod::LocalDelivery->value,
        ], true) && ! $this->filled('fulfillment_address_id');

        $needsBillingAddress = $method === FulfillmentMethod::Pickup->value
            ? ! $this->filled('billing_address_id')
            : ! $this->boolean('billing_same_as_fulfillment') && ! $this->filled('billing_address_id');

        $requiredIfFulfillment = Rule::requiredIf($needsFulfillmentAddress);
        $requiredIfBilling = Rule::requiredIf($needsBillingAddress);

        return [
            'checkout_token' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
            'fulfillment_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'billing_same_as_fulfillment' => ['nullable', 'boolean'],
            'pickup_location_id' => ['nullable', 'integer', 'required_if:fulfillment_method,pickup', 'exists:pickup_locations,id'],

            'fulfillment_recipient_name' => [$requiredIfFulfillment, 'nullable', 'string', 'max:255'],
            'fulfillment_company' => ['nullable', 'string', 'max:255'],
            'fulfillment_address_line_1' => [$requiredIfFulfillment, 'nullable', 'string', 'max:255'],
            'fulfillment_address_line_2' => ['nullable', 'string', 'max:255'],
            'fulfillment_city' => [$requiredIfFulfillment, 'nullable', 'string', 'max:100'],
            'fulfillment_state' => [$requiredIfFulfillment, 'nullable', 'string', 'max:100'],
            'fulfillment_postal_code' => [$requiredIfFulfillment, 'nullable', 'string', 'max:20'],
            'fulfillment_country' => [$requiredIfFulfillment, 'nullable', 'string', 'max:100'],
            'fulfillment_phone' => ['nullable', 'string', 'max:50'],

            'billing_recipient_name' => [$requiredIfBilling, 'nullable', 'string', 'max:255'],
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_address_line_1' => [$requiredIfBilling, 'nullable', 'string', 'max:255'],
            'billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => [$requiredIfBilling, 'nullable', 'string', 'max:100'],
            'billing_state' => [$requiredIfBilling, 'nullable', 'string', 'max:100'],
            'billing_postal_code' => [$requiredIfBilling, 'nullable', 'string', 'max:20'],
            'billing_country' => [$requiredIfBilling, 'nullable', 'string', 'max:100'],
            'billing_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
