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
        return (new MailMessage)
            ->subject('Nouveau prospect : '.$this->lead->name)
            ->greeting('Bonjour,')
            ->line('Un nouveau prospect a été enregistré depuis le site.')
            ->line('Nom : '.$this->lead->name)
            ->line('Email : '.$this->lead->email)
            ->line('Type de demande : '.$this->lead->request_type->label())
            ->action('Voir dans l’administration', url('/admin/prospects'))
            ->line('Merci !');
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
