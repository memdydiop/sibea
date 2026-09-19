<?php

use App\Models\User;
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
        $this->user = $user;
    }

    public function save(): void
    {
        abort_if($this->user->password_changed_at !== null, 410);

        $validated = $this->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $this->user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ]);

        $this->redirect(route('login'), navigate: true);
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
