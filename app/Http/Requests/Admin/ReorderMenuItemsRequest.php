<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menus.manage');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $menuId = $this->route('menu')->id;
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $menuItem = MenuItem::find($item['id']);

                if (! $menuItem || $menuItem->menu_id !== $menuId) {
                    $validator->errors()->add("items.{$index}.id", 'Item does not belong to this menu.');

                    continue;
                }

                if (isset($item['parent_id']) && $item['parent_id'] == $item['id']) {
                    $validator->errors()->add("items.{$index}.parent_id", 'An item cannot be its own parent.');
                }

                if (isset($item['parent_id'])) {
                    $parentItem = MenuItem::find($item['parent_id']);

                    if (! $parentItem || $parentItem->menu_id !== $menuId) {
                        $validator->errors()->add("items.{$index}.parent_id", 'Parent item does not belong to this menu.');
                    }

                    if ($parentItem && $parentItem->parent_id !== null) {
                        $validator->errors()->add("items.{$index}.parent_id", 'Items can only be nested one level deep.');
                    }
                }
            }
        });
    }
}
