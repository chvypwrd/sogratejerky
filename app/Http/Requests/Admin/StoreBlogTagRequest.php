<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blog.tags.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:blog_tags,name'],
        ];
    }
}
