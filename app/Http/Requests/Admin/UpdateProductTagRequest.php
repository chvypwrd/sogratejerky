<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.tags.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('product_tags', 'name')->ignore($this->route('productTag'))],
        ];
    }
}
