<?php
// File: modules/game-servers/game-servers.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('game_servers', $active_modules, true)) return;

// Chargement des fonctions du module
require_once __DIR__ . '/functions/game-servers-helpers.php';
require_once __DIR__ . '/functions/game-servers-crud.php';
require_once __DIR__ . '/functions/game-servers-pages.php';
require_once __DIR__ . '/functions/game-servers-minecraft-auth.php';
require_once __DIR__ . '/functions/game-servers-minecraft-crud.php';
require_once __DIR__ . '/functions/game-servers-stats-fetcher.php';

// Chargement de l'API REST
require_once __DIR__ . '/api/game-servers-rest-api.php';

// Chargement de l'interface d'administration
if (is_admin()) {
    include_once __DIR__ . '/admin/game-servers-admin-ui.php';
}

// Chargement des shortcodes
require_once __DIR__ . '/shortcodes/game-servers-shortcodes.php';

// Activation : création des tables (gérée par le système centralisé) et des pages
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('game_servers', $active, true)) {
        admin_lab_game_servers_create_pages();
        do_action('admin_lab_game_servers_module_activated');
    }
});

// Migration : ajouter le champ enable_subscriber_whitelist si manquant
add_action('admin_lab_game_servers_module_activated', function () {
    if (!class_exists('Admin_Lab_DB')) return;
    $db = Admin_Lab_DB::getInstance();
    $db->createGameServersTable(); // Cette fonction inclut maintenant la migration
}, 20);

// Cron : récupérer les stats depuis le mod toutes les minutes
add_action('admin_lab_game_servers_fetch_stats_cron', 'admin_lab_game_servers_update_all_stats_from_mod');

// Programmer le cron si pas déjà programmé
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (!in_array('game_servers', $active, true)) {
        return;
    }
    
    if (!wp_next_scheduled('admin_lab_game_servers_fetch_stats_cron')) {
        wp_schedule_event(time(), 'every_minute', 'admin_lab_game_servers_fetch_stats_cron');
    }
});

// Créer l'intervalle "every_minute" si n'existe pas
add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['every_minute'])) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display' => __('Every Minute', 'me5rine-lab'),
        ];
    }
    return $schedules;
});

// Désactivation : suppression des pages et nettoyage du cron
add_action('admin_lab_game_servers_module_desactivated', 'admin_lab_game_servers_delete_pages');
add_action('admin_lab_game_servers_module_desactivated', function () {
    $timestamp = wp_next_scheduled('admin_lab_game_servers_fetch_stats_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'admin_lab_game_servers_fetch_stats_cron');
    }
});

// Enregistrement des shortcodes
add_action('init', function() {
    if (function_exists('admin_lab_game_servers_register_shortcodes')) {
        admin_lab_game_servers_register_shortcodes();
    }
});

// Afficher la section "Comptes de jeu" (Minecraft) dans l'onglet Connexions / Comptes liés (Keycloak Account Pages)
add_action('admin_lab_kap_after_connections', 'admin_lab_game_servers_render_linked_game_accounts_section');

// Enqueue des styles et scripts
add_action('wp_enqueue_scripts', function () {
    // Le template utilise les styles globaux, pas besoin de CSS custom supplémentaire
    // wp_enqueue_style(
    //     'admin-lab-game-servers',
    //     ME5RINE_LAB_URL . 'assets/css/game-servers.css',
    //     [],
    //     ME5RINE_LAB_VERSION
    // );
});

// Admin: pleine largeur pour le bloc Microsoft OAuth (Minecraft Settings)
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'me5rine-lab_page_admin-lab-game-servers') {
        return;
    }
    $css = '
        .admin-lab-minecraft-oauth-fullwidth { max-width: none; }
        .admin-lab-minecraft-oauth-fullwidth .card { max-width: none; width: 100%; box-sizing: border-box; }
    ';
    wp_register_style('admin-lab-game-servers-minecraft-fullwidth', false, [], ME5RINE_LAB_VERSION);
    wp_enqueue_style('admin-lab-game-servers-minecraft-fullwidth');
    wp_add_inline_style('admin-lab-game-servers-minecraft-fullwidth', $css);
}, 20);

