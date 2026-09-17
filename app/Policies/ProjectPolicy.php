<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_projects') || $user->can('view_projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('manage_projects') || $user->can('view_projects');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('manage_projects');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('manage_projects');
    }
}
