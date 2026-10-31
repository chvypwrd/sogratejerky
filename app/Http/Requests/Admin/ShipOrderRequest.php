<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShippingCarrier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.fulfill');
    }

    public function rules(): array
    {
        return [
            'shipping_carrier' => ['required', Rule::enum(ShippingCarrier::class)],
            'tracking_number' => ['nullable', 'string', 'max:100', 'required_unless:untracked,1'],
            'untracked' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
