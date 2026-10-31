<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FulfillmentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.fulfill');
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
