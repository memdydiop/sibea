<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ProspectType;
use App\Enums\RequestType;
use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\Sector;
use App\Notifications\LeadAcknowledgment;
use App\Support\LeadAssigner;
use App\Support\PageSeo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {
    public string $name = '';

    public string $company = '';

    public string $prospect_type = 'particulier';

    public string $email = '';

    public string $phone = '';

    public string $residence_country = '';

    public string $target_territory = '';

    public ?int $sector_id = null;

    public string $request_type = '';

    public ?string $budget = null;

    public ?string $deadline = null;

    public string $message = '';

    public string $honeypot = '';

    public bool $success = false;

    public ?string $origin_page = null;

    public ?string $utm_source = null;

    public ?string $utm_medium = null;

    public ?string $utm_campaign = null;

    public function mount(): void
    {
        PageSeo::share('Contact', setting('contact.hero.subtitle'));
        View::share('ogImage', setting_media_url('visuals.hero.contact', 'hero') ?: null);

        $referer = (string) request()->headers->get('referer', '');
        $path = $referer !== '' ? parse_url($referer, PHP_URL_PATH) : null;

        if (is_string($path) && $path !== '' && $path !== '/contact') {
            $this->origin_page = mb_substr($path, 0, 500);
        }

        // UTM : query du moment prioritaire, sinon referer (navigation
        // /secteurs/btp?utm_source=… → /contact), sinon session.
        $refererQuery = [];
        if ($referer !== '') {
            parse_str((string) parse_url($referer, PHP_URL_QUERY), $refererQuery);
        }

        $utm = [
            'utm_source' => request()->query('utm_source') ?? $refererQuery['utm_source'] ?? session('utm.utm_source'),
            'utm_medium' => request()->query('utm_medium') ?? $refererQuery['utm_medium'] ?? session('utm.utm_medium'),
            'utm_campaign' => request()->query('utm_campaign') ?? $refererQuery['utm_campaign'] ?? session('utm.utm_campaign'),
        ];

        if (request()->query('utm_source') || request()->query('utm_medium') || request()->query('utm_campaign')) {
            session(['utm' => $utm]);
        }

        $this->utm_source = is_string($utm['utm_source']) ? mb_substr($utm['utm_source'], 0, 255) : null;
        $this->utm_medium = is_string($utm['utm_medium']) ? mb_substr($utm['utm_medium'], 0, 255) : null;
        $this->utm_campaign = is_string($utm['utm_campaign']) ? mb_substr($utm['utm_campaign'], 0, 255) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'prospect_type' => ['required', Rule::enum(ProspectType::class)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'residence_country' => ['required', 'string', 'max:255'],
            'target_territory' => ['required', 'string', 'max:255'],
            'sector_id' => ['required', 'exists:sectors,id'],
            'request_type' => ['required', Rule::enum(RequestType::class)],
            'budget' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    private function resolveExpertiseFromOrigin(): ?int
    {
        if ($this->origin_page === null || ! str_starts_with($this->origin_page, '/expertises/')) {
            return null;
        }

        $slug = explode('/', trim($this->origin_page, '/'))[1] ?? null;

        if ($slug === null || $slug === '') {
            return null;
        }

        return \App\Models\Expertise::where('slug', $slug)->value('id');
    }

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

        $this->validate();

        RateLimiter::hit($key, 60);

        if (!Sector::whereKey($this->sector_id)->active()->exists()) {
            $this->addError('sector_id', 'Le secteur sélectionné n’est plus disponible.');

            return;
        }

        $lead = DB::transaction(function () {
            $email = mb_strtolower(trim($this->email));

            $created = Lead::create([
                'name' => trim($this->name),
                'company' => $this->company !== '' ? trim($this->company) : $this->company,
                'prospect_type' => $this->prospect_type,
                'email' => $email,
                'phone' => $this->phone,
                'residence_country' => $this->residence_country,
                'target_territory' => $this->target_territory,
                'sector_id' => $this->sector_id,
                'expertise_id' => $this->resolveExpertiseFromOrigin(),
                'request_type' => $this->request_type,
                'budget' => $this->budget,
                'deadline' => $this->deadline,
                'message' => $this->message,
                'status' => LeadStatus::Nouveau,
                'source' => LeadSource::Site,
                'origin_page' => $this->origin_page,
                'utm_source' => $this->utm_source,
                'utm_medium' => $this->utm_medium,
                'utm_campaign' => $this->utm_campaign,
                'assigned_to' => config('leads.auto_assign') ? LeadAssigner::next()?->id : null,
                'consent_at' => now(),
                'consent_ip' => request()->ip(),
            ]);

            $created->activities()->create([
                'action' => 'created',
                'description' => 'Prospect créé via la page contact.'.($created->assigned_to ? ' Assigné auto à '.$created->assignedTo?->name.'.' : ''),
            ]);

            return $created;
        });

        $lead->refresh();

        LeadCreated::dispatch($lead);

        // Accusé de réception (file d'attente) — uniquement le formulaire public,
        // jamais la saisie admin manuelle.
        Notification::route('mail', $lead->email)->notify(new LeadAcknowledgment($lead));

        $this->reset(['name', 'company', 'prospect_type', 'email', 'phone', 'residence_country', 'target_territory', 'sector_id', 'request_type', 'budget', 'deadline', 'message', 'honeypot']);
        $this->prospect_type = ProspectType::Particulier->value;
        $this->success = true;
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white  overflow-hidden">
        <img src="{{ setting_media_url('visuals.hero.contact') }}" alt=""
            class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0"
            style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre"
                            wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">Contact</li>
                </ol>
            </nav>
            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">Contact</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">{{ setting('contact.hero.subtitle') }}</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
        <div class="grid lg:grid-cols-5 gap-12 lg:gap-16">
            <div class="lg:col-span-2">
                <div class="text-accroche font-display font-semibold tracking-widest text-xs mb-3">CONTACT DIRECT</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-4xl mb-6">Coordonnées
                </h2>
                <p class="text-ardoise text-lg max-w-xl mb-12">Une question, un projet à nous confier, ou simplement
                    envie d’échanger ? Notre équipe est à votre écoute.</p>

                <dl class="space-y-6">
                    <div class="flex gap-4 items-start">
                        <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-cuivre/10 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-6 h-6 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </span>
                        <div>
                            <dt class="font-display font-bold mb-1">Adresse</dt>
                            <dd class="text-ardoise text-sm leading-relaxed">{!! nl2br(e(setting('contact.address'))) !!}</dd>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-cuivre/10 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-6 h-6 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                        </span>
                        <div>
                            <dt class="font-display font-bold mb-1">Téléphone</dt>
                            <dd class="text-ardoise text-sm"><a href="tel:{{ setting('contact.phone_link') }}"
                                    class="hover:text-cuivre transition-colors">{{ setting('contact.phone') }}</a></dd>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-cuivre/10 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-6 h-6 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                        </span>
                        <div>
                            <dt class="font-display font-bold mb-1">Email</dt>
                            <dd class="text-ardoise text-sm"><a href="mailto:{{ setting('contact.email') }}"
                                    class="hover:text-cuivre transition-colors">{{ setting('contact.email') }}</a></dd>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-cuivre/10 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-6 h-6 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        <div>
                            <dt class="font-display font-bold mb-1">Horaires</dt>
                            <dd class="text-ardoise text-sm leading-relaxed">{!! nl2br(e(setting('contact.hours'))) !!}</dd>
                        </div>
                    </div>
                </dl>

                <div class="mt-12 border border-bordure rounded-lg bg-surface p-6">
                    <div class="font-display font-bold mb-2">Échange direct</div>
                    <p class="text-ardoise text-sm leading-relaxed mb-5">Discutez en temps réel avec un conseiller
                        SIBEA, du lundi au samedi.</p>
                    <a href="https://wa.me/{{ setting('contact.whatsapp') }}?text={{ urlencode(setting('contact.whatsapp_message')) }}"
                        target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-[#25D366] px-5 py-3 font-display font-semibold text-white text-sm hover:bg-[#1EBE5B] transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Discuter sur WhatsApp
                    </a>
                    @if(setting('social.facebook'))
                        <a href="{{ setting('social.facebook') }}"
                            target="_blank" rel="noopener"
                            class="mt-3 inline-flex items-center justify-center gap-2 w-full rounded-lg border border-bordure bg-casse px-5 py-3 font-display font-semibold text-nuit text-sm transition-colors hover:border-cuivre hover:text-cuivre">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                            Suivre SIBEA sur Facebook
                        </a>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-3">
                @if ($success)
                    <div class="border border-bordure rounded-lg bg-surface p-8 lg:p-10">
                        <flux:callout variant="success" icon="check-circle">
                            Merci, votre demande a bien été envoyée. Nous vous répondrons sous 24h ouvrées.
                        </flux:callout>
                        <div class="mt-6">
                            <flux:button variant="primary" class="!bg-cuivre" wire:click="$set('success', false)">
                                Envoyer une autre demande
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="border border-bordure rounded-lg bg-surface p-8 lg:p-10">
                        <div class="text-accroche font-display font-semibold tracking-widest text-xs mb-3">FORMULAIRE
                            DIRECT</div>
<h2 class="font-display font-extrabold text-nuit tracking-tight text-2xl mb-2">Un projet à structurer ?</h2>
                        <p class="text-ardoise text-sm mb-8">Remplissez ce formulaire, notre équipe vous recontacte
                            sous 24h ouvrées.</p>

                        <form wire:submit="submit" class="space-y-5">
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <flux:input wire:model="name" label="Nom complet *" type="text"
                                        placeholder="Votre nom complet *" />
                                    <flux:error name="name" />
                                </div>

                                <div>
                                    <flux:input wire:model="company" label="Société" type="text"
                                        placeholder="Votre société" />
                                    <flux:error name="company" />
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <flux:select wire:model="prospect_type" label="Vous êtes *">
                                        @foreach (ProspectType::cases() as $type)
                                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="prospect_type" />
                                </div>
                                <div>
                                    <flux:input wire:model="deadline" label="Échéance souhaitée" type="date" />
                                    <flux:error name="deadline" />
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <flux:input wire:model="email" label="Email *" type="email"
                                        placeholder="Votre adresse email" />
                                    <flux:error name="email" />
                                </div>
                                <div>
                                    <flux:input wire:model="phone" label="Votre numéro de téléphone" type="tel" />
                                    <flux:error name="phone" />
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <flux:input wire:model="residence_country" label="Pays de résidence *" type="text"
                                        placeholder="Ex. Côte d’Ivoire, France…" />
                                    <flux:error name="residence_country" />
                                </div>
                                <div>
                                    <flux:input wire:model="target_territory" label="Territoire ciblé *" type="text"
                                        placeholder="Ex. Abidjan, Cocody" />
                                    <flux:error name="target_territory" />
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <flux:select wire:model="sector_id" label="Secteur concerné *">
                                        <flux:select.option value="">Sélectionnez un secteur</flux:select.option>
                                        @foreach (Sector::cachedActiveList() as $sector)
                                            <flux:select.option value="{{ $sector->id }}">{{ $sector->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="sector_id" />
                                </div>

                                <div>
                                    <flux:select wire:model="request_type" label="Type de demande *">
                                        <flux:select.option value="">Sélectionnez</flux:select.option>
                                        @foreach (RequestType::cases() as $type)
                                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="request_type" />
                                </div>
                            </div>

                            <div>
                                <flux:input wire:model="budget" label="Budget indicatif" type="text"
                                    placeholder="Ex. 25 000 000 FCFA" />
                                <flux:error name="budget" />
                            </div>
                            <div>
                                <flux:textarea wire:model="message" label="Message *" rows="5"
                                    placeholder="Décrivez votre projet, vos besoins, vos contraintes…" />
                                <flux:error name="message" />
                            </div>

                            <p class="text-xs text-ardoise">En soumettant ce formulaire, vous consentez à l’utilisation de vos données pour traiter votre demande, conformément à notre <a
                                    href="{{ route('privacy') }}" class="underline text-cuivre"
                                    target="_blank">politique de confidentialité</a>.</p>

                            <input type="text" wire:model="honeypot" class="hidden" tabindex="-1" aria-hidden="true"
                                autocomplete="off">

                            <div class="pt-2">
                                <flux:button type="submit" variant="primary" class="bg-cuivre! w-full sm:w-auto"
                                    wire:loading.attr="disabled" wire:target="submit">
                                    Envoyer ma demande
                                </flux:button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-surface border-y border-bordure">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
            <div class="text-accroche font-display font-semibold tracking-widest text-xs mb-3">NOS IMPLANTATIONS</div>
            <h2 class="font-display font-extrabold text-nuit tracking-tight text-2xl mb-8">Nous trouver</h2>
            <div class="grid lg:grid-cols-3 gap-8 items-start">
                <div class="border border-bordure rounded-lg bg-casse p-6">
                    <div class="font-display font-bold text-lg mb-1">{{ setting('contact.office_title') }}</div>
                    <div class="text-cuivre text-sm font-semibold mb-3">{{ setting('contact.office_location') }}</div>
                    <p class="text-ardoise text-sm leading-relaxed">{{ setting('contact.office_description') }}</p>
                </div>
                <div class="lg:col-span-2 rounded-lg overflow-hidden border border-bordure">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ setting('contact.longitude') - 0.1 }}%2C{{ setting('contact.latitude') - 0.1 }}%2C{{ setting('contact.longitude') + 0.1 }}%2C{{ setting('contact.latitude') + 0.1 }}&layer=mapnik&marker={{ setting('contact.latitude') }}%2C{{ setting('contact.longitude') }}"
                        width="100%" height="380" style="border:0;" loading="lazy"
                        title="Localisation — {{ setting('contact.office_title') }}">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <noscript>
        <section class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
            <div class="border border-bordure rounded-lg p-6 bg-surface max-w-xl">
                <p class="text-sm text-anthracite">
                    JavaScript est désactivé. Écrivez-nous directement à
                    <a class="text-cuivre underline"
                        href="mailto:{{ setting('contact.email') }}">{{ setting('contact.email') }}</a>.
                </p>
            </div>
        </section>
    </noscript>
</div>
