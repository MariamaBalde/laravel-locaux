<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->isAdmin();
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->isAdmin();
    }

    public function manageSettings(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->isAdmin();
    }
}
