# Me5rine LAB - Documentation

<!-- Version: 1.9.0 - Généré automatiquement - Utilisez generate-docs.php pour mettre à jour -->

Plugin WordPress personnalisé pour la gestion de contenu et fonctionnalités avancées.

> **Note** : Pour mettre à jour la version dans cette documentation, exécutez `php generate-docs.php` depuis le répertoire du plugin. La version est automatiquement extraite de `me5rine-lab.php`.

## Table des matières

- [Module Giveaways](#module-giveaways)
  - [Description](#description)
  - [Shortcodes](#shortcodes)
  - [Fonctionnalités](#fonctionnalités)
  - [Configuration](#configuration)

---

## Module Giveaways

### Description

Le module **Giveaways** permet de gérer des concours (giveaways) en intégration avec RafflePress Pro. Il offre une interface complète pour créer, gérer et afficher des concours avec des personnalisations spécifiques à Me5rine LAB.

### Prérequis

- **RafflePress Pro** : Le plugin doit être installé et activé
- **Module activé** : Le module doit être activé dans les paramètres du plugin

### Shortcodes

#### 1. `[custom_rafflepress]`

Affiche un giveaway RafflePress avec des personnalisations Me5rine LAB intégrées.

**Paramètres :**
- `id` (requis) : L'ID du giveaway RafflePress
- `min_height` (optionnel) : Hauteur minimale de l'iframe en pixels (défaut: `900px`)

**Exemple :**
```
[custom_rafflepress id="18" min_height="900px"]
```

**Fonctionnalités :**
- Affiche le giveaway dans une iframe personnalisée
- Hauteur dynamique qui s'adapte au contenu
- Bloc de connexion personnalisé (login/register ou message de bienvenue)
- Styles personnalisés pour Discord, Bluesky, Threads
- Personnalisation des labels et textes selon le partenaire
- Remplacement automatique du séparateur d'action automatique

**Personnalisations automatiques :**
- Bloc de connexion adapté selon l'état de connexion de l'utilisateur
- Messages personnalisés avec les prix du giveaway
- Styles des boutons Discord/Bluesky/Threads avec les couleurs Elementor
- Labels personnalisés selon le partenaire ou le site

#### 2. `[add_giveaway]`

Affiche le formulaire d'ajout d'un nouveau giveaway pour les partenaires.

**Exemple :**
```
[add_giveaway]
```

**Fonctionnalités :**
- Formulaire complet de création de giveaway
- Upload d'images pour les prix
- Gestion des dates de début et de fin
- Synchronisation automatique avec RafflePress

#### 3. `[edit_giveaway]`

Affiche le formulaire d'édition d'un giveaway existant.

**Exemple :**
```
[edit_giveaway]
```

**Fonctionnalités :**
- Édition des informations du giveaway
- Modification des prix et dates
- Mise à jour de la synchronisation avec RafflePress

#### 4. `[admin_giveaways]`

Affiche le tableau de bord des giveaways pour un partenaire.

**Exemple :**
```
[admin_giveaways]
```

**Fonctionnalités :**
- Liste de tous les giveaways du partenaire connecté
- Statistiques et informations sur chaque giveaway
- Actions de gestion (éditer, activer/désactiver, etc.)

#### 5. `[partner_active_giveaways]`

Affiche la liste des giveaways actifs d'un partenaire sur sa page de profil.

**Exemple :**
```
[partner_active_giveaways]
```

**Fonctionnalités :**
- Affiche uniquement les giveaways actifs (en cours)
- Informations sur les prix, participants, temps restant
- Liens vers les pages de giveaway
- Utilisé dans les profils Ultimate Member

#### 6. `[admin_lab_participation_table]`

Affiche le tableau des participations d'un utilisateur.

**Exemple :**
```
[admin_lab_participation_table]
```

**Fonctionnalités :**
- Liste des participations de l'utilisateur connecté
- Statut de chaque participation
- Historique des actions effectuées

#### 7. `[giveaway_redirect_link]`

Génère une URL de redirection vers la page d'ajout de giveaway.

**Exemple :**
```
<a href="[giveaway_redirect_link]">Créer un giveaway</a>
```

**Fonctionnalités :**
- Génère une URL avec paramètre de redirection
- Redirige vers la page d'ajout après création

### Fonctionnalités

#### Personnalisation de l'iframe

Le shortcode `[custom_rafflepress]` génère une iframe personnalisée avec :

1. **Hauteur dynamique** : La hauteur s'adapte automatiquement au contenu
   - Calcul basé sur le dernier élément visible
   - Mise à jour automatique lors de l'ouverture/fermeture d'accordéons
   - Ajout de 5px pour éviter la coupure des bordures

2. **Bloc de connexion personnalisé** :
   - **Utilisateur connecté** : Message de bienvenue avec nom et informations sur les prix
   - **Utilisateur non connecté** : Bloc avec boutons de connexion et d'inscription
   - Styles personnalisés avec les couleurs Elementor

3. **Personnalisation des actions** :
   - **Discord** : Styles et labels personnalisés
   - **Bluesky** : Styles et labels personnalisés
   - **Threads** : Styles et labels personnalisés
   - Labels adaptés selon le partenaire ou le site

4. **Remplacement du séparateur** :
   - Le séparateur d'action automatique est remplacé par un message personnalisé

#### Synchronisation avec RafflePress

- Synchronisation automatique lors de la création/édition
- Création automatique du giveaway dans RafflePress
- Mise à jour des métadonnées
- Gestion des dates de début et de fin

#### Gestion des partenaires

- Association d'un giveaway à un partenaire
- Affichage conditionnel selon le partenaire
- Personnalisation des labels selon le partenaire

### Configuration

#### Activation du module

1. Aller dans **Réglages > Me5rine LAB**
2. Activer le module **Giveaways**
3. Les pages nécessaires seront créées automatiquement

#### Pages créées automatiquement

- Page de tableau de bord des giveaways
- Page d'ajout de giveaway
- Page d'édition de giveaway

#### Métadonnées des giveaways

Chaque giveaway (custom post type `giveaway`) contient :

- `_giveaway_rafflepress_id` : ID du giveaway dans RafflePress
- `_giveaway_partner_id` : ID du partenaire associé
- `_giveaway_start_date` : Date de début
- `_giveaway_end_date` : Date de fin
- `_giveaway_participants_count` : Nombre de participants
- Autres métadonnées spécifiques

#### Taxonomies

- `giveaway_rewards` : Taxonomie pour les récompenses/prix

### Intégration avec RafflePress

Le module utilise le template RafflePress original mais avec des modifications personnalisées :

- **Route personnalisée** : `?me5rine_lab_giveaway_render=1&me5rine_lab_giveaway_id=X`
- **Template personnalisé** : `templates/custom-rafflepress-giveaway.php`
- **Modifications injectées** : Styles et scripts personnalisés ajoutés au template original

### Personnalisation des couleurs

Les couleurs sont récupérées depuis Elementor si disponible :

- `primary` : Couleur principale (boutons, liens)
- `secondary` : Couleur secondaire (hover)
- `338f618` : Couleur du texte sur les boutons
- `3d5ef52` : Couleur de fond du bloc de connexion

### Traductions

Le module utilise les traductions WordPress standard. Les textes personnalisés sont définis dans :
- `modules/giveaways/giveaways.php` (fonction `enqueue_rafflepress_login_script`)

### Notes techniques

- **Pas d'iframe-resizer** : Le système utilise un calcul de hauteur personnalisé via `postMessage`
- **Performance** : Calcul de hauteur optimisé avec debounce et observateurs ciblés
- **Compatibilité** : Utilise le template RafflePress original pour maintenir la compatibilité

### Hooks et filtres disponibles

#### Actions (Actions)

- `admin_lab_giveaways_module_activated` : Déclenché lors de l'activation du module
- `admin_lab_giveaways_module_desactivated` : Déclenché lors de la désactivation du module

#### Fonctions utilitaires

- `admin_lab_get_post_id_from_rafflepress($rafflepress_id)` : Récupère l'ID du post WordPress associé à un giveaway RafflePress
- `admin_lab_user_is_partner($user_id)` : Vérifie si un utilisateur est un partenaire
- `admin_lab_format_time_remaining($end_ts, $now_ts)` : Formate le temps restant d'un giveaway

### Structure des fichiers

```
modules/giveaways/
├── admin-filters/          # Filtres et colonnes admin
├── elementor/              # Intégration Elementor
├── front/                  # Fonctionnalités front-end
├── functions/              # Fonctions principales
│   ├── shortcode-custom-rafflepress.php    # Shortcode principal
│   ├── giveaways-custom-render-route.php   # Route personnalisée
│   └── ...
├── includes/               # Templates et formulaires
├── register/               # Enregistrement des types
├── shortcodes/             # Autres shortcodes
└── templates/             # Templates personnalisés
    └── custom-rafflepress-giveaway.php     # Template iframe personnalisé
```

### Base de données

Le module utilise les tables suivantes de RafflePress :

- `wp_rafflepress_giveaways` : Table principale des giveaways
- `wp_rafflepress_entries` : Entrées des participants
- `wp_rafflepress_contestants` : Participants confirmés

Et les métadonnées WordPress :

- Custom Post Type : `giveaway`
- Taxonomie : `giveaway_rewards`
- Meta keys : `_giveaway_rafflepress_id`, `_giveaway_partner_id`, etc.

### Custom Post Type

Le module crée un custom post type `giveaway` avec les caractéristiques suivantes :

- **Slug** : `giveaway`
- **Supports** : title, editor, thumbnail, custom-fields
- **Taxonomies** : `giveaway_rewards`
- **Capabilities** : Gestion des permissions personnalisées

### Métadonnées importantes

Chaque giveaway stocke les métadonnées suivantes :

| Meta Key | Description | Type |
|----------|-------------|------|
| `_giveaway_rafflepress_id` | ID du giveaway dans RafflePress | integer |
| `_giveaway_partner_id` | ID du partenaire associé | integer |
| `_giveaway_start_date` | Date de début du giveaway | datetime |
| `_giveaway_end_date` | Date de fin du giveaway | datetime |
| `_giveaway_participants_count` | Nombre de participants | integer |
| `_giveaway_status` | Statut du giveaway | string |

### Routes personnalisées

Le module crée une route personnalisée pour le rendu des giveaways :

- **URL** : `?me5rine_lab_giveaway_render=1&me5rine_lab_giveaway_id=X`
- **Template** : `templates/custom-rafflepress-giveaway.php`
- **Priorité** : 5 (intercepte avant RafflePress)

### Système de hauteur dynamique

Le système de calcul de hauteur fonctionne ainsi :

1. **Dans l'iframe** : Script JavaScript calcule la hauteur du contenu
2. **Communication** : Envoi de la hauteur au parent via `postMessage`
3. **Dans le parent** : Réception et application de la hauteur à l'iframe
4. **Mise à jour** : Recalcul automatique lors des changements (accordéons, etc.)

**Méthode de calcul** :
- Parcourt les enfants directs du body
- Trouve le dernier élément visible
- Calcule la position absolue (bottom + scrollTop)
- Ajoute 5px pour éviter la coupure des bordures

### Dépannage

#### L'iframe ne s'affiche pas correctement

1. Vérifier que RafflePress Pro est installé et activé
2. Vérifier que le module Giveaways est activé
3. Vérifier que l'ID du giveaway est correct
4. Vérifier les logs PHP pour les erreurs

#### La hauteur de l'iframe est incorrecte

1. Vérifier la console JavaScript pour les erreurs
2. Vérifier que le script de calcul de hauteur est bien chargé
3. Vider le cache du navigateur
4. Vérifier que `postMessage` fonctionne (console : messages depuis l'iframe)

#### Les personnalisations ne s'appliquent pas

1. Vérifier que les couleurs Elementor sont bien configurées
2. Vérifier que les traductions sont bien chargées
3. Vérifier la console JavaScript pour les erreurs
4. Vérifier que `me5rineLabData` est bien défini dans la console

#### Erreurs de syntaxe PHP

1. Vérifier les logs PHP pour les erreurs de syntaxe
2. Vérifier que tous les apostrophes sont échappées dans les chaînes JavaScript
3. Vérifier la compatibilité PHP (minimum 7.4 recommandé)

### Exemples d'utilisation

#### Exemple 1 : Afficher un giveaway simple

```
[custom_rafflepress id="18"]
```

#### Exemple 2 : Afficher un giveaway avec hauteur personnalisée

```
[custom_rafflepress id="18" min_height="1200px"]
```

#### Exemple 3 : Page de création de giveaway

Créer une page avec le shortcode `[add_giveaway]` pour permettre aux partenaires de créer des giveaways.

#### Exemple 4 : Tableau de bord partenaire

Créer une page avec le shortcode `[admin_giveaways]` pour afficher tous les giveaways d'un partenaire.

#### Exemple 5 : Profil partenaire

Ajouter `[partner_active_giveaways]` dans un template Ultimate Member pour afficher les giveaways actifs.

### Sécurité

- Toutes les entrées utilisateur sont sanitizées
- Vérification des nonces pour les actions sensibles
- Vérification des permissions utilisateur
- Échappement des sorties HTML

### Performance

- Calcul de hauteur optimisé avec debounce
- Observateurs ciblés (MutationObserver, ResizeObserver)
- Pas de recalculs inutiles
- Cache des données utilisateur

---

## Support

Pour toute question ou problème, contactez l'équipe de développement.

