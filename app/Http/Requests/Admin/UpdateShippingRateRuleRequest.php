<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingRateRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fulfillment.manage');
    }

    public function rules(): array
    {
        return [
            'min_value' => ['required', 'numeric', 'min:0'],
            'max_value' => ['required', 'numeric', 'gte:min_value'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
