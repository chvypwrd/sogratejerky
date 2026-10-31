<?php

namespace App\Http\Requests\Admin;

use App\Enums\BlogPostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blog.posts.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(BlogPostStatus::cases())],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'comments_enabled' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'featured_media' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:blog_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:blog_tags,id'],
        ];
    }
}
