<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'max:50'],
            'subject' => ['nullable', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'website' => ['nullable'],
        ];
    }
}
