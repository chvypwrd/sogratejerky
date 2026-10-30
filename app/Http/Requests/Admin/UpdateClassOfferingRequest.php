<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('classes.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'level' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['nullable', 'string', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'featured_media' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'remove_featured_media' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'title',
            'short_description' => 'short description',
            'description' => 'description',
            'level' => 'level',
            'duration' => 'duration',
            'featured' => 'featured',
            'active' => 'active',
            'sort_order' => 'sort order',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'canonical_url' => 'canonical URL',
            'robots' => 'robots directive',
            'featured_media' => 'featured image',
            'alt_text' => 'alt text',
            'remove_featured_media' => 'remove featured image',
        ];
    }
}
