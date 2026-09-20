<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

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
        $url = URL::temporarySignedRoute('invitation.accept', now()->addDays(7), ['user' => $notifiable->getKey()]);

        return (new MailMessage)
            ->subject('Invitation — '.config('app.name'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Un compte a été créé pour vous sur l’administration du site '.config('app.name').'.')
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien expire dans 7 jours. Passé ce délai, demandez à un administrateur de vous renvoyer une invitation.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            //
        ];
    }
}
