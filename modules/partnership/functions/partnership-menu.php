<?php
// File: modules/partnership/functions/partnership-menu.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère la structure du menu des modules accessibles pour un utilisateur.
 * 
 * @param int|null $user_id ID de l'utilisateur (optionnel, utilisateur courant par défaut)
 * @return array Structure du menu avec les modules et leurs pages
 */
function admin_lab_get_partner_menu_structure(?int $user_id = null): array {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) {
        return [];
    }

    // Récupérer les modules accessibles
    $enabled_modules = admin_lab_get_user_enabled_modules($user_id);
    if (empty($enabled_modules)) {
        return [];
    }

    $menu_structure = [];

    // Module Partnership
    if (in_array('partnership', $enabled_modules, true)) {
        $partnership_page_id = get_option('partnership_page_partenariat');
        if ($partnership_page_id) {
            $menu_structure['partnership'] = [
                'label' => __('Dashboard', 'me5rine-lab'),
                'icon' => 'fa-th-large',
                'url' => get_permalink($partnership_page_id),
                'items' => []
            ];
        }
    }

    // Module Giveaways
    if (in_array('giveaways', $enabled_modules, true)) {
        $giveaways_items = [];
        
        $admin_giveaways_page_id = get_option('giveaways_page_admin-giveaways');
        if ($admin_giveaways_page_id) {
            $giveaways_items[] = [
                'label' => __('My Giveaways', 'me5rine-lab'),
                'url' => get_permalink($admin_giveaways_page_id),
            ];
        }

        $add_giveaway_page_id = get_option('giveaways_page_add-giveaway');
        if ($add_giveaway_page_id) {
            $giveaways_items[] = [
                'label' => __('Add Giveaway', 'me5rine-lab'),
                'url' => get_permalink($add_giveaway_page_id),
            ];
        }

        if (!empty($giveaways_items)) {
            $menu_structure['giveaways'] = [
                'label' => __('Giveaways', 'me5rine-lab'),
                'icon' => 'fa-gift',
                'url' => $admin_giveaways_page_id ? get_permalink($admin_giveaways_page_id) : '#',
                'items' => $giveaways_items
            ];
        }
    }

    // Module Socialls
    if (in_array('socialls', $enabled_modules, true)) {
        $socials_page_id = get_option('socialls_page_socials');
        if ($socials_page_id) {
            $menu_structure['socialls'] = [
                'label' => __('Social Networks', 'me5rine-lab'),
                'icon' => 'fa-share-alt',
                'url' => get_permalink($socials_page_id),
                'items' => []
            ];
        }
    }

    // Permettre aux autres modules d'ajouter leurs entrées via un filtre
    $menu_structure = apply_filters('admin_lab_partner_menu_structure', $menu_structure, $user_id, $enabled_modules);

    return $menu_structure;
}

/**
 * Détermine si une page est active dans le menu.
 * 
 * @param string $url URL de la page
 * @return bool True si la page est active
 */
function admin_lab_is_menu_item_active(string $url): bool {
    if (empty($url) || $url === '#') {
        return false;
    }

    $current_url = home_url($_SERVER['REQUEST_URI']);
    $permalink = untrailingslashit($url);
    $current = untrailingslashit(strtok($current_url, '?'));

    return $permalink === $current;
}

/**
 * Détermine si un module avec sous-menu est ouvert (si une de ses pages est active).
 * 
 * @param array $module_data Données du module
 * @return bool True si le module doit être ouvert
 */
function admin_lab_is_menu_module_open(array $module_data): bool {
    if (empty($module_data['items'])) {
        return admin_lab_is_menu_item_active($module_data['url'] ?? '');
    }

    // Vérifier si l'URL principale est active
    if (admin_lab_is_menu_item_active($module_data['url'] ?? '')) {
        return true;
    }

    // Vérifier si une des sous-pages est active
    foreach ($module_data['items'] as $item) {
        if (admin_lab_is_menu_item_active($item['url'] ?? '')) {
            return true;
        }
    }

    return false;
}

