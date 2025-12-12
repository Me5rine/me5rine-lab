<?php
// File: functions/admin-lab-helpers.php

if (!defined('ABSPATH')) exit;

/**
 * Retourne l'URL du site principal définit avec le ME5RINE_LAB_CUSTOM_PREFIX du fichier de config.
 */
function admin_lab_get_main_site_url() {
    $prefix = defined('ME5RINE_LAB_CUSTOM_PREFIX') ? ME5RINE_LAB_CUSTOM_PREFIX : '';
    return $prefix ? get_option("{$prefix}_siteurl") : get_site_url();
}

/**
 * Transforme un nom en slug lisible, en remplaçant certains caractères spéciaux
 * par leur équivalent alphabétique explicite avant de passer par sanitize_title().
 *
 * Exemples :
 * - "Pokémon GO Plus +" → "pokemon-go-plus-plus"
 * - "Cash & Carry" → "cash-and-carry"
 * - "Prix €100" → "prix-euro100"
 *
 * @param string $label Le nom à transformer.
 * @return string Le slug formaté.
 */
function admin_lab_slugify_label($label) {
    $map = [
        '+' => '-plus',
        '&' => '-and',
        '@' => '-at',
        '%' => '-percent',
        '#' => '-sharp',
        '$' => '-dollar',
        '€' => '-euro',
        'œ' => 'oe',
        'æ' => 'ae',
        '©' => 'copyright',
        '™' => 'tm',
    ];

    $replaced = strtr($label, $map);

    return sanitize_title($replaced);
}


if (!function_exists('display_transient_message')) {
    /**
     * Affiche un message temporaire (transient) WordPress sous forme de bloc HTML, puis le supprime.
     *
     * Cette fonction est utile pour afficher des messages d’erreur ou de succès suite à des actions utilisateurs
     * (formulaires, redirections, etc.). Le message est récupéré via get_transient() puis supprimé immédiatement.
     *
     * @param string $transient_name Nom du transient à afficher.
     * @param string $message_type   Type de message CSS (ex: 'success', 'error', 'warning'). Par défaut : 'error'.
     */
    function display_transient_message($transient_name, $message_type = 'error') {
        $message = get_transient($transient_name);
        if (!empty($message)) {
            echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
            delete_transient($transient_name);
        }
    }
}

/**
 * Écrit un message dans un fichier de log personnalisé situé dans le dossier /uploads/admin-lab-logs/.
 *
 * Ce logger permet de stocker des messages de debug ou de suivi spécifiques au plugin Me5rine LAB,
 * sans polluer le fichier debug.log global de WordPress.
 *
 * - Le répertoire `/wp-content/uploads/admin-lab-logs/` est créé automatiquement s'il n'existe pas.
 * - Chaque message est horodaté (format `Y-m-d H:i:s`).
 * - Le fichier de log est ajouté en mode `append`, donc les anciens logs sont conservés.
 *
 * @param string $message  Le message à enregistrer dans le log.
 * @param string $filename (Optionnel) Le nom du fichier de log. Par défaut : 'me5rine-giveaways.log'.
 */
function admin_lab_log_custom($message, $filename = 'me5rine-giveaways.log') {
    // Si on reçoit un entier (comme un post_id), on le traite comme tel, pas comme nom de fichier
    if (is_numeric($filename)) {
        $post_id = $filename;
        $filename = 'me5rine-giveaways.log';
        $message = "[post_id={$post_id}] $message";
    }

    $upload_dir = wp_upload_dir();
    $log_dir = trailingslashit($upload_dir['basedir']) . 'admin-lab-logs';

    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $filepath = trailingslashit($log_dir) . $filename;
    $datetime = date('Y-m-d H:i:s');

    file_put_contents($filepath, "[$datetime] $message\n", FILE_APPEND);
}

/**
 * Récupère le fuseau horaire du site WordPress sous forme d’objet DateTimeZone.
 *
 * Cette fonction retourne le fuseau horaire configuré dans les réglages WordPress,
 * en priorisant l'option `timezone_string` (ex : "Europe/Paris") si elle est définie.
 * Si ce n'est pas le cas, elle reconstruit un fuseau horaire à partir de `gmt_offset`.
 *
 * Cela permet une gestion fiable des dates dans les contextes où WordPress ne fournit pas directement
 * un objet `DateTimeZone`, notamment pour les calculs de dates ou conversions UTC ↔ local.
 *
 * @return DateTimeZone L'objet représentant le fuseau horaire WordPress (ex : Europe/Paris ou +02:00).
 */
function admin_lab_get_wp_timezone(): DateTimeZone {
    $tz_string = get_option('timezone_string');
    if (!empty($tz_string)) {
        return new DateTimeZone($tz_string);
    }
    $offset = get_option('gmt_offset');
    $hours = (int) $offset;
    $minutes = abs($offset - $hours) * 60;
    $sign = ($offset < 0) ? '-' : '+';
    $formatted_offset = sprintf('%s%02d:%02d', $sign, abs($hours), $minutes);
    return new DateTimeZone($formatted_offset);
}

/**
 * Formatte une date en fonction du fuseau horaire de WordPress.
 * Peut gérer la conversion UTC -> local ou local -> UTC.
 *
 * @param string $datetime Date à convertir (en UTC ou locale).
 * @param string $format Format de sortie (défaut : 'd/m/Y \à H\hi').
 * @param bool $to_local Si vrai, la fonction convertit UTC en local, sinon local vers UTC.
 * @return string Date formatée ou '–' en cas d'erreur.
 */
function admin_lab_format_local_datetime($datetime, $format = 'd/m/Y \à H\hi', $to_local = true) {
    try {
        $tz = admin_lab_get_wp_timezone(); // Récupère le fuseau horaire de WordPress.
        $dt = new DateTime($datetime, new DateTimeZone($to_local ? 'UTC' : $tz->getName())); // UTC ou Local selon le flag.

        if (!$to_local) {
            $dt->setTimezone(new DateTimeZone('UTC')); // Convertit vers UTC si $to_local est false.
        } else {
            $dt->setTimezone($tz); // Convertit vers le fuseau local de WordPress si $to_local est true.
        }

        return $dt->format($format); // Retourne la date formatée.
    } catch (Exception $e) {
        return '–'; // En cas d'erreur, retourne un tiret.
    }
}

/**
 * Format time remaining until a given timestamp.
 *
 * @param int $end_ts  Timestamp in UTC.
 * @param int|null $now_ts Optional current timestamp. If null, current time is used.
 *
 * @return string|null Human-readable time left string, or null if expired.
 */
function admin_lab_format_time_remaining(int $end_ts, ?int $now_ts = null): ?string {
    if (!$now_ts) {
        $now_ts = current_time('timestamp', true);
    }

    if ($end_ts <= $now_ts) {
        return null;
    }

    $diff = $end_ts - $now_ts;

    if ($diff >= DAY_IN_SECONDS) {
        $days = floor($diff / DAY_IN_SECONDS);
        return sprintf(_n('%d day', '%d days', $days, 'me5rine-lab'), $days);
    }

    if ($diff >= HOUR_IN_SECONDS) {
        $hours = floor($diff / HOUR_IN_SECONDS);
        return sprintf(_n('%d hour', '%d hours', $hours, 'me5rine-lab'), $hours);
    }

    $minutes = floor($diff / MINUTE_IN_SECONDS);
    return sprintf(_n('%d minute', '%d minutes', $minutes, 'me5rine-lab'), $minutes);
}

/**
 * Vérifie si un utilisateur est partenaire actif sur un site donné.
 *
 * @param int $user_id
 * @param string|null $site_domain Domaine du site à vérifier (défaut : domaine courant).
 * @return bool
 */
function admin_lab_is_partner_active_on_site($user_id, $site_domain = null) {
    $site_domain = $site_domain ?: $_SERVER['HTTP_HOST'];

    if (get_user_meta($user_id, 'partner_all_sites', true) === '1') {
        return true;
    }

    $partner_sites = maybe_unserialize(get_user_meta($user_id, 'partner_sites', true));
    return is_array($partner_sites) && in_array($site_domain, $partner_sites);
}

/**
 * Récupère le nom complet d'une table, avec soit le préfixe WordPress du site courant,
 * soit un préfixe global défini via la constante ME5RINE_LAB_CUSTOM_PREFIX.
 *
 * @param string $name Nom de la table (sans préfixe).
 * @param bool   $use_global_prefix True pour utiliser le préfixe global, false pour le préfixe du site courant.
 * @return string Nom complet de la table.
 */
function admin_lab_getTable($name, $use_global_prefix = true) {
    global $wpdb;

    if ($use_global_prefix && defined('ME5RINE_LAB_CUSTOM_PREFIX')) {
        return ME5RINE_LAB_CUSTOM_PREFIX . $name;
    }

    return $wpdb->prefix . $name;
}

/**
 * Récupère la valeur d'une option stockée dans la table d'options globale du site principal.
 *
 * Cette fonction utilise le préfixe défini par ME5RINE_LAB_CUSTOM_PREFIX pour accéder à la table
 * globale des options (ex : me5rine_options), et permet d'obtenir une valeur d'option
 * indépendamment du site WordPress courant.
 *
 * @param string $key Nom de l'option à récupérer.
 * @return string|null La valeur de l’option si elle existe, sinon null.
 */
function admin_lab_get_global_option($key) {
    global $wpdb;
    $table = admin_lab_getTable('options', true);

    return $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM $table WHERE option_name = %s LIMIT 1",
        $key
    ));
}

/**
 * Vérifie si un module est activé.
 *
 * @param string $module Nom du module à tester (ex: 'giveaways', 'marketing').
 * @return bool True si activé, False sinon.
 */
function admin_lab_is_module_active(string $module): bool {
    $active_modules = get_option('admin_lab_active_modules', []);
    return is_array($active_modules) && in_array($module, $active_modules, true);
}

/**
 * Met à jour la meta utilisée par Ultimate Member pour les permaliens personnalisés.
 *
 * Cette meta `custom_user_nicename` est utilisée lorsque UM est configuré pour utiliser une "meta utilisateur personnalisée"
 * comme base d’URL pour les profils (ex: /profil/{slug}).
 *
 * @param int    $user_id    ID de l'utilisateur concerné.
 * @param string $nicename   Valeur unique à utiliser comme slug de profil.
 */
function admin_lab_sync_um_permalink_meta(int $user_id, string $nicename): void {
    update_user_meta($user_id, 'custom_user_nicename', $nicename);
}

/**
 * Récupère le nom d'une chaîne YouTube à partir de son URL.
 *
 * @param string $url     URL publique de la chaîne YouTube (channel, @handle, etc.)
 * @param string $api_key Clé API YouTube Data v3
 * @return string|null    Le nom de la chaîne, ou null si non trouvé
 */
function admin_lab_get_youtube_channel_name($url): ?string {
    $api_key = admin_lab_get_global_option('admin_lab_youtube_api_key');
    if (empty($api_key)) return null;

    $clean_url = urldecode(strtok($url, '?'));
    $clean_url = preg_replace('#^https?://#', '', $clean_url);
    $clean_url = rtrim($clean_url, '/');

    $cache_key = 'youtube_channel_' . md5($clean_url);
    $cached_name = get_transient($cache_key);

    if ($cached_name) {
        return $cached_name;
    }

    $channel_id = null;

    // Récupération du nom en fonction du type d'URL (channel, handle, c/username)
    if (preg_match('#youtube\.com/channel/([^/?]+)#', $clean_url, $matches)) {
        $channel_id = $matches[1];
    } elseif (preg_match('#youtube\.com/@([^/?]+)#', $clean_url, $matches)) {
        $handle = $matches[1];
        $search_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=@$handle&type=channel&key=$api_key";
        $response = wp_remote_get($search_url);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['items'][0]['snippet']['title'])) {
            $channel_name = $data['items'][0]['snippet']['title'];
            set_transient($cache_key, $channel_name, 24 * HOUR_IN_SECONDS);
            return $channel_name;
        }
        return null;
    } elseif (preg_match('#youtube\.com/c/([^/?]+)#', $clean_url, $matches)) {
        $username = $matches[1];
        $api_url = "https://www.googleapis.com/youtube/v3/channels?part=snippet&forUsername=$username&key=$api_key";
        $response = wp_remote_get($api_url);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['items'][0]['snippet']['title'])) {
            $channel_name = $data['items'][0]['snippet']['title'];
            set_transient($cache_key, $channel_name, 24 * HOUR_IN_SECONDS);
            return $channel_name;
        }
        return null;
    }

    // Si on a l'ID de la chaîne
    if ($channel_id) {
        $api_url = "https://www.googleapis.com/youtube/v3/channels?part=snippet&id=$channel_id&key=$api_key";
        $response = wp_remote_get($api_url);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['items'][0]['snippet']['title'])) {
            $channel_name = $data['items'][0]['snippet']['title'];
            set_transient($cache_key, $channel_name, 24 * HOUR_IN_SECONDS);
            return $channel_name;
        }
    }

    return null;
}

/**
 * Récupère et met en cache le nom d'une chaîne YouTube à partir de son URL.
 * Si un label est déjà stocké (dans usermeta), il est utilisé sans refaire d'appel API.
 *
 * @param string      $url      URL publique de la chaîne YouTube
 * @param int|null    $user_id  ID de l'utilisateur (sinon utilisateur courant)
 * @param string|null $meta_key Clé meta associée au champ (youtube, youtube_2, etc.)
 * @return string|null
 */
function admin_lab_get_youtube_channel_label_cached(string $url, ?int $user_id = null, ?string $meta_key = null): ?string {
    if (empty($url)) return null;

    $user_id = $user_id ?: get_current_user_id();
    $meta_key = $meta_key ?: 'youtube';

    $cached_label = get_user_meta($user_id, 'admin_lab_' . $meta_key . '_label', true);
    if (!empty($cached_label)) return $cached_label;

    $label = admin_lab_get_youtube_channel_name($url);
    if ($label) {
        update_user_meta($user_id, 'admin_lab_' . $meta_key . '_label', $label);
    }

    return $label;
}

function admin_lab_get_all_countries(): array {
    return [
        'AF' => 'Afghanistan',
        'ZA' => 'Afrique du Sud',
        'AX' => 'Åland, Îles',
        'AL' => 'Albanie',
        'DZ' => 'Algérie',
        'DE' => 'Allemagne',
        'AD' => 'Andorre',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctique',
        'AG' => 'Antigua et Barbuda',
        'AN' => 'Antilles néerlandaises',
        'SA' => 'Arabie Saoudite',
        'AR' => 'Argentine',
        'AM' => 'Arménie',
        'AW' => 'Aruba',
        'AU' => 'Australie',
        'AT' => 'Autriche',
        'AZ' => 'Azerbaïdjan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrein',
        'BD' => 'Bangladesh',
        'BB' => 'Barbade',
        'BY' => 'Bélarus',
        'BE' => 'Belgique',
        'BZ' => 'Bélize',
        'BJ' => 'Bénin',
        'BM' => 'Bermudes',
        'BT' => 'Bhoutan',
        'BO' => 'Bolivie (État plurinational de)',
        'BQ' => 'Bonaire, Saint-Eustache et Saba',
        'BA' => 'Bosnie-Herzégovine',
        'BW' => 'Botswana',
        'BV' => 'Bouvet, Ile',
        'BR' => 'Brésil',
        'BN' => 'Brunéi Darussalam',
        'BG' => 'Bulgarie',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CV' => 'Cabo Verde',
        'KY' => 'Caïmans, Iles',
        'KH' => 'Cambodge',
        'CM' => 'Cameroun',
        'CA' => 'Canada',
        'CL' => 'Chili',
        'CN' => 'Chine',
        'CX' => 'Christmas, île',
        'CY' => 'Chypre',
        'CC' => 'Cocos/Keeling (Îles)',
        'CO' => 'Colombie',
        'KM' => 'Comores',
        'CG' => 'Congo',
        'CD' => 'Congo, République démocratique du',
        'CK' => 'Cook, Iles',
        'KR' => 'Corée, République de',
        'KP' => 'Corée, République populaire démocratique de',
        'CR' => 'Costa Rica',
        'CI' => 'Côte d\'Ivoire',
        'HR' => 'Croatie',
        'CU' => 'Cuba',
        'CW' => 'Curaçao',
        'DK' => 'Danemark',
        'DJ' => 'Djibouti',
        'DO' => 'Dominicaine, République',
        'DM' => 'Dominique',
        'EG' => 'Egypte',
        'SV' => 'El Salvador',
        'AE' => 'Emirats arabes unis',
        'EC' => 'Equateur',
        'ER' => 'Erythrée',
        'ES' => 'Espagne',
        'EE' => 'Estonie',
        'US' => 'Etats-Unis d\'Amérique',
        'ET' => 'Ethiopie',
        'FK' => 'Falkland/Malouines (Îles)',
        'FO' => 'Féroé, îles',
        'FJ' => 'Fidji',
        'FI' => 'Finlande',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GM' => 'Gambie',
        'GE' => 'Géorgie',
        'GS' => 'Géorgie du sud et les îles Sandwich du sud',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Grèce',
        'GD' => 'Grenade',
        'GL' => 'Groenland',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernesey',
        'GN' => 'Guinée',
        'GW' => 'Guinée-Bissau',
        'GQ' => 'Guinée équatoriale',
        'GY' => 'Guyana',
        'GF' => 'Guyane française',
        'HT' => 'Haïti',
        'HM' => 'Heard, Ile et MacDonald, îles',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hongrie',
        'IM' => 'Île de Man',
        'UM' => 'Îles mineures éloignées des Etats-Unis',
        'VG' => 'Îles vierges britanniques',
        'VI' => 'Îles vierges des Etats-Unis',
        'IN' => 'Inde',
        'IO' => 'Indien (Territoire britannique de l\'océan)',
        'ID' => 'Indonésie',
        'IR' => 'Iran, République islamique d\'',
        'IQ' => 'Iraq',
        'IE' => 'Irlande',
        'IS' => 'Islande',
        'IL' => 'Israël',
        'IT' => 'Italie',
        'JM' => 'Jamaïque',
        'JP' => 'Japon',
        'JE' => 'Jersey',
        'JO' => 'Jordanie',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KG' => 'Kirghizistan',
        'KI' => 'Kiribati',
        'KW' => 'Koweït',
        'LA' => 'Lao, République démocratique populaire',
        'LS' => 'Lesotho',
        'LV' => 'Lettonie',
        'LB' => 'Liban',
        'LR' => 'Libéria',
        'LY' => 'Libye',
        'LI' => 'Liechtenstein',
        'LT' => 'Lituanie',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macédoine, l\'ex-République yougoslave de',
        'MG' => 'Madagascar',
        'MY' => 'Malaisie',
        'MW' => 'Malawi',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malte',
        'MP' => 'Mariannes du nord, Iles',
        'MA' => 'Maroc',
        'MH' => 'Marshall, Iles',
        'MQ' => 'Martinique',
        'MU' => 'Maurice',
        'MR' => 'Mauritanie',
        'YT' => 'Mayotte',
        'MX' => 'Mexique',
        'FM' => 'Micronésie, Etats Fédérés de',
        'MD' => 'Moldova, République de',
        'MC' => 'Monaco',
        'MN' => 'Mongolie',
        'ME' => 'Monténégro',
        'MS' => 'Montserrat',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibie',
        'NR' => 'Nauru',
        'NP' => 'Népal',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigéria',
        'NU' => 'Niue',
        'NF' => 'Norfolk, Ile',
        'NO' => 'Norvège',
        'NC' => 'Nouvelle-Calédonie',
        'NZ' => 'Nouvelle-Zélande',
        'OM' => 'Oman',
        'UG' => 'Ouganda',
        'UZ' => 'Ouzbékistan',
        'PK' => 'Pakistan',
        'PW' => 'Palaos',
        'PS' => 'Palestine, Etat de',
        'PA' => 'Panama',
        'PG' => 'Papouasie-Nouvelle-Guinée',
        'PY' => 'Paraguay',
        'NL' => 'Pays-Bas',
        'XX' => 'Pays inconnu',
        'ZZ' => 'Pays multiples',
        'PE' => 'Pérou',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Pologne',
        'PF' => 'Polynésie française',
        'PR' => 'Porto Rico',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'SY' => 'République arabe syrienne',
        'CF' => 'République centrafricaine',
        'RE' => 'Réunion',
        'RO' => 'Roumanie',
        'GB' => 'Royaume-Uni de Grande-Bretagne et d\'Irlande du Nord',
        'RU' => 'Russie, Fédération de',
        'RW' => 'Rwanda',
        'EH' => 'Sahara occidental',
        'BL' => 'Saint-Barthélemy',
        'KN' => 'Saint-Kitts-et-Nevis',
        'SM' => 'Saint-Marin',
        'MF' => 'Saint-Martin (partie française)',
        'SX' => 'Saint-Martin (partie néerlandaise)',
        'PM' => 'Saint-Pierre-et-Miquelon',
        'VA' => 'Saint-Siège',
        'VC' => 'Saint-Vincent-et-les-Grenadines',
        'SH' => 'Sainte-Hélène, Ascension et Tristan da Cunha',
        'LC' => 'Sainte-Lucie',
        'SB' => 'Salomon, Iles',
        'WS' => 'Samoa',
        'AS' => 'Samoa américaines',
        'ST' => 'Sao Tomé-et-Principe',
        'SN' => 'Sénégal',
        'RS' => 'Serbie',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapour',
        'SK' => 'Slovaquie',
        'SI' => 'Slovénie',
        'SO' => 'Somalie',
        'SD' => 'Soudan',
        'SS' => 'Soudan du Sud',
        'LK' => 'Sri Lanka',
        'SE' => 'Suède',
        'CH' => 'Suisse',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard et île Jan Mayen',
        'SZ' => 'Swaziland',
        'TJ' => 'Tadjikistan',
        'TW' => 'Taïwan, Province de Chine',
        'TZ' => 'Tanzanie, République unie de',
        'TD' => 'Tchad',
        'CS' => 'Tchécoslovaquie',
        'CZ' => 'Tchèque, République',
        'TF' => 'Terres australes françaises',
        'TH' => 'Thaïlande',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinité-et-Tobago',
        'TN' => 'Tunisie',
        'TM' => 'Turkménistan',
        'TC' => 'Turks-et-Caïcos (Îles)',
        'TR' => 'Turquie',
        'TV' => 'Tuvalu',
        'UA' => 'Ukraine',
        'SU' => 'URSS',
        'UY' => 'Uruguay',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela (République bolivarienne du)',
        'VN' => 'Viet Nam',
        'VD' => 'Viet Nam (Sud)',
        'WF' => 'Wallis et Futuna',
        'YE' => 'Yémen',
        'YU' => 'Yougoslavie',
        'ZR' => 'Zaïre',
        'ZM' => 'Zambie',
        'ZW' => 'Zimbabwe',
    ];
}

/**
 * Vérifie si l'utilisateur est partenaire ou partenaire+ (peu importe la portée).
 *
 * @param int|null $user_id ID utilisateur (optionnel).
 * @return bool
 */
function admin_lab_user_is_partner(?int $user_id = null): bool {
    $user_id = $user_id ?: get_current_user_id();
    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    return is_array($types) && (in_array('partenaire', $types) || in_array('partenaire_plus', $types));
}


/**
 * Vérifie si l'utilisateur est sub ou premium (peu importe la portée).
 *
 * @param int|null $user_id ID utilisateur (optionnel).
 * @return bool
 */
function admin_lab_user_is_subscriber(?int $user_id = null): bool {
    $user_id = $user_id ?: get_current_user_id();
    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    return is_array($types) && (in_array('sub', $types) || in_array('premium', $types));
}

/**
 * Vérifie si un utilisateur a un type de compte autorisé (partenaire, sub, premium)
 * et si un module est activé pour lui (optionnel).
 *
 * @param string|null $module  Nom du module (ex : 'giveaways')
 * @param int|null    $user_id ID utilisateur (facultatif, utilisateur courant par défaut)
 * @return bool
 */
function admin_lab_user_has_allowed_role(?string $module = null, ?int $user_id = null): bool {
    $user_id = $user_id ?? get_current_user_id();
    if (!$user_id) {
        return false;
    }

    if (!admin_lab_user_is_partner($user_id) && !admin_lab_user_is_subscriber($user_id)) {
        return false;
    }

    if (!$module) {
        return true;
    }

    if (defined('ADMIN_LAB_MODULES_ALWAYS_ACTIVE') && ADMIN_LAB_MODULES_ALWAYS_ACTIVE === true) {
        return true;
    }

    $enabled_modules = admin_lab_get_user_enabled_modules($user_id);

    return in_array($module, $enabled_modules, true);
}

/**
 * Vérifie que l'utilisateur est connecté et autorisé à accéder à un module.
 * Si ce n'est pas le cas, affiche un message d'erreur personnalisé et arrête l'exécution.
 *
 * Cette fonction est utile pour simplifier la vérification d'accès dans les fichiers front-end
 * (ex : création ou édition de campagnes, accès à une section réservée).
 *
 * @param string $module      Nom du module (ex : 'giveaways')
 * @param string $action_desc Description de l'action (ex : 'create a campaign')
 * @return bool true si l'utilisateur est connecté et autorisé, false sinon
 */
function admin_lab_require_access(string $module, string $action_desc): bool {
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('You must be logged in to ' . $action_desc . '.', 'me5rine-lab') . '</p>';
        return false;
    }

    if (!admin_lab_user_has_allowed_role($module)) {
        echo '<p>' . esc_html__('You are not authorized to ' . $action_desc . '.', 'me5rine-lab') . '</p>';
        return false;
    }

    return true;
}

/**
 * Vérifie si l'utilisateur connecté a au moins un des types de comptes donnés.
 * Si ce n'est pas le cas, affiche un message d'erreur et arrête l'exécution.
 *
 * @param array $types Liste des slugs de types de comptes autorisés.
 * @param string|null $custom_error_message Message personnalisé (sinon message par défaut).
 * @return bool true si accès autorisé, false sinon.
 */
function admin_lab_require_account_types(array $types, ?string $custom_error_message = null): bool {
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('You must be logged in to access this section.', 'me5rine-lab') . '</p>';
        return false;
    }

    $user_id = get_current_user_id();

    foreach ($types as $type) {
        if (admin_lab_is_account_type_active_here($type, $user_id)) {
            return true;
        }
    }

    echo '<p>' . esc_html($custom_error_message ?: __('You are not authorized to access this section.', 'me5rine-lab')) . '</p>';
    return false;
}

/**
 * Récupère une propriété d'un tableau ou d'un objet, avec une valeur par défaut si la clé n'existe pas.
 *
 * Cette fonction est utile pour manipuler des structures de données dynamiques (ex: JSON décodé),
 * qui peuvent être des objets (stdClass) ou des tableaux associatifs.
 *
 * @param array|object $source  Source à inspecter (tableau associatif ou stdClass)
 * @param string       $key     Nom de la clé ou propriété à récupérer
 * @param mixed        $default Valeur par défaut si la clé n'existe pas
 *
 * @return mixed La valeur trouvée ou la valeur par défaut
 */
function admin_lab_get_property($source, string $key, $default = '') {
    if (is_array($source) && isset($source[$key])) {
        return $source[$key];
    }
    if (is_object($source) && isset($source->$key)) {
        return $source->$key;
    }
    return $default;
}

/**
 * Enregistre ou supprime une option dans la table options du site principal.
 *
 * Si la valeur est vide, l'option est supprimée. Sinon, elle est insérée ou mise à jour.
 *
 * @param string $key   Nom de l'option à enregistrer.
 * @param string $value Valeur à enregistrer. Si vide, l'option est supprimée.
 * @return bool|int Nombre de lignes affectées ou false en cas d'échec.
 */
function admin_lab_save_global_option($key, $value) {
    global $wpdb;
    $prefix = defined('ME5RINE_LAB_CUSTOM_PREFIX') ? ME5RINE_LAB_CUSTOM_PREFIX : $wpdb->prefix;
    $table = $prefix . 'options';

    if ($value === '') {
        return $wpdb->delete($table, ['option_name' => $key], ['%s']);
    }

    $wpdb->delete($table, ['option_name' => $key], ['%s']);

    return $wpdb->insert($table, [
        'option_name'  => $key,
        'option_value' => $value
    ], ['%s', '%s']);
}

/**
 * Supprime une option dans la table options du site principal.
 *
 * @param string $key Nom de l'option à supprimer.
 * @return bool True si une ligne a été supprimée, false sinon.
 */
function admin_lab_delete_global_option($key) {
    global $wpdb;
    $prefix = defined('ME5RINE_LAB_CUSTOM_PREFIX') ? ME5RINE_LAB_CUSTOM_PREFIX : $wpdb->prefix;
    $table = $prefix . 'options';

    return (bool) $wpdb->delete($table, ['option_name' => $key], ['%s']);
}

/**
 * Récupère dynamiquement la liste des domaines/sites partageant la base.
 */
function admin_lab_get_available_sites() {
    global $wpdb;

    $sites = [];
    $tables = $wpdb->get_results("SHOW TABLES LIKE '%\\_options'", ARRAY_N);

    foreach ($tables as $table) {
        $table_name = $table[0];

        $site_url = $wpdb->get_var("SELECT option_value FROM {$table_name} WHERE option_name = 'siteurl' LIMIT 1");
        $blogname = $wpdb->get_var("SELECT option_value FROM {$table_name} WHERE option_name = 'blogname' LIMIT 1");

        if ($site_url) {
            $domain = parse_url($site_url, PHP_URL_HOST);
            $sites[$domain] = $blogname ?: $domain;
        }
    }

    return $sites;
}

/**
 * Vérifie si le site actuel est le site principal basé sur le préfixe de la table.
 *
 * @return bool
 */
function admin_lab_is_main_site() {
    static $is_main_site = null;

    if ($is_main_site !== null) {
        return $is_main_site;
    }

    global $wpdb;

    if (defined('ME5RINE_LAB_GLOBAL_PREFIX')) {
        $site_prefix = ME5RINE_LAB_GLOBAL_PREFIX;

        $site_url = $wpdb->get_var("SELECT option_value FROM {$site_prefix}options WHERE option_name = 'siteurl' LIMIT 1");

        $is_main_site = !empty($site_url);
        return $is_main_site;
    }

    $is_main_site = false;
    return false;
}

/**
 * Récupère la liste de tous les préfixes de sites disponibles.
 *
 * Cette fonction scanne toutes les tables de la base de données
 * et récupère les préfixes utilisés pour les sites en se basant
 * sur l'existence des tables `options`.
 *
 * Exemple de retour : ['wp_', 'hub_', 'lab_', 'm5_']
 *
 * @return array Liste des préfixes de sites.
 */
function admin_lab_get_all_sites_prefixes() {
    global $wpdb;
    $all_tables = $wpdb->get_col('SHOW TABLES');
    $prefixes = [];

    foreach ($all_tables as $table) {
        if (substr($table, -7) === 'options') {
            $prefix = str_replace('options', '', $table);
            if (!empty($prefix)) {
                $prefixes[] = $prefix;
            }
        }
    }

    return $prefixes;
}

/**
 * Récupère dynamiquement le domaine d'un site à partir de son préfixe en lisant la table options.
 * Optimisé avec un cache statique pour éviter les accès répétés à la base de données.
 *
 * @param string $prefix Préfixe du site (ex: hub_, lab_, m5_).
 * @return string Domaine du site (ex: admin.me5rine-lab.com) ou une chaîne vide si non trouvé.
 */
function admin_lab_get_site_domain_from_prefix($prefix) {
    static $domains_cache = [];

    if (isset($domains_cache[$prefix])) {
        return $domains_cache[$prefix];
    }

    global $wpdb;

    if (empty($prefix)) {
        $domains_cache[$prefix] = '';
        return '';
    }

    $options_table = $prefix . 'options'; // Ex: hub_options, lab_options, m5_options

    $siteurl = $wpdb->get_var(
        $wpdb->prepare("SELECT option_value FROM {$options_table} WHERE option_name = %s", 'siteurl')
    );

    if (!$siteurl) {
        $domains_cache[$prefix] = '';
        return '';
    }

    $parsed = parse_url($siteurl);
    $domain = $parsed['host'] ?? '';

    $domains_cache[$prefix] = $domain;
    return $domain;
}

/**
 * Récupère l'ID de l'utilisateur Me5rine LAB global (compte source pour les réseaux sociaux).
 *
 * @return int|null ID utilisateur ou null si aucun.
 */
function admin_lab_get_global_admin_lab_account_id(): ?int {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . 'options';
    $option_name = 'admin_lab_account_id';

    $value = $wpdb->get_var(
        $wpdb->prepare("SELECT option_value FROM {$table} WHERE option_name = %s", $option_name)
    );

    if ($value === null) {
        return null;
    }

    $int_value = (int) $value;
    return $int_value > 0 ? $int_value : null;
}

/**
 * Définit l'ID de l'utilisateur Me5rine LAB global.
 *
 * @param int|null $user_id ID utilisateur à enregistrer ou null pour effacer.
 */
function admin_lab_set_global_admin_lab_account_id(?int $user_id): void {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . 'options';
    $option_name = 'admin_lab_account_id';

    if ($user_id === null || $user_id <= 0) {
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE option_name = %s", $option_name)
        );
    } else {
        $exists = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE option_name = %s", $option_name)
        );

        if ($exists) {
            $wpdb->query(
                $wpdb->prepare("UPDATE {$table} SET option_value = %d WHERE option_name = %s", $user_id, $option_name)
            );
        } else {
            $wpdb->query(
                $wpdb->prepare("INSERT INTO {$table} (option_name, option_value, autoload) VALUES (%s, %d, 'yes')", $option_name, $user_id)
            );
        }
    }
}

/**
 * Récupère la liste des modules détectés dans le plugin Me5rine LAB.
 *
 * @return array Liste des modules [slug => label].
 */
function admin_lab_get_modules(): array {
    $modules = [];
    $modules_dir = plugin_dir_path(__FILE__) . '../../modules/'; // Remonte d'un dossier depuis admin/ ou shortcodes/

    if (!is_dir($modules_dir)) {
        return $modules;
    }

    foreach (scandir($modules_dir) as $module_slug) {
        if ($module_slug === '.' || $module_slug === '..') {
            continue;
        }

        $module_file = $modules_dir . $module_slug . '/' . $module_slug . '.php';
        if (is_file($module_file)) {
            $modules[$module_slug] = ucfirst(str_replace('-', ' ', $module_slug));
        }
    }

    return $modules;
}

/**
 * Récupère la liste des campagnes marketing disponibles pour affichage dans un champ select.
 *
 * Cette fonction interroge la table `marketing_links` pour obtenir l'ID et le slug de toutes
 * les campagnes marketing existantes, triées par date de création décroissante.
 * 
 * Elle retourne un tableau associatif où :
 * - la clé est l'ID numérique de la campagne,
 * - la valeur est le slug de la campagne (utilisé comme libellé dans les champs select).
 *
 * @return array Tableau associatif des campagnes disponibles, au format [id => campaign_slug]
 */
function admin_lab_get_marketing_campaigns_for_select(): array {
    global $wpdb;
    $table = admin_lab_getTable('marketing_links');
    $results = $wpdb->get_results("SELECT id, campaign_slug FROM $table ORDER BY created_at DESC");

    $campaigns = [];
    foreach ($results as $row) {
        $campaigns[$row->id] = $row->campaign_slug;
    }
    return $campaigns;
}

/**
 * Récupère une campagne marketing en base de données à partir de son ID.
 *
 * @param int $id L'identifiant unique de la campagne.
 * @return object|null Un objet contenant les données de la campagne si elle existe, sinon null.
 */
function admin_lab_get_campaign_by_id($id) {
    global $wpdb;
    $table = admin_lab_getTable('marketing_links');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
}

/**
 * Récupère les couleurs globales définies dans Elementor.
 *
 * @return array Tableau associatif [clé] => [valeur hex]
 */
function admin_lab_get_elementor_colors_from_db(): array {
    $options = get_option('elementor_global_settings');

    if (!is_array($options)) return [];

    $colors = [];

    if (!empty($options['system_colors']) && is_array($options['system_colors'])) {
        foreach ($options['system_colors'] as $color) {
            if (!empty($color['_id']) && !empty($color['color'])) {
                $colors[$color['_id']] = [
                    'title' => $color['title'] ?? $color['_id'],
                    'color' => $color['color'],
                ];
            }
        }
    }

    if (!empty($options['custom_colors']) && is_array($options['custom_colors'])) {
        foreach ($options['custom_colors'] as $color) {
            if (!empty($color['_id']) && !empty($color['color'])) {
                $colors[$color['_id']] = [
                    'title' => $color['title'] ?? $color['_id'],
                    'color' => $color['color'],
                ];
            }
        }
    }

    return $colors;
}

/**
 * Extrait toutes les couleurs CSS globales définies dans le kit Elementor actif.
 *
 * @return array Tableau associatif [slug => hex].
 */
/**
 * Extrait toutes les couleurs CSS globales définies dans le kit Elementor actif.
 *
 * @return array Tableau associatif [slug => hex].
 */
function admin_lab_get_elementor_kit_colors() {
    $kit_id = (int) get_option('admin_lab_elementor_kit_id');
    if (!$kit_id) return [];

    $css_path = wp_normalize_path(WP_CONTENT_DIR . "/uploads/elementor/css/post-{$kit_id}.css");
    if (!file_exists($css_path)) return [];

    $css = file_get_contents($css_path);

    // Match plus robuste, même sur fichier compressé
    if (!preg_match('/\.elementor-kit-\d+\s*\{([^}]+)\}/s', $css, $matches)) return [];

    $lines = explode(';', $matches[1]);
    $colors = [];

    foreach ($lines as $line) {
        if (preg_match('/--e-global-color-([a-zA-Z0-9_-]+)\s*:\s*(#[0-9a-fA-F]{6})/', trim($line), $m)) {
            $colors[$m[1]] = $m[2];
        }
    }

    return $colors;
}

/**
 * Récupère la couleur hexadécimale associée à une variable CSS globale Elementor.
 *
 * @param string $slug Le slug de la variable (ex: 'primary', 'secondary', 'accent', etc.).
 * @return string|null La valeur hexadécimale (ex: #0485C8) ou null si introuvable.
 */
function admin_lab_get_elementor_color($slug) {
    static $cached_colors = null;

    // Si les couleurs sont déjà chargées, retourne depuis le cache
    if ($cached_colors === null) {
        $cached_colors = admin_lab_get_elementor_kit_colors();
    }

    return $cached_colors[$slug] ?? null;
}

/**
 * Génère une balise <i> avec la classe Font Awesome appropriée.
 *
 * Cette fonction prend en entrée une chaîne représentant une icône Font Awesome
 * (comme "fab fa-twitter" ou "fa-facebook") et détermine automatiquement le préfixe
 * correct (fab, fas, far, etc.). Si aucun préfixe valide n'est détecté, "fas" est utilisé par défaut.
 *
 * Elle protège aussi contre les entrées vides ou invalides en utilisant une icône de secours.
 *
 * @param string $fa      Classe(s) FA (ex: "fab fa-twitter", "fa-facebook", "fas fa-user", etc.)
 * @param string $default Icône FA par défaut si l'entrée est vide ou invalide (défaut : "fa-question")
 *
 * @return string Balise HTML <i> avec les classes Font Awesome correctement formatées
 *
 * @example
 *     echo admin_lab_render_fa_icon('fab fa-twitter');   // <i class="fab fa-twitter"></i>
 *     echo admin_lab_render_fa_icon('fa-facebook');      // <i class="fas fa-facebook"></i>
 *     echo admin_lab_render_fa_icon('');                 // <i class="fas fa-question"></i>
 */
function admin_lab_render_fa_icon( $fa = '', $default = 'fa-question' ) {
	$fa = trim( $fa ?: $default );
	$parts = preg_split( '/\s+/', $fa );

	// Préfixes Font Awesome valides
	$prefixes = ['fab', 'fas', 'far', 'fal', 'fad'];
	$prefix = 'fas'; // fallback par défaut

	if ( in_array( $parts[0], $prefixes ) ) {
		$prefix = $parts[0];
		$icon = $parts[1] ?? $default;
	} else {
		$icon = $parts[0];
	}

	return sprintf( '<i class="%s %s"></i>', esc_attr( $prefix ), esc_attr( $icon ) );
}