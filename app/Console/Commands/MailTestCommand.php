<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:mail-test {email : Adresse e-mail destinataire du message de test}')]
#[Description('Envoie un email de test via le mailer configuré (Brevo en production)')]
class MailTestCommand extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $port = (string) config('mail.mailers.smtp.port');
        $from = (string) config('mail.from.address');

        $this->info("Mailer : {$mailer} | SMTP : {$host}:{$port} | From : {$from}");

        try {
            Mail::raw(
                'Test SIBEA — si vous recevez ce message, Brevo est correctement configuré.',
                function ($message) use ($email): void {
                    $message->to($email)->subject('Test SIBEA — '.config('app.name'));
                }
            );
        } catch (\Throwable $e) {
            $this->error('Échec d’envoi : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Email de test envoyé à {$email}.");

        return self::SUCCESS;
    }
}
