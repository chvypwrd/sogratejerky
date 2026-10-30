<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('testimonials.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'quote' => ['required', 'string', 'max:2000'],
            'attribution' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'quote' => 'quote',
            'attribution' => 'attribution',
            'featured' => 'featured',
            'active' => 'active',
            'sort_order' => 'sort order',
        ];
    }
}
