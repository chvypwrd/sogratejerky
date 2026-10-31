<?php

namespace App\Http\Controllers;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\StoreBlogCommentRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicBlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::published()
            ->with(['author', 'categories'])
            ->latestPublished()
            ->paginate(9);

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug, PageSlugRedirectService $slugService): View|RedirectResponse
    {
        $redirect = $slugService->resolve('blog/'.$slug);

        if ($redirect !== null) {
            return redirect($redirect['to'], $redirect['status']);
        }

        $post = BlogPost::published()
            ->with(['author', 'categories', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        $comments = $post->comments()->approved()->oldest()->get();

        return view('blog.show', compact('post', 'comments'));
    }

    public function author(User $author): View
    {
        $posts = BlogPost::published()
            ->where('author_id', $author->id)
            ->with(['author', 'categories'])
            ->latestPublished()
            ->paginate(9);

        return view('blog.archive', [
            'posts' => $posts,
            'heading' => 'Articles by '.$author->name,
            'description' => null,
            'archiveType' => 'Author',
        ]);
    }

    public function category(BlogCategory $blogCategory): View
    {
        $posts = $blogCategory->posts()
            ->published()
            ->with(['author', 'categories'])
            ->latestPublished()
            ->paginate(9);

        return view('blog.archive', [
            'posts' => $posts,
            'heading' => $blogCategory->name,
            'description' => $blogCategory->description,
            'archiveType' => 'Category',
        ]);
    }

    public function tag(BlogTag $blogTag): View
    {
        $posts = $blogTag->posts()
            ->published()
            ->with(['author', 'categories'])
            ->latestPublished()
            ->paginate(9);

        return view('blog.archive', [
            'posts' => $posts,
            'heading' => $blogTag->name,
            'description' => null,
            'archiveType' => 'Tag',
        ]);
    }

    public function search(Request $request): View
    {
        $term = Str::limit(trim((string) $request->get('q', '')), 100, '');

        $query = BlogPost::published()->with(['author', 'categories']);

        if ($term !== '') {
            $escaped = addcslashes($term, '%_\\');

            $query->where(function ($query) use ($escaped) {
                $query->where('title', 'like', "%{$escaped}%")
                    ->orWhere('excerpt', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $posts = $query->latestPublished()->paginate(9)->withQueryString();

        return view('blog.search', compact('posts', 'term'));
    }

    public function feed(): Response
    {
        $posts = BlogPost::published()
            ->with('author')
            ->latestPublished()
            ->limit(20)
            ->get();

        return response()
            ->view('blog.feed', compact('posts'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function storeComment(StoreBlogCommentRequest $request, string $slug): RedirectResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        abort_unless($post->comments_enabled, 404);

        if ($request->filled('website')) {
            return redirect()
                ->to(route('blog.show', $post->slug).'#comments')
                ->with('status', 'Thank you. Your comment is awaiting moderation.');
        }

        $fingerprint = hash_hmac(
            'sha256',
            $post->id.'|'.strtolower($request->input('email')).'|'.strtolower($request->input('content')),
            config('app.key')
        );

        if (Cache::has("blog_comment:{$fingerprint}")) {
            return redirect()
                ->to(route('blog.show', $post->slug).'#comments')
                ->with('status', 'Thank you. Your comment is awaiting moderation.');
        }

        Cache::put("blog_comment:{$fingerprint}", true, now()->addMinutes(5));

        $data = $request->validated();

        $post->comments()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'content' => $data['content'],
            'status' => BlogCommentStatus::Pending,
            'approved_at' => null,
        ]);

        return redirect()
            ->to(route('blog.show', $post->slug).'#comments')
            ->with('status', 'Thank you. Your comment is awaiting moderation.');
    }
}
