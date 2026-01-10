<?php
// File: modules/giveaways/shortcodes/giveaways-shortcodes.php

if (!defined('ABSPATH')) exit;

// Fonction helper pour enqueue les scripts/styles du formulaire de campagne
function enqueue_campaign_form_assets() {
    static $enqueued = false;
    if ($enqueued) return; // Éviter les doublons
    
    $base_url = plugin_dir_url(dirname(dirname(__DIR__))) . 'assets/';
    
    // Charger Select2 pour le champ pays multiple
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
    
    wp_enqueue_script('admin-lab-campaign-form', $base_url . 'js/giveaway-form-campaign.js', ['jquery', 'select2-js'], ME5RINE_LAB_VERSION, true);
    wp_enqueue_style('admin-lab-campaign-style', $base_url . 'css/giveaway-form-campaign.css', [], ME5RINE_LAB_VERSION);
    wp_localize_script('admin-lab-campaign-form', 'mlab_i18n', [
        'name' => __('Name', 'me5rine-lab'),
        'description' => __('Description', 'me5rine-lab'),
        'image' => __('Image (upload)', 'me5rine-lab'),
        'activate' => __('Activate', 'me5rine-lab'),
        'desactivate' => __('Desactivate', 'me5rine-lab'),
        'removePrize' => __('Remove', 'me5rine-lab'),
        'selectCountries' => __('Select countries...', 'me5rine-lab'),
        'searchCountries' => __('Search a country...', 'me5rine-lab'),
    ]);
    $enqueued = true;
}

// Shortcode ajout d'un concours
function rafflepress_render_add_campaign_form_shortcode() {
    enqueue_campaign_form_assets();
    ob_start();
    include_once __DIR__ . '/../includes/giveaways-add-form-campaign.php';
    return ob_get_clean();
}
add_shortcode('add_giveaway', 'rafflepress_render_add_campaign_form_shortcode');

// Shortcode édition d'un concours
function rafflepress_render_edit_campaign_form_shortcode() {
    enqueue_campaign_form_assets();
    ob_start();
    include_once __DIR__ . '/../includes/giveaways-edit-form-campaign.php';
    return ob_get_clean();
}
add_shortcode('edit_giveaway', 'rafflepress_render_edit_campaign_form_shortcode');

// Shortcode dashboard des concours d'un partenaire
function admin_giveaways_shortcode() {
    ob_start();
    include_once __DIR__ . '/../includes/giveaways-front-dashboard.php';
    if (function_exists('giveaways_display_front_dashboard')) {
        giveaways_display_front_dashboard();
    }
    return ob_get_clean();
}
add_shortcode('admin_giveaways', 'admin_giveaways_shortcode');

// Shortcode de redirection d'url d'un concours
function giveaway_redirect_url_shortcode() {
    $current_url = esc_url(home_url($_SERVER['REQUEST_URI']));
    $redirect_url = add_query_arg('redirect_url', $current_url, site_url('/add-giveaway/'));
    return esc_url($redirect_url);
}
add_shortcode('giveaway_redirect_link', 'giveaway_redirect_url_shortcode');
