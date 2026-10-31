<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('blog.posts.view');
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $user->can('blog.posts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('blog.posts.create');
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->can('blog.posts.update');
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->can('blog.posts.delete');
    }

    public function publish(User $user, BlogPost $post): bool
    {
        return $user->can('blog.posts.update');
    }
}
