<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Enums\RobotsDirective;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pages.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'template' => ['nullable', Rule::in(PageTemplate::cases())],
            'status' => ['nullable', Rule::in(PageStatus::cases())],
            'robots' => ['nullable', Rule::in(RobotsDirective::cases())],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'featured_media' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'remove_featured_media' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'title',
            'body' => 'body',
            'excerpt' => 'excerpt',
            'template' => 'template',
            'status' => 'status',
            'robots' => 'robots directive',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'canonical_url' => 'canonical URL',
            'sort_order' => 'sort order',
            'published_at' => 'publish date',
            'featured_media' => 'featured image',
            'alt_text' => 'alt text',
            'remove_featured_media' => 'remove featured image',
        ];
    }
}
