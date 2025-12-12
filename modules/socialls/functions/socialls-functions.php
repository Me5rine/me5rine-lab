<?php
// File: functions/socialls-functions.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère la liste des réseaux sociaux définis dans les options globales.
 * Permet de filtrer par type (social, support, ou les deux).
 *
 * @param string|array $types Type(s) de réseaux à récupérer. Peut être 'social', 'support', ou un tableau contenant ces types.
 * @return array Liste des réseaux sociaux filtrée.
 */
function admin_lab_get_socials_list($types = ['social', 'support']) {
    // Récupérer les options globales des réseaux sociaux
    $socials_data = admin_lab_get_global_option('admin_lab_socials_list');
    $socials = $socials_data ? unserialize($socials_data) : [];

    // Si aucun réseau social n'est défini, on retourne un tableau vide
    if (empty($socials)) {
        return [];
    }

    // Si $types est un tableau, on fusionne les types 'social' et 'support', sinon on les utilise séparément
    if (!is_array($types)) {
        $types = [$types];
    }

    // Filtrer les réseaux sociaux par type(s)
    return array_filter($socials, function($social) use ($types) {
        // Si le type du réseau social est dans les types spécifiés, on le garde
        return in_array($social['type'], $types);
    });
}

/**
 * Récupère les réseaux sociaux renseignés par l'utilisateur, filtrés par type (social, support, ou les deux).
 * Utilise la fonction existante `admin_lab_get_socials_list()` pour récupérer les réseaux sociaux définis globalement.
 *
 * @param int $user_id L'ID de l'utilisateur pour lequel récupérer les réseaux sociaux.
 * @param string|array $types Type(s) de réseaux à récupérer. Peut être 'social', 'support', ou un tableau contenant ces types.
 * @return array Liste des réseaux sociaux renseignés par l'utilisateur, filtrée par type.
 */
function admin_lab_get_user_socials_list($user_id, $types = ['social', 'support']) {
    // Récupérer les réseaux sociaux définis globalement, filtrés par type
    $socials = admin_lab_get_socials_list($types); // Utilise la fonction existante pour récupérer les réseaux filtrés

    // Initialiser un tableau pour les réseaux renseignés par l'utilisateur
    $user_socials = [];

    // Vérifier si l'utilisateur a renseigné une valeur pour chaque réseau social filtré
    foreach ($socials as $key => $data) {
        // Vérifier si l'utilisateur a renseigné une valeur pour ce réseau social
        $user_social_value = get_user_meta($user_id, $key, true);

        // Si l'utilisateur a renseigné ce réseau, on l'ajoute à la liste
        if ($user_social_value) {
            $user_socials[$key] = $data;
        }
    }

    return $user_socials;
}

/**
 * Récupère les propriétés par défaut d'un champ social défini via Ultimate Member.
 *
 * Cette fonction lit tous les champs prédéfinis UM (predefined_fields),
 * repère ceux avec 'advanced' => 'social', et extrait leurs propriétés utiles.
 *
 * @param string $key Le meta_key du champ social (ex: youtube, paypal, etc.)
 * @return array Un tableau avec les clés : label, meta_key, icon, color, url_text, match
 */
function admin_lab_get_um_social_defaults($key) {
    $socials_data = admin_lab_get_global_option('admin_lab_socials_list');
    $socials = $socials_data ? unserialize($socials_data) : [];

    $base_key = preg_replace('/^([^_]+).*$/', '$1', $key);

    $icon_file = $base_key . '.svg';
    $icon_path = ME5RINE_LAB_PATH . 'assets/icons/' . $icon_file;
    $icon = file_exists($icon_path) ? $icon_file : '';

    $color = 'var(--admin-lab-color-admin-text)';
    if ($key === 'instagram') {
        $color = 'radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%,#d6249f 60%,#285AEB 90%)';
    } elseif (!empty($socials[$key]['color'])) {
        $color = $socials[$key]['color'];
    }

    $fa = $socials[$key]['fa'] ?? 'fa-' . strtolower($key);

    return [
        'meta_key' => $key,
        'icon'     => $icon,
        'fa'       => $fa,
        'color'    => $color,
        'url_text' => ucfirst($base_key),
        'match'    => '',
    ];
}

/**
 * Récupère tous les réseaux sociaux d'un utilisateur avec toutes les données (globales et spécifiques à l'utilisateur),
 * en les organisant selon l'ordre global et en séparant en 'social' et 'support'.
 *
 * @param int $user_id L'ID de l'utilisateur pour lequel récupérer les réseaux sociaux.
 * @param string|array $types Type(s) de réseaux à récupérer ('social', 'support', ou un tableau contenant ces types).
 * @param bool $use_global_label Utiliser le label global au lieu du label personnalisé.
 * @param bool $force_include_all Inclure tous les réseaux ayant une URL même si non activés (enabled ≠ 1).
 * @return array Liste complète des réseaux sociaux de l'utilisateur.
 */
function admin_lab_get_user_socials_full_info($user_id, $types = ['social', 'support'], $use_global_label = false, $force_include_all = false) {
    $socials = admin_lab_get_socials_list($types); // réseaux globaux filtrés
    $user_socials = [];

    foreach ($socials as $key => $data) {
        $url = get_user_meta($user_id, $key, true);
        if (!$url) continue;

        $defaults = admin_lab_get_um_social_defaults($key); // icon, color, fa

        $label_global = $data['label'] ?? ucfirst($key);
        $label_custom = get_user_meta($user_id, $key . '_label', true);
        $label = $use_global_label ? $label_global : ($label_custom ?: $label_global);

        $user_enabled = get_user_meta($user_id, $key . '_enabled', true);
        $enabled_bool = ($user_enabled === '1');

        // 🟡 Skip si non activé par l'utilisateur, sauf si on force l'inclusion
        if (!$enabled_bool && !$force_include_all) {
            continue;
        }

        $user_socials[$key] = array_merge(
            $data,
            $defaults,
            [
                'url'           => $url,
                'label_global'  => $label_global,
                'label_custom'  => $label_custom,
                'label'         => $label,
                'user_enabled'  => $enabled_bool,
            ]
        );
    }

    return $user_socials;
}


