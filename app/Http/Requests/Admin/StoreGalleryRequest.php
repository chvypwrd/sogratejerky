<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('galleries.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['nullable', 'string', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'photos' => ['nullable', 'array', 'max:20'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'active' => 'active',
            'featured' => 'featured',
            'sort_order' => 'sort order',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'canonical_url' => 'canonical URL',
            'robots' => 'robots directive',
            'photos' => 'photos',
            'alt_text' => 'alt text',
            'caption' => 'caption',
        ];
    }
}
