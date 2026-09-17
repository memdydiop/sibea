# Plan d'implémentation — Site vitrine Groupe SIBEA (CDC v1.2)

**Source :** `cdc.md` v1.2 — Laravel 13, Livewire 4 (SFC), Flux UI Free, Tailwind, PostgreSQL.
**État de départ :** starter-kit Livewire 4.1 / Laravel 13 / Flux 2.13 / PHP 8.4 / PG `sibea_ci`. Métier inexistant : 1 modèle `User`, 0 `Enums/Events/Policies`, 0 vue `livewire/`, routes minimales.

---

## Phase 0 — Socle (prérequis tout le reste)

- `composer require spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-sitemap spatie/schema-org ralphjsmit/laravel-seo`
- `php artisan vendor:publish` + `migrate` Spatie (permission, media). Vérifier PG : `php artisan db:show`, `migrate:fresh --seed` OK.
- `php artisan make:enum LeadStatus` (cases ASCII : `Nouveau, Contacte, EnCours…`, backed `string`, `label()`), `RequestType`, `LeadSource`.
- Décisions à figer (nits v1.2) :
  - `projects.is_published` seul gate public (`is_active` = visible admin),
  - upload = `<input type="file">` stylé uniquement (pas de `flux:file-upload` Pro),
  - abandon du middleware `throttle:contact` au profit du `RateLimiter` applicatif dans `submit()`.
- Done : `composer check-platform-reqs` vert, migrations passent.

## Phase 1 — Données + sécurité

Ordre : migrations → modèles + casts → policies → seeders.

- Migrations : `sectors` (`cdc.md:499`), `expertises`, `services`, `projects`, `leads` (`cdc.md:579`), `seo_metadata` (`cdc.md:603`), 5 pivots (`cdc.md:485` : `expertise_sector`, `expertise_service`, `project_sector`, `project_expertise`, `project_service`).
  - V2 seulement (ne pas créer en V1 sauf `statistics` en lecture seule) : `pages`, `testimonials`, `statistics`, `menus`, `settings`.
- Modèles : `Sector/Expertise/Service/Project/Lead` + relations N-N (`cdc.md:618`), scopes `active()`, `ordered()`, `published()`, `HasMedia` + `registerMediaCollections()` (`hero/icon/og_image`, `cover/gallery`), casts `LeadStatus`, index PG sur `slug/is_active/is_published/FK`.
- `SectorPolicy::delete` si `is_locked`, 7 policies (`cdc.md:1597`), matrice V1 (`cdc.md:1568`) + `view_sectors/view_projects` commercial, seed permissions.
- Seeders : 4 secteurs `is_locked=true`, agro `is_active=false`, chiffres `is_active=false`, 5 expertises, services, projets démo, rôles.
- Done : tests Pest §28.1 secteurs (CRUD non-verrouillé, refus delete verrouillé), associations, policies.

## Phase 2 — Site public

- `layouts/public.blade.php` (`cdc.md:788`) + `partials/seo.blade.php` (seule exception réutilisable), footer `foreach active()` (`cdc.md:844`).
- `livewire/contact-modal.blade.php` en premier (SFC sans `render()`) : tous champs (`cdc.md:393`), honeypot silencieux, `RateLimiter::tooManyAttempts/hit`, `LeadCreated` + `$success`, reset incluant `honeypot`.
- `Events/LeadCreated`, `Listeners/SendLeadNotification` (queue, `QUEUE_CONNECTION=database` déjà OK), `Notifications/NewLeadNotification`, `MAIL_MAILER=log` en dev.
- Pages SFC `pages::` : `home` (hero + carrousel 7000ms, pause hover/focus, touchstart/end, `isMobile`, `aria-live`, `destroy()` — `cdc.md:1131`), `sectors/index+show`, `expertises/index+show`, `projects/index+show`, `contact` (+ fallback no-JS), `legal`, `privacy`.
- Routes (`cdc.md:742`) + 4 redirects 301, canonical auto, sitemap + `Organization/BreadcrumbList`.
- Done : Playwright homepage, carrousel (autoplay/pause/clavier/swipe/reduced-motion/mobile-off), modale + succès, 301, sitemap.

## Phase 3 — Admin V1 (8 modules)

- `pages::admin.*` sous `auth` + layout starter-kit tel quel : `dashboard`, `sectors/expertises/services/projects/leads/users/roles/index`.
- Chaque index : tableau Tailwind (`flux:table` indisponible en Free), recherche/filtres/pagination, CRUD 100% modale Flux (`heading/subheading/field/error`, Annuler/Enregistrer — `cdc.md:456`), upload `<input type="file">` → Media Library (max 5 Mo, MIME), gestion pivots N-N en modale.
- `leads` : filtres statut/secteur, assignation, notes, changement statut (Enum), purge. `statistics` : lecture seule V1 (commande `statistics:toggle` à créer).
- Done : Pest permissions par rôle, Playwright création via modale, upload, publish/unpublish.

## Phase 4 — Transverse

- RGPD : `consent_at/ip`, rétention 3 ans, `leads:purge` + schedule, suppression à la demande.
- Backups : `pg_dump + gpg → S3`, rétention 30 j, script + test restore.
- Perf : eager loading, `preventLazyLoading()` en test, cache contenus publics, WebP/AVIF, lazy loading.
- A11y : contraste AA, focus-trap modales, labels, alt obligatoire, boutons ≥44px.

## Phase 5 — Validation (§30 CDC)

Checklist bloquante : 4 secteurs verrouillés, agro off, N-N + pivots, 100% Spatie, canoniques + 301, carrousel conforme, Free-only, modales partout, SFC sans `render()`, 1 layout `public`, Events/Listeners/Notifications/Enums présents, SEO + sitemap + OG, RGPD + backups, Annexe A respectée, docs fournies.

## Enchaînement proposé

1. Phase 0 + 1 (DB + policies + seeds).
2. Phase 2 (public + contact-modal).
3. Phase 3 (admin).
4. Phases 4-5 (durcissement + validation).
