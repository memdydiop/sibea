# Guide d'utilisateur — Administration du site Groupe SIBEA

**Public :** équipe interne SIBEA (Super administrateur, Administrateur, Éditeur, Commercial)
**Site public :** `/` — **Administration :** `/admin`
**Version :** état livré v2.8 (Laravel 13, Livewire 4, Flux UI, PostgreSQL)

Ce guide explique comment utiliser l'administration au quotidien : se connecter, gérer les contenus, traiter les prospects, administrer les comptes et régler le site. Il décrit l'application **telle que livrée** (voir `cdc-realise.md`).

---

## 1. Accéder à l'administration

### 1.1 Connexion

1. Allez sur `/admin` (ex. `https://votre-domaine/admin`).
2. Connectez-vous avec votre **email + mot de passe**.
3. Limites de sécurité : **5 tentatives/min** pour login, 2FA et invitation. Au-delà, attendez 1 minute.

### 1.2 Première connexion (obligatoire)

- À la création de votre compte, vous recevez un **email d'invitation** avec un **lien signé valable 7 jours, à usage unique**.
- Ouvrez le lien (`/invitation/{user}`) et **définissez votre mot de passe**.
- Si le lien est expiré ou déjà utilisé (erreur 410), demandez à un Administrateur de **renvoyer l'invitation** (voir §7).
- Au premier login, le **changement de mot de passe est exigé** avant d'accéder à `/admin`.

Règles du mot de passe : **12 caractères minimum**, avec minuscules + majuscules + chiffres + symboles.

### 1.3 Mot de passe oublié, 2FA, passkeys

- **Mot de passe oublié :** lien « Mot de passe oublié » sur la page de login. Lien de réinitialisation valable **60 minutes**.
- **Double authentification (2FA TOTP) :** activable dans `/settings/*` (menu Profil / Sécurité du starter-kit). Recommandée pour tous les administrateurs.
- **Passkeys (WebAuthn) :** créables dans `/settings/*` comme alternative au mot de passe.
- **Comptes suspendus :** bloqués au login (mot de passe, passkey, 2FA), au reset et à l'invitation. Contactez un Administrateur.

### 1.4 Rôles — qui peut faire quoi ?

| Module | Super admin | Admin | Éditeur | Commercial |
|---|---|---|---|---|
| Tableau de bord (`view_dashboard`) | ✅ | ✅ | ✅ | ✅ |
| Secteurs (`manage_sectors`) | ✅ | ✅ | ✅ | lecture seule (`view_sectors`) |
| Expertises (`manage_expertises`) | ✅ | ✅ | ✅ | ❌ |
| Services (`manage_services`) | ✅ | ✅ | ✅ | ❌ |
| Réalisations (`manage_projects` / `view_projects`) | ✅ | ✅ | ✅ | lecture seule |
| Témoignages (`manage_testimonials`) | ✅ | ✅ | ✅ | ❌ |
| Statistiques (`manage_statistics`) | ✅ | ✅ | ✅ | ❌ |
| Pages (`manage_pages`) | ✅ | ✅ | ✅ | ❌ |
| Paramètres (`manage_settings`) | ✅ | ✅ | ❌ | ❌ |
| Prospects (`manage_leads`) | ✅ | ✅ | ❌ | ✅ |
| Utilisateurs (`manage_users`) | ✅ | ✅ | ❌ | ❌ |
| Rôles (`manage_roles`) | ✅ | ❌ | ❌ | ❌ |

> Le Commercial voit les secteurs et réalisations **en lecture seule** uniquement pour contextualiser un prospect. Il ne peut rien modifier.
> Si un menu ou un bouton n'apparaît pas, c'est que votre rôle ne possède pas la permission.

---

## 2. Principes communs à tous les écrans admin

- **Listes :** recherche instantanée (nom, email, titre), filtres (statut, secteur, rôle), pagination (15 par page par défaut), tri par défaut = plus récent.
- **Création / modification :** toujours dans une **modale Flux UI**. Remplissez, cliquez **Enregistrer**, annulez avec **Annuler**. Un **toast de confirmation** vert s'affiche en cas de succès.
- **Suppression :** bouton poubelle rouge + **confirmation**. Action irréversible.
- **Publication :** interrupteur ou bouton œil :
  - `is_published = true` → visible sur le site public (Réalisations, Pages).
  - `is_published = false` (brouillon) → invisible du public, visible dans l'admin + compté dans « À traiter » du dashboard.
- **Activation (`is_active`) :** Secteurs, Expertises, Services, Témoignages, Statistiques. Seuls les éléments actifs apparaissent sur le site.
- **Ordre (`sort_order`) :** nombre entier. Plus petit = affiché en premier.
- **Images :** `≤ 5 Mo`, formats image uniquement. Documents PDF des réalisations : `≤ 10 Mo, PDF seuls`. Au-delà, l'upload est refusé.
- **Textes markdown** (Pages, descriptions) : mis en forme simple acceptée ; contenus dangereux (scripts, liens non sûrs) automatiquement neutralisés.

---

## 3. Tableau de bord (`/admin`)

Vue d'ensemble, accessible à tous les rôles connectés (contenu filtré par permissions).

- **4 compteurs :** Secteurs actifs, Expertises actives, Projets publiés, Prospects (total).
- **Bloc « À traiter » :** prospects `Nouveau`, réalisations en brouillon, pages en brouillon, avec lien direct.
- **Derniers prospects** (si `manage_leads`), **Réalisations récentes** (si `view_projects`), **Activité récente** (dernières mises à jour).
- Bouton **« Voir le site »** (ouvre le site public dans un nouvel onglet).

Réflexe quotidien : commencez par ce bloc « À traiter ».

---

## 4. Gérer les contenus du site

### 4.1 Secteurs (`/admin/secteurs`) — ⚠️ verrouillés

Les 4 secteurs officiels : **BTP, Immobilier, Énergie, Agro-industrie**. Ils sont créés avec `is_locked = true`.

- **Interdit :** supprimer un secteur verrouillé (cadenas affiché, suppression refusée par `SectorPolicy`). Vous pouvez modifier son contenu, le désactiver temporairement (`is_active`), changer son ordre.
- **Autorisé :** créer un 5e secteur (ex. extension future). Lui seul sera supprimable.
- Contenu par secteur : nom, slug (URL `/secteurs/{slug}`), descriptions courte/complète, **hero** (titre, description, label CTA, image), image d'illustration, cartes, chiffres, CTA, liaisons expertises.
- Désactiver un secteur (`is_active = false`) le retire du menu, de l'accueil, des filtres et du formulaire de contact.

> Ne renommez jamais les 4 secteurs officiels (règle métier). Modifiez les textes, pas les intitulés.

### 4.2 Expertises (`/admin/expertises`)

5 expertises de référence (Études & ingénierie, Construction & réalisation, Développement immobilier, Solutions énergétiques, Solutions agro-industrielles).

- Champs : nom, slug, descriptions, couverture, icône, bénéfices (liste), étapes d'intervention, `is_active`, ordre.
- **Liaisons :** associez chaque expertise à un ou plusieurs **secteurs** et **services** depuis la modale. Ces liaisons alimentent les pages `/secteurs`, `/expertises` et les fiches réalisations.
- Sur le site public, le **détail d'une expertise s'affiche en modale** (pas de page dédiée).

### 4.3 Services (`/admin/services`)

Catalogue des prestations concrètes. Pas de page publique dédiée.

- Champs : nom, slug, descriptions, `is_active`, ordre.
- Rattachez chaque service à une ou plusieurs **expertises**. Les secteurs d'un service sont **déduits automatiquement** de ses expertises (pas de `sector_id` direct).

### 4.4 Réalisations (`/admin/realisations` → site `/realisations`)

- Champs : titre (le **slug** est généré automatiquement, modifiable), descriptions, localisation, date projet, **statut** (`Livré` / `En cours` / `En développement`), client + case « nom publiable », résultats (liste), `is_published`, ordre.
- **Médias :** image de couverture (`cover`), galerie (`gallery`), documents PDF joints, image OG pour le partage.
- **Liaisons :** secteurs, expertises, services concernés. Servent aux filtres publics et aux pages secteurs.
- **Témoignage lié, géolocalisation** (latitude/longitude pour la carte OSM) : optionnels mais recommandés.
- **Publier / dépublier :** interrupteur `is_published` ou bouton œil dans la liste. Seuls les `is_published = true` apparaissent sur `/realisations` et dans le sitemap.
- Filtres admin : par statut (publié/brouillon), par secteur, recherche par titre.

### 4.5 Témoignages (`/admin/temoignages`)

- Champs : auteur, fonction/entreprise, texte, note éventuelle, photo, `is_active`, ordre.
- Seuls les témoignages actifs apparaissent sur l'accueil et les fiches réalisations.

### 4.6 Chiffres clés / Statistiques (`/admin/statistiques`)

- Champs : libellé (ex. « Projets réalisés »), valeur (ex. « 120+ »), ordre, `is_active`.
- Seuls les actifs s'affichent sur l'accueil.
- Bascule rapide possible en ligne de commande : `php artisan statistics:toggle` (réservé technique).

### 4.7 Pages éditoriales (`/admin/pages` → site `/pages/{slug}`)

Pages libres en **markdown** : Engagements, Le Groupe (complément), mentions légales, politique de confidentialité, etc.

- Champs : titre (slug auto), contenu markdown, `is_published`.
- Bouton œil pour publier/dépublier. Les brouillons sont comptés dans le dashboard.
- Pages spéciales :
  - `/pages/le-groupe` : inclut la section **« Mot du Président »** (texte + signature + photo, réglés dans Paramètres).
  - `/mentions-legales` et `/politique-de-confidentialite` : pages réservées (9 et 8 sections, base loi ivoirienne n°2013-450, recours ARTCI). Modifiez le texte, ne les supprimez pas.
- Après modification des contenus par défaut, le technique doit rejouer `php artisan db:seed --class=PageSeeder` + vider le cache si demandé.

### 4.8 Paramètres du site (`/admin/parametres`) — Administrateurs uniquement

Écran à **onglets**. Modifiez puis cliquez **Enregistrer** (bandeau sticky en haut + bouton en bas). Mention « Modifications non enregistrées » tant que ce n'est pas sauvegardé.

| Onglet | Contenu |
|---|---|
| **Général & accueil** | Nom du site, hero (titre, accroche, bouton), footer (baseline, copyright sans l'année), sections Secteurs / Expertises / Groupe / Réalisations / Témoignages / RSE / Méthode (étapes ajoutables, montables/descendables) / CTA final, bloc Le Groupe + citation Président, Mot du Président (titre, nom/fonction, texte — un paragraphe par ligne vide) |
| **Pages** | Titres et sous-titres des pages Secteurs, Expertises, Réalisations ; textes d'état vide ; encarts CTA |
| **Header** | Logo + menu principal : pour Secteurs / Expertises / Réalisations / Contact, case **Visible** + libellé modifiable |
| **Contact & localisation** | Sous-titre page contact, adresse (une ligne par ligne), horaires, téléphone affiché + lien `tel:`, email, WhatsApp (numéro avec indicatif sans `+` + message pré-rempli), latitude/longitude (ex. `5.35` / `-4.00`), bloc Implantation (titre, ville/zone, description) |
| **SEO** | Titre meta et description meta par défaut (utilisés si aucune valeur spécifique) |
| **Visuels** | 9 images : logo, heroes (accueil, secteurs, expertises, réalisations, contact, mentions, privacy), bloc Groupe, **photo du Président**. Badge `Personnalisée` / `Par défaut`, aperçu, bouton **Retirer** pour revenir au défaut. `≤ 5 Mo` par image |

> Si une valeur est vide en base, le site utilise le repli `config/site.php`. Pour voir un changement : enregistrez, rechargez la page publique (Ctrl+F5).

---

## 5. Traiter les prospects (`/admin/prospects`)

Origine : formulaire public `/contact` + saisie manuelle admin. Chaque prospect crée une entrée d'historique + notification email au commercial (via file d'attente).

### 5.1 Liste et filtres

- Colonnes : Nom (+ badge **Doublon ×N** si même email), Email, Secteur, Type de demande, Statut, Assigné à, Actions.
- **Recherche :** nom ou email. **Filtres :** statut + secteur. Pagination 15/page.
- Bouton **« Purger +3 ans »** (avec confirmation) : supprime définitivement les prospects de plus de 3 ans (obligation RGPD, voir §8). Commande équivalente : `php artisan leads:purge` (tâche mensuelle côté technique).

### 5.2 Cycle de vie d'un prospect

Statuts (dans l'ordre de traitement) :

1. **Nouveau** (à qualifier — prioritaire dashboard)
2. **Contacté**
3. **En cours**
4. **Qualifié**
5. **Converti** (affaire gagnée)
6. **Non qualifié** (abandon motivé)
7. **Archivé**

Types de demande : Demande d'information, Demande de devis, Partenariat, Candidature, Presse, Autre.
Sources : Site web, Téléphone, Email, Réseaux sociaux, Recommandation, Autre.

### 5.3 Traiter un prospect (modale « Traiter le prospect »)

1. Cliquez le crayon sur la ligne.
2. Lisez l'encadré récapitulatif (identité, secteur, demande, budget, message complet, pays de résidence / territoire ciblé).
3. Changez le **Statut**, choisissez **Assigné à** (un utilisateur — il reçoit un email), ajoutez des **Notes internes** (invisibles du prospect).
4. **Enregistrer.** Chaque changement de statut ou d'assignation est consigné dans l'**Historique** (date, auteur, description, 20 dernières entrées visibles).

### 5.4 Créer un prospect manuellement (bouton `+`)

Pour les demandes reçues par téléphone, email ou visite : **Nouveau prospect** → nom*, email*, secteur*, type de demande*, message*, société, téléphone, pays de résidence, territoire ciblé, budget, source*. Enregistrement = statut `Nouveau` + historique « créé manuellement par … » + notification.

### 5.5 Supprimer

Poubelle rouge + confirmation. Réservé aux rôles autorisés. Préférez **Archivé** à la suppression sauf demande RGPD (voir §8).

---

## 6. Ce que voient les visiteurs (pour contextualiser votre saisie)

- `/` : hero, bloc Groupe + citation Président, chiffres clés, secteurs, expertises, réalisations, méthode, témoignages, CTA.
- `/secteurs`, `/secteurs/{slug}` : secteurs actifs + expertises, services, réalisations liées.
- `/expertises` : cartes + services, détail en modale.
- `/realisations` : grille 3 colonnes, 9 par page, filtre par secteur. Fiche : galerie, fiche technique, contexte/solution/impact, témoignage, carte OSM, documents PDF, précédent/suivant.
- `/contact` : coordonnées, WhatsApp, **formulaire inline** (nom*, société, email*, téléphone, pays de résidence*, territoire ciblé*, secteur actif*, type de demande*, budget, message* ; consentement implicite par soumission + lien privacy ; anti-spam honeypot + 5 envois/min/IP), carte OSM.
- `/pages/le-groupe`, `/pages/{slug}`, mentions, privacy, `/robots.txt`, `/sitemap.xml`.

Vos contenus (titres, images, `is_active` / `is_published`) déterminent directement ces pages.

---

## 7. Gérer les utilisateurs et rôles (`/admin/utilisateurs`)

Réservé Administrateur / Super administrateur. Deux blocs sur la même page.

### 7.1 Rôles (cartes en haut, Super admin seul pour créer)

- Carte par rôle : nom, description, 4 premières permissions + compteur, avatars + total utilisateurs, bouton œil vers `/admin/roles/{role}` (détail + matrice).
- **Nouveau rôle :** nom*, description, permissions à cocher, utilisateurs à assigner.
- Rôles par défaut : **Super administrateur** (tout), **Administrateur** (tout sauf rôles), **Éditeur** (contenus), **Commercial** (prospects + lecture).

### 7.2 Utilisateurs (tableau)

- Colonnes : Utilisateur (avatar initiales + nom + email), Rôles (badges + `+N directe(s)`), Statut (`Actif` vert / `Suspendu` rouge), Mis à jour, Actions.
- Filtres : statut (actif/suspendu), rôle. Recherche nom/email.
- **Créer :** bouton `+` → nom*, email*, rôles, permissions directes (cumulées avec les rôles ; celles déjà couvertes par les rôles sont masquées). À la création, un **mot de passe aléatoire** est posé et une **invitation email** est envoyée. L'utilisateur définira son mot de passe via le lien 7 jours.
- **Règles importantes :**
  - On ne modifie pas le mot de passe d'un autre : seul le propriétaire (invitation, mot de passe oublié, profil).
  - Pour **modifier** un compte déjà activé : **suspendez-le d'abord** (bouton pause), modifiez, puis réactivez (bouton lecture).
  - Pour **supprimer** : compte **suspendu uniquement** (bouton poubelle désactivé sinon). Vous ne pouvez pas suspendre votre propre compte.
  - **Renvoyer l'invitation** (enveloppe) : uniquement si l'utilisateur n'a jamais défini son mot de passe.
  - **Historique du compte** (horloge) : 30 dernières actions (création, modification, suspension, renvoi… avec auteur et date).
- Toute action (création, suspension, suppression, renvoi) est journalisée + toast de confirmation.

---

## 8. Conformité RGPD (loi ivoirienne n°2013-450)

- **Consentement :** chaque prospect du site horodate `consent_at` + `consent_ip` à la soumission. Mention + lien privacy affichés sous le formulaire.
- **Conservation :** **3 ans après le dernier contact**. Utilisez **Purger +3 ans** dans Prospects ou demandez la commande mensuelle `leads:purge` au technique.
- **Droits des personnes** (accès, rectification, suppression) : via `contact@sibea.ci`. En cas de demande de suppression, supprimez le prospect + confirmez par email. Recours possible devant l'**ARTCI**.
- **Cookies :** strictement nécessaires uniquement (session, sécurité). Pas de traceurs publicitaires.
- **Emails :** expéditeur `contact@sibea.ci` (SMTP Brevo). Les notifications prospects transitent par la file d'attente (`database` — worker requis côté hébergement Laravel Cloud).

---

## 9. Bonnes pratiques et erreurs fréquentes

1. **Brouillon vs publié :** vérifiez toujours `is_published` / `is_active` si un contenu n'apparaît pas sur le site.
2. **Images trop lourdes :** compressez avant upload (paysage, ≥1600 px pour les heroes, texte alternatif soigné côté public).
3. **Slug / URL :** généré depuis le titre. Évitez de le changer après publication (perte de référencement). Redirections 301 existantes : `/btp`, `/immobilier`, `/energie`, `/agroalimentaire`, `/agro-industrie` → `/secteurs/…`.
4. **Liaisons oubliées :** une réalisation sans secteur n'apparaît dans aucun filtre ; un service sans expertise est invisible.
5. **Prospects sans assigné :** assignez systématiquement + changez le statut, sinon ils restent en « Nouveau ».
6. **Paramètres non enregistrés :** guettez « Modifications non enregistrées », cliquez **Enregistrer**, puis Ctrl+F5 côté public.
7. **Comptes :** ne créez pas de doublon email ; renvoyez l'invitation plutôt que recréer.

---

## 10. Dépannage rapide

| Symptôme | Cause probable | Action |
|---|---|---|
| `/admin` → 403 | Permission manquante ou compte suspendu | Vérifiez rôle / statut avec un Admin ; menu absent = normal |
| Lien d'invitation invalide (410/403) | Expiré (7 j), déjà utilisé, ou compte suspendu | Demandez un renvoi d'invitation |
| « Trop de tentatives » | Rate-limit 5/min | Attendez 1 minute, réessayez |
| Contenu invisible sur le site | `is_published` / `is_active` à false | Publiez/activez dans l'admin |
| Image refusée | > 5 Mo (ou PDF > 10 Mo / non-PDF) | Compressez / convertissez, réessayez |
| Email de notification non reçu | File d'attente sans worker, `MAIL_*` mal configurés | Prévenez le technique (vérifier Brevo + `QUEUE_CONNECTION=database` + worker) |
| Toast d'erreur de validation | Champ requis / email invalide / latitude hors bornes | Corrigez le champ signalé en rouge |

**En cas de blocage :** notez l'heure, l'URL (`/admin/...`), l'action et le message exact (ou capture), puis contactez l'Administrateur ou le prestataire technique (logs : `laravel.log`).

---

*Fin du guide — équipe SIBEA. Pour toute évolution (nouveau secteur, nouveau rôle, nouveau bloc de page), passez par un Super administrateur.*
