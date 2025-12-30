<?php
// File: modules/remote-news/elementor/remote-news-elementor-queries.php
if (!defined('ABSPATH')) exit;

/**
 * Convert category slugs to term IDs for a given taxonomy.
 *
 * @param array|string $slugs
 * @param string       $taxonomy
 * @return int[]
 */
function admin_lab_term_ids_from_slugs($slugs, $taxonomy = 'category') {
    if (empty($slugs)) {
        return [];
    }

    // Accept CSV or array
    if (!is_array($slugs)) {
        $slugs = array_filter(array_map('trim', explode(',', (string) $slugs)));
    }

    $slugs = array_values(array_unique(array_map('sanitize_title', $slugs)));
    if (!$slugs) {
        return [];
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'slug'       => $slugs,
        'fields'     => 'ids',
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    return array_map('intval', $terms);
}

/**
 * Normalize a single query profile row (from DB).
 *
 * Expected normalized keys:
 * - query_id      (string)
 * - post_type     (string) default 'remote_news'
 * - sources       (array of source keys)
 * - include_cats  (array of local slugs)
 * - exclude_cats  (array of local slugs)
 * - limit         (int)
 * - orderby       (date|modified|title|rand)
 * - order         (ASC|DESC)
 *
 * @param string $qid
 * @param array  $row
 * @return array
 */
function admin_lab_remote_news_normalize_profile($qid, $row) {
    // Base profile
    $profile = [
        'query_id'     => sanitize_key($qid ?: ($row['query_id'] ?? '')),
        'post_type'    => 'remote_news',
        'sources'      => [],
        'include_cats' => [],
        'exclude_cats' => [],
        'limit'        => 12,
        'orderby'      => 'date',
        'order'        => 'DESC',
    ];

    // post_type (optional, default remote_news)
    if (!empty($row['post_type'])) {
        $profile['post_type'] = sanitize_key($row['post_type']);
    }

    // sources: JSON string or array
    if (isset($row['sources'])) {
        $srcs = $row['sources'];

        if (!is_array($srcs)) {
            $decoded = json_decode((string) $srcs, true);
            $srcs    = is_array($decoded) ? $decoded : [];
        }

        $profile['sources'] = array_values(
            array_unique(
                array_filter(
                    array_map('sanitize_key', (array) $srcs)
                )
            )
        );
    }

    // include/exclude cats: CSV or array of slugs (LOCAL taxonomy)
    $incl = $row['include_cats'] ?? [];
    if (!is_array($incl)) {
        $incl = array_filter(array_map('trim', explode(',', (string) $incl)));
    }
    $profile['include_cats'] = array_values(
        array_unique(
            array_map('sanitize_title', $incl)
        )
    );

    $excl = $row['exclude_cats'] ?? [];
    if (!is_array($excl)) {
        $excl = array_filter(array_map('trim', explode(',', (string) $excl)));
    }
    $profile['exclude_cats'] = array_values(
        array_unique(
            array_map('sanitize_title', $excl)
        )
    );

    // limit
    if (isset($row['limit_items']) || isset($row['limit'])) {
        $profile['limit'] = max(1, (int) ($row['limit_items'] ?? $row['limit']));
    }

    // orderby
    $ob         = $row['orderby'] ?? 'date';
    $allowed_ob = ['date', 'modified', 'title', 'rand'];
    $profile['orderby'] = in_array($ob, $allowed_ob, true) ? $ob : 'date';

    // order
    $od               = strtoupper((string) ($row['order'] ?? $row['sort_order'] ?? 'DESC'));
    $profile['order'] = ($od === 'ASC') ? 'ASC' : 'DESC';

    /**
     * Filter: allow last-second customization of a normalized profile.
     *
     * @param array $profile Normalized profile.
     * @param array $row     Raw DB row.
     */
    return apply_filters('admin_lab_remote_news/normalized_profile', $profile, $row);
}

/**
 * Apply a normalized profile to a WP_Query instance.
 *
 * Utilisé pour :
 * - Elementor core : elementor/query/{query_id}
 * - Elementor Pro  : elementor_pro/posts/query/{query_id}
 * - Element Pack   : element_pack/query/{query_id}
 *
 * IMPORTANT :
 *  - On ne fait rien si la query n'est pas marquée avec _adminlab_remote_query_id
 *    correspondant au profile courant (sécurité anti-contamination).
 *
 * @param WP_Query $query
 * @param array    $profile Normalized profile
 */
function admin_lab_remote_news_apply_profile_to_query($query, array $profile) {
    if (empty($profile['query_id'])) {
        return;
    }

    // Sécurité : on ne touche qu'aux WP_Query
    if (!($query instanceof WP_Query)) {
        return;
    }

    // Sécurité supplémentaire :
    // on ne traite que les requêtes qu'on a explicitement marquées
    // dans nos callbacks de hook (cf. enregistrement plus bas).
    $marker = $query->get('_adminlab_remote_query_id');
    if ($marker !== $profile['query_id']) {
        return;
    }

    // Post type (default remote_news)
    $query->set('post_type', $profile['post_type'] ?: 'remote_news');

    // Order & Orderby
    $query->set('orderby', $profile['orderby']);
    $query->set('order',   $profile['order']);

    // Respect existing posts_per_page; otherwise apply limit from profile
    $ppp = (int) $query->get('posts_per_page');
    if ($ppp <= 0 && !empty($profile['limit'])) {
        $query->set('posts_per_page', (int) $profile['limit']);
    }

    /**
     * IMPORTANT : on REPART DE ZERO pour tax_query et meta_query
     * pour éviter une accumulation entre plusieurs widgets
     * si un même objet WP_Query est réutilisé (cas très probable avec Element Pack).
     */

    // --- TAX QUERY (on remplace) ---
    $tax_query = [];

    $include_ids = admin_lab_term_ids_from_slugs($profile['include_cats'], 'category');
    if ($include_ids) {
        $tax_query[] = [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $include_ids,
            'operator' => 'IN',
        ];
    }

    $exclude_ids = admin_lab_term_ids_from_slugs($profile['exclude_cats'], 'category');
    if ($exclude_ids) {
        $tax_query[] = [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $exclude_ids,
            'operator' => 'NOT IN',
        ];
    }

    if ($tax_query) {
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $query->set('tax_query', $tax_query);
    } else {
        // Si rien, on nettoie le précédent éventuel
        $query->set('tax_query', []);
    }

    // --- META QUERY (on remplace) ---
    $meta_query = [];
    $sources    = array_values(
        array_unique(
            array_filter(
                array_map('sanitize_key', (array) $profile['sources'])
            )
        )
    );

    if ($sources) {
        $meta_query[] = [
            'key'     => '_remote_source_site',
            'value'   => $sources,
            'compare' => 'IN',
        ];
    }

    if ($meta_query) {
        if (count($meta_query) > 1) {
            $meta_query['relation'] = 'AND';
        }
        $query->set('meta_query', $meta_query);
    } else {
        // Nettoyage si pas de filtre
        $query->set('meta_query', []);
    }

    /**
     * Final hook: allow last-second WP_Query adjustments per query_id.
     *
     * @param WP_Query $query
     * @param array    $profile Normalized profile.
     */
    do_action('admin_lab_remote_news/elementor_query_built', $query, $profile);
}

/**
 * Helper global: get all normalized profiles indexed by query_id.
 *
 * @return array [query_id => profile]
 */
function admin_lab_remote_news_get_all_profiles() {
    static $profiles = null;

    if ($profiles !== null) {
        return $profiles;
    }

    $profiles = [];

    if (!function_exists('remote_news_queries_all')) {
        return $profiles;
    }

    // Vérifier que admin_lab_getTable existe avant d'appeler remote_news_queries_all
    if (!function_exists('admin_lab_getTable')) {
        return $profiles;
    }

    try {
        $rows = remote_news_queries_all(); // rows from lab_remote_news_queries
        
        if (!is_array($rows)) {
            return $profiles;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $qid  = $row['query_id'] ?? '';
            if (empty($qid)) {
                continue;
            }
            
            $norm = admin_lab_remote_news_normalize_profile($qid, $row);

            if (!empty($norm['query_id'])) {
                $profiles[$norm['query_id']] = $norm;
            }
        }
    } catch (Exception $e) {
        // En cas d'erreur, retourner un tableau vide
        return $profiles;
    }

    return $profiles;
}

/**
 * Register hooks for Elementor, Elementor Pro & Element Pack
 * for each Query ID defined in DB.
 */
add_action('init', function () {
    $profiles = admin_lab_remote_news_get_all_profiles();
    if (!$profiles) {
        return;
    }

    // Helper interne pour éviter de dupliquer le callback
    $register = function ($hook_base, $qid, $profile) {
        add_action(
            "{$hook_base}{$qid}",
            function ($query) use ($profile, $qid) {
                // On marque la query avec l'ID, ce qui permet à apply_profile
                // de vérifier qu'il s'agit bien de la requête prévue.
                if ($query instanceof WP_Query) {
                    $query->set('_adminlab_remote_query_id', $qid);
                }
                admin_lab_remote_news_apply_profile_to_query($query, $profile);
            },
            999,
            1
        );
    };

    foreach ($profiles as $qid => $profile) {
        // Elementor core
        $register('elementor/query/', $qid, $profile);

        // Elementor Pro custom query filter
        $register('elementor_pro/posts/query/', $qid, $profile);

        // Element Pack Pro (doc officielle BDThemes)
        $register('element_pack/query/', $qid, $profile);
    }
}, 20);
