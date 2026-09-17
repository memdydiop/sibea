<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Permission\Models\Permission;

class SendLeadNotification implements ShouldQueue
{
    public function handle(LeadCreated $event): void
    {
        $notifiable = $event->lead->assignedTo;

        if ($notifiable === null && Permission::where('name', 'manage_leads')->exists()) {
            $notifiable = User::permission('manage_leads')->first();
        }

        if ($notifiable !== null) {
            $notifiable->notify(new NewLeadNotification($event->lead));
        }
    }
}
