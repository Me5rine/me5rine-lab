<?php
// File: modules/marketing/functions/marketing-shortcodes.php

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [marketing_banner format="banner|sidebar" slot="1"]
 * Affiche une bannière marketing liée à la zone choisie (et à l'emplacement).
 */
function admin_lab_render_marketing_banner($atts) {
    $atts = shortcode_atts([
        'format' => 'banner',
        'slot'   => '1',
        'image'  => '1',
    ], $atts, 'marketing_banner');

    // Vérifier format valide
    $valid_formats = ['sidebar', 'banner', 'background'];
    if (!in_array($atts['format'], $valid_formats, true)) {
        return '';
    }

    // Construire le nom de la zone
    $zone = $atts['format'] . '_' . intval($atts['slot']);

    // Récupérer la campagne active pour cette zone
    $campaign_id = get_option("admin_lab_marketing_zone_$zone");
    if (!$campaign_id) return '';

    global $wpdb;
    $table = admin_lab_getTable('marketing_links');
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $campaign_id));
    if (!$campaign) return '';

    // On utilise toujours la même colonne d’image par format
    $image_column = "image_url_{$atts['format']}_{$atts['image']}";
    $image_url = $campaign->$image_column ?? '';
    $url = $campaign->campaign_url ?? '';

    if (!$image_url || !$url) return '';

    ob_start(); ?>
    <div class="admin-lab-marketing-banner" data-zone="<?php echo esc_attr($zone); ?>">
        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($campaign->campaign_slug); ?>" style="max-width:100%;height:auto;">
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('marketing_banner', 'admin_lab_render_marketing_banner');