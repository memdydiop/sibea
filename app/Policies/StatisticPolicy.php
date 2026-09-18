<?php

namespace App\Policies;

use App\Models\Statistic;
use App\Models\User;

class StatisticPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_statistics');
    }

    public function view(User $user, Statistic $statistic): bool
    {
        return $user->can('manage_statistics');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_statistics');
    }

    public function update(User $user, Statistic $statistic): bool
    {
        return $user->can('manage_statistics');
    }

    public function delete(User $user, Statistic $statistic): bool
    {
        return $user->can('manage_statistics');
    }
}
