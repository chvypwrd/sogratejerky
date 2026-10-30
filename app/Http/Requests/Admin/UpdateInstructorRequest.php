<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('instructors.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'bio' => ['required', 'string'],
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
            'name' => 'name',
            'title' => 'title',
            'short_bio' => 'short bio',
            'bio' => 'bio',
            'active' => 'active',
            'sort_order' => 'sort order',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'canonical_url' => 'canonical URL',
            'robots' => 'robots directive',
            'featured_media' => 'portrait image',
            'alt_text' => 'alt text',
            'remove_featured_media' => 'remove portrait image',
        ];
    }
}
