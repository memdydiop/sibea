<?php

namespace App\Policies;

use App\Models\Sector;
use App\Models\User;

class SectorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_sectors') || $user->can('view_sectors');
    }

    public function view(User $user, Sector $sector): bool
    {
        return $user->can('manage_sectors') || $user->can('view_sectors');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_sectors');
    }

    public function update(User $user, Sector $sector): bool
    {
        return $user->can('manage_sectors');
    }

    public function delete(User $user, Sector $sector): bool
    {
        if ($sector->is_locked) {
            return false;
        }

        return $user->can('manage_sectors');
    }
}
