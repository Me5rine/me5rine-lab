=== Me5rine LAB ===
Contributors: Me5rine LAB
Tags: content management, giveaways, rafflepress, custom post types, marketing, subscription, partnership, socials, events, remote news, shortcodes, user management, comparator
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.10.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress personnalisé pour la gestion de contenu et fonctionnalités avancées.

== Description ==

Me5rine LAB est un plugin WordPress modulaire offrant diverses fonctionnalités pour la gestion de contenu, d'utilisateurs, de partenariats, d'abonnements et bien plus encore.

== Modules disponibles ==

Le plugin propose 10 modules activables indépendamment :

* Giveaways - Gestion de concours avec RafflePress Pro
* Marketing - Campagnes marketing et bannières publicitaires
* Subscription - Système d'abonnements multi-fournisseurs
* Partnership - Gestion des partenariats
* Socialls - Gestion des liens sociaux utilisateurs
* Events - Transformation de posts en événements
* Remote News - Import et synchronisation d'articles distants
* Shortcodes - Création de shortcodes personnalisés
* User Management - Gestion des slugs et noms d'affichage
* Comparator - Comparateur de prix pour jeux vidéo

== Module Giveaways ==

Le module Giveaways permet de gérer des concours en intégration avec RafflePress Pro.

Shortcodes disponibles :
* [custom_rafflepress id="X" min_height="900px"] - Affiche un giveaway avec personnalisations
* [add_giveaway] - Formulaire d'ajout de giveaway
* [edit_giveaway] - Formulaire d'édition de giveaway
* [admin_giveaways] - Tableau de bord des giveaways
* [partner_active_giveaways] - Liste des giveaways actifs d'un partenaire
* [admin_lab_participation_table] - Tableau des participations
* [giveaway_redirect_link] - URL de redirection

== Module Marketing ==

Le module Marketing permet de gérer des campagnes marketing avec bannières et zones publicitaires.

Shortcodes disponibles :
* [marketing_banner format="banner|sidebar|background" slot="1|2|3" image="1"] - Affiche une bannière marketing

== Module Subscription ==

Le module Subscription gère un système complet d'abonnements avec intégration de multiples fournisseurs (Twitch, Patreon, Tipeee, YouTube, Discord, Keycloak).

Fonctionnalités :
* Synchronisation automatique des abonnements
* Gestion des niveaux et rôles utilisateurs
* OAuth pour chaque fournisseur
* CRON pour synchronisation périodique

== Module Partnership ==

Le module Partnership gère les partenariats avec création de rôles Ultimate Member spécifiques.

Shortcodes disponibles :
* [partner_dashboard] - Tableau de bord des partenaires
* [partner_menu] - Menu latéral adaptatif listant les modules accessibles

== Module Socialls ==

Le module Socialls permet de gérer les liens sociaux des utilisateurs avec affichage de type Linktree.

Shortcodes disponibles :
* [me5rine_lab_socials user_id="X" type="social|support" label="custom|global"] - Affiche les liens sociaux
* [socials_dashboard] - Tableau de bord de gestion des liens sociaux
* [me5rine_lab_author_socials size="24" color="#000000" layout="horizontal|vertical"] - Liens sociaux de l'auteur

== Module Events ==

Le module Events permet de transformer des posts WordPress en événements avec gestion de dates et récurrence.

Fonctionnalités :
* Taxonomie event_type pour classifier les événements
* Métadonnées de dates (début, fin, fenêtre)
* Support des événements récurrents
* Modes de date (local ou fixed UTC)

== Module Remote News ==

Le module Remote News permet d'importer et synchroniser des articles depuis d'autres sites WordPress.

Fonctionnalités :
* Synchronisation automatique via CRON
* Sources multiples
* Queries personnalisées
* Mapping de catégories
* Anti-duplication

== Module Shortcodes ==

Le module Shortcodes permet de créer et gérer des shortcodes personnalisés depuis l'interface d'administration.

Shortcodes disponibles :
* [custom_shortcode name="nom_du_shortcode"] - Exécute un shortcode personnalisé

== Module User Management ==

Le module User Management gère les slugs utilisateurs, les noms d'affichage et les types de comptes.

Fonctionnalités :
* Génération automatique de slugs uniques
* Options de display name
* Types de comptes avec synchronisation des rôles
* Filtrage par type de compte

== Module Comparator ==

Le module Comparator permet d'afficher des comparateurs de prix pour les jeux vidéo.

Shortcodes disponibles :
* [me5rine_comparator layout="classic|banner" game_id="X" category_id="Y"] - Comparateur de prix
* [me5rine_comparator_banner game_id="X" category_id="Y"] - Comparateur au format bannière

Blocs Gutenberg :
* me5rine-lab/comparator-classic
* me5rine-lab/comparator-banner

== Installation ==

1. Télécharger et activer le plugin
2. Aller dans Réglages > Me5rine LAB
3. Activer les modules souhaités
4. Configurer chaque module selon vos besoins

== Prérequis ==

* WordPress 5.0 ou supérieur
* PHP 7.4 ou supérieur
* RafflePress Pro (pour le module Giveaways)
* Ultimate Member (pour les modules Subscription et Partnership, optionnel pour User Management)

== Configuration ==

Chaque module peut être activé ou désactivé indépendamment depuis Réglages > Me5rine LAB.

Voir README.md pour la documentation complète de chaque module.

== Changelog ==

= 1.10.1 =
* Documentation complète de tous les modules
* Améliorations diverses

= 1.9.5 =
* Module Giveaways avec intégration RafflePress
* Shortcodes personnalisés
* Personnalisation des iframes
* Gestion des partenaires
* Système de hauteur dynamique pour les iframes
* Personnalisation des blocs de connexion
* Styles personnalisés pour Discord, Bluesky, Threads
