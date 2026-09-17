# CAHIER DES CHARGES FONCTIONNEL ET TECHNIQUE

## Site vitrine institutionnel du Groupe SIBEA

**Version :** 1.3  
**Type de projet :** Site vitrine institutionnel administrable  
**Technologies :** Laravel 13, Livewire 4 (Single File Components), Flux UI Free, Tailwind CSS, PostgreSQL  
**Périmètre :** Site public + interface d’administration CMS (V1) + CRM prospects

**Avenant v1.3 :** le 4e secteur officiel « Agroalimentaire » est renommé « Agro-industrie » (positionnement transformation). Anciennes URLs redirigées en 301 vers les nouvelles.

---

# 1. Présentation du projet

Le Groupe SIBEA souhaite mettre en place un site web institutionnel moderne, professionnel, responsive et administrable.

Le site doit présenter :

- L’identité et le positionnement du Groupe SIBEA ;
- Ses quatre secteurs d’activité officiels ;
- Ses expertises et savoir-faire ;
- Ses réalisations ;
- Ses engagements ;
- Ses informations de contact ;
- Ses opportunités commerciales.

Le site doit permettre à l’équipe interne de gérer les contenus depuis une interface d’administration sans modifier le code source.

## 1.1 Principes directeurs

- **Clarté institutionnelle** : message court, lisible, porté par la homepage.
- **Simplicité d’administration** : CMS structuré, phasé V1 / V2.
- **Flux UI Free exclusivement en V1** : aucun achat Flux UI Pro. Fallbacks Tailwind documentés.
- **Formulaires en modal** : tous les formulaires publics et admin s’ouvrent dans une modale Flux UI.
- **Single File Components** : pages Livewire 4 en SFC.
- **Pas de composants Blade réutilisables** : pages autonomes ; header/footer inline dans le layout public.
- **Exception : partial SEO** : `resources/views/partials/seo.blade.php` centralise les balises SEO.
- **Starter kit officiel** : `livewire/livewire-starter-kit`, layout admin utilisé tel quel.
- **Regroupement par domaine** : fichiers liés dans un même dossier.
- **Direction artistique inspirée de Bigness Group**, adaptée au positionnement multi-activités.
- **Spatie Media Library** : gestion 100 % Spatie, pas de colonnes string pour les images.

---

# 2. Objectifs du site

## 2.1 Objectifs institutionnels

- Présenter clairement le Groupe SIBEA ;
- Renforcer sa crédibilité ;
- Valoriser son image professionnelle ;
- Présenter ses quatre secteurs d’activité ;
- Démontrer son expertise ;
- Mettre en avant ses réalisations ;
- Présenter ses valeurs et engagements.

## 2.2 Objectifs commerciaux

- Générer des demandes de contact ;
- Recevoir des demandes de devis ;
- Recevoir des demandes d’informations ;
- Identifier les besoins des prospects ;
- Orienter les visiteurs vers le bon secteur d’activité ;
- Faciliter la prise de rendez-vous.

## 2.3 Objectifs marketing

- Être optimisé pour les moteurs de recherche ;
- Être partageable sur les réseaux sociaux ;
- Mettre en avant les projets réalisés ;
- Valoriser l’expertise du Groupe SIBEA ;
- Servir de support aux campagnes commerciales.

---

# 3. Positionnement du Groupe SIBEA

## 3.1 Secteurs d’activité officiels

Les secteurs d’activité officiels sont exclusivement :

1. **BTP**
2. **Immobilier**
3. **Énergie**
4. **Agro-industrie**

Ces quatre secteurs sont utilisés dans :

- La homepage ;
- Le menu principal ;
- La page « Secteurs d’activité » ;
- Le CMS ;
- Les filtres des réalisations ;
- Les associations avec les expertises ;
- Les formulaires de contact ;
- Les données SEO.

## 3.2 Règle de nommage

Les secteurs ne doivent pas être renommés en :

- BTP & Construction ;
- Ingénierie & Conseil ;
- Énergie & Services ;
- Autres appellations similaires.

## 3.3 Protection des secteurs officiels

Les 4 secteurs officiels sont créés par seeder avec `is_locked = true`. La Policy `SectorPolicy` interdit leur suppression. Un 5e secteur peut être ajouté plus tard, mais reste supprimable.

Le secteur **Agro-industrie** a été créé avec `is_active = false` jusqu’à validation par SIBEA (avenant v1.3 : validé, `is_active = true`).

---

# 4. Différence entre secteurs, expertises et services

## 4.1 Secteurs

```text
BTP
Immobilier
Énergie
Agro-industrie
```

## 4.2 Expertises

```text
Études & ingénierie
Construction & réalisation
Développement immobilier
Solutions énergétiques
Solutions agro-industrielles
```

## 4.3 Services

Prestations concrètes, rattachées à une ou plusieurs expertises. Pas de page publique dédiée. Les secteurs d’un service sont dérivés de ses expertises.

## 4.4 Hiérarchie fonctionnelle

```text
Groupe SIBEA
│
├── Secteurs d’activité (4, verrouillés)
│   ├── BTP
│   ├── Immobilier
│   ├── Énergie
│   └── Agro-industrie
│
└── Expertises (5)
    ├── Études & ingénierie
    │   └── Services associés…
    ├── Construction & réalisation
    │   └── Services associés…
    ├── Développement immobilier
    │   └── Services associés…
    ├── Solutions énergétiques
    │   └── Services associés…
    └── Solutions agro-industrielles
        └── Services associés…
```

---

# 5. Architecture générale du site

## 5.1 Partie publique

- Accueil ;
- Secteurs d’activité + 4 pages détail ;
- Nos expertises + 5 pages détail ;
- Réalisations + pages détail ;
- Contact ;
- Mentions légales ;
- Politique de confidentialité.

## 5.2 Partie administration

### V1 (lancement) — 8 modules

```text
dashboard
sectors
expertises
services
projects
leads
users
roles
```

Chiffres clés : lecture seule (is_active togglable via commande artisan).

### V2 (post-lancement)

```text
pages
testimonials
statistics
media
menus
settings
seo
```

---

# 6. Arborescence du site public

```text
/
├── Accueil
├── Secteurs d’activité
│   ├── BTP
│   ├── Immobilier
│   ├── Énergie
│   └── Agro-industrie
│
├── Expertises
│   ├── Études & ingénierie
│   ├── Construction & réalisation
│   ├── Développement immobilier
│   ├── Solutions énergétiques
│   └── Solutions agro-industrielles
│
├── Réalisations
│   └── Détail d’une réalisation
│
├── Contact
├── Mentions légales
└── Politique de confidentialité
```

---

# 7. Navigation

## 7.1 Navigation principale

```text
Accueil
Secteurs d’activité
Expertises
Réalisations
Contact
```

Le logo SIBEA renvoie vers l’accueil. Le bouton « Nous contacter » ouvre la modale de contact.

## 7.2 Header

- Logo SIBEA ;
- Navigation principale ;
- Bouton « Nous contacter » ;
- Menu mobile responsive ;
- Sticky, transparent sur hero, bleu nuit après défilement.

---

# 8. Page d’accueil

## 8.1 Structure

```text
1. Header
2. Hero hybride
3. Présentation synthétique
4. Secteurs d’activité
5. Nos expertises
6. Chiffres clés
7. Réalisations
8. Valeurs et engagements
9. CTA contact
10. Footer
```

## 8.2 Hero hybride

### Colonne gauche (fixe)

Label : `GROUPE SIBEA`

Titre H1 : `Des expertises solides pour construire des projets durables.`

Description : `Le Groupe SIBEA rassemble des compétences complémentaires dans le BTP, l’immobilier, l’énergie et l’agro-industrie pour accompagner des projets structurants et créateurs de valeur.`

Boutons : `Découvrir nos secteurs`, `Voir nos réalisations`

### Colonne droite — carrousel sectoriel

4 slides : BTP, Immobilier, Énergie, Agro-industrie.

### Comportement du carrousel

- Autoplay **7000 ms**, pause au survol et au focus ;
- Boutons prev / next visibles ;
- Indicateurs cliquables ;
- Navigation clavier (flèches gauche/droite) ;
- **Swipe tactile sur mobile** (touchstart / touchend) ;
- **Autoplay désactivé sur mobile** (< 1024px) ;
- `clearInterval` au démontage (Alpine `destroy()`) ;
- Respect de `prefers-reduced-motion` : autoplay désactivé ;
- `aria-live="polite"` sur le conteneur du slide actif ;
- `aria-hidden="true"` sur les slides inactifs ;
- `role="tablist"` sur les indicateurs, `aria-selected` sur le point actif.

### Implémentation

- Logique dans la page SFC `pages::home`.
- Données issues de la table `sectors`.
- Champs nécessaires : `hero_title`, `hero_description`, `hero_cta_label`, `hero_is_active`, `hero_sort_order` + media Spatie `hero`.

## 8.3 Présentation synthétique

Titre : `Un groupe, plusieurs expertises, une même ambition.`

Contenu : présentation courte, mission, vision, valeurs. Pas d’historique détaillé.

## 8.4 Secteurs d’activité

Cartes : 01 BTP, 02 Immobilier, 03 Énergie, 04 Agro-industrie (affichée uniquement si `is_active = true`).

## 8.5 Nos expertises

5 cartes avec extrait des services associés.

## 8.6 Chiffres clés

**En V1, les chiffres clés sont en lecture seule côté admin.** L’édition se fait via seeder ou commande artisan jusqu’à l’écran V2. La section homepage n’affiche que les chiffres `is_active = true`.

Champs administrables (via commande) : années d’expérience, projets réalisés, secteurs, collaborateurs, partenaires, clients.

Aucune valeur ne doit être publiée sans validation SIBEA.

## 8.7 Réalisations

3 projets sélectionnés + filtres secteur.

## 8.8 Valeurs et engagements

Qualité · Fiabilité · Innovation · Responsabilité · Durabilité.

## 8.9 CTA final

`Vous avez un projet à structurer ?` + bouton `Nous contacter`.

## 8.10 Footer

Logo, description courte, navigation, secteurs (filtrés sur `active()`), expertises, contact, mentions légales, politique de confidentialité, copyright.

---

# 9. Pages fonctionnelles

## 9.1 Page « Secteurs d’activité »

Affiche exclusivement les secteurs actifs. Chaque secteur : image, description, expertises associées, services associés, réalisations, CTA contact.

## 9.2 Page détail d’un secteur

**URL canonique :** `/secteurs/{slug}`  
**Redirect 301 :** `/btp` → `/secteurs/btp`, `/immobilier` → `/secteurs/immobilier`, etc.

Contenu : hero sectoriel, présentation, expertises associées, services associés, réalisations, CTA contact.

## 9.3 Page « Nos expertises »

5 expertises. Chaque expertise contient : titre, slug, description courte et complète, image, icône, secteurs concernés, services associés, bénéfices, étapes d’intervention, réalisations liées, CTA contact.

## 9.4 Page détail d’une expertise

```text
/expertises
/expertises/etudes-ingenierie
/expertises/construction-realisation
/expertises/developpement-immobilier
/expertises/solutions-energetiques
/expertises/solutions-agro-industrielles
```

## 9.5 Page « Réalisations »

Filtres : secteur, année, localisation. Recherche, pagination, page détail.

## 9.6 Page « Contact »

Coordonnées, carte, bouton principal « Nous contacter » ouvrant la modale. Formulaire inline de secours si JS désactivé.

---

# 10. Formulaire de contact en modal

## 10.1 Champs

- Nom complet (requis) ;
- Société ;
- Email (requis) ;
- Téléphone ;
- **Secteur concerné** (requis, parmi les **secteurs actifs**) ;
- Type de demande (requis) : Demande d’information, Demande de devis, Partenariat, Candidature, Presse, Autre ;
- Budget indicatif (optionnel) ;
- Message (requis) ;
- Consentement politique de confidentialité (requis) ;
- Honeypot anti-spam (champ caché) ;
- **Rate limiting applicatif : `RateLimiter::attempt("contact:{$ip}", 5, 60)`** ;
- **Message de succès affiché dans la modale après envoi**.

## 10.2 Traitement

- Vérification honeypot (retour silencieux si rempli) ;
- **Rate limiting applicatif** : `RateLimiter::attempt("contact:{$ip}", 5, 60)` ;
- Enregistrement dans `leads` avec `consent_at` et `consent_ip` ;
- Dispatch event `LeadCreated` ;
- Listener `SendLeadNotification` → mail via queue ;
- État `success = true` affiché dans la modale.

## 10.3 Statuts

Enum PHP `App\Enums\LeadStatus` : `Nouveau`, `Contacté`, `En cours`, `Qualifié`, `Converti`, `Non qualifié`, `Archivé`. Valeur sérialisée en base : `nouveau`. Cast dans le modèle `Lead`.

---

# 11. CMS et administration

## 11.1 Principes

Contenus gérés sans modifier le code. Modales Flux UI pour tous les formulaires CRUD.

## 11.2 V1 — Modules administrables

```text
Dashboard
Secteurs (4 verrouillés + extensions)
Expertises
Services
Réalisations
Prospects
Utilisateurs
Rôles
```

## 11.3 V2 — Modules post-lancement

```text
Pages
Témoignages
Chiffres clés (statistics)
Médias
Menus
Paramètres
SEO
```

## 11.4 Formulaires d’administration

Tous en modale Flux UI. Chaque modale : `flux:heading`, `flux:subheading`, `flux:field`, `flux:input`, `flux:textarea`, `flux:select`, `flux:switch`, `flux:error`, pied avec `flux:button` (Annuler) et `flux:button variant="primary"` (Enregistrer).

## 11.5 Éléments non dynamiques

Design tokens, palette, typographies, structure Tailwind, composants UI, responsive, navigation technique, permissions, architecture, sécurité.

---

# 12. Modèle de données

## 12.1 Tables

```text
users
roles
permissions
pages
sectors
expertises
services
projects
testimonials
statistics
leads
settings
menus
seo_metadata
```

Tables pivot :

```text
expertise_sector
expertise_service
project_sector
project_expertise
project_service
```

Tables supprimées : `posts`, `post_categories`, `authors`, `contacts`, `media` (remplacée par Spatie), `project_images` (galerie via Spatie collection `gallery`).

## 12.2 Table `sectors`

```text
id
name
slug
short_description
description
hero_title
hero_description
hero_cta_label
hero_is_active
hero_sort_order
is_active
is_locked
sort_order
created_at
updated_at
```

Médias Spatie : collections `hero`, `icon`, `og_image`.  
Aucune colonne string d’image.  
Seeder : 4 secteurs avec `is_locked = true`, tous `is_active = true` depuis l’avenant v1.3.

## 12.3 Table `expertises`

```text
id
name
slug
short_description
description
benefits (json)
process_steps (json)
is_active
sort_order
created_at
updated_at
```

Médias Spatie : `cover`, `icon`, `og_image`.

## 12.4 Table `services`

```text
id
name
slug
short_description
description
is_active
sort_order
created_at
updated_at
```

Pas de `sector_id`. Les secteurs sont dérivés des expertises.

## 12.5 Table `projects`

```text
id
title
slug
short_description
description
location
project_date
status
client_name
client_publishable (boolean)
results (json)
is_active
is_published
created_at
updated_at
```

Médias Spatie : collections `cover`, `gallery`, `og_image`.  
La galerie d’images est portée par la collection `gallery`.  
Aucune table pivot d’images.

## 12.6 Table `leads`

```text
id
name
company
email
phone
sector_id
request_type
budget
message
status
source
assigned_to
notes
consent_at
consent_ip
created_at
updated_at
```

## 12.7 Table `seo_metadata`

```text
id
model_type
model_id
meta_title
meta_description
canonical_url
noindex (boolean)
structured_data (json)
created_at
updated_at
```

OG image : media Spatie sur le modèle.

## 12.8 Relations

```text
Sector belongsToMany Expertises        (expertise_sector)
Sector belongsToMany Projects          (project_sector)
Expertise belongsToMany Sectors        (expertise_sector)
Expertise belongsToMany Services       (expertise_service)
Service belongsToMany Expertises       (expertise_service)
Project belongsToMany Sectors          (project_sector)
Project belongsToMany Expertises       (project_expertise)
Project belongsToMany Services         (project_service)
```

## 12.9 Packages

```text
spatie/laravel-permission
spatie/laravel-medialibrary
spatie/laravel-sitemap
spatie/schema-org
ralphjsmit/laravel-seo
```

---

# 13. Architecture Laravel

## 13.1 Stack

```text
Laravel 13
Livewire 4 (SFC)
Flux UI Free
Tailwind CSS
PostgreSQL
Spatie Laravel Permission
Spatie Media Library
Spatie Sitemap
Spatie Schema.org
RalphJSmit Laravel SEO
Pest
Playwright
```

## 13.2 Principes

- `pages::` : pages routées, dans `resources/views/pages/`.
- `livewire::` : composants embarqués non routés.
- `layouts::` : layouts.
- Regroupement par domaine.
- **Pas de composants Blade réutilisables (pas de dossier `components/`).**
- **Exception : un partial `resources/views/partials/seo.blade.php` est autorisé** pour centraliser les balises SEO, inclus depuis les layouts.
- SFC : classe PHP + template inline, **pas de méthode `render()`** retournant une vue.
- **Les fichiers réels ne portent pas le préfixe `⚡`.** Le préfixe est réservé à la documentation pour signaler les SFC.
- **Les layouts sont des fichiers Blade classiques** (pas des SFC).

## 13.3 Structure

```text
app/
├── Actions/
├── Enums/
│   └── LeadStatus.php
├── Events/
│   └── LeadCreated.php
├── Listeners/
│   └── SendLeadNotification.php
├── Models/
├── Notifications/
│   └── NewLeadNotification.php
├── Policies/
├── Providers/
└── Support/

resources/views/
├── pages/
│   ├── home.blade.php
│   ├── contact.blade.php
│   ├── legal.blade.php
│   ├── privacy.blade.php
│   ├── sectors/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── expertises/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── projects/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   └── admin/
│       ├── dashboard.blade.php
│       ├── sectors/index.blade.php
│       ├── expertises/index.blade.php
│       ├── services/index.blade.php
│       ├── projects/index.blade.php
│       ├── leads/index.blade.php
│       ├── users/index.blade.php
│       └── roles/index.blade.php
│
├── livewire/
│   └── contact-modal.blade.php
│
├── partials/
│   └── seo.blade.php
│
└── layouts/
    ├── app.blade.php       (starter kit — admin, Blade classique)
    ├── auth.blade.php      (starter kit — auth, Blade classique)
    └── public.blade.php    (créé — site public, Blade classique)

routes/
└── web.php
```

**Notes :**

- `app/Events/LeadCreated` : événement dispatché après création d’un lead.
- `app/Listeners/SendLeadNotification` : écoute `LeadCreated`, envoie la notification via queue.
- `app/Notifications/NewLeadNotification` : notification email au commercial assigné.
- `app/Enums/LeadStatus` : enum PHP des statuts, casté dans le modèle `Lead`.
- Seules les pages et `livewire/contact-modal` sont des SFC Livewire.

## 13.4 Routes

```php
use Illuminate\Support\Facades\Route;

// Site public
Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/secteurs', 'pages::sectors.index')->name('sectors.index');
Route::livewire('/secteurs/{sector:slug}', 'pages::sectors.show')->name('sectors.show');
Route::livewire('/expertises', 'pages::expertises.index')->name('expertises.index');
Route::livewire('/expertises/{expertise:slug}', 'pages::expertises.show')->name('expertises.show');
Route::livewire('/realisations', 'pages::projects.index')->name('projects.index');
Route::livewire('/realisations/{project:slug}', 'pages::projects.show')->name('projects.show');
Route::livewire('/contact', 'pages::contact')->name('contact');
Route::livewire('/mentions-legales', 'pages::legal')->name('legal');
Route::livewire('/politique-de-confidentialite', 'pages::privacy')->name('privacy');

// Redirects 301 (compat SEO)
Route::redirect('/btp', '/secteurs/btp', 301);
Route::redirect('/immobilier', '/secteurs/immobilier', 301);
Route::redirect('/energie', '/secteurs/energie', 301);
Route::redirect('/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::redirect('/secteurs/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::redirect('/agro-industrie', '/secteurs/agro-industrie', 301);

// Administration (V1)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/secteurs', 'pages::admin.sectors.index')->name('admin.sectors');
    Route::livewire('/expertises', 'pages::admin.expertises.index')->name('admin.expertises');
    Route::livewire('/services', 'pages::admin.services.index')->name('admin.services');
    Route::livewire('/realisations', 'pages::admin.projects.index')->name('admin.projects');
    Route::livewire('/prospects', 'pages::admin.leads.index')->name('admin.leads');
    Route::livewire('/utilisateurs', 'pages::admin.users.index')->name('admin.users');
    Route::livewire('/roles', 'pages::admin.roles.index')->name('admin.roles');
});
```

## 13.5 Layouts

| Layout | Origine | Usage |
|---|---|---|
| `layouts::app` | Starter kit | Administration |
| `layouts::auth` | Starter kit | Authentification |
| `layouts::public` | Créé | Site vitrine public |

Le layout admin du starter kit est utilisé **tel quel**.

## 13.6 Layout public

**Fichier :** `resources/views/layouts/public.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Groupe SIBEA — BTP, Immobilier, Énergie, Agro-industrie' }}</title>
    <meta name="description" content="{{ $description ?? 'Le Groupe SIBEA rassemble des expertises complémentaires dans le BTP, l’immobilier, l’énergie et l’agro-industrie.' }}">
    {{-- Partial SEO centralisé : exception documentée au principe "aucun composant réutilisable" --}}
    @include('partials.seo')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|inter:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#F7F8FA] text-[#111827] font-[Inter] antialiased">

    <header
        x-data="{ scrolled: false }"
        x-on:scroll.window="scrolled = window.scrollY > 40"
        :class="scrolled ? 'bg-[#0B1F33] text-white shadow-lg' : 'bg-transparent text-white'"
        class="fixed top-0 inset-x-0 z-50 transition-colors duration-300"
    >
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between h-20">
            <a href="{{ route('home') }}" class="font-[Manrope] font-extrabold tracking-tight text-xl">
                GROUPE SIBEA
            </a>
            <nav class="hidden lg:flex items-center gap-8 text-sm font-[Manrope] font-medium">
                <a href="{{ route('sectors.index') }}">Secteurs d’activité</a>
                <a href="{{ route('expertises.index') }}">Expertises</a>
                <a href="{{ route('projects.index') }}">Réalisations</a>
                <a href="{{ route('contact') }}">Contact</a>
            </nav>
            <flux:button variant="primary" x-on:click="$flux.modal('contact').open()" class="!bg-[#E58A32] !text-white">
                Nous contacter
            </flux:button>
        </div>
    </header>

    <main id="main" class="pt-20">{{ $slot }}</main>

    <footer class="bg-[#0B1F33] text-white mt-24">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 grid grid-cols-1 md:grid-cols-4 gap-12">
            <div>
                <div class="font-[Manrope] font-extrabold text-lg mb-4">GROUPE SIBEA</div>
                <p class="text-sm text-white/70 leading-relaxed">
                    Un groupe multi-activités intervenant dans le BTP, l’immobilier,
                    l’énergie et l’agro-industrie.
                </p>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Secteurs</div>
                <ul class="space-y-2 text-sm text-white/70">
                    @foreach(\App\Models\Sector::active()->ordered()->get() as $sector)
                        <li>
                            <a href="{{ route('sectors.show', $sector->slug) }}">{{ $sector->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Expertises</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('expertises.index') }}">Nos expertises</a></li>
                    <li><a href="{{ route('projects.index') }}">Réalisations</a></li>
                </ul>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Contact</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('contact') }}">Nous contacter</a></li>
                    <li><a href="{{ route('legal') }}">Mentions légales</a></li>
                    <li><a href="{{ route('privacy') }}">Politique de confidentialité</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 text-xs text-white/50">
                © {{ date('Y') }} Groupe SIBEA. Tous droits réservés.
            </div>
        </div>
    </footer>

    <livewire:contact-modal />
    @livewireScripts
</body>
</html>
```

## 13.7 Exemples de Single File Components

### Modale de contact

**Fichier :** `resources/views/livewire/contact-modal.blade.php`

```php
<?php

use Livewire\Component;
use App\Models\Lead;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;

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

        $key = 'contact:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Trop de tentatives. Réessayez dans une minute.');
            return;
        }
        RateLimiter::hit($key, 60);

        $this->validate();

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
            'source' => 'site',
            'consent_at' => now(),
            'consent_ip' => request()->ip(),
        ]);

        LeadCreated::dispatch($lead);

        $this->reset(['name', 'company', 'email', 'phone', 'sector_id',
                      'request_type', 'budget', 'message', 'consent']);
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
                <flux:button variant="primary" class="!bg-[#E58A32]"
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
                    @foreach(\App\Models\Sector::active()->ordered()->get() as $sector)
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
                <flux:button type="submit" variant="primary" class="!bg-[#E58A32]">
                    Envoyer
                </flux:button>
            </div>
        </form>
    @endif
</flux:modal>
```

### Page d’accueil (SFC)

**Fichier :** `resources/views/pages/home.blade.php`

```php
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Sector;
use App\Models\Expertise;
use App\Models\Project;

new #[Layout('layouts::public')] class extends Component
{
    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->get();
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::active()->ordered()->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::published()->latest()->take(3)->get();
    }
};
?>

<div>
    <section class="grid lg:grid-cols-2 min-h-[90vh]">
        <div class="bg-[#0B1F33] text-white flex items-center px-6 lg:px-16 py-24">
            <div class="max-w-xl">
                <div class="text-[#E58A32] font-[Manrope] font-semibold tracking-widest text-sm mb-6">
                    GROUPE SIBEA
                </div>
                <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-6xl leading-tight mb-6">
                    Des expertises solides pour construire des projets durables.
                </h1>
                <p class="text-white/80 text-lg leading-relaxed mb-10">
                    Le Groupe SIBEA rassemble des compétences complémentaires
                    dans le BTP, l’immobilier, l’énergie et l’agro-industrie
                    pour accompagner des projets structurants et créateurs de valeur.
                </p>
                <div class="flex flex-wrap gap-4">
                    <flux:button href="{{ route('sectors.index') }}" variant="primary" class="!bg-[#E58A32]">
                        Découvrir nos secteurs
                    </flux:button>
                    <flux:button href="{{ route('projects.index') }}" variant="outline" class="!text-white !border-white/40">
                        Voir nos réalisations
                    </flux:button>
                </div>
            </div>
        </div>

        <div
            x-data="{
                current: 0,
                paused: false,
                isMobile: window.innerWidth < 1024,
                autoplay: !window.matchMedia('(prefers-reduced-motion: reduce)').matches
                          && window.innerWidth >= 1024,
                timer: null,
                touchStartX: 0,
                sectors: @js($this->sectors->map(fn($s) => [
                    'name' => $s->name,
                    'title' => $s->hero_title,
                    'description' => $s->hero_description,
                    'image' => $s->getFirstMediaUrl('hero'),
                    'url' => route('sectors.show', $s->slug),
                ])),
                start() {
                    if (!this.autoplay) return;
                    this.timer = setInterval(() => {
                        if (!this.paused) this.current = (this.current + 1) % this.sectors.length;
                    }, 7000);
                },
                destroy() { if (this.timer) clearInterval(this.timer); },
                next() { this.current = (this.current + 1) % this.sectors.length; },
                prev() { this.current = (this.current - 1 + this.sectors.length) % this.sectors.length; },
                goTo(i) { this.current = i; },
                onTouchStart(e) { this.touchStartX = e.touches[0].clientX; },
                onTouchEnd(e) {
                    const delta = e.changedTouches[0].clientX - this.touchStartX;
                    if (Math.abs(delta) > 50) delta < 0 ? this.next() : this.prev();
                }
            }"
            x-init="start()"
            x-on:mouseenter="paused = true"
            x-on:mouseleave="paused = false"
            x-on:focusin="paused = true"
            x-on:focusout="paused = false"
            x-on:keydown.arrow-left.window="prev()"
            x-on:keydown.arrow-right.window="next()"
            x-on:touchstart="onTouchStart($event)"
            x-on:touchend="onTouchEnd($event)"
            class="relative overflow-hidden bg-[#164E70] min-h-[60vh]"
            aria-live="polite"
        >
            <template x-for="(sector, index) in sectors" :key="index">
                <div
                    x-show="current === index"
                    x-transition.opacity.duration.700ms
                    :aria-hidden="current !== index"
                    class="absolute inset-0 flex flex-col justify-end p-10 lg:p-16 text-white bg-cover bg-center"
                    :style="`background-image: linear-gradient(180deg, rgba(11,31,51,0.2), rgba(11,31,51,0.85)), url('${sector.image}')`"
                >
                    <div class="text-[#E58A32] font-[Manrope] font-semibold tracking-widest text-xs mb-3">
                        GROUPE SIBEA — <span x-text="sector.name"></span>
                    </div>
                    <h2 class="font-[Manrope] font-bold text-3xl mb-3" x-text="sector.title"></h2>
                    <p class="text-white/80 max-w-md mb-6" x-text="sector.description"></p>
                    <flux:button :href="sector.url" variant="primary" class="!bg-[#E58A32] self-start">
                        Découvrir le secteur
                    </flux:button>
                </div>
            </template>

            <button x-on:click="prev()" aria-label="Slide précédent"
                class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white">‹</button>
            <button x-on:click="next()" aria-label="Slide suivant"
                class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white">›</button>

            <div class="absolute bottom-6 left-10 lg:left-16 flex gap-2 z-10" role="tablist">
                <template x-for="(sector, index) in sectors" :key="index">
                    <button
                        x-on:click="goTo(index)"
                        :aria-selected="current === index"
                        :aria-label="`Slide ${index + 1}`"
                        :class="current === index ? 'bg-[#E58A32] w-8' : 'bg-white/40 w-2'"
                        class="h-2 rounded-full transition-all"
                    ></button>
                </template>
            </div>
        </div>
    </section>

    {{-- Présentation, secteurs, expertises, chiffres, réalisations, valeurs, CTA — voir §8.3 à 8.9 --}}
</div>
```

### Page détail d’un secteur

**Fichier :** `resources/views/pages/sectors/show.blade.php`

```php
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Sector;

new #[Layout('layouts::public')] class extends Component
{
    public Sector $sector;

    #[Computed]
    public function expertises()
    {
        return $this->sector->expertises()->where('is_active', true)->ordered()->get();
    }

    #[Computed]
    public function services()
    {
        return $this->sector->expertises()
            ->with('services')
            ->get()
            ->flatMap->services
            ->where('is_active', true)
            ->unique('id');
    }

    #[Computed]
    public function projects()
    {
        return $this->sector->projects()->where('is_published', true)->latest()->take(6)->get();
    }
};
?>

<div>
    <section class="bg-[#0B1F33] text-white py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-[#E58A32] font-[Manrope] font-semibold tracking-widest text-sm mb-4">
                SECTEUR D’ACTIVITÉ
            </div>
            <h1 class="font-[Manrope] font-extrabold text-5xl lg:text-6xl mb-6">
                {{ $sector->name }}
            </h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                {{ $sector->short_description }}
            </p>
        </div>
    </section>
    {{-- Présentation, expertises, services, réalisations, CTA — voir §9.2 --}}
</div>
```

---

# 14. Interface utilisateur et composants Flux UI

## 14.1 Principe directeur

**Priorité à Flux UI Free. Aucun achat Flux UI Pro en V1.** Pour les composants absents, utiliser les fallbacks Tailwind documentés en §14.3. Toute décision d’achat Flux UI Pro ultérieure fera l’objet d’un avenant.

## 14.2 Composants Flux UI Free

**Structure / typographie :** `flux:heading`, `flux:subheading`, `flux:text`, `flux:separator`, `flux:spacer`, `flux:container`.

**Navigation :** `flux:brand`, `flux:link`, `flux:menu`, `flux:navlist`, `flux:breadcrumbs`, `flux:profile`.

**Actions :** `flux:button`, `flux:button.group`, `flux:dropdown`.

**Formulaires :** `flux:field`, `flux:label`, `flux:input`, `flux:textarea`, `flux:select`, `flux:checkbox`, `flux:radio`, `flux:switch`, `flux:error`.

**Retour utilisateur :** `flux:modal`, `flux:tooltip`, `flux:badge`, `flux:callout`.

## 14.3 Fallbacks Tailwind (Flux Free)

Avenant v1.3 : `flux:card`, `flux:table` et `flux:pagination` sont inclus dans le package installé et utilisés directement. Restent en fallback :

| Composant | Fallback |
|---|---|
| `flux:file-upload` | `<input type="file">` stylé |
| `flux:editor` | `<textarea>` |
| `flux:date-picker` | `<input type="date">` |
| `flux:chart` | Non prévu en V1 |

Toute décision d’achat Flux UI Pro ultérieure fera l’objet d’un avenant au CDC. En V1, le projet se construit exclusivement sur Flux UI Free.

## 14.4 Modales — règles communes

- Ouverture : `$flux.modal('nom').open()`.
- Fermeture : bouton, Échap, clic sur overlay.
- Focus auto sur le premier champ, focus trap, retour du focus.
- Taille adaptée (`sm`, `md`, `lg`).
- En-tête `flux:heading` + `flux:subheading`.
- Pied : `flux:button` (Annuler) + `flux:button variant="primary"`.
- Validation Livewire temps réel.

---

# 15. Direction artistique

## 15.1 Référence

Inspiration **Bigness Group**, adaptée :

- Alternance sections sombres / claires ;
- 4 secteurs au cœur du récit ;
- Positionnement multi-activités ;
- Palette bleu nuit / orange cuivre propre à SIBEA.

## 15.2 Principes

| Principe | Application |
|---|---|
| Contraste fort | Bleu nuit + blanc + accent orange |
| Typographie massive | Manrope extrabold H1/H2 |
| Respiration | `py-24` minimum entre sections |
| Photos plein cadre | Overlays bleu nuit pour lisibilité |
| Simplicité | Peu d’effets, navigation claire |
| Cohérence | Un seul accent (orange cuivre) |
| Alternance | Sombre / clair |
| Discrétion | Transitions 200-300ms, fade in up |

---

# 16. Charte graphique

## 16.1 Palette

| Rôle | Nom | HEX | Usage |
|---|---|---|---|
| Fond sombre principal | Bleu nuit | `#0B1F33` | Header, hero, footer, CTA |
| Fond sombre secondaire | Bleu technique | `#164E70` | Dégradés, overlays |
| Accent | Orange cuivre | `#E58A32` | CTA, liens actifs, chiffres clés |
| Fond clair | Blanc cassé | `#F7F8FA` | Sections de contenu |
| Fond clair alt | Blanc | `#FFFFFF` | Cartes |
| Texte | Anthracite | `#111827` | Corps de texte |
| Texte secondaire | Gris ardoise | `#64748B` | Descriptions |
| Bordures | Gris clair | `#E2E8F0` | Cartes, séparateurs |

## 16.2 Typographies

| Élément | Police | Graisse | Desktop | Mobile |
|---|---|---|---|---|
| H1 | Manrope | 800 | 56-72px | 36-44px |
| H2 | Manrope | 800 | 40-48px | 28-36px |
| H3 | Manrope | 700 | 24-32px | 20-24px |
| Corps | Inter | 400 | 16-18px | 15-16px |
| Labels | Manrope | 600 | 12-14px | 11-13px |
| Boutons | Manrope | 600 | 14-16px | 14px |

## 16.3 Espacements

| Contexte | Desktop | Mobile |
|---|---|---|
| Entre sections | `py-24` | `py-16` |
| Cartes | 32-48px | 24px |
| Conteneur latéral | `px-8` | `px-6` |
| Largeur max | `max-w-7xl` | 100% |

## 16.4 Composants visuels

- **Boutons** : orange primaire, contour secondaire, ghost.
- **Cartes** : fond blanc, bordure fine, hover orange.
- **Badges** : majuscules, tracking large.
- **Icônes** : outline fin, 24px.

## 16.5 Imagerie

Photos architecturales et institutionnelles, format paysage, ≥1600px, overlays bleu nuit, alt text obligatoire.

---

# 17. Maquettes

Les maquettes ASCII des pages clés sont fournies en **Annexe A** du présent document. Elles couvrent : homepage, secteurs, détail secteur, expertises, détail expertise, réalisations, contact, modale de contact. Elles servent de référence visuelle, non contractuelle sur les pixels.

---

# 18. Correspondance Design ↔ Composants Flux UI

| Élément | Flux UI | Fallback Tailwind |
|---|---|---|
| Boutons | `flux:button` | — |
| Cartes (non liées) | `flux:card` | — |
| Cartes liées | `<a>` stylisé | `flux:card` sans support `href` |
| Titres | `flux:heading` | — |
| Modales | `flux:modal` | — |
| Formulaires | `flux:field`, `flux:input`… | — |
| Tableaux admin | `flux:table` | — |
| Pagination | `flux:pagination` | — |
| Upload médias | `flux:file-upload` (Pro) | `<input type="file">` |
| Éditeur riche | `flux:editor` (Pro) | `<textarea>` |

---

# 19. Règles de mise en œuvre

- Alternance stricte sombre / clair entre sections.
- Orange uniquement CTA, accents, chiffres clés.
- Overlay bleu nuit sur photos avec texte superposé.
- Animations discrètes 200-300ms.
- Contraste AA, focus visible, navigation clavier.
- Cohérence bordures / rayons / espacements.

---

# 20. SEO

## 20.1 Schéma `seo_metadata`

```text
id
model_type
model_id
meta_title
meta_description
canonical_url
noindex (boolean)
structured_data (json)
created_at
updated_at
```

OG image : media Spatie sur le modèle.

## 20.2 Packages retenus

```text
spatie/laravel-sitemap
spatie/schema-org
ralphjsmit/laravel-seo
```

## 20.3 Stratégie

- Canonical auto depuis la route ;
- Sitemap régénéré à chaque publication ;
- OG image par défaut dans `settings` (V2) ou config ;
- Structured data : Organization, LocalBusiness, BreadcrumbList.

## 20.4 URLs SEO

```text
/secteurs/{slug}       (canonique)
/btp → 301 /secteurs/btp
/expertises/{slug}
/realisations/{slug}
```

## 20.5 Sitemap

Contient : pages publiques, secteurs, expertises, réalisations publiées.

---

# 21. Médias

**Spatie Media Library** exclusivement. Aucune colonne string pour les images.

**Collections :**

| Modèle | Collections |
|---|---|
| Sector | `hero`, `icon`, `og_image` |
| Expertise | `cover`, `icon`, `og_image` |
| Project | `cover`, `gallery`, `og_image` |
| User | `avatar` |
| Settings (V2) | `logo`, `favicon` |

**Conversions :** `thumb` (600×400), `medium` (1200×800), `og` (1200×630), `hero` (1600×1200).

**Upload :** `flux:file-upload` (Pro) ou `<input type="file">` stylé. Contrôle du type MIME et de la taille (max 5 Mo).

---

# 22. CRM et gestion des prospects

## 22.1 Table `leads`

```text
id
name
company
email
phone
sector_id
request_type
budget
message
status
source
assigned_to
notes
consent_at
consent_ip
created_at
updated_at
```

## 22.2 Statuts

Enum PHP `App\Enums\LeadStatus` :

```text
Nouveau
Contacté
En cours
Qualifié
Converti
Non qualifié
Archivé
```

Valeur sérialisée en base : `nouveau`. Cast dans le modèle `Lead`.

## 22.3 Fonctionnalités

- Liste via `flux:table` ;
- Recherche, filtrage par statut et secteur ;
- Affectation à un utilisateur ;
- Notes internes, historique ;
- Changement de statut ;
- Notification email (`LeadCreated` + `SendLeadNotification`, via queue) ;
- Création et édition via modale Flux UI.

## 22.4 RGPD

- `consent_at` et `consent_ip` enregistrés ;
- Conservation 3 ans après dernier contact ;
- Commande `leads:purge` pour purge automatique ;
- Endpoint admin de suppression à la demande.

---

# 23. Utilisateurs, rôles et permissions

**Spatie Laravel Permission**.

## 23.1 Rôles

- Super administrateur : accès complet.
- Administrateur : gestion globale, sauf rôles.
- Éditeur : gestion des contenus.
- Commercial : accès prospects.

## 23.2 Matrice permissions — V1

| Permission | Super admin | Admin | Éditeur | Commercial |
|---|---|---|---|---|
| `view_dashboard` | ✅ | ✅ | ✅ | ✅ |
| `manage_sectors` | ✅ | ✅ | ✅ | ❌ |
| `manage_expertises` | ✅ | ✅ | ✅ | ❌ |
| `manage_services` | ✅ | ✅ | ✅ | ❌ |
| `manage_projects` | ✅ | ✅ | ✅ | ❌ |
| `manage_leads` | ✅ | ✅ | ❌ | ✅ |
| `manage_users` | ✅ | ✅ | ❌ | ❌ |
| `manage_roles` | ✅ | ❌ | ❌ | ❌ |
| `view_sectors` | ✅ | ✅ | ✅ | ✅ |
| `view_projects` | ✅ | ✅ | ✅ | ✅ |

Note : `view_sectors` et `view_projects` sont des permissions de lecture attribuées au Commercial pour lui permettre de contextualiser un lead. Elles n’ouvrent aucun écran d’édition.

## 23.3 Matrice permissions — V2 (différées)

| Permission | Super admin | Admin | Éditeur | Commercial |
|---|---|---|---|---|
| `manage_pages` | ✅ | ✅ | ✅ | ❌ |
| `manage_testimonials` | ✅ | ✅ | ❌ | ❌ |
| `manage_statistics` | ✅ | ✅ | ❌ | ❌ |
| `manage_media` | ✅ | ✅ | ✅ | ❌ |
| `manage_menus` | ✅ | ✅ | ❌ | ❌ |
| `manage_settings` | ✅ | ✅ | ❌ | ❌ |
| `manage_seo` | ✅ | ✅ | ❌ | ❌ |

## 23.4 Policies

Une Policy par modèle : `SectorPolicy`, `ExpertisePolicy`, `ServicePolicy`, `ProjectPolicy`, `LeadPolicy`, `UserPolicy`, `RolePolicy`.

`SectorPolicy::delete` interdit la suppression si `is_locked = true`.

---

# 24. Sécurité

- Authentification sécurisée ;
- Autorisations par rôle et Policies ;
- Protection CSRF, XSS ;
- Validation serveur systématique ;
- Uploads : contrôle type MIME + taille ;
- Rate limiting : `throttle:contact` (5 req/min), `throttle:login` (5 req/min) ;
- Anti-spam : honeypot + rate limit applicatif dans `submit()` ;
- Journalisation des actions sensibles ;
- Variables sensibles dans `.env` ;
- Sauvegardes PostgreSQL chiffrées (pg_dump + gpg), stockage S3, rétention 30 jours ;
- Politique de mot de passe robuste (12 caractères min, complexité).

---

# 25. Performance

- Cache des contenus publics ;
- Optimisation des images (WebP/AVIF via Spatie conversions) ;
- Lazy loading ;
- Pagination ;
- Index PostgreSQL sur slug, is_active, is_published, foreign keys ;
- Requêtes Eloquent optimisées (eager loading) ;
- Éviter N+1 (tests avec `preventLazyLoading()`) ;
- Files d’attente pour emails ;
- Minification des ressources ;
- Compression HTTP ;
- Cache des paramètres du site.

---

# 26. Responsive design

- Desktop, laptop, tablette, smartphone.
- Tester : header, menu mobile, hero, carrousel, grilles, modales, formulaires, footer, tableaux admin.

---

# 27. Accessibilité

- Contraste WCAG AA ;
- Navigation clavier ;
- Labels sur tous les champs ;
- Alt text obligatoire ;
- Hiérarchie H1/H2/H3 stricte ;
- Focus visible ;
- Erreurs compréhensibles ;
- `prefers-reduced-motion` respecté ;
- Boutons ≥44px sur mobile ;
- Modales accessibles (focus trap, Échap, retour focus) ;
- Carrousel : contrôles clavier, pause, `aria-live="polite"`, swipe tactile.

---

# 28. Tests

## 28.1 Automatisés (Pest)

- Création / modification / suppression d’un secteur non verrouillé ;
- Refus de suppression d’un secteur verrouillé ;
- Création / modification / suppression d’une expertise ;
- Association secteur / expertise ;
- Création d’un service ;
- Association expertise / service ;
- Création / modification d’une réalisation ;
- Soumission du formulaire de contact avec tous les champs ;
- Refus sans consentement ;
- Rate limiting `contact` après 5 tentatives ;
- Enregistrement `consent_at` et `consent_ip` ;
- Création d’un prospect ;
- Envoi de notification email ;
- Permissions par rôle et Policy ;
- Accès aux pages publiques ;
- Accès refusé à l’admin sans auth ;
- Redirect 301 `/btp` → `/secteurs/btp` ;
- Sitemap contient les URLs attendues.

## 28.2 Navigateur (Playwright)

- Navigation homepage ;
- Carrousel : autoplay, pause au survol, pause au focus, prev/next, clavier, indicateurs, swipe ;
- `prefers-reduced-motion` désactive l’autoplay ;
- Autoplay désactivé sur mobile ;
- Navigation mobile, menu hamburger ;
- Navigation vers un secteur ;
- Filtrage des réalisations ;
- Ouverture et soumission de la modale de contact ;
- Affichage du message de succès ;
- Connexion à l’administration ;
- Création d’un contenu via modale ;
- Upload média ;
- Publication / dépublication.

---

# 29. Livrables

- Code source Laravel ;
- Migrations ;
- Modèles Eloquent, Policies, Enums ;
- Factories et Seeders (4 secteurs verrouillés et actifs, chiffres clés `is_active=false`) ;
- Pages Livewire SFC ;
- Composant `contact-modal` ;
- Layout public ;
- Layout admin (starter kit) ;
- Partial `seo.blade.php` ;
- Events, Listeners, Notifications ;
- Configuration Tailwind (tokens) ;
- Configuration Spatie Permission, Media Library, Sitemap, Schema.org, RalphJSmit SEO ;
- Commandes artisan (`leads:purge`) ;
- Tests Pest et Playwright ;
- Documentation installation, administration, déploiement ;
- `.env.example` ;
- Script de sauvegarde PostgreSQL chiffrée ;
- Guide de maintenance.

---

# 30. Critères de validation

- Les 4 secteurs officiels présents, verrouillés et actifs ;
- Chiffres clés `is_active=false` jusqu’à validation ;
- Expertises séparées des secteurs ;
- Services rattachés aux expertises, sans page publique dédiée ;
- Relations N-N implémentées avec pivots définis ;
- Aucune colonne string d’image, 100 % Spatie ;
- URLs canoniques `/secteurs/{slug}` + redirects 301 ;
- Hero hybride avec carrousel conforme (autoplay 7000ms, pause, clavier, swipe, reduced-motion, aria-live) ;
- Flux UI Free exclusivement en V1, fallbacks Tailwind documentés ;
- Tous les formulaires en modale ;
- Modale de contact avec rate limiting applicatif, message de succès, enum `LeadStatus` ;
- Pages en SFC Livewire 4 sans `render()` retournant une vue ;
- Layout admin starter kit utilisé tel quel ;
- Un seul layout supplémentaire (`public`) ;
- Aucun composant Blade réutilisable, sauf partial SEO documenté ;
- Fichiers regroupés par domaine, sans préfixe `⚡` sur les noms réels ;
- `app/` contient Events, Listeners, Notifications, Enums ;
- Permissions V1/V2 séparées, Commercial avec `view_sectors` et `view_projects` ;
- SEO : packages tranchés, `seo_metadata`, sitemap, canonical, OG ;
- RGPD : consentement, purge, backups chiffrés ;
- Admin V1 = 8 modules, chiffres clés en lecture seule ;
- Footer et select secteur filtrés sur `active()` ;
- Responsive, accessible, testé ;
- Maquettes jointes en Annexe A ;
- Documentation fournie.

---

# 31. Synthèse finale

```text
SECTEURS (verrouillés)
BTP
Immobilier
Énergie
Agro-industrie
```

```text
EXPERTISES
Études & ingénierie
Construction & réalisation
Développement immobilier
Solutions énergétiques
Solutions agro-industrielles
```

```text
SERVICES
Prestations concrètes, rattachées aux expertises (N-N),
sans page publique dédiée.
```

Architecture publique :

```text
Accueil
Secteurs d’activité (/secteurs/{slug})
Expertises
Réalisations
Contact
```

Modèle de données :

```text
Relations N-N :
  expertise_sector
  expertise_service
  project_sector
  project_expertise
  project_service

Médias : 100 % Spatie Media Library
```

Direction artistique : inspiration Bigness adaptée, palette bleu nuit / orange cuivre / blanc cassé, Manrope + Inter.

Principes techniques : Flux UI Free exclusivement, formulaires en modale, SFC Livewire 4, layout admin starter kit, un seul layout `public`, pas de composant Blade réutilisable sauf partial SEO, fichiers regroupés par domaine, CMS phasé V1/V2, sécurité/RGPD/SEO intégrés.

---

# Annexe A — Maquettes ASCII

Les maquettes ci-dessous sont la référence visuelle des pages clés. Elles ne sont pas contractuelles sur les pixels mais fixent la structure et la hiérarchie.

## A.1 Légende

```text
╔══════════════════╗    Bloc principal
│                  │
╚══════════════════╝

[  Bouton  ]            Bouton / CTA
═══ Section ═══         Séparateur de section
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓         Fond bleu nuit
░░░░░░░░░░░░░░░         Fond blanc cassé
─────────────────       Bordure fine
```

## A.2 Homepage

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER (sticky, transparent puis bleu nuit au scroll)          ▓
▓  ┌──────────────────────────────────────────────────────────────┐ ▓
▓  │ GROUPE SIBEA      Accueil  Secteurs  Expertises  Réalisations│ ▓
▓  │                                          Contact  [Nous cont.]│ ▓
▓  └──────────────────────────────────────────────────────────────┘ ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  ┌───────────────────────────┬──────────────────────────────────┐▓
▓  │ GROUPE SIBEA              │     [ IMAGE SECTEUR ]            │▓
▓  │ Des expertises solides    │     GROUPE SIBEA — BTP           │▓
▓  │ pour construire des       │     Construire des infrastr.…    │▓
▓  │ projets durables.         │     [Découvrir le secteur]       │▓
▓  │ [Découvrir nos secteurs]  │     ‹ › ● ○ ○ ○                  │▓
▓  │ [Voir nos réalisations]   │                                  │▓
▓  └───────────────────────────┴──────────────────────────────────┘▓
▓  HERO HYBRIDE — colonne gauche fixe, colonne droite carrousel    ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Un groupe, plusieurs expertises, une même ambition.             ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

────────────────────────────────────────────────────────────────────
│  Nos secteurs d'activité                                         │
│  ┌──────────┬──────────┬──────────┬──────────┐                   │
│  │ 01 BTP   │ 02 Immo. │ 03 Éner. │ 04 Agro. │                   │
│  └──────────┴──────────┴──────────┴──────────┘                   │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Nos expertises                                                  ░
░  ┌──────────┬──────────┬──────────┐                              ░
░  │ Études   │ Constr.  │ Dév.     │                              ░
░  └──────────┴──────────┴──────────┘                              ░
░  ┌──────────┬──────────┐                                         ░
░  │ Sol. én. │ Sol. ag. │                                         ░
░  └──────────┴──────────┘                                         ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  CHIFFRES CLÉS — orange cuivre sur bleu nuit                    ▓
▓  ┌──────────┬──────────┬──────────┬──────────┐                   ▓
▓  │ 15+      │ 200+     │ 4        │ 50+      │                   ▓
▓  └──────────┴──────────┴──────────┴──────────┘                   ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  Nos réalisations — filtres [Tous][BTP][Immo.][Éner.][Agro.]      │
│  ┌──────────┬──────────┬──────────┐                              │
│  │ IMG      │ IMG      │ IMG      │                              │
│  └──────────┴──────────┴──────────┘                              │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Nos valeurs : Qualité · Fiabilité · Innovation · Durabilité     ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓           Vous avez un projet à structurer ?                     ▓
▓                    [Nous contacter]                              ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  FOOTER — 4 colonnes : Groupe · Secteurs · Expertises · Contact  ▓
▓  © 2025 Groupe SIBEA. Tous droits réservés.                      ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
```

## A.3 Page Secteurs d’activité

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER                                                          ▓
▓  HERO : « Nos secteurs d'activité »                              ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  ┌─────────────────────────┬─────────────────────────┐           │
│  │ [ IMAGE ]               │ [ IMAGE ]               │           │
│  │ 01 — BTP                │ 02 — Immobilier         │           │
│  │ [Découvrir le secteur →]│ [Découvrir le secteur →]│           │
│  └─────────────────────────┴─────────────────────────┘           │
│  ┌─────────────────────────┬─────────────────────────┐           │
│  │ [ IMAGE ]               │ [ IMAGE ]               │           │
│  │ 03 — Énergie            │ 04 — Agro-industrie    │           │
│  │ [Découvrir le secteur →]│ [Découvrir le secteur →]│           │
│  └─────────────────────────┴─────────────────────────┘           │
────────────────────────────────────────────────────────────────────

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓           Vous avez un projet à structurer ?                     ▓
▓                    [Nous contacter]                              ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
```

## A.4 Page détail d’un secteur

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER                                                          ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  SECTEUR D'ACTIVITÉ                                              ▓
▓  BTP                                                             ▓
▓  Construction, bâtiments, travaux publics, infrastructures…      ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  Présentation du secteur                                         │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Expertises associées — 3 cartes                                 ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

────────────────────────────────────────────────────────────────────
│  Services associés — liste                                        │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Réalisations dans ce secteur — 3 cartes                         ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓       Un projet dans le secteur BTP ?                            ▓
▓                    [Nous contacter]                              ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
```

## A.5 Page Nos expertises

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER — HERO « Nos expertises »                                ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  ┌──────────┬──────────┬──────────┐                              │
│  │ Études   │ Constr.  │ Dév.     │                              │
│  │ & ing.   │ & réal.  │ immo.    │                              │
│  └──────────┴──────────┴──────────┘                              │
│  ┌──────────┬──────────┐                                         │
│  │ Sol. én. │ Sol. ag. │                                         │
│  └──────────┴──────────┘                                         │
────────────────────────────────────────────────────────────────────
```

## A.6 Page détail d’une expertise

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER — HERO « Construction & réalisation »                    ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  Présentation                                                     │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Secteurs concernés : [BTP]  [Immobilier]                        ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

────────────────────────────────────────────────────────────────────
│  Services associés : liste                                        │
────────────────────────────────────────────────────────────────────

░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
░  Notre méthode : 1. Analyse · 2. Études · 3. Planification       ░
░                  4. Réalisation · 5. Contrôle et livraison       ░
░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

────────────────────────────────────────────────────────────────────
│  Réalisations liées — 3 cartes                                    │
────────────────────────────────────────────────────────────────────
```

## A.7 Page Réalisations

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER — HERO « Nos réalisations »                              ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  [Tous][BTP][Immobilier][Énergie][Agro-industrie]  [Recherche…]  │
│  ┌──────────┬──────────┬──────────┐                              │
│  │ IMG      │ IMG      │ IMG      │                              │
│  │ Projet 1 │ Projet 2 │ Projet 3 │                              │
│  └──────────┴──────────┴──────────┘                              │
│  ┌──────────┬──────────┬──────────┐                              │
│  │ IMG      │ IMG      │ IMG      │                              │
│  └──────────┴──────────┴──────────┘                              │
│  [1] [2] [3] [Suivant →]                                          │
────────────────────────────────────────────────────────────────────
```

## A.8 Page Contact

```text
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
▓  HEADER — HERO « Contactez-nous »                                ▓
▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

────────────────────────────────────────────────────────────────────
│  ┌─────────────────────────┬──────────────────────────┐          │
│  │ Adresse · Téléphone     │ [ CARTE DE LOCALISATION ]│          │
│  │ Email · Horaires        │                          │          │
│  │ Réseaux sociaux         │                          │          │
│  │ [Nous contacter]        │                          │          │
│  └─────────────────────────┴──────────────────────────┘          │
────────────────────────────────────────────────────────────────────
```

## A.9 Modale de contact

```text
        ┌─────────────────────────────────────────┐
        │  Nous contacter                         │
        │  Parlons de votre projet.               │
        │                                         │
        │  Nom complet *   [___________________]  │
        │  Société         [___________________]  │
        │  Email *         [___________________]  │
        │  Téléphone       [___________________]  │
        │  Secteur *       [Sélectionnez    ▾]    │
        │  Type demande *  [Sélectionnez    ▾]    │
        │  Budget          [___________________]  │
        │  Message *       [___________________]  │
        │                  [___________________]  │
        │  ☐ J’accepte la politique de confid. *  │
        │                                         │
        │              [Annuler]  [Envoyer]       │
        └─────────────────────────────────────────┘
        (Overlay bleu nuit semi-transparent)
```

---

**Fin du CDC v1.2.**