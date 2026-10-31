<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blog.categories.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'name')->ignore($this->route('blogCategory'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
