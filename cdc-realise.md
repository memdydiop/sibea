# CAHIER DES CHARGES — TEL QUE CONSTRUIT (AS-BUILT)

## Site vitrine institutionnel du Groupe SIBEA

**Version :** 2.8 — état du code au commit de réception
**Objet :** décrit l’application **telle que livrée**, à partir du code source (et non l’intention initiale).
Le `cdc.md` v1.3 reste la référence d’origine ; la section 12 liste les écarts assumés.

---

## 1. Stack technique livrée

| Couche | Technologie | Version |
|---|---|---|
| Framework | Laravel | 13.32 |
| Langage | PHP | 8.4 |
| UI réactive | Livewire (Single File Components `pages::`) | 4.x |
| Composants | Flux UI (édition Free) + `flux:table`, `flux:card` | 2.x |
| CSS | Tailwind CSS | 4.x |
| Base de données | PostgreSQL (SQLite en mémoire pour les tests) | — |
| Auth | Laravel Fortify (login, reset, 2FA/TOTP, passkeys WebAuthn, vérification email) | — |
| Permissions | Spatie Permission (rôles + 14 permissions) | — |
| Médias | Spatie Media Library (conversions WebP : `thumb`, `medium`, `og`, `hero`) | — |
| SEO | Spatie Sitemap + Spatie Schema.org + partial `partials/seo.blade.php` | — |
| Tests | Pest (209 tests), PHPStan niveau 7, Pint (preset Laravel) | — |
| Emails | SMTP Brevo (`smtp-relay.brevo.com:587`, TLS), expéditeur `contact@sibea.ci` | — |
| Hébergement cible | Laravel Cloud, PostgreSQL, file d’attente `database` (worker requis) | — |

---

## 2. Site public (sans authentification)

### 2.1 Pages

| URL | Contenu |
|---|---|
| `/` | Hero statique (bannière, pas de carrousel), bloc Groupe + citation du Président, chiffres clés, secteurs, expertises, réalisations, méthode, témoignages, CTA |
| `/secteurs` | Liste des secteurs actifs + leurs expertises |
| `/secteurs/{slug}` | Page sectorielle (intro, cartes, chiffres, CTA, réalisations liées) |
| `/expertises` | Cartes expertises + services ; **détails en modale** (pas de page dédiée par expertise) |
| `/realisations` | Liste filtrable par secteur (grille 3 colonnes, 9 par page) |
| `/realisations/{slug}` | Galerie, fiche technique, contexte/solution/impact, témoignage, carte OSM, documents, navigation précédent/suivant |
| `/contact` | Coordonnées, WhatsApp, **formulaire inline** (pas de modale), carte OSM, fallback no-JS |
| `/mentions-legales`, `/politique-de-confidentialite` | Pages éditoriales réservées (mentions : 9 sections dont « Utilisation du site » ; privacy : 8 sections, loi 2013-450, recours ARTCI) |
| `/pages/le-groupe` | Page dédiée : hero + **section « Mot du Président »** (texte + signature + photo optionnelle) + contenu éditorial |
| `/pages/{slug}` | Pages libres (ex. Engagements : 6 sections) |
| `/invitation/{user}` | Définition du mot de passe sur **lien signé 7 jours** |
| `/robots.txt`, `/sitemap.xml` | Sitemap caché 1h (lastmod + fréquences), robots avec `Disallow: /admin` |

### 2.2 Redirections 301
`/btp`, `/immobilier`, `/energie`, `/agroalimentaire`, `/secteurs/agroalimentaire`, `/agro-industrie` → URLs canoniques `/secteurs/…`. Plus `/admin/contenus-secteurs` → `/admin/secteurs`.

### 2.3 Formulaire de contact
Champs : nom*, société, email*, téléphone, pays de résidence*, territoire ciblé*, secteur*, type de demande*, budget, message*. **Consentement implicite** à la soumission (mention + lien privacy, `consent_at`/`consent_ip` horodatés). Protections : honeypot silencieux, rate-limit 5/min/IP **après** validation, vérification secteur actif. Crée un prospect `nouveau`/`site` + entrée d’historique + événement `LeadCreated` → notification au commercial.

### 2.4 Contenus administrables (64 réglages par défaut)
Textes (titres, accroches, citation, mot du Président, SEO), liens du header (libellé + visibilité), 9 visuels (logo, heroes, bloc Groupe, **photo du Président**), crop… via `/admin/parametres`. Pages éditoriales via `/admin/pages`. Fallback `config/site.php` si aucune valeur en base.

---

## 3. Administration (`/admin`, auth + mot de passe changé requis)

| Module | URL | Fonctions |
|---|---|---|
| Dashboard | `/admin` | Compteurs, prospects récents, réalisations/pages récentes, journal d’activité |
| Secteurs | `/admin/secteurs` | CRUD en modale, hero/médias, cartes, chiffres, verrouillage anti-suppression, ordre |
| Expertises | `/admin/expertises` | CRUD, couverture, liaisons secteurs/services, bénéfices, étapes |
| Services | `/admin/services` | CRUD, activation |
| Réalisations | `/admin/realisations` | CRUD, couverture, galerie, documents PDF, secteurs/expertises/services, statuts, témoignage, géolocalisation |
| Témoignages | `/admin/temoignages` | CRUD, activation, ordre |
| Statistiques | `/admin/statistiques` | CRUD + bascule CLI `statistics:toggle` |
| Pages | `/admin/pages` | CRUD markdown, publication |
| Paramètres | `/admin/parametres` | Textes, liens header, mot du Président, visuels, SEO, contact |
| Prospects | `/admin/prospects` | Filtres statut/secteur, assignation, notes, historique, purge +3 ans, création manuelle |
| Utilisateurs | `/admin/utilisateurs` | CRUD, rôles, **invitation par email**, suspension/réactivation, historique |
| Rôles | `/admin/roles` | CRUD, matrice de permissions |
| Profil/sécurité | `/settings/*` | Profil, mot de passe, 2FA, passkeys (starter-kit) |

Listes : recherche instantanée, filtres, pagination, actions en modales Flux (`Annuler`/`Enregistrer`), toasts de confirmation.

---

## 4. Données

**Modèles :** `User`, `Sector`, `Expertise`, `Service`, `Project`, `Lead`, `LeadActivity`, `Activity`, `Page`, `Testimonial`, `Statistic`, `Setting`.
**Enums :** `LeadStatus` (7), `LeadSource` (6), `RequestType` (6), `ProjectStatus` (3).
**Relations N-N :** expertise↔secteur, expertise↔service, projet↔secteur/expertise/service.
**Règles métier :** secteurs `is_locked` insupprimables ; `is_published` seul gate public des projets/pages ; statuts projets normalisés (`livre`, `en_cours`, `en_developpement`).

### 4.1 Rôles et permissions (4 rôles, 14 permissions)
- **Super administrateur** : tout.
- **Administrateur** : tout sauf rôles.
- **Éditeur** : contenus (pas prospects/utilisateurs/rôles).
- **Commercial** : prospects + lecture secteurs/projets.
11 policies dédiées (`SectorPolicy` refuse la suppression si verrouillé, etc.).

### 4.2 Sécurité et conformité
- Mots de passe 12 caractères (lettres, casse mixte, chiffres, symboles) ; changement forcé à la 1ʳᵉ connexion ; 2FA TOTP + passkeys ; reset via lien 60 min.
- **Comptes suspendus** bloqués au login (password, passkey, 2FA via événement `Login`), au reset et à l’invitation ; sessions révoquées par middleware.
- Invitations : lien signé 7 jours, usage unique (410 si rejoué), 403 si suspendu, throttle 5/min.
- Rate-limits : login 5/min, 2FA 5/min, passkeys 10/min, contact 5/min/IP, invitation 5/min.
- Uploads : images ≤ 5 Mo (documents ≤ 10 Mo, PDF seuls), contenus markdown assainis (`strip`, liens non sûrs interdits).
- **RGPD (loi ivoirienne 2013-450)** : consentement horodaté + IP, conservation **3 ans** (`leads:purge` mensuel), droits via `contact@sibea.ci`, recours ARTCI, cookies strictement nécessaires uniquement.
- Commande `app:create-admin` (rôle Super administrateur, mot de passe généré).

---

## 5. Qualité et exploitation

- **209 tests Pest** (public, admin, auth, invitations, contact, SEO, policies, console), Pint et PHPStan verts en CI (`tests.yml` sur `master`).
- Logs : `laravel.log` applicatif ; toasts admin persistants.
- **Prérequis production :** `migrate --force`, `db:seed --class=PageSeeder` après modification des contenus par défaut, `cache:clear` + `optimize`, variables `MAIL_*` Brevo, `QUEUE_CONNECTION=database` **avec worker actif** (managed queues), `SESSION_ENCRYPT=true` recommandé, `FORTIFY_REGISTRATION=false` par défaut en prod, `trustProxies` derrière proxy HTTPS.

---

## 6. Écarts assumés avec le CDC d’origine (v1.3)

1. `flux:table`/`flux:card` utilisés partout (le CDC les disait indisponibles en Free).
2. Secteur Agro-industrie **actif** et tous les modules (témoignages, statistiques, pages, paramètres) livrés, bien que classés V2.
3. Hero d’accueil **statique** (pas de carrousel autoplay/swipe).
4. Formulaire de contact **inline** (pas de modale) ; détails expertises en modale (pas de page `/expertises/{slug}`).
5. Composants Blade mutualisés `x-admin.card` / `x-admin.toolbar` (le CDC les interdisait hors SEO).
6. Pas de CGU : vitrine sans comptes visiteurs — mentions + privacy suffisent (section « Utilisation du site » dans les mentions).
7. Pas de backup `pg_dump`/`gpg` ni WebP/AVIF automatiques au-delà des conversions Spatie : à traiter au niveau Cloud.
