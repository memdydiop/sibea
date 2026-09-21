<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignedLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Prospect assigné : '.$this->lead->name)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Un prospect vous a été assigné sur '.config('app.name').'.')
            ->line('Nom : '.$this->lead->name)
            ->line('Email : '.$this->lead->email)
            ->line('Type de demande : '.$this->lead->request_type->label());

        if (filled($this->lead->company)) {
            $message->line('Société : '.$this->lead->company);
        }

        if (filled($this->lead->phone)) {
            $message->line('Téléphone : '.$this->lead->phone);
        }

        $message
            ->action('Voir dans l’administration', url('/admin/prospects'))
            ->line('Merci !');

        // Réponse directe au prospect, expéditeur vérifié conservé.
        if (filter_var($this->lead->email, FILTER_VALIDATE_EMAIL) !== false) {
            $message->replyTo($this->lead->email, $this->lead->name);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'name' => $this->lead->name,
            'email' => $this->lead->email,
        ];
    }
}
