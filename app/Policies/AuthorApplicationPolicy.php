<?php

namespace App\Policies;

use App\Models\AuthorApplication;
use App\Models\User;

class AuthorApplicationPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can create an author application.
     */
    public function create(User $user): bool
    {
        // Any authenticated normal user can apply.
        // Duplicate application logic is handled in the controller.
        return true;
    }

    /**
     * Determine whether the user can review (approve/reject) the application.
     */
    public function review(User $user, AuthorApplication $application): bool
    {
        // Admins are handled by before(), editors might also be allowed depending on business logic.
        // For now, based on the notification logic in the controller, it seems strictly for admins.
        return false;
    }
}
