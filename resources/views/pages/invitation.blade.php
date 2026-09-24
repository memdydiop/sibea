<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Invitation')] class extends Component
{
    public User $user;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        abort_if($user->suspended_at !== null, 403);

        // Le middleware `signed` protège le GET, mais les POST Livewire
        // (`/livewire/update`) ne portent pas la signature. On mémorise donc
        // en session qu'un lien signé valide a été présenté pour ce compte.
        if (request()->hasValidSignature()) {
            session()->put($this->sessionKey($user), true);
        }

        $this->user = $user;
    }

    public function save(): void
    {
        abort_if($this->user->password_changed_at !== null, 410);
        abort_if($this->user->suspended_at !== null, 403);
        abort_unless(session()->get($this->sessionKey($this->user)) === true, 403);

        $key = 'invitation:'.$this->user->id.'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('password', 'Trop de tentatives. Réessayez dans une minute.');

            return;
        }
        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $this->user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ]);

        RateLimiter::clear($key);
        session()->forget($this->sessionKey($this->user));

        $this->redirect(route('login'), navigate: true);
    }

    private function sessionKey(User $user): string
    {
        return 'invitation_verified_'.$user->id;
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Définir votre mot de passe')" :description="__('Choisissez le mot de passe de votre compte')" />

    @if($user->password_changed_at !== null)
        <p class="text-center text-sm text-zinc-500">Cette invitation a déjà été utilisée. Connectez-vous avec votre mot de passe.</p>

        <flux:button variant="primary" :href="route('login')" wire:navigate class="w-full">Se connecter</flux:button>
    @else
        <p class="text-center text-sm text-zinc-500">Compte : {{ $user->email }}</p>

        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:field>
                <flux:label>Mot de passe *</flux:label>
                <flux:input wire:model="password" type="password" autocomplete="new-password" viewable />
                <flux:description>12 caractères minimum : minuscules, majuscules, chiffres et symboles.</flux:description>
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirmation *</flux:label>
                <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" viewable />
                <flux:error name="password_confirmation" />
            </flux:field>

            <flux:button variant="primary" type="submit" class="w-full">Définir mon mot de passe</flux:button>
        </form>
    @endif
</div>
