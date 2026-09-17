<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\Sector;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $company = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $phone = '';

    #[Validate('required|exists:sectors,id')]
    public ?int $sector_id = null;

    #[Validate('required|in:information,devis,partenariat,candidature,presse,autre')]
    public string $request_type = '';

    public ?string $budget = null;

    #[Validate('required|string|max:2000')]
    public string $message = '';

    #[Validate('accepted')]
    public bool $consent = false;

    public string $honeypot = '';

    public bool $success = false;

    public function submit(): void
    {
        if ($this->honeypot !== '') {
            return;
        }

        $key = 'contact:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Trop de tentatives. Réessayez dans une minute.');

            return;
        }
        RateLimiter::hit($key, 60);

        $this->validate();

        if (! Sector::whereKey($this->sector_id)->active()->exists()) {
            $this->addError('sector_id', 'Le secteur sélectionné n’est plus disponible.');

            return;
        }

        $lead = Lead::create([
            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'sector_id' => $this->sector_id,
            'request_type' => $this->request_type,
            'budget' => $this->budget,
            'message' => $this->message,
            'status' => LeadStatus::Nouveau,
            'source' => LeadSource::Site,
            'consent_at' => now(),
            'consent_ip' => request()->ip(),
        ]);

        LeadCreated::dispatch($lead);

        $this->reset(['name', 'company', 'email', 'phone', 'sector_id', 'request_type', 'budget', 'message', 'consent', 'honeypot']);
        $this->success = true;
    }
};
?>

<flux:modal name="contact" class="md:w-[32rem]">
    @if($success)
        <div class="space-y-6">
            <flux:heading size="lg">Message envoyé</flux:heading>
            <flux:callout variant="success" icon="check-circle">
                Merci, votre demande a bien été envoyée. Nous vous répondrons rapidement.
            </flux:callout>
            <div class="flex justify-end">
                <flux:button variant="primary" class="!bg-cuivre"
                    x-on:click="$flux.modal('contact').close(); $wire.set('success', false)">
                    Fermer
                </flux:button>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5">
            <div>
                <flux:heading size="lg">Nous contacter</flux:heading>
                <flux:subheading>Parlons de votre projet.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nom complet *</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Société</flux:label>
                <flux:input wire:model="company" type="text" />
                <flux:error name="company" />
            </flux:field>

            <flux:field>
                <flux:label>Email *</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Téléphone</flux:label>
                <flux:input wire:model="phone" type="tel" />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:label>Secteur concerné *</flux:label>
                <flux:select wire:model="sector_id">
                    <option value="">Sélectionnez un secteur</option>
                    @foreach(\App\Models\Sector::cachedActiveList() as $sector)
                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="sector_id" />
            </flux:field>

            <flux:field>
                <flux:label>Type de demande *</flux:label>
                <flux:select wire:model="request_type">
                    <option value="">Sélectionnez</option>
                    <option value="information">Demande d’information</option>
                    <option value="devis">Demande de devis</option>
                    <option value="partenariat">Partenariat</option>
                    <option value="candidature">Candidature</option>
                    <option value="presse">Presse</option>
                    <option value="autre">Autre</option>
                </flux:select>
                <flux:error name="request_type" />
            </flux:field>

            <flux:field>
                <flux:label>Budget indicatif</flux:label>
                <flux:input wire:model="budget" type="text" />
                <flux:error name="budget" />
            </flux:field>

            <flux:field>
                <flux:label>Message *</flux:label>
                <flux:textarea wire:model="message" rows="4" />
                <flux:error name="message" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="consent" />
                <flux:label>J’accepte la politique de confidentialité *</flux:label>
                <flux:error name="consent" />
            </flux:field>

            <input type="text" wire:model="honeypot" class="hidden" tabindex="-1" autocomplete="off">

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$flux.modal('contact').close()">
                    Annuler
                </flux:button>
                <flux:button type="submit" variant="primary" class="!bg-cuivre">
                    Envoyer
                </flux:button>
            </div>
        </form>
    @endif
</flux:modal>
