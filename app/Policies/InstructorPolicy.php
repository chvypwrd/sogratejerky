<?php

namespace App\Policies;

use App\Models\Instructor;
use App\Models\User;

class InstructorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('instructors.view');
    }

    public function view(User $user, Instructor $instructor): bool
    {
        return $user->can('instructors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('instructors.create');
    }

    public function update(User $user, Instructor $instructor): bool
    {
        return $user->can('instructors.update');
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return $user->can('instructors.delete');
    }

    public function restore(User $user, Instructor $instructor): bool
    {
        return $user->can('instructors.delete');
    }

    public function forceDelete(User $user, Instructor $instructor): bool
    {
        return $user->can('instructors.delete');
    }
}
