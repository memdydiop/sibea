<?php

namespace App\Policies;

use App\Models\Expertise;
use App\Models\User;

class ExpertisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_expertises');
    }

    public function view(User $user, Expertise $expertise): bool
    {
        return $user->can('manage_expertises');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_expertises');
    }

    public function update(User $user, Expertise $expertise): bool
    {
        return $user->can('manage_expertises');
    }

    public function delete(User $user, Expertise $expertise): bool
    {
        return $user->can('manage_expertises');
    }
}
