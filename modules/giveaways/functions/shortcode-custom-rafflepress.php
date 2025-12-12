<?php
// File: modules/giveaways/functions/shortcode-custom-rafflepress.php

if (!defined('ABSPATH')) exit;

add_shortcode('custom_rafflepress', 'custom_rafflepress_shortcode');

function custom_rafflepress_shortcode($atts) {
    global $wpdb;

    $atts = shortcode_atts([
        'id' => '',
        'min_height' => '900px'
    ], $atts, 'custom_rafflepress');

    $rafflepress_id = absint($atts['id']);
    if (!$rafflepress_id) return '';

    $settings_json = $wpdb->get_var($wpdb->prepare(
        "SELECT settings FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d",
        $rafflepress_id
    ));

    $prizes = [];
    if ($settings_json) {
        $settings = json_decode($settings_json, true);
        if (!empty($settings['prizes']) && is_array($settings['prizes'])) {
            foreach ($settings['prizes'] as $prize) {
                if (!empty($prize['name'])) {
                    $prizes[] = $prize['name'];
                }
            }
        }
    }

    $data_prizes = esc_attr(implode('|', $prizes));

    // Récupérer le nom du partenaire lié au concours
    $partner_name = '';
    $post_id = admin_lab_get_post_id_from_rafflepress($rafflepress_id);
    if ($post_id) {
        $partner_id = get_post_meta($post_id, '_giveaway_partner_id', true);
        if ($partner_id) {
            $partner = get_userdata($partner_id);
            $partner_name = $partner ? $partner->display_name : '';
        }
    }

    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();

    $admin_id = get_option('admin_lab_account_id');
    $admin_user = $admin_id ? get_userdata($admin_id) : null;
    $website_name = $admin_user ? $admin_user->display_name : 'Me5rine LAB';

    $iframe_url = add_query_arg([
        'rafflepress_page' => 'rafflepress_render',
        'rafflepress_id'   => $rafflepress_id,
        'iframe'           => '1',
        'giframe'          => 'false',
        'parent_url'       => home_url(add_query_arg([], $_SERVER['REQUEST_URI']))
    ], home_url('/'));

    return sprintf(
    '<div id="rafflepress-giveaway-iframe-wrapper-%1$d" class="rafflepress-giveaway-iframe-wrapper rafflepress-iframe-container loading">
            <iframe id="rafflepress-%1$d" class="rafflepress-iframe"
                src="%2$s"
                data-login-url="%3$s"
                data-register-url="%4$s"
                data-user-name="%5$s"
                data-user-email="%6$s"
                data-prizes="%7$s"
                data-partner-name="%9$s"
                data-website-name="%10$s"
                frameborder="0" scrolling="no" allowtransparency="true"
                style="width:100%%; min-height:%8$s;"></iframe>
        </div>',
        $rafflepress_id,
        esc_url($iframe_url),
        esc_url(wp_login_url(get_permalink())),
        esc_url(wp_registration_url()),
        esc_attr($is_logged_in ? $current_user->display_name : ''),
        esc_attr($is_logged_in ? $current_user->user_email : ''),
        $data_prizes,
        esc_attr($atts['min_height']),
        esc_attr($partner_name),
        esc_attr($website_name)
    );
}
