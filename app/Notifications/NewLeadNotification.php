<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Nouveau prospect : '.$this->lead->name)
            ->greeting('Bonjour,')
            ->line('Un nouveau prospect a été enregistré depuis le site.')
            ->line('Nom : '.$this->lead->name)
            ->line('Email : '.$this->lead->email)
            ->line('Type de demande : '.$this->lead->request_type->label());

        if (filled($this->lead->company)) {
            $message->line('Société : '.$this->lead->company);
        }

        if (filled($this->lead->phone)) {
            $message->line('Téléphone : '.$this->lead->phone);
        }

        if (filled($this->lead->target_territory)) {
            $message->line('Territoire ciblé : '.$this->lead->target_territory);
        }

        if (filled($this->lead->budget)) {
            $message->line('Budget indicatif : '.$this->lead->budget);
        }

        $message
            ->action('Voir dans l’administration', url('/admin/prospects'))
            ->line('Merci !');

        // Permet au commercial de répondre directement au prospect
        // tout en gardant un expéditeur vérifié (contact@sibea.ci) pour Brevo/SPF/DKIM.
        if (filter_var($this->lead->email, FILTER_VALIDATE_EMAIL) !== false) {
            $message->replyTo($this->lead->email, $this->lead->name);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'name' => $this->lead->name,
            'email' => $this->lead->email,
        ];
    }
}
