<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogCommentStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCommentController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->get('filter', BlogCommentStatus::Pending->value);
        $postId = $request->integer('post_id') ?: null;

        $statusValues = array_map(fn ($status) => $status->value, BlogCommentStatus::cases());

        $query = BlogComment::with('post')->latest();

        if (in_array($filter, $statusValues, true)) {
            $query->where('status', $filter);
        }

        if ($postId) {
            $query->where('blog_post_id', $postId);
        }

        return view('admin.blog.comments.index', [
            'comments' => $query->paginate(30)->withQueryString(),
            'filter' => $filter,
            'postId' => $postId,
            'posts' => BlogPost::orderBy('title')->get(['id', 'title']),
            'statuses' => BlogCommentStatus::cases(),
            'statusValues' => $statusValues,
        ]);
    }

    public function approve(BlogComment $comment): RedirectResponse
    {
        $comment->approve();

        return back()->with('status', 'Comment approved.');
    }

    public function reject(BlogComment $comment): RedirectResponse
    {
        $comment->reject();

        return back()->with('status', 'Comment rejected.');
    }

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
