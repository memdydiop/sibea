<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLeadNotification implements ShouldQueue
{
    public function handle(LeadCreated $event): void
    {
        // Verrou atomique : un prospect n'est notifié qu'une fois, même si
        // la file rejoue le job (redistribution Cloud, retry après timeout SMTP).
        $claimed = Lead::query()
            ->whereKey($event->lead->getKey())
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
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
        } catch (Throwable $e) {
            // Échec réel d'envoi : on libère le verrou pour laisser le retry notifier.
            Lead::query()->whereKey($event->lead->getKey())->update(['notified_at' => null]);

            throw $e;
        }
    }
}
