<?php
// File: modules/subscription/functions/subscription-ultimate-member.php

if (!defined('ABSPATH')) exit;

/**
 * Traitement du shortcode dans les onglets Ultimate Member
 * Si Ultimate Member passe le contenu comme texte brut au lieu d'exécuter les shortcodes
 */
function admin_lab_subscription_process_um_tab_content($content, $tab) {
    // Si le contenu contient notre shortcode en texte brut, le traiter
    if (is_string($content)) {
        if (strpos($content, '[admin_lab_subscriptions]') !== false) {
            $content = str_replace('[admin_lab_subscriptions]', admin_lab_render_subscriptions(), $content);
        }
        // Traiter aussi via do_shortcode au cas où
        $content = do_shortcode($content);
    }
    return $content;
}

// Si Ultimate Member est actif, ajouter le filtre pour traiter les shortcodes dans les onglets
if (function_exists('um_is_core_page')) {
    // Ce filtre traite le contenu des onglets si Ultimate Member les passe comme texte
    // Le format dépend de la façon dont Ultimate Member gère les onglets
    add_filter('um_profile_tab_content_abonnements', 'admin_lab_subscription_process_um_tab_content', 10, 2);
    
    // Ajouter aussi pour d'autres noms d'onglets possibles
    add_filter('um_profile_tab_content_subscriptions', 'admin_lab_subscription_process_um_tab_content', 10, 2);
}

