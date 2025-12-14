<?php
/**
 * Script de génération automatique de la documentation
 * Utilise la version du plugin définie dans me5rine-lab.php
 * 
 * Usage: php generate-docs.php
 */

// Charger WordPress si nécessaire (pour utiliser get_file_data)
if (!function_exists('get_file_data')) {
    // Essayer de charger WordPress
    $wp_load = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        // Si WordPress n'est pas disponible, parser manuellement le fichier
        $plugin_file = __DIR__ . '/me5rine-lab.php';
        if (file_exists($plugin_file)) {
            $file_content = file_get_contents($plugin_file);
            if (preg_match('/Version:\s*([0-9.]+)/i', $file_content, $matches)) {
                $version = $matches[1];
            } else {
                $version = '1.0.0';
            }
        } else {
            die("Erreur: Fichier me5rine-lab.php introuvable.\n");
        }
    }
}

// Charger la version du plugin
if (!isset($version)) {
    $plugin_file = __DIR__ . '/me5rine-lab.php';
    if (function_exists('get_file_data')) {
        $plugin_data = get_file_data($plugin_file, ['Version' => 'Version'], false);
        $version = $plugin_data['Version'] ?? '1.0.0';
    } else {
        // Parser manuellement
        $file_content = file_get_contents($plugin_file);
        if (preg_match('/Version:\s*([0-9.]+)/i', $file_content, $matches)) {
            $version = $matches[1];
        } else {
            $version = '1.0.0';
        }
    }
}

// Template pour readme.txt
$readme_template = <<<'README'
=== Me5rine LAB ===
Contributors: Me5rine LAB
Tags: content management, giveaways, rafflepress, custom post types
Requires at least: 5.0
Tested up to: 6.4
Stable tag: {VERSION}
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress personnalisé pour la gestion de contenu et fonctionnalités avancées.

== Description ==

Me5rine LAB est un plugin WordPress modulaire offrant diverses fonctionnalités pour la gestion de contenu.

== Module Giveaways ==

Le module Giveaways permet de gérer des concours en intégration avec RafflePress Pro.

== Shortcodes disponibles ==

* [custom_rafflepress id="X" min_height="900px"] - Affiche un giveaway avec personnalisations
* [add_giveaway] - Formulaire d'ajout de giveaway
* [edit_giveaway] - Formulaire d'édition de giveaway
* [admin_giveaways] - Tableau de bord des giveaways
* [partner_active_giveaways] - Liste des giveaways actifs d'un partenaire
* [admin_lab_participation_table] - Tableau des participations
* [giveaway_redirect_link] - URL de redirection

Voir README.md pour la documentation complète.

== Installation ==

1. Télécharger et activer le plugin
2. Activer le module Giveaways dans les réglages
3. Configurer les couleurs Elementor si nécessaire

== Changelog ==

= {VERSION} =
* Module Giveaways avec intégration RafflePress
* Shortcodes personnalisés
* Personnalisation des iframes
* Gestion des partenaires
* Système de hauteur dynamique pour les iframes
* Personnalisation des blocs de connexion
* Styles personnalisés pour Discord, Bluesky, Threads

README;

// Remplacer la version dans le template
$readme_content = str_replace('{VERSION}', $version, $readme_template);

// Écrire le fichier readme.txt
file_put_contents(__DIR__ . '/readme.txt', $readme_content);

// Mettre à jour le README.md avec la version
$readme_md_file = __DIR__ . '/README.md';
if (file_exists($readme_md_file)) {
    $readme_md = file_get_contents($readme_md_file);
    
    // Mettre à jour la version dans le commentaire HTML
    if (preg_match('/<!-- Version: ([^ ]+) -/', $readme_md)) {
        $readme_md = preg_replace('/<!-- Version: [^ ]+ -/', "<!-- Version: {$version} -", $readme_md);
    } else {
        // Ajouter le commentaire de version en haut
        $version_note = "<!-- Version: {$version} - Généré automatiquement - Utilisez generate-docs.php pour mettre à jour -->\n\n";
        $readme_md = $version_note . $readme_md;
    }
    
    file_put_contents($readme_md_file, $readme_md);
} else {
    echo "Avertissement: README.md introuvable, ignoré.\n";
}

echo "Documentation générée avec succès !\n";
echo "Version du plugin : {$version}\n";
echo "- readme.txt mis à jour\n";
echo "- README.md mis à jour\n";

