<?php
/**
 * Module Affiliate Links – Me5rine LAB
 * Version modifiée de Content Egg : tables dédiées (catégories + produits), récupération par mot-clé (parsers CE), pas de CPT, pas de licence.
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('affiliate_links', $active_modules, true)) {
    return;
}

define('ME5RINE_LAB_AFFILIATE_LINKS_PATH', ME5RINE_LAB_MODULES . 'affiliate-links/');
define('ME5RINE_LAB_AFFILIATE_LINKS_URL', ME5RINE_LAB_URL . 'modules/affiliate-links/');

// Charger Content Egg comme moteur (parsers, modules, réglages) – pas de dépendance externe, le code est dans le plugin
$content_egg_file = ME5RINE_LAB_PATH . 'modules/content-egg/content-egg.php';
if (file_exists($content_egg_file)) {
    require_once $content_egg_file;
    add_action('init', 'admin_lab_affiliate_links_after_content_egg', 8);
}

require_once ME5RINE_LAB_AFFILIATE_LINKS_PATH . 'includes/affiliate-links-helpers.php';
require_once ME5RINE_LAB_AFFILIATE_LINKS_PATH . 'includes/affiliate-links-shortcode.php';
if (file_exists($content_egg_file)) {
    require_once ME5RINE_LAB_AFFILIATE_LINKS_PATH . 'includes/affiliate-links-content-egg-fetch.php';
}

if (is_admin()) {
    require_once ME5RINE_LAB_AFFILIATE_LINKS_PATH . 'admin/affiliate-links-admin.php';
}

add_action('init', 'admin_lab_affiliate_links_register_shortcode');
add_action('wp_enqueue_scripts', 'admin_lab_affiliate_links_register_assets');

function admin_lab_affiliate_links_after_content_egg() {
    // Activation CE au premier lancement (tables CE, options)
    if (class_exists('\ContentEgg\application\Plugin', false)) {
        $ce_slug = \ContentEgg\application\Plugin::slug;
        if ((int) get_option($ce_slug . '_db_version', 0) < (int) \ContentEgg\application\Plugin::db_version) {
            \ContentEgg\application\Installer::activate();
        }
    }
}

function admin_lab_affiliate_links_register_assets() {
    wp_register_style(
        'admin-lab-affiliate-links',
        ME5RINE_LAB_AFFILIATE_LINKS_URL . 'assets/affiliate-links.css',
        [],
        ME5RINE_LAB_VERSION
    );
}
