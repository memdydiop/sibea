<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class LeadAssigner
{
    /**
     * @return Collection<int, User>
     */
    public static function commercials(): Collection
    {
        if (! Cache::remember('leads.manage_leads_permission_exists', 300, fn (): bool => Permission::where('name', 'manage_leads')->exists())) {
            return collect();
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = Cache::remember(
            'leads.commercials.v1',
            60,
            fn (): array => User::permission('manage_leads')->orderBy('id')->get()->toArray()
        );

        /** @var Collection<int, User> */
        return User::hydrate($items);
    }

    public static function flushCommercialsCache(): void
    {
        Cache::forget('leads.commercials.v1');
        Cache::forget('leads.manage_leads_permission_exists');
    }

    /**
     * Commercial le moins chargé (prospects ouverts assignés).
     */
    public static function leastLoaded(): ?User
    {
        $commercials = self::commercials();

        if ($commercials->isEmpty()) {
            return null;
        }

        $openStatuses = Lead::openStatusValues();

        $counts = Lead::query()
            ->whereIn('status', $openStatuses)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        return $commercials
            ->sortBy(fn (User $user) => [(int) ($counts[$user->id] ?? 0), $user->id])
            ->first();
    }

    /**
     * Round-robin strict : rotation A → B → C → A à partir du dernier
     * prospect assigné. Sans état externe, résiste au restart.
     * Sérialisé par lock cache pour éviter le double-assign sous concurrence.
     */
    public static function roundRobin(): ?User
    {
        $lock = Cache::lock('leads.assign', 5);

        try {
            $lock->block(3);

            return self::roundRobinUnsafe();
        } catch (\Throwable) {
            return self::roundRobinUnsafe();
        } finally {
            optional($lock)->release();
        }
    }

    private static function roundRobinUnsafe(): ?User
    {
        $commercials = self::commercials();

        if ($commercials->isEmpty()) {
            return null;
        }

        if ($commercials->count() === 1) {
            return $commercials->first();
        }

        $lastAssigneeId = Lead::query()
            ->whereNotNull('assigned_to')
            ->latest('id')
            ->value('assigned_to');

        if ($lastAssigneeId === null) {
            return $commercials->first();
        }

        $ids = $commercials->pluck('id')->all();
        $position = array_search($lastAssigneeId, $ids, true);

        // Dernier assigné sorti de l'équipe → on repart au premier.
        if ($position === false) {
            return $commercials->first();
        }

        $nextId = $ids[($position + 1) % count($ids)];

        return $commercials->firstWhere('id', $nextId);
    }

    /**
     * Point d'entrée : respecte `leads.assign_mode` (round_robin par défaut).
     */
    public static function next(): ?User
    {
        return config('leads.assign_mode') === 'least_loaded'
            ? self::leastLoaded()
            : self::roundRobin();
    }
}
