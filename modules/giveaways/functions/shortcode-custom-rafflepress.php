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

    // Utiliser la route personnalisée au lieu de celle de RafflePress
    $iframe_url = add_query_arg([
        'me5rine_lab_giveaway_render' => '1',
        'me5rine_lab_giveaway_id'      => $rafflepress_id,
        'parent_url'                   => home_url(add_query_arg([], $_SERVER['REQUEST_URI']))
    ], home_url('/'));

    // Générer un ID unique pour cette iframe
    $iframe_id = 'rafflepress-' . $rafflepress_id;
    $wrapper_id = 'rafflepress-giveaway-iframe-wrapper-' . $rafflepress_id;
    
    return sprintf(
    '<div id="%11$s" class="rafflepress-giveaway-iframe-wrapper rafflepress-iframe-container loading">
            <iframe id="%12$s" class="rafflepress-iframe"
                src="%2$s"
                data-login-url="%3$s"
                data-register-url="%4$s"
                data-user-name="%5$s"
                data-user-email="%6$s"
                data-prizes="%7$s"
                data-partner-name="%9$s"
                data-website-name="%10$s"
                frameborder="0" scrolling="no" allowtransparency="true"
                style="width:100%%; min-height:%8$s; border: none;"></iframe>
        </div>
        <script>
        (function() {
            "use strict";
            const iframe = document.getElementById("%12$s");
            const wrapper = document.getElementById("%11$s");
            
            if (!iframe) return;
            
            // Écouter les messages de hauteur depuis l\'iframe
            window.addEventListener("message", function(event) {
                // Vérifier l\'origine pour la sécurité (optionnel, peut être ajusté)
                // if (event.origin !== window.location.origin) return;
                
                if (event.data && event.data.type === "me5rine-lab-iframe-height" && event.data.iframeId === "%12$s") {
                    const height = event.data.height;
                    
                    // Appliquer la hauteur à l\'iframe
                    if (height && height > 0) {
                        iframe.style.height = height + "px";
                        if (wrapper) {
                            wrapper.classList.remove("loading");
                        }
                    }
                }
            });
            
            // Hauteur par défaut si aucun message n\'est reçu
            setTimeout(function() {
                if (iframe.style.height === "" || iframe.style.height === "auto") {
                    iframe.style.height = "%8$s";
                }
            }, 5000);
        })();
        </script>',
        $rafflepress_id,
        esc_url($iframe_url),
        esc_url(wp_login_url(get_permalink())),
        esc_url(wp_registration_url()),
        esc_attr($is_logged_in ? $current_user->display_name : ''),
        esc_attr($is_logged_in ? $current_user->user_email : ''),
        $data_prizes,
        esc_attr($atts['min_height']),
        esc_attr($partner_name),
        esc_attr($website_name),
        esc_attr($wrapper_id),
        esc_attr($iframe_id)
    );
}
