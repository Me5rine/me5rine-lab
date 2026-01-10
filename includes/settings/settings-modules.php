<?php
// File: /includes/settings/settings-modules.php

if (!defined('ABSPATH')) exit;

/**
 * Liste des modules disponibles dans Me5rine LAB
 * Slug => chemin relatif depuis modules/
 */
function admin_lab_get_modules_registry() {
    return [
        'marketing_campaigns' => 'marketing/marketing.php',
        'giveaways'           => 'giveaways/giveaways.php',
        'subscription'        => 'subscription/subscription.php',
        'user_management'     => 'user-management/user-management.php',
        'shortcodes'          => 'shortcodes/shortcodes.php',
        'socialls'            => 'socialls/socialls.php',
        'events'              => 'events/events.php',
        'partnership'         => 'partnership/partnership.php',
        'remote_news'         => 'remote-news/remote-news.php',
        'comparator'          => 'comparator/comparator.php',
        'keycloak_account_pages' => 'keycloak-account-pages/keycloak-account-pages.php'
    ];
}
