# Guide complet du projet — SIBEA-CI

**Site vitrine institutionnel du Groupe SIBEA + CMS + mini-CRM prospects.**
**Stack :** Laravel 13 / PHP 8.4 / Livewire 4 (SFC `pages::`) / Flux UI Free / Tailwind 4 / PostgreSQL (SQLite en tests) / Pest / Pint / PHPStan.
**Références :** `cdc.md` (intention v1.3), `cdc-realise.md` (as-built v2.8), `GUIDE-UTILISATEUR.md` (usage admin), `plan-implementation.md` (phasage).

---

## 1. Vision et périmètre

Le Groupe SIBEA (BTP, Immobilier, Énergie, Agro-industrie) dispose d’un site vitrine administrable qui fait aussi office d’outil commercial : chaque visiteur (contact, devis, WhatsApp, page secteur/expertise) devient un prospect traçé, qualifié (B2B/Particulier), assigné, suivi en pipeline jusqu’au Gagné/Perdu, avec mesure de la promesse « réponse sous 24h ouvrées ».

Périmètre : site public (accueil, secteurs, expertises en modale, réalisations, contact, pages éditoriales) + admin `/admin` (dashboard, secteurs, expertises, services, réalisations, témoignages, statistiques, pages, paramètres, prospects, utilisateurs) + auth Fortify + SEO/sitemap + médias Spatie + queue `database`.

---

## 2. Démarrage développeur

```bash
composer setup        # install + key + migrate + build
composer dev          # php artisan dev
npm run build / npm run dev
php artisan migrate
php artisan test --compact
vendor/bin/pint --dirty --format agent   # obligatoire après modif PHP
```

**Comptes :** invitation par email (lien signé 7 j, usage unique, `/invitation/{user}`), changement de mot de passe exigé au premier login, 12 caractères min, 5 tentatives/min, 2FA TOTP + passkeys dans `/settings/*`.
**Commandes utiles :** `leads:purge` (RGPD > 3 ans), `statistics:toggle`, `app:create-admin`, `app:mail-test`, `php artisan queue:work --once`.

---

## 3. Architecture générale

```
app/
  Enums/       LeadStatus, LeadSource, ProspectType, RequestType, ProjectStatus
  Models/      Lead (+LeadActivity), Sector, Expertise, Service, Project,
               Page, Testimonial, Statistic, Setting, User, Activity
  Support/     SlaClock, LeadAssigner, PageSeo, SitemapCache, UniqueSlug, helpers.php
  Events/      LeadCreated
  Listeners/   SendLeadNotification (queue, verrou notified_at)
  Notifications/ NewLeadNotification, AssignedLeadNotification,
                 LeadAcknowledgment, UserInvitation
  Policies/    une par modèle (SectorPolicy bloque delete si is_locked)
  Console/Commands/ PurgeLeads, MailTestCommand, CreateAdmin, StatisticsToggle
  Http/Middleware/ EnsurePasswordWasChanged
resources/views/
  pages/       SFC Livewire : home, contact, page, groupe, invitation,
               sectors/index+show, expertises/index, projects/index+show,
               admin/* (dashboard, sectors, expertises, services, projects,
               testimonials, statistics, pages, settings, leads, users, roles/show),
               auth/*, settings/*
  layouts/     public.blade.php / app.blade.php (starter-kit) / auth
  partials/seo.blade.php   seule exception réutilisable
  components/admin/ card, toolbar
routes/web.php  redirects 301 → public (Route::livewire) → robots/sitemap →
                invitation (signed) → admin (auth, password.changed)
config/         leads.php (notification_email, auto_assign, assign_mode), site.php (fallbacks)
```

**Conventions :** SFC sans `render()`, CRUD 100 % modale Flux (`heading/subheading/field/error`, Annuler/Enregistrer), Flux Free only (`flux:table/card` OK ; upload = `<input type=file>` ≤ 5 Mo), médias 100 % Spatie (jamais de colonne image), `setting()`/`setting_array()`/`setting_media_url()` pour les réglages, markdown assaini côté affichage.

---

## 4. Site public (sans auth)

| URL | Page SFC | Contenu |
|---|---|---|
| `/` | `home` | Hero statique, Groupe + mot du Président, chiffres, secteurs, expertises, réalisations, méthode, témoignages, CTA |
| `/secteurs`, `/secteurs/{slug}` | `sectors/*` | Liste actifs + page sectorielle (intro, cartes, chiffres, réalisations, CTA) |
| `/expertises` | `expertises/index` | Cartes + services, **détails en modale** (pas de page show) |
| `/realisations`, `/realisations/{slug}` | `projects/*` | Grille filtrable (9/page), fiche (galerie, technique, contexte/solution/impact, témoignage, OSM, documents, prev/next) |
| `/contact` | `contact` | Coordonnées, WhatsApp (`wa.me`, lun–sam), **formulaire inline** + fallback no-JS |
| `/mentions-legales`, `/politique-de-confidentialite`, `/pages/{slug}`, `/pages/le-groupe` | `page`, `groupe` | Pages éditoriales (privacy : loi 2013-450, ARTCI) |
| `/robots.txt`, `/sitemap.xml` | closure | Sitemap caché 1h, `Disallow: /admin` |
| 301 | — | `/btp /immobilier /energie /agroalimentaire /secteurs/agroalimentaire /agro-industrie → /secteurs/…` |

**Formulaire contact :** nom*, société, type* (Particulier/Entreprise), email*, téléphone, résidence*, territoire*, secteur* (actif vérifié), demande*, budget, échéance (`after:today`), message*. Consentement implicite (`consent_at/ip`). Protections : honeypot silencieux, rate-limit 5/min/IP **après** validation. Crée `Lead(nouveau/site)` + `origin_page` (Referer) + `expertise` inférée (`/expertises/{slug}`) + UTM (query → referer-query → session) + assignation auto + activité + `LeadCreated` + accusé prospect.

---

## 5. Administration (`/admin`, middleware `auth, password.changed`)

| URL | Module | Points clés |
|---|---|---|
| `/` | Dashboard | 4 compteurs, « À traiter », **Pilotage 24h ouvrées** (`salesPilot()` : reçus, contactés, `<24h+%`, moyenne `3h42` en minutes ouvrées, conversion, SLA breach, relances en retard, pipeline), derniers prospects/projets, activité |
| `/secteurs` | Secteurs | 4 seedés `is_locked` (delete bloquée par policy), `is_active`, `sort_order`, hero + page + médias |
| `/expertises`, `/services` | Catalogue | N-N `expertise_sector`, `expertise_service` gérées en modale |
| `/realisations` | Projets | Gate public = `is_published` seul, galerie Spatie, documents PDF ≤ 10 Mo, pivots secteur/expertise/service |
| `/temoignages`, `/statistiques` | Preuves | `is_active`, tri ; stats en lecture seule (toggle par commande) |
| `/pages`, `/parametres` | CMS | Pages markdown + 64 réglages (`setting()`, fallback `config/site.php`), 9 visuels (logo, heroes, photo Président) |
| `/prospects` | **CRM** | §6 |
| `/utilisateurs`, `/roles/{role}` | Comptes | Invitation, suspension, rôles/permissions (§8) |

Listes : recherche (nom/email/société/réf), filtres statut/secteur/type, pagination 15, suppression confirmée.

---

## 6. Mini-CRM prospects

### 6.1 Pipeline — `LeadStatus`
`Nouveau → Qualification → RDV → Étude → Proposition → Négociation → Gagné / Perdu (+ Archivé)`. Helpers `isWon/isLost/isOpen`. Anciens statuts mappés (`contacte/en_cours/qualifie → qualification`, `converti → gagne`, `non_qualifie → perdu`).

### 6.2 Fiche `Lead`
`reference` unique `SIB-00001` (auto `created + updateQuietly`), `prospect_type`, `sector_id`/`expertise_id`, `request_type`, `budget` + `estimated_amount` (FCFA), `deadline`, `source (+whatsapp)`, `origin_page`, `utm_*`, `assigned_to`, `notes`, `next_action(_at)`, `consent_at/ip`, `notified_at`, `first_contacted_at`. Relations `sector/expertise/assignedTo/activities`. Recherche admin : nom/email/société/réf. Filtres : statut/secteur/type. Doublons email badgés.

### 6.3 SLA 24h ouvrées — `SlaClock`
Lun–sam (dimanche exclu, fériés non gérés) : `addWorkingHours` (samedi 10h +24h → lundi 10h), `workingMinutesBetween` (base `responseTimeInMinutes()`), `slaDeadline()`, `respondedWithinHours()`, `isSlaBreached()`. Signatures en `CarbonInterface` (casts parfois `CarbonImmutable`). Journées bornées par `addDay()->startOfDay()` (jamais `endOfDay()`, dérive 1 min).

### 6.4 Assignation — `LeadAssigner`
`roundRobin()` par défaut (rotation A→B→C→A depuis le dernier assigné, sans état), `leastLoaded()` (moins d’ouverts), `next()` selon `leads.assign_mode`. `commercials()` = `manage_leads` ordonnés, `collect()` vide si permission absente (tests sans seed). `LEADS_AUTO_ASSIGN=false` = tout en boîte partagée.

### 6.5 Notifications (queue `database`, worker requis)
`LeadCreated → SendLeadNotification` (claim atomique `notified_at`, retry libère le verrou) → assigné (`NewLeadNotification` : réf, type, secteur, expertise, origine, campagne, échéance, assigné, replyTo prospect, flag doublons) sinon boîte `LEADS_NOTIFICATION_EMAIL`. `AssignedLeadNotification` au changement d’assigné, `LeadAcknowledgment` au prospect (formulaire public seul).

---

## 7. Auth, rôles, RGPD

Fortify (login, reset 60 min, 2FA, passkeys, verify email), `EnsurePasswordWasChanged`, suspension (`suspended_at` bloque login/reset/invitation).
Matrice (`RolesPermissionsSeeder`, 14 permissions) : Super admin (tout) / Admin (tout sauf rôles) / Éditeur (contenus, ni prospects ni users) / Commercial (`view_dashboard, manage_leads, view_sectors, view_projects`). Policies : une par modèle, `authorize()` dans chaque action Livewire, `Permission::firstOrCreate` en tests.
RGPD : consentement horodaté + IP, `leads:purge` (> 3 ans) + bouton admin, suppression à la demande.

---

## 8. Schéma SQL (PostgreSQL migré)

Conventions : `slug` + `is_active`/`is_published` + `sort_order`, FK `nullOnDelete`, index `status/email/reference/first_contacted_at/next_action_at/deadline`.

```sql
CREATE TABLE leads (
  id bigserial PRIMARY KEY,
  reference varchar(20) UNIQUE,       -- SIB-00001
  name varchar(255) NOT NULL,
  company varchar(255),
  prospect_type varchar(20),          -- particulier|entreprise (index)
  email varchar(255) NOT NULL,        -- index
  phone varchar(255),
  residence_country varchar(255),
  target_territory varchar(255),
  sector_id bigint REFERENCES sectors(id) ON DELETE SET NULL,
  expertise_id bigint REFERENCES expertises(id) ON DELETE SET NULL,
  request_type varchar(255) NOT NULL,
  budget varchar(255),
  estimated_amount bigint,
  deadline date,                      -- index
  message text NOT NULL,
  status varchar(255) NOT NULL DEFAULT 'nouveau',  -- index
  source varchar(255) NOT NULL DEFAULT 'site',
  origin_page varchar(500),
  utm_source varchar(255), utm_medium varchar(255), utm_campaign varchar(255),
  assigned_to bigint REFERENCES users(id) ON DELETE SET NULL,
  notes text, next_action varchar(255),
  next_action_at timestamp(0) without time zone,   -- index
  consent_at timestamp(0) without time zone, consent_ip varchar(45),
  notified_at timestamp(0) without time zone,
  first_contacted_at timestamp(0) without time zone, -- index
  created_at timestamp(0) without time zone,
  updated_at timestamp(0) without time zone
);
```

`lead_activities (lead_id, user_id, action, description, meta json)`. Contenus : `sectors` (hero + page + `is_locked`), `expertises` (`benefits/process_steps` json), `services`, `projects` (fiche + `challenge/solution/impact`, lat/long), pivots `expertise_sector/_service`, `project_sector/_expertise/_service`. Éditorial : `pages`, `testimonials`, `statistics`, `settings (key/value)`, `seo_metadata` polymorphe. Spatie : `media`, `roles/permissions/model_has_*`, `users` (+2FA, `password_changed_at`, `suspended_at`), `sessions`, `cache(_locks)`, `jobs/job_batches/failed_jobs`.

---

## 9. `.env` prod

Base `.env.production.example` (`APP_URL=https://www.sibea.ci`, pgsql `sibea`, session chiffrée + secure, SMTP Brevo, `FORTIFY_REGISTRATION=false`). Secrets jamais commités.

```bash
APP_KEY=                         # key:generate --show
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sibea
DB_USERNAME=
DB_PASSWORD=                     # secret
QUEUE_CONNECTION=database        # worker : queue:work supervisé
CACHE_STORE=database
MAIL_MAILER=smtp
MAIL_SCHEME=smtp                 # STARTTLS 587 Brevo ; smtps = 465 seul
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=                   # email Brevo
MAIL_PASSWORD=                   # clé SMTP (secret)
MAIL_FROM_ADDRESS="contact@sibea.ci"
LEADS_NOTIFICATION_EMAIL="contact@sibea.ci"
LEADS_AUTO_ASSIGN=true
LEADS_ASSIGN_MODE=round_robin    # | least_loaded
```

Brevo : domaine `sibea.ci` (SPF/DKIM/DMARC) → expéditeur validé → clé SMTP → `app:mail-test` + `queue:work --once`. Checklist : key, `migrate --force`, `npm run build`, queue, `leads:purge` planifié, backups `pg_dump+gpg→S3` 30 j.

---

## 10. Tests / qualité / recettes

```bash
php artisan test --compact tests/Feature/LeadPipelineTest.php
php artisan test --compact tests/Feature/AdminLeadsTest.php tests/Feature/ContactPageTest.php tests/Feature/DashboardTest.php
vendor/bin/pint --dirty --format agent
```

Couverture : `LeadPipelineTest` (SLA/UTM/round-robin/réf/relances/filtres), `ContactPageTest` (consentement, honeypot, secteur inactif, throttle, routage notif), `AdminLeadsTest`, `DashboardTest`, `NewLeadNotificationTest`, `SectorPolicyTest`, `PublicSeoTest`, `InvitationTest`, `Admin*` (catalogue, projets, pages, users…). Pièges : garde-fou permission, email sous le nom (pas de colonne), `CarbonInterface`, journées `startOfDay`, `reference` via `updateQuietly`.
Recettes : champ lead = migration + fillable/casts + contact + admin (`edit/save/create/store` + modales) + factory + Pest ; statut = enum + migration mapping + `isOpen()` + tests ; assignation = config seule. Debug : fiche admin, `lead_activities`, `laravel.log`, tinker par `reference`.
