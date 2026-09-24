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
        $this->lead->loadMissing(['sector', 'expertise', 'assignedTo']);

        $message = (new MailMessage)
            ->subject('Nouveau prospect '.($this->lead->reference ?? '').' : '.$this->lead->name)
            ->greeting('Bonjour,')
            ->line('Un nouveau prospect a été enregistré depuis le site.')
            ->line('Réf : '.($this->lead->reference ?? '—'))
            ->line('Nom : '.$this->lead->name)
            ->line('Email : '.$this->lead->email)
            ->line('Type : '.($this->lead->prospect_type?->label() ?? '—'))
            ->line('Type de demande : '.($this->lead->request_type?->label() ?? '—'));

        if (filled($this->lead->company)) {
            $message->line('Société : '.$this->lead->company);
        }

        if (filled($this->lead->phone)) {
            $message->line('Téléphone : '.$this->lead->phone);
        }

        if (filled($this->lead->target_territory)) {
            $message->line('Territoire ciblé : '.$this->lead->target_territory);
        }

        if ($this->lead->sector) {
            $message->line('Secteur : '.$this->lead->sector->name);
        }

        if ($this->lead->expertise) {
            $message->line('Expertise : '.$this->lead->expertise->name);
        }

        if (filled($this->lead->origin_page)) {
            $message->line('Page d’origine : '.$this->lead->origin_page);
        }

        if (filled($this->lead->utm_source) || filled($this->lead->utm_campaign)) {
            $utm = $this->lead->utm_source ?? '—';
            if (filled($this->lead->utm_medium)) {
                $utm .= ' / '.$this->lead->utm_medium;
            }
            if (filled($this->lead->utm_campaign)) {
                $utm .= ' / '.$this->lead->utm_campaign;
            }
            $message->line('Campagne : '.$utm);
        }

        if ($this->lead->deadline) {
            $message->line('Échéance : '.$this->lead->deadline->format('d/m/Y'));
        }

        if ($this->lead->assignedTo) {
            $message->line('Assigné à : '.$this->lead->assignedTo->name);
        }

        if (filled($this->lead->budget)) {
            $message->line('Budget indicatif : '.$this->lead->budget);
        }

        $duplicates = Lead::query()
            ->where('email', $this->lead->email)
            ->whereKeyNot($this->lead->getKey())
            ->count();

        if ($duplicates > 0) {
            $message->line('Doublon possible : '.$duplicates.' autre(s) demande(s) existe(nt) déjà pour cet email.');
        }

        try {
            $adminUrl = route('admin.leads');
        } catch (\Throwable) {
            $adminUrl = url('/admin/prospects');
        }

        $message
            ->action('Voir dans l’administration', $adminUrl)
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
