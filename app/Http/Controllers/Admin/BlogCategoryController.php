<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BlogCategory::withCount('posts')->ordered()->paginate(20);

        return view('admin.blog.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.blog.categories.create', ['category' => new BlogCategory]);
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        BlogCategory::create($request->validated());

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(BlogCategory $blogCategory): View
    {
        return view('admin.blog.categories.edit', ['category' => $blogCategory]);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $blogCategory->update($request->validated());

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->posts()->exists()) {
            return redirect()
                ->route('admin.blog.categories.index')
                ->with('error', 'Remove this category from its posts before deleting it.');
        }

        $blogCategory->delete();

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('status', 'Category deleted successfully.');
    }
}
