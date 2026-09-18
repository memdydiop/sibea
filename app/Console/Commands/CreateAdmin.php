<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

#[Signature('app:create-admin {--name= : Nom complet} {--email= : Adresse e-mail} {--password= : Mot de passe (min. 8 caractères)} {--generate-password : Génère un mot de passe aléatoire} {--force : Réinitialise les identifiants si le compte existe déjà}')]
#[Description('Crée un administrateur (ou le réinitialise avec --force) avec le rôle Super administrateur')]
class CreateAdmin extends Command
{
    protected const ROLE = 'Super administrateur';

    public function handle(): int
    {
        $name = $this->resolveName();
        $email = $this->resolveEmail();

        if ($name === null || $email === null) {
            $this->error('Les options --name et --email sont requises en mode non interactif.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255']],
        );

        if ($validator->fails()) {
            $this->reportErrors($validator->errors()->all());

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $force = (bool) $this->option('force');

        $password = null;
        $generatedPassword = false;

        if ($user === null || $force) {
            [$password, $generatedPassword] = $this->resolvePassword();

            if ($password === null) {
                return self::FAILURE;
            }
        }

        if (! Role::query()->where('name', self::ROLE)->exists()) {
            $this->call('db:seed', ['--class' => RolesPermissionsSeeder::class, '--force' => true]);
        }

        if ($user === null) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $this->info("Administrateur créé : {$user->email} (rôle ".self::ROLE.').');
        } elseif ($force) {
            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'password_changed_at' => null,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $this->info("Administrateur mis à jour : {$user->email} (rôle ".self::ROLE.').');
        } else {
            $this->info("L'administrateur {$user->email} existe déjà ; rôle vérifié, identifiants inchangés.");
        }

        $user->syncRoles([self::ROLE]);

        if ($generatedPassword) {
            $this->warn("Mot de passe généré : {$password}");
        }

        return self::SUCCESS;
    }

    protected function resolveName(): ?string
    {
        $name = $this->option('name');

        if (filled($name)) {
            return (string) $name;
        }

        return $this->input->isInteractive() ? $this->ask('Nom complet') : null;
    }

    protected function resolveEmail(): ?string
    {
        $email = $this->option('email');

        if (filled($email)) {
            return (string) $email;
        }

        return $this->input->isInteractive() ? $this->ask('Adresse e-mail') : null;
    }

    /**
     * @return array{0: string|null, 1: bool}
     */
    protected function resolvePassword(): array
    {
        if ($this->option('generate-password')) {
            return [Str::password(20, symbols: false), true];
        }

        $password = $this->option('password');

        if (filled($password)) {
            $password = (string) $password;

            return $this->passwordIsValid($password) ? [$password, false] : [null, false];
        }

        if ($this->input->isInteractive()) {
            $password = (string) $this->secret('Mot de passe (min. 8 caractères)');

            return filled($password) && $this->passwordIsValid($password) ? [$password, false] : [null, false];
        }

        return [Str::password(20, symbols: false), true];
    }

    protected function passwordIsValid(string $password): bool
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:8']],
        );

        if ($validator->fails()) {
            $this->reportErrors($validator->errors()->all());

            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $errors
     */
    protected function reportErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->error($error);
        }
    }
}
