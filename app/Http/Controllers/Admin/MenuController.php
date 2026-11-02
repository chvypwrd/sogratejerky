<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(
        private MenuService $menuService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Menu::class);

        $locations = MenuLocation::cases();

        return view('admin.menus.index', compact('locations'));
    }

    public function edit(Menu $menu): View
    {
        $this->authorize('view', $menu);

        $items = $this->menuService->buildMenuTree($menu->id, null, true);

        return view('admin.menus.edit', compact('menu', 'items'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $this->authorize('update', $menu);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $menu->update($data);
        $this->menuService->invalidateCache();

        return redirect()
            ->route('admin.menus.edit', $menu)
            ->with('status', 'Menu updated successfully.');
    }
}
