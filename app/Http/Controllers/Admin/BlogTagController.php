<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogTagRequest;
use App\Http\Requests\Admin\UpdateBlogTagRequest;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlogTagController extends Controller
{
    public function index(): View
    {
        $tags = BlogTag::withCount('posts')->ordered()->paginate(20);

        return view('admin.blog.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('admin.blog.tags.create', ['tag' => new BlogTag]);
    }

    public function store(StoreBlogTagRequest $request): RedirectResponse
    {
        BlogTag::create($request->validated());

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('status', 'Tag created successfully.');
    }

    public function edit(BlogTag $blogTag): View
    {
        return view('admin.blog.tags.edit', ['tag' => $blogTag]);
    }

    public function update(UpdateBlogTagRequest $request, BlogTag $blogTag): RedirectResponse
    {
        $blogTag->update($request->validated());

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('status', 'Tag updated successfully.');
    }

    public function destroy(BlogTag $blogTag): RedirectResponse
    {
        if ($blogTag->posts()->exists()) {
            return redirect()
                ->route('admin.blog.tags.index')
                ->with('error', 'Remove this tag from its posts before deleting it.');
        }

        $blogTag->delete();

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('status', 'Tag deleted successfully.');
    }
}
