<?php
// File: modules/subscription/subscription.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('subscription', $active_modules, true)) return;

// Chargement de l’interface d’administration
if (is_admin()) {
    include_once __DIR__ . '/admin/subscription-admin-ui.php';
}

// Setup functions (roles, account types, pages)
require_once __DIR__ . '/functions/subscription-setup.php';

// Utilities (must be loaded early)
require_once __DIR__ . '/functions/utils/subscription-encryption.php';

// CRUD operations
require_once __DIR__ . '/functions/crud/subscription-providers.php';
require_once __DIR__ . '/functions/crud/subscription-levels.php';
require_once __DIR__ . '/functions/crud/subscription-accounts.php';
require_once __DIR__ . '/functions/crud/subscription-channels.php';
require_once __DIR__ . '/functions/crud/subscription-tiers.php';
require_once __DIR__ . '/functions/crud/subscription-provider-account-types.php';

// User display functions
require_once __DIR__ . '/functions/subscription-user-display.php';
require_once __DIR__ . '/functions/subscription-ultimate-member.php';

// Initialization
require_once __DIR__ . '/functions/init/subscription-default-types.php';
require_once __DIR__ . '/functions/init/subscription-cleanup-types.php';

// OAuth (must be loaded first as they define shared functions)
require_once __DIR__ . '/functions/oauth/subscription-oauth.php';
require_once __DIR__ . '/functions/oauth/subscription-oauth-generic.php';
require_once __DIR__ . '/functions/oauth/subscription-twitch-oauth.php';
require_once __DIR__ . '/functions/oauth/subscription-youtube-oauth.php';

// Synchronization
require_once __DIR__ . '/functions/sync/subscription-sync-levels.php';
require_once __DIR__ . '/functions/sync/subscription-sync.php'; // Orchestrator
require_once __DIR__ . '/functions/sync/subscription-sync-patreon.php';
require_once __DIR__ . '/functions/sync/subscription-sync-tipeee.php';
require_once __DIR__ . '/functions/sync/subscription-sync-twitch.php';
require_once __DIR__ . '/functions/sync/subscription-sync-discord.php';
require_once __DIR__ . '/functions/providers/subscription-youtube-members.php'; // YouTube members API
require_once __DIR__ . '/functions/sync/subscription-sync-youtube.php';
require_once __DIR__ . '/functions/sync/subscription-sync-youtube-fallback.php'; // YouTube (No API)
require_once __DIR__ . '/functions/sync/subscription-sync-keycloak.php';
require_once __DIR__ . '/functions/sync/subscription-sync-cron.php'; // Automatic sync scheduling

// Activation : création des rôles et types
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('subscription', $active, true)) {
        if (!get_option('um_role_sub_meta')) {
            admin_lab_create_um_subscription_roles_if_missing();
        }

        admin_lab_register_subscription_account_types(); // Ajouter les types de comptes "sub" et "premium"
        admin_lab_init_default_subscription_types(); // Initialize default subscription types (only tier1, tier2, tier3 for Twitch and booster for Discord)
        // Cleanup old subscription types (remove tier1_payant, tier1_gift, etc. and default types for Patreon/Tipeee/YouTube)
        if (function_exists('admin_lab_cleanup_subscription_types')) {
            admin_lab_cleanup_subscription_types();
        }
        do_action('admin_lab_subscription_module_activated');
    }
});

// Désactivation : suppression des rôles et types
add_action('admin_lab_subscription_module_desactivated', 'admin_lab_delete_um_subscription_roles');
add_action('admin_lab_subscription_module_desactivated', 'admin_lab_unregister_subscription_account_types');

// Protection front-end des pages souscription
add_action('template_redirect', 'admin_lab_protect_subscription_pages');

// Enqueue Choices.js
function admin_lab_enqueue_choices_js_subscription() {
    wp_enqueue_style('choices-css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'admin_lab_enqueue_choices_js_subscription');
