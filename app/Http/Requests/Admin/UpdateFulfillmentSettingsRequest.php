<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShippingBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFulfillmentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fulfillment.manage');
    }

    public function rules(): array
    {
        return [
            'shipping_enabled' => ['nullable', 'boolean'],
            'local_delivery_enabled' => ['nullable', 'boolean'],
            'pickup_enabled' => ['nullable', 'boolean'],
            'shipping_basis' => ['required', Rule::enum(ShippingBasis::class)],
        ];
    }
}
