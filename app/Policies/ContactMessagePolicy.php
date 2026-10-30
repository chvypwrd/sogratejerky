<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view');
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contacts.view');
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contacts.update');
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contacts.delete');
    }
}
