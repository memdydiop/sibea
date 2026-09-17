<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads');
    }
}
