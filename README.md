# Me5rine LAB - Documentation

<!-- Version: 1.10.1 - Généré automatiquement - Utilisez generate-docs.php pour mettre à jour -->

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

- Personnalisation de l'iframe avec hauteur dynamique
- Bloc de connexion personnalisé selon l'état de l'utilisateur
- Styles personnalisés pour Discord, Bluesky, Threads
- Synchronisation automatique avec RafflePress
- Gestion des partenaires et des participations

#### Configuration

1. Aller dans **Réglages > Me5rine LAB**
2. Activer le module **Giveaways**
3. Les pages nécessaires seront créées automatiquement

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
3. Uploader les images pour chaque zone
4. Assigner la campagne aux zones souhaitées
5. Utiliser le shortcode `[marketing_banner]` dans vos templates

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
3. Créer les canaux/serveurs
4. Définir les types d'abonnements et niveaux
5. Configurer la synchronisation automatique

#### Synchronisation

La synchronisation peut être effectuée :
- **Manuellement** : Via l'interface d'administration
- **Automatiquement** : Via CRON (configurable dans les paramètres)

---

### Module Partnership

#### Description

Le module **Partnership** gère les partenariats avec création de rôles Ultimate Member spécifiques et un tableau de bord pour les partenaires.

#### Shortcodes

##### `[partner_dashboard]`

Affiche le tableau de bord des partenaires avec statistiques sur les giveaways.

**Exemple :**
```
[partner_dashboard]
```

#### Fonctionnalités

- **Rôles Ultimate Member** : Création automatique des rôles `um_partenaire` et `um_partenaire_plus`
- **Types de comptes** : Gestion des types de comptes "partenaire" et "partenaire_plus"
- **Tableau de bord** : Interface dédiée pour les partenaires
- **Statistiques** : Statistiques sur les giveaways (participants, entrées, etc.)
- **Pages automatiques** : Création automatique de la page de tableau de bord

#### Configuration

1. Aller dans **Me5rine LAB > Partnership**
2. Le module crée automatiquement les rôles et types de comptes
3. La page de tableau de bord est créée automatiquement avec le slug `partenariat`

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
- **Image par défaut** : Image affichée pour ce type d'événement
- **Couleur** : Couleur associée au type (via color picker)

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
- **Offres de prix** : Affichage des meilleures offres de prix
- **Plateformes** : Support de multiples plateformes de vente
- **Tracking** : Suivi des clics sur les liens d'achat
- **Widgets** : Support des widgets (legacy)
- **API** : API REST pour la récupération des données de comparaison

#### Configuration

1. Aller dans **Me5rine LAB > Comparator**
2. Configurer les paramètres de l'API
3. Utiliser les shortcodes ou blocs dans vos pages

---

## Configuration générale

### Activation des modules

1. Aller dans **Réglages > Me5rine LAB**
2. Cocher les modules à activer
3. Enregistrer les modifications

### Hooks personnalisés

Le plugin supporte un fichier de hooks personnalisés :

**Emplacement :** `/wp-content/uploads/me5rine-lab/custom-hooks.php`

Ce fichier est créé automatiquement lors de l'activation du plugin et permet d'ajouter des hooks personnalisés sans modifier le code du plugin.

### Préfixes de tables

Le plugin utilise des préfixes configurables pour les tables de base de données :

- **Préfixe site** : Utilise le préfixe WordPress standard (`$wpdb->prefix`)
- **Préfixe global** : Préfixe personnalisable via la constante `ME5RINE_LAB_CUSTOM_PREFIX` (défaut: `me5rine_lab_global_`)

### Couleurs Elementor

Le plugin peut synchroniser les couleurs Elementor pour une utilisation dans les modules. Configuration disponible dans **Réglages > Me5rine LAB > Elementor Colors**.

---

## Support

Pour toute question ou problème, contactez l'équipe de développement.

### Modules et dépendances

| Module | Dépendances |
|--------|-------------|
| Giveaways | RafflePress Pro |
| Subscription | Ultimate Member (pour les rôles) |
| Partnership | Ultimate Member (pour les rôles) |
| User Management | Ultimate Member (optionnel) |
| Events | Aucune |
| Remote News | Aucune |
| Marketing | Aucune |
| Socialls | Aucune |
| Shortcodes | Aucune |
| Comparator | Aucune |

### Version

Version actuelle : **1.10.1**

Pour mettre à jour la version dans la documentation, exécutez :
```bash
php generate-docs.php
```
