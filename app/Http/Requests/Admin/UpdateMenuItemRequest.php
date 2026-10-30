<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menus.manage');
    }

    public function rules(): array
    {
        $menuId = $this->route('menu')->id;

        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:page,class,instructor,gallery,home,custom'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'class_offering_id' => ['nullable', 'exists:class_offerings,id'],
            'instructor_id' => ['nullable', 'exists:instructors,id'],
            'gallery_id' => ['nullable', 'exists:galleries,id'],
            'url' => ['nullable', 'required_if:type,custom', 'string', 'max:2048'],
            'target' => ['nullable', 'in:_self,_blank'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $menuId = $this->route('menu')->id;
            $parentId = $this->input('parent_id');
            $menuItem = $this->route('menuItem');

            if ($parentId == $menuItem->id) {
                $validator->errors()->add('parent_id', 'An item cannot be its own parent.');
            }

            if ($parentId) {
                $parentItem = MenuItem::find($parentId);

                if (! $parentItem || $parentItem->menu_id !== $menuId) {
                    $validator->errors()->add('parent_id', 'The parent item must belong to the same menu.');
                }

                if ($parentItem && $parentItem->parent_id !== null) {
                    $validator->errors()->add('parent_id', 'Items can only be nested one level deep.');
                }
            }

            if ($this->input('type') === 'custom' && $this->input('url')) {
                $url = $this->input('url');
                $parsed = parse_url($url);

                if ($parsed === false || isset($parsed['host'])) {
                    $validator->errors()->add('url', 'Custom URLs must be internal paths (e.g., /about).');
                }

                if (isset($parsed['scheme']) && $parsed['scheme'] !== 'http' && $parsed['scheme'] !== 'https') {
                    $validator->errors()->add('url', 'Custom URLs must use http or https scheme.');
                }

                if ($url === '/') {
                    $validator->errors()->add('url', 'Custom URLs cannot be the homepage path.');
                }
            }
        });
    }
}
