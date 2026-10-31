<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fulfillment.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_details' => ['nullable', 'string', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
