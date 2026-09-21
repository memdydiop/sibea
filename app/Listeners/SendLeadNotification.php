<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendLeadNotification implements ShouldQueue
{
    public function handle(LeadCreated $event): void
    {
        $notifiable = $event->lead->assignedTo;

        if ($notifiable !== null) {
            $notifiable->notify(new NewLeadNotification($event->lead));

            return;
        }

        // Prospect non assigné : alerte la boîte partagée (ex. contact@sibea.ci),
        // qui n'est pas un utilisateur en base et ne peut pas être notifiée sinon.
        $address = (string) config('leads.notification_email');

        if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
            Notification::route('mail', $address)->notify(new NewLeadNotification($event->lead));
        }
    }
}
