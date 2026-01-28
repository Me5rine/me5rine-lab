<?php
// File: includes/api/clicksngames-api.php
// API ClicksNGames globale : utilisable par tous les modules (Comparator, Game Servers, etc.)
// Les clés sont configurées dans Me5rine LAB → Settings → API Keys.

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère les paramètres API ClicksNGames (stockés dans Settings → API Keys).
 *
 * @return array { api_base: string, api_token: string }
 */
function admin_lab_get_clicksngames_api_settings() {
    $options = get_option('admin_lab_clicksngames_api', []);
    if (!is_array($options)) {
        $options = [];
    }
    return wp_parse_args($options, [
        'api_base'  => '',
        'api_token' => '',
    ]);
}

/**
 * Appel brut à l’API ClicksNGames (utilise les clés de Settings → API Keys).
 *
 * @param string $path       Chemin relatif, ex: 'games/123'
 * @param array  $query_args Paramètres de query string
 * @return array|WP_Error
 */
function admin_lab_clicksngames_api_request($path, $query_args = []) {
    $settings = admin_lab_get_clicksngames_api_settings();

    $base = !empty($settings['api_base'])
        ? untrailingslashit($settings['api_base'])
        : 'https://api.clicksngames.com/api';

    $url = $base . '/' . ltrim($path, '/');

    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }

    $args = [
        'method'      => 'GET',
        'timeout'     => 10,
        'redirection' => 3,
        'blocking'    => true,
        'headers'     => [
            'Accept' => 'application/json',
        ],
    ];

    if (!empty($settings['api_token'])) {
        $args['headers']['Authorization'] = 'Bearer ' . $settings['api_token'];
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            'api_error',
            'Invalid API status code: ' . $code . ' for URL: ' . $url
        );
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('api_error', 'Invalid JSON response from ClicksNGames API.');
    }

    return $data;
}

/**
 * Récupère un jeu par ID depuis ClicksNGames (format normalisé pour usage inter-modules).
 *
 * @param int $game_id
 * @return array|WP_Error Tableau [ id, name, slug, logo ] ou WP_Error
 */
function admin_lab_clicksngames_get_game_by_id($game_id) {
    $game_id = (int) $game_id;
    if ($game_id <= 0) {
        return new WP_Error('invalid_game_id', __('Invalid game ID.', 'me5rine-lab'));
    }

    $data = admin_lab_clicksngames_api_request('games/' . $game_id, ['populate' => '*']);

    if (is_wp_error($data)) {
        return $data;
    }

    $game = isset($data['data']) ? $data['data'] : $data;

    if (isset($game['attributes'])) {
        $attrs = $game['attributes'];
        return [
            'id'   => $game['id'] ?? $game_id,
            'name' => $attrs['name'] ?? '',
            'slug' => $attrs['slug'] ?? '',
            'logo' => isset($attrs['logo']['data']['attributes']['url'])
                ? $attrs['logo']['data']['attributes']['url']
                : '',
        ];
    }

    return new WP_Error('api_not_available', __('ClicksNGames API is not available.', 'me5rine-lab'));
}
