<?php

namespace App\Policies;

use App\Models\Gallery;
use App\Models\User;

class GalleryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('galleries.view');
    }

    public function view(User $user, Gallery $gallery): bool
    {
        return $user->can('galleries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('galleries.create');
    }

    public function update(User $user, Gallery $gallery): bool
    {
        return $user->can('galleries.update');
    }

    public function delete(User $user, Gallery $gallery): bool
    {
        return $user->can('galleries.delete');
    }

    public function restore(User $user, Gallery $gallery): bool
    {
        return $user->can('galleries.delete');
    }

    public function forceDelete(User $user, Gallery $gallery): bool
    {
        return $user->can('galleries.delete');
    }
}
