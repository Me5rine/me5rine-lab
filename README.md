# Me5rine LAB - Documentation

<!-- Version: 1.10.7 - Généré automatiquement - Utilisez generate-docs.php pour mettre à jour -->

Plugin WordPress personnalisé pour la gestion de contenu et fonctionnalités avancées.

> **Note** : Pour mettre à jour la version dans cette documentation, exécutez `php generate-docs.php` depuis le répertoire du plugin. La version est automatiquement extraite de `me5rine-lab.php`.

## Table des matières

- [Introduction](#introduction)
- [Modules disponibles](#modules-disponibles)
  - [Module Giveaways](#module-giveaways)
  - [Module Marketing](#module-marketing)
  - [Module Subscription](#module-subscription)
  - [Module Partnership](#module-partnership)
  - [Module Socialls](#module-socialls)
  - [Module Events](#module-events)
  - [Module Remote News](#module-remote-news)
  - [Module Shortcodes](#module-shortcodes)
  - [Module User Management](#module-user-management)
  - [Module Comparator](#module-comparator)
- [Configuration générale](#configuration-générale)
- [Support](#support)

---

## Introduction

**Me5rine LAB** est un plugin WordPress modulaire offrant diverses fonctionnalités pour la gestion de contenu, d'utilisateurs, de partenariats, d'abonnements et bien plus encore. Chaque module peut être activé ou désactivé indépendamment selon les besoins du site.

### Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Certains modules nécessitent des plugins complémentaires (voir la documentation de chaque module)

### Installation

1. Télécharger et activer le plugin
2. Aller dans **Réglages > Me5rine LAB**
3. Activer les modules souhaités
4. Configurer chaque module selon vos besoins

---

## Modules disponibles

### Module Giveaways

#### Description

Le module **Giveaways** permet de gérer des concours (giveaways) en intégration avec RafflePress Pro. Il offre une interface complète pour créer, gérer et afficher des concours avec des personnalisations spécifiques à Me5rine LAB.

#### Prérequis

- **RafflePress Pro** : Le plugin doit être installé et activé
- **Module activé** : Le module doit être activé dans les paramètres du plugin

#### Shortcodes

##### 1. `[custom_rafflepress]`

Affiche un giveaway RafflePress avec des personnalisations Me5rine LAB intégrées.

**Paramètres :**
- `id` (requis) : L'ID du giveaway RafflePress
- `min_height` (optionnel) : Hauteur minimale de l'iframe en pixels (défaut: `900px`)

**Exemple :**
```
[custom_rafflepress id="18" min_height="900px"]
```

##### 2. `[add_giveaway]`

Affiche le formulaire d'ajout d'un nouveau giveaway pour les partenaires.

**Exemple :**
```
[add_giveaway]
```

##### 3. `[edit_giveaway]`

Affiche le formulaire d'édition d'un giveaway existant.

**Exemple :**
```
[edit_giveaway]
```

##### 4. `[admin_giveaways]`

Affiche le tableau de bord des giveaways pour un partenaire.

**Exemple :**
```
[admin_giveaways]
```

##### 5. `[partner_active_giveaways]`

Affiche la liste des giveaways actifs d'un partenaire sur sa page de profil.

**Exemple :**
```
[partner_active_giveaways]
```

##### 6. `[admin_lab_participation_table]`

Affiche le tableau des participations d'un utilisateur.

**Exemple :**
```
[admin_lab_participation_table]
```

##### 7. `[giveaway_redirect_link]`

Génère une URL de redirection vers la page d'ajout de giveaway.

**Exemple :**
```
<a href="[giveaway_redirect_link]">Créer un giveaway</a>
```

#### Fonctionnalités

- **Personnalisation de l'iframe** : Hauteur dynamique via postMessage avec calcul automatique
- **Bloc de connexion personnalisé** : Bloc de connexion adaptatif selon l'état de l'utilisateur
- **Styles personnalisés** : Styles spécifiques pour Discord, Bluesky, Threads dans les formulaires RafflePress
- **Synchronisation automatique** : Synchronisation bidirectionnelle avec RafflePress Pro
- **Gestion des partenaires** : Association des giveaways aux partenaires
- **Gestion des participations** : Suivi des participations utilisateurs via AJAX
- **Intégration Elementor** : Requêtes Elementor pour afficher les giveaways dans les widgets
- **CRON de métadonnées** : Synchronisation périodique des métadonnées (participants, entrées, dates)
- **Actions rapides** : Actions admin (publier, dépublier, éditer dans RafflePress)
- **Routes personnalisées** : Route personnalisée pour l'affichage des giveaways avec template dédié

#### Configuration

1. Aller dans **Réglages > Me5rine LAB**
2. Activer le module **Giveaways**
3. Les pages nécessaires seront créées automatiquement

#### Custom Post Type

Le module crée automatiquement un Custom Post Type `giveaway` lors de l'activation.

**Caractéristiques du CPT :**

- **Slug** : `giveaway`
- **Public** : Oui (accessible publiquement)
- **Archive** : Oui (`has_archive` activé)
- **Permalien** : `/giveaway/{slug}/`
- **Menu** : Intégré dans le menu "Me5rine LAB" (pas de menu séparé)
- **Supports** :
  - `title` : Titre du giveaway
  - `editor` : Éditeur de contenu
  - `thumbnail` : Image à la une
  - `custom-fields` : Métadonnées personnalisées
- **Capabilities** : Utilise les mêmes permissions que les posts WordPress (`capability_type => 'post'`)
- **Hiérarchique** : Non

**Colonnes personnalisées dans l'admin :**

Le module ajoute des colonnes personnalisées dans la liste des giveaways :

- **Start Date** : Date de début du giveaway (triable)
- **End Date** : Date de fin du giveaway (triable)
- **Partner & Reward** : Partenaire associé et récompenses
- **Status** : Statut du giveaway (triable)
- **Participants** : Nombre de participants (triable)
- **Entries** : Nombre d'entrées (triable)
- **Actions** : Actions rapides (Éditer dans RafflePress, Publier, etc.)

#### Taxonomies

Le module enregistre deux taxonomies pour le CPT `giveaway` :

##### 1. `giveaway_rewards`

Taxonomie pour les récompenses/prix des giveaways.

- **Slug** : `giveaway-rewards`
- **Type** : Non hiérarchique (tags)
- **Colonne admin** : Oui
- **Interface** : Oui

##### 2. `giveaway_category`

Taxonomie pour catégoriser les giveaways.

- **Slug** : `giveaway-category`
- **Type** : Hiérarchique (catégories)
- **Colonne admin** : Oui
- **Interface** : Oui

**Catégories par défaut :**

Lors de l'activation du module, deux catégories sont créées automatiquement :

- **Me5rine LAB** : Pour les giveaways officiels
- **Partenaires** : Pour les giveaways des partenaires

#### Pages créées automatiquement

Lors de l'activation du module, trois pages sont créées automatiquement :

1. **Mes concours** (`admin-giveaways`)
   - **Slug** : `admin-giveaways`
   - **Titre** : "Mes concours"
   - **Contenu** : `[admin_giveaways]`
   - **Protection** : Accessible uniquement aux utilisateurs connectés avec les permissions appropriées

2. **Ajouter un concours** (`add-giveaway`)
   - **Slug** : `add-giveaway`
   - **Titre** : "Ajouter un concours"
   - **Contenu** : `[add_giveaway]`
   - **Protection** : Accessible uniquement aux utilisateurs connectés avec les permissions appropriées

3. **Modifier un concours** (`edit-giveaway`)
   - **Slug** : `edit-giveaway`
   - **Titre** : "Modifier un concours"
   - **Contenu** : `[edit_giveaway]`
   - **Protection** : Accessible uniquement aux utilisateurs connectés avec les permissions appropriées

**Gestion des pages :**

- Les pages sont créées automatiquement lors de l'activation du module
- Les IDs des pages sont stockés dans les options WordPress :
  - `giveaways_page_admin-giveaways`
  - `giveaways_page_add-giveaway`
  - `giveaways_page_edit-giveaway`
- Les pages sont supprimées automatiquement lors de la désactivation du module
- Si une page avec le même slug existe déjà, le module l'utilise au lieu d'en créer une nouvelle

**Protection des pages :**

Les pages sont protégées par la fonction `admin_lab_protect_giveaways_pages()` :

- **Utilisateurs non connectés** : Redirection vers la page de connexion
- **Utilisateurs connectés sans permissions** : Redirection vers la page d'accueil
- **Utilisateurs avec permissions** : Accès autorisé

La vérification des permissions utilise la fonction `admin_lab_user_has_allowed_role('giveaways', $user_id)`.

#### Métadonnées

Chaque giveaway stocke les métadonnées suivantes :

| Meta Key | Description | Type |
|----------|-------------|------|
| `_giveaway_rafflepress_id` | ID du giveaway dans RafflePress | integer |
| `_rafflepress_campaign` | ID de la campagne RafflePress | integer |
| `_giveaway_partner_id` | ID du partenaire associé | integer |
| `_giveaway_start_date` | Date de début du giveaway (UTC) | datetime |
| `_giveaway_end_date` | Date de fin du giveaway (UTC) | datetime |
| `_giveaway_status` | Statut du giveaway | string |
| `_giveaway_participants_count` | Nombre de participants | integer |
| `_giveaway_entries_count` | Nombre d'entrées | integer |

#### Filtres et recherches

Le module ajoute des fonctionnalités de filtrage et de recherche dans l'interface d'administration :

- **Filtres par statut** : Filtrage des giveaways par statut
- **Filtres par partenaire** : Filtrage par partenaire associé
- **Filtres par catégorie** : Filtrage par catégorie de giveaway
- **Recherche** : Recherche dans les titres et contenus
- **Tri** : Tri par date de début, date de fin, statut, participants, entrées

---

### Module Marketing

#### Description

Le module **Marketing** permet de gérer des campagnes marketing avec des bannières et des zones publicitaires configurables. Il offre un système de gestion de campagnes avec assignation à différentes zones (sidebars, bannières, background).

#### Shortcodes

##### `[marketing_banner]`

Affiche une bannière marketing dans une zone spécifique.

**Paramètres :**
- `format` (requis) : Format de la zone (`banner`, `sidebar`, ou `background`)
- `slot` (requis) : Numéro du slot (1, 2, 3, etc.)
- `image` (optionnel) : Numéro de l'image à utiliser (défaut: `1`)

**Exemple :**
```
[marketing_banner format="banner" slot="1" image="1"]
[marketing_banner format="sidebar" slot="2"]
```

#### Fonctionnalités

- Gestion de campagnes marketing avec images multiples
- Zones configurables : Sidebar 1-3, Banner 1-3, Background
- Upload d'images via la médiathèque WordPress ou URL directe
- Assignation de campagnes à des zones spécifiques
- Support des couleurs personnalisées
- Gestion des partenaires associés aux campagnes

#### Configuration

1. Aller dans **Me5rine LAB > Marketing Campaigns**
2. Créer une nouvelle campagne
3. Uploader les images via la médiathèque WordPress ou saisir une URL directe
4. Configurer les couleurs personnalisées pour chaque image
5. Assigner la campagne aux zones souhaitées (sidebar, banner, background)
6. Utiliser le shortcode `[marketing_banner]` dans vos templates

#### Interface d'administration

- **Liste des campagnes** : Tableau de gestion avec actions (éditer, supprimer)
- **Éditeur de campagne** : Interface d'édition complète avec :
  - Upload d'images multiples (via médiathèque WordPress)
  - Support des URLs directes (sans passer par la médiathèque)
  - Color picker pour les couleurs personnalisées
  - Assignation aux zones marketing
  - Association avec des partenaires

#### Zones disponibles

- `sidebar_1`, `sidebar_2`, `sidebar_3` : Zones de sidebar
- `banner_1`, `banner_2`, `banner_3` : Zones de bannière
- `background` : Zone de fond

---

### Module Subscription

#### Description

Le module **Subscription** gère un système complet d'abonnements avec intégration de multiples fournisseurs (Twitch, Patreon, Tipeee, YouTube, Discord, Keycloak). Il permet de synchroniser les abonnements, gérer les niveaux et les rôles utilisateurs.

#### Fonctionnalités

- **Gestion des fournisseurs** : Twitch, Patreon, Tipeee, YouTube, Discord, Keycloak
- **Synchronisation automatique** : Synchronisation périodique des abonnements via CRON
- **Gestion des niveaux** : Création et gestion de niveaux d'abonnement (tiers, boosters, etc.)
- **OAuth** : Authentification OAuth pour chaque fournisseur
- **Rôles Ultimate Member** : Création automatique des rôles `um_sub` et `um_premium`
- **Types de comptes** : Gestion des types de comptes "sub" et "premium"
- **Channels/Servers** : Gestion des canaux et serveurs pour chaque fournisseur

#### Interface d'administration

L'interface d'administration propose plusieurs onglets :

- **Providers** : Gestion des fournisseurs d'abonnement
- **Channels/Servers** : Gestion des canaux et serveurs
- **Providers → Account Types** : Types de comptes par fournisseur
- **Subscription Types** : Types d'abonnements
- **Tiers** : Niveaux d'abonnement (tiers)
- **Subscription Levels** : Niveaux d'abonnement complets
- **Keycloak Identities** : Identités Keycloak
- **User Subscriptions** : Abonnements des utilisateurs

#### Configuration

1. Aller dans **Me5rine LAB > Subscription**
2. Configurer les fournisseurs (OAuth, API keys, etc.)
3. Créer les canaux/serveurs pour chaque fournisseur
4. Définir les types d'abonnements et niveaux
5. Configurer la synchronisation automatique

#### Synchronisation

La synchronisation peut être effectuée :
- **Manuellement** : Via l'interface d'administration
- **Automatiquement** : Via CRON (configurable dans les paramètres)

**Fournisseurs supportés :**
- **Twitch** : OAuth + API Twitch pour récupérer les abonnements
- **Patreon** : OAuth + API Patreon
- **Tipeee** : Synchronisation via API Tipeee
- **YouTube** : OAuth + API YouTube Members (avec fallback si pas d'API)
- **Discord** : OAuth + synchronisation des boosters serveur
- **Keycloak** : Authentification et synchronisation via Keycloak

**Fonctionnalités avancées :**
- **Chiffrement** : Les tokens OAuth sont chiffrés en base de données
- **Niveaux par défaut** : Initialisation automatique des types d'abonnements (tier1, tier2, tier3 pour Twitch, booster pour Discord)
- **Nettoyage des types** : Suppression automatique des anciens types d'abonnements obsolètes
- **Synchronisation OpenID** : Support de la synchronisation OpenID pour certains types de comptes

---

### Module Partnership

#### Description

Le module **Partnership** gère les partenariats avec création de rôles Ultimate Member spécifiques et un tableau de bord pour les partenaires.

#### Shortcodes

##### 1. `[partner_dashboard]`

Affiche le tableau de bord des partenaires avec statistiques sur les giveaways.

**Exemple :**
```
[partner_dashboard]
```

##### 2. `[partner_menu]`

Affiche un menu latéral adaptatif listant les modules accessibles pour l'utilisateur connecté (partenaires et subscribers). Le menu s'adapte automatiquement aux modules accessibles selon le type de compte.

**Fonctionnalités :**
- Menu adaptatif selon les modules accessibles
- Sous-menus pour les modules avec plusieurs pages
- Détection automatique de la page active
- Responsive avec toggle mobile
- Style Ultimate Member compatible

**Exemple :**
```
[partner_menu]
```

**Note :** Le CSS pour le menu doit être copié dans votre thème. Voir `docs/PARTNER_MENU_CSS.md` pour les styles CSS.

#### Fonctionnalités

- **Rôles Ultimate Member** : Création automatique des rôles `um_partenaire` et `um_partenaire_plus`
- **Types de comptes** : Gestion des types de comptes "partenaire" et "partenaire_plus"
- **Tableau de bord** : Interface dédiée pour les partenaires
- **Statistiques** : Statistiques sur les giveaways (participants, entrées, etc.)
- **Pages automatiques** : Création automatique de la page de tableau de bord
- **Menu partenaires** : Menu latéral adaptatif pour naviguer entre les modules accessibles

#### Configuration

1. Aller dans **Me5rine LAB > Partnership**
2. Le module crée automatiquement les rôles et types de comptes
3. La page de tableau de bord est créée automatiquement avec le slug `partenariat`

#### Pages créées automatiquement

- **Page Partenariat** (`partenariat`) : Page de tableau de bord des partenaires avec le shortcode `[partner_dashboard]`
- Les pages sont protégées et accessibles uniquement aux utilisateurs avec les rôles appropriés

#### Protection des pages

Les pages partenaires sont protégées par `admin_lab_protect_partnership_pages()` :
- Redirection automatique pour les utilisateurs non autorisés
- Vérification des rôles Ultimate Member `um_partenaire` et `um_partenaire_plus`

---

### Module Socialls

#### Description

Le module **Socialls** permet de gérer les liens sociaux des utilisateurs avec un système de labels personnalisables et un affichage de type Linktree.

#### Shortcodes

##### 1. `[me5rine_lab_socials]`

Affiche les liens sociaux d'un utilisateur au format Linktree.

**Paramètres :**
- `user_id` (optionnel) : ID de l'utilisateur (défaut: auteur du post actuel)
- `type` (optionnel) : Type de liens (`social` ou `support`, défaut: `social`)
- `label` (optionnel) : Utiliser les labels globaux (`global`) ou personnalisés (`custom`, défaut: `custom`)

**Exemple :**
```
[me5rine_lab_socials user_id="123" type="social" label="custom"]
```

##### 2. `[socials_dashboard]`

Affiche le tableau de bord de gestion des liens sociaux pour l'utilisateur connecté.

**Exemple :**
```
[socials_dashboard]
```

##### 3. `[me5rine_lab_author_socials]`

Affiche les liens sociaux de l'auteur du post actuel avec icônes.

**Paramètres :**
- `size` (optionnel) : Taille des icônes en pixels (défaut: `24`)
- `color` (optionnel) : Couleur des icônes (défaut: `#000000`)
- `layout` (optionnel) : Disposition (`horizontal` ou `vertical`, défaut: `horizontal`)

**Exemple :**
```
[me5rine_lab_author_socials size="32" color="#FF0000" layout="horizontal"]
```

#### Fonctionnalités

- **Gestion des réseaux sociaux** : Support de nombreux réseaux (Twitter, Facebook, Instagram, Discord, Bluesky, Threads, etc.)
- **Labels personnalisables** : Chaque utilisateur peut personnaliser les labels de ses liens
- **Activation/Désactivation** : Les utilisateurs peuvent activer/désactiver leurs liens
- **Ordre personnalisable** : Les utilisateurs peuvent définir l'ordre d'affichage
- **Types de liens** : Distinction entre liens sociaux et liens de support
- **Icônes SVG** : Utilisation d'icônes SVG personnalisées

#### Configuration

1. Aller dans **Me5rine LAB > Social Labels**
2. Configurer les réseaux sociaux disponibles
3. Les utilisateurs peuvent gérer leurs liens via le shortcode `[socials_dashboard]`

#### Pages créées automatiquement

- **Page Socials Dashboard** : Page de gestion des liens sociaux pour les utilisateurs
- Les pages sont protégées et accessibles uniquement aux utilisateurs connectés

#### Réseaux sociaux supportés

Le module supporte de nombreux réseaux sociaux :
- Twitter/X
- Facebook
- Instagram
- Discord
- Bluesky
- Threads
- LinkedIn
- Pinterest
- TikTok
- Twitch
- YouTube
- Et bien d'autres...

Chaque réseau peut être :
- Activé/désactivé individuellement par l'utilisateur
- Personnalisé avec un label personnalisé
- Réordonné selon les préférences de l'utilisateur

#### Icônes SVG

Le module utilise des icônes SVG personnalisées stockées dans `assets/icons/` pour chaque réseau social.

---

### Module Events

#### Description

Le module **Events** permet de transformer des posts WordPress en événements avec gestion de dates, récurrence et types d'événements personnalisables.

#### Fonctionnalités

- **Taxonomie `event_type`** : Classification des événements par type
- **Métadonnées d'événement** : Dates de début, fin, fenêtre de fin
- **Modes de date** : Mode local (heure flottante) ou fixed (UTC ISO)
- **Récurrence** : Support des événements récurrents
- **Colonnes admin** : Colonnes personnalisées dans la liste des posts
- **Meta box** : Interface d'édition des options d'événement
- **Types distants** : Support des types d'événements distants (multi-site)

#### Métadonnées

Chaque événement peut contenir :

- `_event_enabled` : Activation de l'événement
- `_event_mode` : Mode de date (`local` ou `fixed`)
- `_event_title` : Titre personnalisé de l'événement
- `_event_start` / `_event_end` : Dates en UTC ISO (mode fixed)
- `_event_start_local` / `_event_end_local` : Dates locales (mode local)
- `_event_window_end` : Date de fin de fenêtre
- `_event_recurrence` : Configuration de récurrence
- `_event_type_slug` / `_event_type_name` / `_event_type_color` : Type d'événement (mode distant)

#### Configuration

1. Aller dans **Posts > Event Types** pour gérer les types d'événements
2. Éditer un post et utiliser la meta box "Event Options"
3. Activer l'événement et configurer les dates

#### Types d'événements

Les types d'événements peuvent avoir :
- **Image par défaut** : Image affichée pour ce type d'événement (upload via médiathèque WordPress ou URL directe)
- **Couleur** : Couleur associée au type (via color picker WordPress)

**Type par défaut :**
- Un type d'événement "Default" est créé automatiquement lors de l'activation du module

#### Colonnes admin personnalisées

Le module ajoute des colonnes personnalisées dans la liste des posts :
- **Event Enabled** : Statut d'activation de l'événement
- **Event Type** : Type d'événement associé
- **Event Dates** : Dates de début et de fin de l'événement

#### Scripts et styles

- Script JavaScript pour la gestion de la meta box événement
- Support de la médiathèque WordPress pour l'image par défaut des types
- Color picker WordPress pour la couleur des types

---

### Module Remote News

#### Description

Le module **Remote News** permet d'importer et de synchroniser des articles depuis d'autres sites WordPress (même base de données, préfixes différents) ou via des sources externes.

#### Fonctionnalités

- **Custom Post Type `remote_news`** : Type de post dédié aux articles distants
- **Synchronisation automatique** : Synchronisation via CRON ou manuelle
- **Sources multiples** : Gestion de plusieurs sources de données
- **Queries** : Requêtes personnalisées pour filtrer les articles importés
- **Mapping de catégories** : Mapping des catégories distantes vers les catégories locales
- **Anti-duplication** : Système de détection des doublons basé sur `origin_key` et `remote_id`
- **Images distantes** : Support des images distantes (sideload ou URL directe)
- **Permaliens externes** : Les permaliens pointent vers l'URL distante

#### Interface d'administration

L'interface propose plusieurs onglets :

- **Overview** : Vue d'ensemble et synchronisation
- **Sources** : Gestion des sources de données
- **Queries** : Gestion des requêtes de filtrage
- **Category Mapping** : Mapping des catégories

#### Configuration

1. Aller dans **Me5rine LAB > Remote News**
2. Créer une source (table prefix, URL du site)
3. Créer des queries pour filtrer les articles
4. Configurer le mapping des catégories
5. Lancer la synchronisation (manuelle ou automatique)

#### Synchronisation

- **Manuelle** : Bouton "Sync now" dans l'interface
- **Automatique** : Via CRON (configurable)

---

### Module Shortcodes

#### Description

Le module **Shortcodes** permet de créer et gérer des shortcodes personnalisés directement depuis l'interface d'administration WordPress.

#### Shortcode générique

##### `[custom_shortcode]`

Exécute un shortcode personnalisé créé via l'interface d'administration.

**Paramètres :**
- `name` (requis) : Nom du shortcode personnalisé

**Exemple :**
```
[custom_shortcode name="mon_shortcode"]
```

#### Fonctionnalités

- **Création de shortcodes** : Interface d'administration pour créer des shortcodes
- **Code PHP personnalisé** : Possibilité d'écrire du code PHP pour chaque shortcode
- **Paramètres** : Support des paramètres `$atts` et `$content`
- **Gestion** : Liste, édition, suppression des shortcodes
- **Recherche** : Recherche dans les shortcodes

#### Configuration

1. Aller dans **Me5rine LAB > Shortcodes**
2. Cliquer sur "Add a Shortcode"
3. Définir le nom, la description et le code PHP
4. Utiliser le shortcode avec `[custom_shortcode name="nom_du_shortcode"]`

#### Exemple de code

```php
// Dans le champ "Code PHP" du shortcode
$message = isset($atts['message']) ? $atts['message'] : 'Hello World';
return '<div class="custom-message">' . esc_html($message) . '</div>';
```

Utilisation :
```
[custom_shortcode name="mon_shortcode" message="Bonjour"]
```

---

### Module User Management

#### Description

Le module **User Management** gère les slugs utilisateurs, les noms d'affichage et les types de comptes avec synchronisation des rôles.

#### Fonctionnalités

- **Gestion des slugs** : Génération automatique de slugs uniques pour les utilisateurs (format: `slug-id`)
- **Display Name** : Options pour le nom d'affichage (display_name, user_login, first_name, last_name, etc.)
- **Types de comptes** : Système de types de comptes avec synchronisation des rôles
- **Synchronisation** : Synchronisation automatique entre types de comptes et rôles WordPress/Ultimate Member
- **Filtres** : Filtrage des utilisateurs par type de compte dans la liste WordPress
- **Colonnes** : Colonne "Account Type" dans la liste des utilisateurs
- **OpenID** : Support de la synchronisation OpenID pour les types de comptes

#### Interface d'administration

L'interface propose deux onglets :

- **Display & Slug** : Configuration des noms d'affichage et gestion des slugs
- **Account Types** : Gestion des types de comptes

#### Configuration

1. Aller dans **Me5rine LAB > User management**
2. Configurer le type de display name souhaité
3. Créer et gérer les types de comptes
4. Les slugs sont générés automatiquement lors de la création/modification d'utilisateurs

#### Types de display name

- `display_name` : Nom d'affichage WordPress
- `user_login` : Identifiant de connexion
- `first_name` : Prénom
- `last_name` : Nom de famille
- `first_name last_name` : Prénom + Nom
- `last_name first_name` : Nom + Prénom

---

### Module Comparator

#### Description

Le module **Comparator** permet d'afficher des comparateurs de prix pour les jeux vidéo avec intégration de différentes plateformes de vente.

#### Shortcodes

##### 1. `[me5rine_comparator]`

Affiche un comparateur de prix pour un jeu.

**Paramètres :**
- `layout` (optionnel) : Layout (`classic` ou `banner`, défaut: `classic`)
- `game_id` (optionnel) : ID du jeu
- `category_id` (optionnel) : ID de la catégorie

**Exemple :**
```
[me5rine_comparator layout="classic" game_id="123"]
```

##### 2. `[me5rine_comparator_banner]`

Affiche un comparateur de prix au format bannière.

**Paramètres :**
- `game_id` (optionnel) : ID du jeu
- `category_id` (optionnel) : ID de la catégorie

**Exemple :**
```
[me5rine_comparator_banner game_id="123"]
```

#### Blocs Gutenberg

Le module enregistre deux blocs Gutenberg :

- `me5rine-lab/comparator-classic` : Comparateur au format classique
- `me5rine-lab/comparator-banner` : Comparateur au format bannière

#### Fonctionnalités

- **Détection automatique** : Détection du jeu depuis le contexte (post actuel, catégorie)
- **Offres de prix** : Affichage des meilleures offres de prix depuis l'API externe
- **Plateformes** : Support de multiples plateformes de vente (Instant Gaming, etc.)
- **Tracking** : Suivi des clics sur les liens d'achat avec enregistrement en base de données
- **Widgets** : Support des widgets WordPress (legacy) - `Admin_Lab_Comparator_Classic_Widget` et `Admin_Lab_Comparator_Banner_Widget`
- **API REST** : API REST pour la récupération des données de comparaison
- **Mappage de catégories** : Mappage entre catégories WordPress et catégories de jeux dans l'API

#### Configuration

1. Aller dans **Me5rine LAB > Comparator**
2. Configurer les paramètres de l'API dans l'onglet "General" :
   - Mode (auto/manual)
   - API Base URL
   - API Token
   - Frontend Base URL
3. Configurer le mapping des catégories dans l'onglet "Categories"
4. Utiliser les shortcodes ou blocs dans vos pages

#### Interface d'administration

L'interface propose trois onglets :
- **General** : Configuration de l'API et paramètres généraux
- **Categories** : Mapping des catégories WordPress vers les catégories de jeux
- **Stats** : Statistiques des clics avec tableau de données
  - Filtrage et recherche dans les statistiques
  - Options d'écran (nombre de clics par page)
  - Colonnes personnalisables

#### Statistiques et tracking

Le module enregistre tous les clics sur les liens d'achat avec :
- Date et heure du clic
- ID du jeu
- URL du lien cliqué
- Informations sur l'utilisateur (si connecté)

---

## Configuration générale

### Activation des modules

1. Aller dans **Réglages > Me5rine LAB > Settings**
2. Cocher les modules à activer dans la section "Active Modules"
3. Enregistrer les modifications

**Note sur les dépendances :**
- Certains modules nécessitent des plugins complémentaires pour être activés
- Les modules nécessitant Ultimate Member : Giveaways, Partnership, Subscription, Socialls (si User Management n'est pas activé)
- Les modules dépendant du module User Management : Partnership, Subscription, Socialls
- Le module Giveaways nécessite également RafflePress Pro

Si un plugin requis n'est pas installé, le module sera désactivé dans l'interface.

### Hooks personnalisés

Le plugin supporte un fichier de hooks personnalisés :

**Emplacement :** `/wp-content/uploads/me5rine-lab/custom-hooks.php`

Ce fichier est créé automatiquement lors de l'activation du plugin et permet d'ajouter des hooks personnalisés sans modifier le code du plugin.

**Utilisation :**
- Le fichier est chargé automatiquement si il existe
- Un message d'avertissement s'affiche dans l'admin si le fichier est manquant
- Le fichier doit être créé via FTP (le plugin ne peut pas le créer automatiquement pour des raisons de sécurité)

### Préfixes de tables

Le plugin utilise des préfixes configurables pour les tables de base de données :

- **Préfixe site** : Utilise le préfixe WordPress standard (`$wpdb->prefix`) via la constante `ME5RINE_LAB_SITE_PREFIX`
- **Préfixe global** : Préfixe personnalisable via la constante `ME5RINE_LAB_CUSTOM_PREFIX` (défaut: `me5rine_lab_global_`) via la constante `ME5RINE_LAB_GLOBAL_PREFIX`

**Configuration :**
- Les préfixes sont définis dans le fichier principal du plugin (`me5rine-lab.php`)
- Le préfixe global permet de partager des données entre plusieurs sites dans un réseau multisite

### Couleurs Elementor

Le plugin peut synchroniser les couleurs Elementor pour une utilisation dans les modules. Configuration disponible dans **Réglages > Me5rine LAB > Elementor Colors**.

**Fonctionnalités :**
- Configuration de l'ID du kit Elementor
- Extraction automatique des couleurs globales depuis le fichier CSS généré par Elementor
- Génération de variables CSS (`var(--e-global-color-{slug})`) utilisables dans les modules
- Synchronisation côté front-end via JavaScript pour appliquer les couleurs dynamiquement

### API YouTube

Le plugin permet de configurer une clé API YouTube pour récupérer les noms de chaînes depuis les profils utilisateurs.

**Configuration :** **Réglages > Me5rine LAB > API**
- Saisie d'une clé API YouTube Data API v3
- Affichage visuel de la présence/absence de la clé
- Bouton pour afficher/masquer la clé lors de la saisie
- Suppression sécurisée de la clé

### Suppression des données

Le plugin propose une option pour supprimer toutes les données lors de la désinstallation :
- **Option** : `admin_lab_delete_data_on_uninstall`
- Configuration dans **Réglages > Me5rine LAB > General**
- Permet de nettoyer complètement les données du plugin lors de la désinstallation

### Assets et scripts

Le plugin charge automatiquement :
- **Select2** : Bibliothèque pour les champs de sélection avancés (admin)
- **jQuery UI Touch Punch** : Support tactile pour les éléments sortables (admin + front)
- **Choices.js** : Bibliothèque pour les champs de sélection multiples (modules Subscription et Partnership)
- **Styles CSS unifiés** : `admin-unified.css` pour toutes les interfaces admin
- **Couleurs globales** : `global-colors.css` synchronisé avec Elementor

---

## 📚 Documentation

Une documentation complète est disponible dans le dossier [`docs/`](./docs/) :

### Documentation générale
- **[Guide d'intégration thème](./docs/THEME_INTEGRATION.md)** - Guide complet pour intégrer les styles CSS dans votre thème WordPress
- **[Guide d'intégration plugin](./docs/PLUGIN_INTEGRATION.md)** - Guide pour utiliser les classes CSS génériques `me5rine-lab-form-*` dans d'autres plugins/thèmes
- **[Système CSS](./docs/CSS_SYSTEM.md)** - Documentation complète du système de classes CSS
- **[Règles CSS Formulaires](./docs/CSS_RULES.md)** - Règles CSS complètes pour les formulaires à copier dans le thème
- **[Règles CSS Front-End](./docs/FRONT_CSS.md)** - Règles CSS unifiées pour tous les éléments front-end (boutons, cartes, pagination, filtres, etc.)
- **[Règles CSS Admin](./docs/ADMIN_CSS.md)** - Règles CSS pour l'interface d'administration
- **[Règles CSS Tableaux](./docs/TABLE_CSS.md)** - Règles CSS pour les tableaux
- **[Guide de copie plugin](./docs/PLUGIN_COPY_GUIDE.md)** - Guide complet : Fichiers à copier pour réutiliser la structure dans un nouveau plugin

### Documentation par module
- **[Giveaways](./docs/giveaways/)** - Documentation spécifique au module Giveaways
  - [Configuration Ultimate Member](./docs/giveaways/ULTIMATE_MEMBER_SETUP.md)
- **[Socialls](./docs/socialls/)** - Documentation spécifique au module Socialls
- **[Menu Partenaires](./docs/PARTNER_MENU_CSS.md)** - CSS pour le menu partenaires à copier dans le thème

Voir [docs/README.md](./docs/README.md) pour la structure complète de la documentation.

---

## Support

Pour toute question ou problème, contactez l'équipe de développement.

### Modules et dépendances

| Module | Dépendances | Optionnel |
|--------|-------------|-----------|
| Giveaways | RafflePress Pro + Ultimate Member | Non |
| Subscription | Ultimate Member + User Management | Non |
| Partnership | Ultimate Member + User Management | Non |
| Socialls | Ultimate Member + User Management | Non (sans User Management, fonctionnalité limitée) |
| User Management | Ultimate Member | Oui (mais recommandé pour d'autres modules) |
| Events | Aucune | - |
| Remote News | Aucune | - |
| Marketing | Aucune | - |
| Shortcodes | Aucune | - |
| Comparator | Aucune | - |

**Note :** Les modules avec dépendances ne peuvent pas être activés si les plugins requis ne sont pas installés et activés.

### Version

Version actuelle : **1.10.7**

Pour mettre à jour la version dans la documentation, exécutez :
```bash
php generate-docs.php
```
