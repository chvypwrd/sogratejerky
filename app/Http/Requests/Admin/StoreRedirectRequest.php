<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;

class StoreRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('redirects.manage');
    }

    public function rules(): array
    {
        return [
            'old_path' => ['required', 'string', 'max:2048', 'starts_with:/', 'unique:redirects,old_path'],
            'new_path' => ['required', 'string', 'max:2048', 'starts_with:/'],
            'status_code' => ['required', 'integer', 'in:301,302'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('old_path') === $this->input('new_path')) {
                $validator->errors()->add('new_path', 'The redirect cannot point to itself.');
            }

            $oldPath = $this->input('old_path');
            $newPath = $this->input('new_path');

            if ($oldPath && $newPath) {
                $existing = Redirect::where('old_path', $newPath)->where('active', true)->first();
                if ($existing) {
                    $validator->errors()->add('new_path', 'This would create a redirect loop.');
                }
            }
        });
    }
}
