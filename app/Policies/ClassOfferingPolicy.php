<?php

namespace App\Policies;

use App\Models\ClassOffering;
use App\Models\User;

class ClassOfferingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('classes.view');
    }

    public function view(User $user, ClassOffering $classOffering): bool
    {
        return $user->can('classes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('classes.create');
    }

    public function update(User $user, ClassOffering $classOffering): bool
    {
        return $user->can('classes.update');
    }

    public function delete(User $user, ClassOffering $classOffering): bool
    {
        return $user->can('classes.delete');
    }

    public function restore(User $user, ClassOffering $classOffering): bool
    {
        return $user->can('classes.delete');
    }

    public function forceDelete(User $user, ClassOffering $classOffering): bool
    {
        return $user->can('classes.delete');
    }
}
