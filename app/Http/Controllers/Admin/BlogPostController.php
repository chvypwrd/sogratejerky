<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogPost::class);

        $filter = $request->get('filter', 'all');

        $query = BlogPost::with(['author', 'categories'])->withCount('comments')->latest();

        $query = match ($filter) {
            'draft' => $query->where('status', BlogPostStatus::Draft),
            'scheduled' => $query->where('status', BlogPostStatus::Scheduled),
            'published' => $query->where('status', BlogPostStatus::Published)->where('published_at', '<=', now()),
            default => $query,
        };

        $posts = $query->paginate(20)->withQueryString();

        return view('admin.blog.posts.index', compact('posts', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', BlogPost::class);

        return view('admin.blog.posts.create', [
            'post' => new BlogPost([
                'status' => BlogPostStatus::Draft,
                'comments_enabled' => true,
            ]),
            'statuses' => BlogPostStatus::cases(),
            'authors' => User::orderBy('name')->get(['id', 'name']),
            'categories' => BlogCategory::ordered()->get(),
            'tags' => BlogTag::ordered()->get(),
            'alt_text' => null,
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);

        $data = $this->prepareData($request->validated());
        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);
        $data['comments_enabled'] = $request->boolean('comments_enabled');
        $data['author_name'] = $this->authorName($data['author_id'] ?? null);

        unset($data['categories'], $data['tags'], $data['featured_media'], $data['alt_text']);

        $post = BlogPost::create($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));

        if ($request->hasFile('featured_media')) {
            $post->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        }

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('status', 'Blog post created successfully.');
    }

    public function edit(BlogPost $post): View
    {
        $this->authorize('update', $post);

        $post->load('categories', 'tags');

        return view('admin.blog.posts.edit', [
            'post' => $post,
            'statuses' => BlogPostStatus::cases(),
            'authors' => User::orderBy('name')->get(['id', 'name']),
            'categories' => BlogCategory::ordered()->get(),
            'tags' => BlogTag::ordered()->get(),
            'alt_text' => $post->getFirstMedia('featured')?->getCustomProperty('alt_text', ''),
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $oldSlug = $post->slug;
        $data = $this->prepareData($request->validated());
        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);
        $data['comments_enabled'] = $request->boolean('comments_enabled');

        if (! empty($data['author_id'])) {
            $data['author_name'] = $this->authorName($data['author_id']);
        }

        unset($data['categories'], $data['tags'], $data['featured_media'], $data['alt_text'], $data['remove_featured_media']);

        $post->update($data);
        $this->slugService->handleSlugChange($post, $oldSlug, 'blog/');

        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));

        if ($request->boolean('remove_featured_media')) {
            $post->clearMediaCollection('featured');
        } elseif ($request->hasFile('featured_media')) {
            $post->clearMediaCollection('featured');
            $post->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        } elseif ($post->hasMedia('featured')) {
            $media = $post->getFirstMedia('featured');
            $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
            $media->save();
        }

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('status', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('admin.blog.posts.index')
            ->with('status', 'Blog post deleted successfully.');
    }

    public function publish(BlogPost $post): RedirectResponse
    {
        $this->authorize('publish', $post);

        $post->publish();

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('status', 'Blog post published successfully.');
    }

    public function unpublish(BlogPost $post): RedirectResponse
    {
        $this->authorize('publish', $post);

        $post->unpublish();

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('status', 'Blog post unpublished successfully.');
    }

    private function prepareData(array $data): array
    {
        $status = BlogPostStatus::tryFrom($data['status'] ?? '') ?? BlogPostStatus::Draft;

        if ($status === BlogPostStatus::Published && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($status === BlogPostStatus::Published && ! empty($data['published_at']) && Carbon::parse($data['published_at'])->isFuture()) {
            $status = BlogPostStatus::Scheduled;
        }

        $data['status'] = $status->value;

        return $data;
    }

    private function authorName(?int $authorId): ?string
    {
        return $authorId ? User::find($authorId)?->name : null;
    }
}
