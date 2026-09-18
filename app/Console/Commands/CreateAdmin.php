<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

#[Signature('app:create-admin {--name= : Nom complet} {--email= : Adresse e-mail} {--password= : Mot de passe (min. 8 caractères)}')]
#[Description('Crée ou met à jour un administrateur avec le rôle Super administrateur')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail');
        $password = $this->option('password') ?: $this->secret('Mot de passe (min. 8 caractères)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! Role::where('name', 'Super administrateur')->exists()) {
            $this->call('db:seed', ['--class' => RolesPermissionsSeeder::class, '--force' => true]);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['Super administrateur']);

        $this->info("Administrateur prêt : {$user->email} (rôle Super administrateur).");

        return self::SUCCESS;
    }
}
