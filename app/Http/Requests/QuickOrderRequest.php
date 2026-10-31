<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:'.config('cart.max_quantity', 99)],
        ];
    }
}
