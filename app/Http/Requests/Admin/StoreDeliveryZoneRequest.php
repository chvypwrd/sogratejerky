<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fulfillment.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['zip_code' => strtoupper(trim((string) $this->input('zip_code')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:20', 'unique:delivery_zones,zip_code'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
