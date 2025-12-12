<?php
// File: modules/partnership/partnership-shortcodes.php

if (!defined('ABSPATH')) exit;

// Shortcode dashboard des partenaires
function admin_lab_render_partner_dashboard_shortcode() {
    $base_url = plugin_dir_url(dirname(dirname(__DIR__))) . 'assets/';
    wp_enqueue_style('admin-lab-partnership-dashboard-style', $base_url . 'css/partnership-front-dashboard.css', [], ME5RINE_LAB_VERSION);
    ob_start();
    include __DIR__ . '/../templates/partnership-dashboard.php';
    return ob_get_clean();
}
add_shortcode('partner_dashboard', 'admin_lab_render_partner_dashboard_shortcode');
