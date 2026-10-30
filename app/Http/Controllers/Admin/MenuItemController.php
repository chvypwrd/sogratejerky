<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMenuItemsRequest;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuService $menuService,
    ) {}

    public function edit(Menu $menu, MenuItem $menuItem)
    {
        $this->authorize('update', $menuItem);

        return view('admin.menus.item-edit', compact('menu', 'menuItem'));
    }

    public function store(StoreMenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('create', Menu::class);

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        MenuItem::create($data);
        $this->menuService->invalidateCache();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', 'Menu item added successfully.');
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        $data = $request->validated();

        $menuItem->update($data);
        $this->menuService->invalidateCache();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', 'Menu item updated successfully.');
    }

    public function destroy(Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('delete', $menuItem);

        if ($menuItem->children()->count() > 0) {
            return redirect()
                ->route('admin.menus.edit', $menu)
                ->with('error', 'Cannot delete a menu item that has children. Remove children first.');
        }

        $menuItem->delete();
        $this->menuService->invalidateCache();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', 'Menu item deleted successfully.');
    }

    public function reorder(ReorderMenuItemsRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('update', $menu);

        DB::transaction(function () use ($request, $menu) {
            foreach ($request->input('items', []) as $item) {
                MenuItem::where('id', $item['id'])
                    ->where('menu_id', $menu->id)
                    ->update([
                        'parent_id' => $item['parent_id'] ?? null,
                        'sort_order' => $item['sort_order'],
                    ]);
            }
        });

        $this->menuService->invalidateCache();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', 'Menu order updated successfully.');
    }
}
