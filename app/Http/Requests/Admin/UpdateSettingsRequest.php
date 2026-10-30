<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    public function rules(): array
    {
        $registry = config('site-settings');
        $rules = [];

        foreach ($registry as $group => $settings) {
            foreach ($settings as $key => $config) {
                $validationRules = explode('|', $config['validation']);
                $rules["settings.{$key}"] = $validationRules;
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $registry = config('site-settings');
        $attributes = [];

        foreach ($registry as $group => $settings) {
            foreach ($settings as $key => $config) {
                $attributes["settings.{$key}"] = $config['label'];
            }
        }

        return $attributes;
    }
}
