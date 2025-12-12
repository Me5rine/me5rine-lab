<?php
// File: modules/remote-news/functions/remote-news-ingestion.php
if (!defined('ABSPATH')) exit;

/**
 * Remote News ingestion flow:
 * - Manual sync handler (admin-post)
 * - CRON schedule
 * - Ingestion loops (DB-driven: queries first, then fallback to sources)
 * - Cross-prefix fetch (same DB, different table prefixes)
 * - Category mapping (remote slugs -> local category terms)
 * - Anti-dup (origin_key + remote_id)
 * - Per-source sideload behavior (or mirror remote attachment / thumbnail URL)
 *
 * Text domain: me5rine-lab
 */

/* ----------------------------------------------------------------
 *  Admin "Sync now" handler
 * ---------------------------------------------------------------- */
add_action('admin_post_remote_news_sync_now', function () {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'me5rine-lab'));
    }

    if (isset($_REQUEST['_wpnonce'])) {
        check_admin_referer('remote_news_sync_now');
    }

    $result = remote_news_run_ingestion();

    $msg = sprintf(
        __('Sync finished. Imported: %1$d, Updated: %2$d, Skipped: %3$d', 'me5rine-lab'),
        (int) $result['imported'],
        (int) $result['updated'],
        (int) $result['skipped']
    );

    $page = defined('ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG') ? ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG : 'admin-lab-remote-news';
    $tab  = isset($_REQUEST['tab']) ? sanitize_key($_REQUEST['tab']) : 'overview';

    $url = add_query_arg([
        'page'      => $page,
        'tab'       => $tab,
        'rn_notice' => 'updated',
        'rn_msg'    => rawurlencode($msg),
    ], admin_url('admin.php'));

    wp_safe_redirect($url);
    exit;
});


/* ----------------------------------------------------------------
 *  CRON scheduling
 * ---------------------------------------------------------------- */
add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['quarterhour'])) {
        $schedules['quarterhour'] = [
            'interval' => 900,
            'display'  => __('Every 15 minutes', 'me5rine-lab'),
        ];
    }
    return $schedules;
});

add_action('remote_news_sync', 'remote_news_run_ingestion');

/**
 * Schedule the cron if the module is active.
 */
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (!is_array($active)) {
        $active = [];
    }

    // Module not active => unschedule
    if (!in_array('remote_news', $active, true) && !in_array('subscription', $active, true)) {
        $ts = wp_next_scheduled('remote_news_sync');
        if ($ts) {
            wp_unschedule_event($ts, 'remote_news_sync');
        }
        return;
    }

    // Fixed interval: quarterhour
    $interval = 'quarterhour';

    if (!wp_next_scheduled('remote_news_sync')) {
        wp_schedule_event(time() + 90, $interval, 'remote_news_sync');
    }
}, 20);


/* ----------------------------------------------------------------
 *  Ingestion Orchestrator (DB-first with fallback)
 * ---------------------------------------------------------------- */

/**
 * Run ingestion.
 * - If DB queries exist -> ingest per Query ID profile
 * - Else fallback -> ingest all sources with default profile derived from source row
 *
 * @return array{imported:int,updated:int,skipped:int}
 */
function remote_news_run_ingestion() {
    $totals = [
        'imported' => 0,
        'updated'  => 0,
        'skipped'  => 0,
    ];

    // IDs par origin_key dans cette exécution
    $window_ids_by_origin = [];

    // 1) DB queries (Query IDs)
    $queries = function_exists('remote_news_queries_all') ? remote_news_queries_all() : [];
    if (!empty($queries)) {
        foreach ($queries as $profile) {
            $qid = is_array($profile) ? ($profile['query_id'] ?? '') : '';
            $res = remote_news_run_profile($qid, $profile);

            $totals['imported'] += $res['imported'];
            $totals['updated']  += $res['updated'];
            $totals['skipped']  += $res['skipped'];

            if (!empty($res['origin_ids']) && is_array($res['origin_ids'])) {
                foreach ($res['origin_ids'] as $origin_key => $ids) {
                    if (!isset($window_ids_by_origin[$origin_key])) {
                        $window_ids_by_origin[$origin_key] = [];
                    }
                    $window_ids_by_origin[$origin_key] = array_merge(
                        $window_ids_by_origin[$origin_key],
                        (array) $ids
                    );
                }
            }
        }

        // Nettoyage global après ingestion
        remote_news_cleanup_out_of_window($window_ids_by_origin);
        remote_news_cleanup_orphans_by_source();

        return $totals;
    }

    // 2) Fallback: no queries => ingest all sources directly
    $sources = function_exists('remote_news_sources_all') ? remote_news_sources_all() : [];

    foreach ($sources as $src) {
        $limit   = max(1, (int) ($src['limit_items'] ?? $src['limit'] ?? 10));
        $max_age = max(1, (int) ($src['max_age_days'] ?? 14));

        $profile = [
            'limit'        => $limit,
            'max_age_days' => $max_age,
        ];

        $origin_key = remote_news_build_origin_key($src);

        $res = remote_news_ingest_from_prefix($src['source_key'], $src, $profile, $limit, $max_age);

        $totals['imported'] += $res['imported'];
        $totals['updated']  += $res['updated'];
        $totals['skipped']  += $res['skipped'];

        if (!empty($res['remote_ids'])) {
            if (!isset($window_ids_by_origin[$origin_key])) {
                $window_ids_by_origin[$origin_key] = [];
            }
            $window_ids_by_origin[$origin_key] = array_merge(
                $window_ids_by_origin[$origin_key],
                $res['remote_ids']
            );
        }
    }

    // Nettoyage global après ingestion
    remote_news_cleanup_out_of_window($window_ids_by_origin);
    remote_news_cleanup_orphans_by_source();

    return $totals;
}

/**
 * Run a single profile (Query ID style).
 *
 * $profile (DB row) keys expected:
 *  - sources: array OR JSON string of source keys
 *  - limit_items / max_age_days
 *
 * @return array{imported:int,updated:int,skipped:int}
 */
function remote_news_run_profile($profile_key, $profile) {
    $totals = [
        'imported' => 0,
        'updated'  => 0,
        'skipped'  => 0,
    ];

    // IDs par origin_key pour cette profile
    $window_ids_by_origin = [];

    // Limite de base pour la query (fallback 12)
    $profile_limit = isset($profile['limit_items'])
        ? (int) $profile['limit_items']
        : (int) ($profile['limit'] ?? 12);

    if ($profile_limit <= 0) {
        $profile_limit = 12;
    }

    // Default max age (days) if source does not override
    $default_max_age = 14;

    // Available sources
    $all_sources = function_exists('remote_news_sources_all') ? remote_news_sources_all() : [];
    $map_sources = [];
    foreach ($all_sources as $row) {
        $map_sources[$row['source_key']] = $row;
    }

    // Normalize sources from query
    $sources = $profile['sources'] ?? [];
    if (!is_array($sources)) {
        $decoded = json_decode((string) $sources, true);
        $sources = is_array($decoded) ? $decoded : [];
    }

    foreach ($sources as $source_key) {
        if (empty($map_sources[$source_key])) {
            continue;
        }

        $src = $map_sources[$source_key];

        $src_max_age = isset($src['max_age_days'])
            ? max(1, (int) $src['max_age_days'])
            : $default_max_age;

        // Limite côté source (peut être 0 = non définie)
        $source_limit = isset($src['limit_items']) ? (int) $src['limit_items'] : 0;

        // Calcul de la limite finale par source :
        // - si query ET source ont une limite => on prend le MIN
        // - sinon on prend celle qui est définie
        if ($profile_limit > 0 && $source_limit > 0) {
            $src_limit = min($profile_limit, $source_limit);
        } elseif ($source_limit > 0) {
            $src_limit = $source_limit;
        } else {
            $src_limit = $profile_limit;
        }

        $src_limit = max(1, (int) $src_limit);

        $origin_key = remote_news_build_origin_key($src);

        $res = remote_news_ingest_from_prefix($source_key, $src, $profile, $src_limit, $src_max_age);

        $totals['imported'] += $res['imported'];
        $totals['updated']  += $res['updated'];
        $totals['skipped']  += $res['skipped'];

        if (!empty($res['remote_ids'])) {
            if (!isset($window_ids_by_origin[$origin_key])) {
                $window_ids_by_origin[$origin_key] = [];
            }
            $window_ids_by_origin[$origin_key] = array_merge(
                $window_ids_by_origin[$origin_key],
                $res['remote_ids']
            );
        }
    }

    // Dedup final
    foreach ($window_ids_by_origin as $origin_key => $ids) {
        $window_ids_by_origin[$origin_key] = array_values(array_unique(array_map('intval', $ids)));
    }

    // On retourne les totaux + les IDs par origin
    return [
        'imported'   => $totals['imported'],
        'updated'    => $totals['updated'],
        'skipped'    => $totals['skipped'],
        'origin_ids' => $window_ids_by_origin,
    ];
}

/* ----------------------------------------------------------------
 *  Cross-prefix fetching + upsert
 * ---------------------------------------------------------------- */

/**
 * Fetch remote posts (same DB, different prefix), transform and upsert locally.
 * Applies:
 *  - age filter (max_age_days)
 *  - per-source include_cats (REMOTE slugs) if provided on the source row
 *  - category mapping (remote -> local slugs) during upsert
 *  - per-source sideload behavior OR attachment mirroring (no sideload)
 *
 * @return array{imported:int,updated:int,skipped:int,remote_ids:int[]}
 */
function remote_news_ingest_from_prefix($source_key, $source, $profile, $limit, $max_age) {
    global $wpdb;

    $counts = [
        'imported'   => 0,
        'updated'    => 0,
        'skipped'    => 0,
        'remote_ids' => [],
    ];

    $prefix   = sanitize_key($source['table_prefix'] ?? '');
    $site_url = esc_url_raw($source['site_url'] ?? '');

    if (!$prefix || !$site_url) {
        return $counts;
    }

    // Tables (remote)
    $posts_table = $prefix . 'posts';
    $pm_table    = $prefix . 'postmeta';
    $tr_table    = $prefix . 'term_relationships';
    $tt_table    = $prefix . 'term_taxonomy';
    $terms_table = $prefix . 'terms';

    // include_cats from source (REMOTE slugs)
    $include_cats = [];
    if (!empty($source['include_cats'])) {
        if (is_array($source['include_cats'])) {
            $include_cats = array_map('sanitize_title', $source['include_cats']);
        } else {
            $include_cats = array_filter(
                array_map(
                    'sanitize_title',
                    array_map('trim', explode(',', (string) $source['include_cats']))
                )
            );
        }
    }

    // Index de mapping des catégories (on le calcule une seule fois)
    $map_by_source = remote_news_build_category_map_index();

    // Batching
    $batch_size = 20;
    $processed  = 0;
    $limit      = max(1, (int) $limit);

    while ($processed < $limit) {
        $current_limit  = min($batch_size, $limit - $processed);
        $current_offset = $processed;

        // Construire la requête SQL pour ce batch
        if ($include_cats) {
            $placeholders = implode(',', array_fill(0, count($include_cats), '%s'));

            $params = array_merge(
                [$max_age],
                $include_cats,
                [$current_limit, $current_offset]
            );

            $sql = $wpdb->prepare("
                SELECT DISTINCT p.ID,
                       p.post_title,
                       p.post_excerpt,
                       p.post_content,
                       p.post_date_gmt,
                       p.post_author,
                       pm_thumb.meta_value AS thumb_id,
                       u.display_name AS author_name,
                       p.post_name
                FROM {$posts_table} p
                LEFT JOIN {$pm_table} pm_thumb
                    ON (pm_thumb.post_id = p.ID AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->users} u
                    ON (u.ID = p.post_author)
                JOIN {$tr_table} tr
                    ON (tr.object_id = p.ID)
                JOIN {$tt_table} tt
                    ON (tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy='category')
                JOIN {$terms_table} t
                    ON (t.term_id = tt.term_id)
                WHERE p.post_type = 'post'
                  AND p.post_status = 'publish'
                  AND p.post_date >= (NOW() - INTERVAL %d DAY)
                  AND t.slug IN ($placeholders)
                ORDER BY p.post_date DESC
                LIMIT %d OFFSET %d
            ", $params);

        } else {
            $sql = $wpdb->prepare("
                SELECT p.ID,
                       p.post_title,
                       p.post_excerpt,
                       p.post_content,
                       p.post_date_gmt,
                       p.post_author,
                       pm_thumb.meta_value AS thumb_id,
                       u.display_name AS author_name,
                       p.post_name
                FROM {$posts_table} p
                LEFT JOIN {$pm_table} pm_thumb
                    ON (pm_thumb.post_id = p.ID AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->users} u
                    ON (u.ID = p.post_author)
                WHERE p.post_type = 'post'
                  AND p.post_status = 'publish'
                  AND p.post_date >= (NOW() - INTERVAL %d DAY)
                ORDER BY p.post_date DESC
                LIMIT %d OFFSET %d
            ", $max_age, $current_limit, $current_offset);
        }

        $rows = $wpdb->get_results($sql);
        if (!$rows) {
            break;
        }

        // Categories (remote slugs) by post pour ce batch
        $post_ids     = array_map('intval', wp_list_pluck($rows, 'ID'));
        $cats_by_post = [];

        if ($post_ids) {
            $in_ids = implode(',', $post_ids);

            $cat_sql = "
                SELECT tr.object_id AS post_id, t.slug
                FROM {$tr_table} tr
                JOIN {$tt_table} tt
                  ON (tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy='category')
                JOIN {$terms_table} t
                  ON (t.term_id = tt.term_id)
                WHERE tr.object_id IN ($in_ids)
            ";

            $cat_rows = $wpdb->get_results($cat_sql);
            foreach ($cat_rows as $cr) {
                $pid = (int) $cr->post_id;
                $cats_by_post[$pid][] = sanitize_title($cr->slug);
            }
        }

        // Thumbnail URLs by attachment ID pour ce batch
        $thumb_ids       = array_unique(array_map('intval', array_filter(wp_list_pluck($rows, 'thumb_id'))));
        $thumb_url_by_id = [];

        if ($thumb_ids) {
            $in_thumbs = implode(',', $thumb_ids);
            $as3_table = $prefix . 'as3cf_items';
            $has_as3   = ($wpdb->get_var("SHOW TABLES LIKE '{$as3_table}'") === $as3_table);

            if ($has_as3) {
                $att_sql = "
                    SELECT p.ID,
                           p.guid,
                           a.bucket,
                           a.path
                    FROM {$posts_table} p
                    LEFT JOIN {$as3_table} a
                        ON a.source_id = p.ID
                    WHERE p.ID IN ({$in_thumbs})
                ";
                $att_rows = $wpdb->get_results($att_sql);

                foreach ($att_rows as $att) {
                    $aid = (int) $att->ID;
                    $url = '';

                    if (!empty($att->bucket) && !empty($att->path)) {
                        $url = 'https://' . $att->bucket . '/' . ltrim($att->path, '/');
                    } elseif (!empty($att->guid)) {
                        $url = $att->guid;
                    }

                    if ($aid && $url !== '') {
                        $thumb_url_by_id[$aid] = esc_url_raw($url);
                    }
                }
            } else {
                $att_sql = "
                    SELECT ID, guid
                    FROM {$posts_table}
                    WHERE ID IN ({$in_thumbs})
                ";
                $att_rows = $wpdb->get_results($att_sql);

                foreach ($att_rows as $att) {
                    $aid = (int) $att->ID;
                    $url = trim((string) $att->guid);
                    if ($aid && $url !== '') {
                        $thumb_url_by_id[$aid] = esc_url_raw($url);
                    }
                }
            }
        }

        // Process posts de ce batch
        foreach ($rows as $r) {
            $remote_id   = (int) $r->ID;
            $remote_cats = $cats_by_post[$remote_id] ?? [];

            // Safety filter: ensure categories still match include_cats (if defined)
            if ($include_cats && empty(array_intersect($include_cats, $remote_cats))) {
                $counts['skipped']++;
                $processed++;
                continue;
            }

            // Thumbnail URL
            $image_url = '';
            if (!empty($r->thumb_id) && isset($thumb_url_by_id[$r->thumb_id])) {
                $image_url = $thumb_url_by_id[$r->thumb_id];
            }

            // Normalized item
            $item = [
                'source_site'         => $source_key,
                'remote_id'           => $remote_id,
                'title'               => $r->post_title,
                'excerpt'             => $r->post_excerpt ?: wp_trim_words(wp_strip_all_tags($r->post_content), 40),
                'url'                 => remote_news_build_remote_permalink($site_url, $r->post_name, $remote_id),
                'date_gmt'            => gmdate('Y-m-d H:i:s', strtotime($r->post_date_gmt)),
                'author_name'         => $r->author_name,
                'image_url'           => $image_url,
                'categories'          => $remote_cats,
                // Pour le mirroring d'attachment
                'remote_thumb_id'     => !empty($r->thumb_id) ? (int) $r->thumb_id : 0,
                'source_table_prefix' => $prefix,
            ];

            $mode = remote_news_upsert_one_item($item, $source, $map_by_source);

            if ($mode === 'imported') {
                $counts['imported']++;
            } elseif ($mode === 'updated') {
                $counts['updated']++;
            } else {
                $counts['skipped']++;
            }

            // On mémorise ce remote_id dans la fenêtre
            $counts['remote_ids'][] = $remote_id;
            $processed++;
        }

        // si le batch retourne moins que demandé, c’est qu’on a atteint la fin
        if (count($rows) < $current_limit) {
            break;
        }
    }

    // Dedup propre des IDs
    $counts['remote_ids'] = array_values(array_unique(array_map('intval', $counts['remote_ids'])));

    return $counts;
}

/**
 * Build a stable "origin key" for a remote site.
 * This is used to de-duplicate posts across multiple sources
 * that point to the same remote site.
 *
 * @param array $source Source row from DB (remote_news_sources)
 * @return string
 */
function remote_news_build_origin_key(array $source) {
    // Priority 1: table_prefix (same DB / same WP install)
    if (!empty($source['table_prefix'])) {
        return 'prefix:' . sanitize_key($source['table_prefix']);
    }

    // Priority 2: site_url hostname
    if (!empty($source['site_url'])) {
        $url  = trim((string) $source['site_url']);
        $url  = preg_replace('#^https?://#i', '', $url);
        $url  = rtrim($url, '/');
        return 'site:' . strtolower($url);
    }

    // Fallback: source_key
    $src = isset($source['source_key']) ? sanitize_key($source['source_key']) : '';
    return 'source:' . $src;
}

/* ----------------------------------------------------------------
 *  Helpers: upsert, mapping, thumbnails, permalinks
 * ---------------------------------------------------------------- */

/**
 * Insert or update one local post (post_type=remote_news) from a remote item.
 *
 * @param array $item   Normalized remote item
 * @param array $source Source row (includes table_prefix, site_url, sideload_images, include_cats, etc.)
 * @param array $map_by_source Map index: [source_key => [remote_slug => local_slug]]
 * @return 'imported'|'updated'|'skipped'
 */
function remote_news_upsert_one_item(array $item, array $source, array $map_by_source) {
    $origin_key = remote_news_build_origin_key($source);
    $remote_id  = (string) $item['remote_id'];
    $source_key = (string) $item['source_site'];

    // 1) Anti-dup primaire: origin_key + remote_id
    $existing_id = 0;

    $existing = get_posts([
        'post_type'      => 'remote_news',
        'post_status'    => 'any',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'posts_per_page' => 1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_remote_origin_key',
                'value'   => $origin_key,
                'compare' => '=',
            ],
            [
                'key'     => '_remote_original_id',
                'value'   => $remote_id,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($existing)) {
        $existing_id = (int) $existing[0];
    }

    // 2) Fallback legacy: _remote_source_site + _remote_original_id
    if (!$existing_id) {
        $legacy = get_posts([
            'post_type'      => 'remote_news',
            'post_status'    => 'any',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_remote_source_site',
                    'value'   => $source_key,
                    'compare' => '=',
                ],
                [
                    'key'     => '_remote_original_id',
                    'value'   => $remote_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if (!empty($legacy)) {
            $existing_id = (int) $legacy[0];
            // Migration vers le nouveau système d'origin_key
            update_post_meta($existing_id, '_remote_origin_key', $origin_key);
        }
    }

    // Base post array
    $postarr = [
        'post_type'     => 'remote_news',
        'post_status'   => 'publish',
        'post_title'    => wp_strip_all_tags($item['title'] ?? ''),
        'post_excerpt'  => (string) ($item['excerpt'] ?? ''),
        'post_content'  => (string) ($item['content'] ?? ''),
        'post_date_gmt' => $item['date_gmt'] ?? current_time('mysql', true),
        'post_date'     => isset($item['date_gmt']) ? get_date_from_gmt($item['date_gmt']) : current_time('mysql'),
    ];

    $mode    = 'imported';
    $post_id = 0;

    if ($existing_id) {
        // UPDATE
        $post_id       = $existing_id;
        $postarr['ID'] = $post_id;
        $res = wp_update_post($postarr, true);
        if (is_wp_error($res)) {
            return 'skipped';
        }
        $mode = 'updated';
    } else {
        // INSERT
        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id) || !$post_id) {
            return 'skipped';
        }

        // store metas at creation
        update_post_meta($post_id, '_remote_origin_key',  $origin_key);
        update_post_meta($post_id, '_remote_source_site', $source_key);
        update_post_meta($post_id, '_remote_original_id', $remote_id);
    }

    // Toujours garder ces metas à jour
    update_post_meta($post_id, '_remote_origin_key',  $origin_key);
    update_post_meta($post_id, '_remote_source_site', $source_key);
    update_post_meta($post_id, '_remote_original_id', $remote_id);

    // Core metas
    update_post_meta($post_id, '_remote_url', esc_url_raw($item['url'] ?? ''));
    if (!empty($item['author_name'])) {
        update_post_meta($post_id, '_remote_author_name', sanitize_text_field($item['author_name']));
    }
    if (!empty($item['date_gmt'])) {
        update_post_meta($post_id, '_remote_published_at', $item['date_gmt']);
    }

    // Thumbnail behavior
    $sideload   = !empty($source['sideload_images']);
    $image_url  = !empty($item['image_url']) ? esc_url_raw($item['image_url']) : '';
    $thumb_id   = 0;

    if ($image_url !== '') {
        if ($sideload) {
            // VRAI sideload sur le site local (fichier téléchargé)
            $thumb_id = remote_news_maybe_sideload($image_url, $post_id);
        } else {
            // Pas de sideload → on va "miroirer" l'attachment d'origine
            $remote_thumb_id = isset($item['remote_thumb_id']) ? (int) $item['remote_thumb_id'] : 0;

            $thumb_id = remote_news_ensure_external_attachment_from_source(
                $image_url,
                $post_id,
                $origin_key,
                $source,
                $remote_thumb_id,
                $item
            );

            // Fallback de sécurité si pour une raison X on n'a pas pu remonter à l'attachment source
            if (!$thumb_id) {
                $thumb_id = remote_news_ensure_external_attachment($image_url, $post_id);
            }
        }

        if ($thumb_id) {
            set_post_thumbnail($post_id, $thumb_id);
            update_post_meta($post_id, '_remote_thumbnail_url', $image_url);
        } else {
            // On conserve au moins l'URL dans la méta pour les templates custom
            update_post_meta($post_id, '_remote_thumbnail_url', $image_url);
            delete_post_thumbnail($post_id);
        }
    } else {
        // Plus d'image → on nettoie
        delete_post_thumbnail($post_id);
        delete_post_meta($post_id, '_remote_thumbnail_url');
    }

    // Category mapping: remote slugs -> local category terms
    $remote_slugs = (array) ($item['categories'] ?? []);
    if ($remote_slugs) {
        $local_term_ids = remote_news_map_remote_to_local_terms($item['source_site'], $remote_slugs, $map_by_source);
        if (!empty($local_term_ids)) {
            wp_set_post_terms($post_id, $local_term_ids, 'category', false);
        }
    }

    return $mode;
}

/**
 * Build category mapping index from DB.
 *
 * @return array [source_key => [remote_slug => local_slug]]
 */
function remote_news_build_category_map_index() {
    $index = [];

    if (function_exists('remote_news_map_all')) {
        $rows = remote_news_map_all();
        foreach ($rows as $m) {
            $src = sanitize_key($m['source_key']);
            $rem = sanitize_title($m['remote_slug']);
            $loc = sanitize_title($m['local_slug']);
            if ($src && $rem && $loc) {
                $index[$src][$rem] = $loc;
            }
        }
    }

    return $index;
}

/**
 * Convert remote slugs into local category term IDs using a prebuilt map index.
 * Creates local category if missing (slug=local_slug).
 *
 * @param string $source_key
 * @param array  $remote_slugs
 * @param array  $map_by_source
 * @return int[] term IDs
 */
function remote_news_map_remote_to_local_terms($source_key, array $remote_slugs, array $map_by_source) {
    $term_ids = [];
    $map      = $map_by_source[$source_key] ?? [];
    if (!$map) {
        return $term_ids;
    }

    foreach ($remote_slugs as $remote_slug) {
        $remote_slug = sanitize_title($remote_slug);
        $local_slug  = $map[$remote_slug] ?? '';
        if (!$local_slug) {
            continue;
        }

        $term = get_term_by('slug', $local_slug, 'category');
        if (!$term || is_wp_error($term)) {
            $inserted = wp_insert_term($local_slug, 'category', ['slug' => $local_slug]);
            if (!is_wp_error($inserted) && !empty($inserted['term_id'])) {
                $term = get_term($inserted['term_id'], 'category');
            }
        }

        if ($term && !is_wp_error($term)) {
            $term_ids[] = (int) $term->term_id;
        }
    }

    return array_values(array_unique(array_filter($term_ids)));
}

/**
 * Sideload une image distante dans la médiathèque (retourne l'ID d'attachment ou 0)
 * - RÉUTILISE un attachment existant s'il a déjà été sideloadé pour cette URL.
 *
 * @param string $url
 * @param int    $post_id
 * @return int
 */
function remote_news_maybe_sideload($url, $post_id) {
    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $url = esc_url_raw(trim((string) $url));
    if ($url === '') {
        return 0;
    }

    // Réutiliser un attachment existant pour cette URL
    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => '_remote_source_image',
                'value'   => $url,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    // Sinon, sideload réel
    $att_id = media_sideload_image($url, $post_id, null, 'id');

    $att_id = (int) $att_id;

    // Marqueur d'origine
    update_post_meta($att_id, '_remote_source_image', $url);

    return $att_id;
}

/**
 * Crée ou réutilise un attachment local "miroir" d'un attachment distant
 * (même DB, autre prefix) SANS sideload :
 * - parent = $post_id (remote_news)
 * - copie _wp_attachment_metadata + _wp_attached_file depuis le site source
 * - duplique la ligne as3cf_items du site source vers le site courant
 * - évite toute duplication inutile (réutilisation systématique si existant)
 *
 * @param string $url                  URL distante de l'image
 * @param int    $post_id              ID du post remote_news (site courant)
 * @param string $origin_key           Clé d'origine (remote_news_build_origin_key)
 * @param array  $source               Ligne source (remote_news_sources)
 * @param int    $remote_attachment_id ID de l'attachment sur le site d'origine
 * @param array  $item                 Item normalisé (pour récupérer source_table_prefix)
 *
 * @return int Attachment ID ou 0
 */
function remote_news_ensure_external_attachment_from_source($url, $post_id, $origin_key, array $source, $remote_attachment_id, array $item = []) {
    global $wpdb;

    $url                  = esc_url_raw(trim((string) $url));
    $post_id              = (int) $post_id;
    $remote_attachment_id = (int) $remote_attachment_id;
    $origin_key           = (string) $origin_key;

    if ($url === '' || !$post_id || !$remote_attachment_id || $origin_key === '') {
        return 0;
    }

    // Prefix du site source (tables cross-prefix)
    $source_prefix = '';
    if (!empty($item['source_table_prefix'])) {
        $source_prefix = sanitize_key($item['source_table_prefix']);
    } elseif (!empty($source['table_prefix'])) {
        $source_prefix = sanitize_key($source['table_prefix']);
    }

    if (!$source_prefix) {
        // Impossible de remonter proprement, on laissera le fallback générique
        return 0;
    }

    // 1) Tenter de réutiliser un attachment déjà mappé à cet attachment d'origine
    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_remote_origin_key',
                'value'   => $origin_key,
                'compare' => '=',
            ],
            [
                'key'     => '_remote_original_attachment_id',
                'value'   => (string) $remote_attachment_id,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($existing)) {
        $att_id = (int) $existing[0];

        // Mettre à jour le parent si besoin
        $current_parent = (int) get_post_field('post_parent', $att_id);
        if ($current_parent !== $post_id) {
            wp_update_post([
                'ID'          => $att_id,
                'post_parent' => $post_id,
            ]);
        }

        // S'assurer qu'on a la ligne as3cf_items locale
        remote_news_mirror_as3cf_row_if_needed($source_prefix, $remote_attachment_id, $att_id);

        return $att_id;
    }

    // 2) Sinon, réutiliser un attachment existant pour cette URL
    $url_existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => '_remote_source_image',
                'value'   => $url,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($url_existing)) {
        $att_id = (int) $url_existing[0];

        // Ajouter le mapping pour cet attachment d'origine
        update_post_meta($att_id, '_remote_origin_key', $origin_key);
        update_post_meta($att_id, '_remote_original_attachment_id', (string) $remote_attachment_id);

        // Mettre le parent si besoin
        $current_parent = (int) get_post_field('post_parent', $att_id);
        if ($current_parent !== $post_id) {
            wp_update_post([
                'ID'          => $att_id,
                'post_parent' => $post_id,
            ]);
        }

        // Copier la ligne as3cf_items si nécessaire
        remote_news_mirror_as3cf_row_if_needed($source_prefix, $remote_attachment_id, $att_id);

        return $att_id;
    }

    // 3) Aucune réutilisation possible → créer un nouvel attachment miroir
    $remote_posts_table    = $source_prefix . 'posts';
    $remote_postmeta_table = $source_prefix . 'postmeta';

    // Récupérer la ligne de l'attachment d'origine
    $remote_row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$remote_posts_table} WHERE ID = %d", $remote_attachment_id)
    );

    // Déterminer un titre / mime type de secours
    $path          = parse_url($url, PHP_URL_PATH);
    $filename      = $path ? basename($path) : $url;
    $default_title = sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME));
    $ext           = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'png':
            $default_mime = 'image/png';
            break;
        case 'gif':
            $default_mime = 'image/gif';
            break;
        case 'webp':
            $default_mime = 'image/webp';
            break;
        case 'svg':
            $default_mime = 'image/svg+xml';
            break;
        case 'jpg':
        case 'jpeg':
        default:
            $default_mime = 'image/jpeg';
            break;
    }

    $att_postarr = [
        'post_title'     => $remote_row && $remote_row->post_title ? $remote_row->post_title : $default_title,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_type'      => 'attachment',
        'post_mime_type' => $remote_row && $remote_row->post_mime_type ? $remote_row->post_mime_type : $default_mime,
        // IMPORTANT : guid = URL finale
        'guid'           => $url,
        'post_parent'    => $post_id,
    ];

    $att_id = wp_insert_post($att_postarr, true);

    $att_id = (int) $att_id;

    // Meta de mapping
    update_post_meta($att_id, '_remote_source_image', $url);
    update_post_meta($att_id, '_remote_origin_key', $origin_key);
    update_post_meta($att_id, '_remote_original_attachment_id', (string) $remote_attachment_id);

    // Copier _wp_attachment_metadata
    $raw_meta = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_value FROM {$remote_postmeta_table} WHERE post_id = %d AND meta_key = '_wp_attachment_metadata' LIMIT 1",
            $remote_attachment_id
        )
    );

    if ($raw_meta !== null && $raw_meta !== '') {
        $meta = maybe_unserialize($raw_meta);
        if (is_array($meta)) {
            wp_update_attachment_metadata($att_id, $meta);
        } else {
            update_post_meta($att_id, '_wp_attachment_metadata', $raw_meta);
        }
    }

    // Copier _wp_attached_file
    $attached_file = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_value FROM {$remote_postmeta_table} WHERE post_id = %d AND meta_key = '_wp_attached_file' LIMIT 1",
            $remote_attachment_id
        )
    );
    if ($attached_file !== null && $attached_file !== '') {
        update_post_meta($att_id, '_wp_attached_file', $attached_file);
    }

    // Dupliquer la ligne as3cf_items
    remote_news_mirror_as3cf_row_if_needed($source_prefix, $remote_attachment_id, $att_id);

    return $att_id;
}

/**
 * Duplique la ligne as3cf_items d'un attachment source (prefix distant)
 * vers le site courant, en l'associant à $local_attachment_id.
 * - Ne crée rien si une ligne existe déjà pour ce local_attachment_id.
 *
 * @param string $source_prefix        Prefix des tables du site source
 * @param int    $remote_attachment_id ID de l'attachment sur le site source
 * @param int    $local_attachment_id  ID de l'attachment sur le site courant
 */
function remote_news_mirror_as3cf_row_if_needed($source_prefix, $remote_attachment_id, $local_attachment_id) {
    global $wpdb;

    $source_prefix        = sanitize_key($source_prefix);
    $remote_attachment_id = (int) $remote_attachment_id;
    $local_attachment_id  = (int) $local_attachment_id;

    if (!$source_prefix || !$remote_attachment_id || !$local_attachment_id) {
        return;
    }

    $remote_table = $source_prefix . 'as3cf_items';
    $local_table  = $wpdb->prefix . 'as3cf_items';

    // Vérifier que les tables existent
    $has_remote = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $remote_table)) === $remote_table);
    $has_local  = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $local_table)) === $local_table);

    if (!$has_remote || !$has_local) {
        return;
    }

    // Y a-t-il déjà une ligne pour cet attachment local ?
    $existing_local_id = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$local_table} WHERE source_id = %d LIMIT 1", $local_attachment_id)
    );

    if ($existing_local_id) {
        return; // rien à faire, on ne duplique pas
    }

    // Récupérer la ligne distante
    $remote_row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$remote_table} WHERE source_id = %d LIMIT 1", $remote_attachment_id),
        ARRAY_A
    );

    if (!$remote_row) {
        return;
    }

    // On enlève la PK pour laisser l'AUTO_INCREMENT faire son travail
    if (isset($remote_row['id'])) {
        unset($remote_row['id']);
    }

    // On remappe le source_id sur l'attachment local
    $remote_row['source_id'] = $local_attachment_id;

    // Optionnel : forcer certains champs
    // $remote_row['source_type'] = 'media-library';
    // $remote_row['is_private']  = 0;

    $wpdb->insert($local_table, $remote_row);
}

/**
 * Garantit l'existence d'un attachment pour une URL distante SANS sideload.
 * - Ne télécharge PAS le fichier.
 * - Crée un attachment minimal avec guid = URL distante.
 * - Réutilise un attachment existant si déjà créé pour cette URL.
 *
 * @param string $url
 * @param int    $post_id
 * @return int Attachment ID ou 0
 */
function remote_news_ensure_external_attachment($url, $post_id = 0) {
    $url = esc_url_raw(trim((string) $url));
    if ($url === '') {
        return 0;
    }

    // Réutiliser un attachment déjà lié à cette URL
    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => '_remote_source_image',
                'value'   => $url,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($existing)) {
        $att_id         = (int) $existing[0];
        $current_parent = (int) get_post_field('post_parent', $att_id);

        if ($post_id && $current_parent !== (int) $post_id) {
            wp_update_post([
                'ID'          => $att_id,
                'post_parent' => (int) $post_id,
            ]);
        }

        return $att_id;
    }

    // Sinon on crée un attachment "virtuel"
    $path     = parse_url($url, PHP_URL_PATH);
    $filename = $path ? basename($path) : $url;
    $title    = sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME));

    // Déterminer un mime type "raisonnable"
    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'png':
            $mime = 'image/png';
            break;
        case 'gif':
            $mime = 'image/gif';
            break;
        case 'webp':
            $mime = 'image/webp';
            break;
        case 'svg':
            $mime = 'image/svg+xml';
            break;
        case 'jpg':
        case 'jpeg':
        default:
            $mime = 'image/jpeg';
            break;
    }

    $attachment = [
        'post_title'     => $title,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_type'      => 'attachment',
        'post_mime_type' => $mime,
        'guid'           => $url,
    ];

    if ($post_id) {
        $attachment['post_parent'] = (int) $post_id;
    }

    $att_id = wp_insert_post($attachment, true);

    $att_id = (int) $att_id;

    // On marque l'attachment avec l'URL distante
    update_post_meta($att_id, '_remote_source_image', $url);

    return $att_id;
}

/**
 * Build a robust remote permalink.
 * Prefer pretty permalink if we have post_name, else fallback to ?p=ID
 *
 * @param string $site_url
 * @param string $post_name
 * @param int    $post_id
 * @return string
 */
function remote_news_build_remote_permalink($site_url, $post_name, $post_id) {
    $base = rtrim((string) $site_url, '/');
    if (!empty($post_name)) {
        return trailingslashit($base) . trailingslashit($post_name);
    }
    return $base . '/?p=' . (int) $post_id;
}

/**
 * Supprime les remote_news qui ne font plus partie de la fenêtre d’ingestion
 * pour chaque origin_key.
 *
 * @param array $window_ids_by_origin [origin_key => int[] remote_ids]
 */
function remote_news_cleanup_out_of_window(array $window_ids_by_origin) {
    if (empty($window_ids_by_origin)) {
        return;
    }

    foreach ($window_ids_by_origin as $origin_key => $ids) {
        $origin_key = (string) $origin_key;
        $ids        = array_values(array_unique(array_map('strval', (array) $ids)));

        if ($origin_key === '' || empty($ids)) {
            continue;
        }

        // On va chercher les remote_news de cette origin_key dont _remote_original_id
        // n’est PAS dans la liste de remote_ids qu’on vient de voir.
        $q = new WP_Query([
            'post_type'      => 'remote_news',
            'post_status'    => 'any',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_remote_origin_key',
                    'value'   => $origin_key,
                    'compare' => '=',
                ],
                [
                    'key'     => '_remote_original_id',
                    'value'   => $ids,
                    'compare' => 'NOT IN',
                ],
            ],
        ]);

        if (!empty($q->posts)) {
            foreach ($q->posts as $post_id) {
                wp_trash_post($post_id);
            }
        }
    }
}

/**
 * Supprime les remote_news dont le _remote_source_site
 * ne correspond plus à une source définie dans remote_news_sources.
 */
function remote_news_cleanup_orphans_by_source() {
    if (!function_exists('remote_news_sources_all')) {
        return;
    }

    $sources = remote_news_sources_all();
    $active_source_keys = array_values(array_unique(array_map(
        function ($row) {
            return sanitize_key($row['source_key'] ?? '');
        },
        (array) $sources
    )));

    if (!$active_source_keys) {
        return;
    }

    $q = new WP_Query([
        'post_type'      => 'remote_news',
        'post_status'    => 'any',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_remote_source_site',
                'value'   => $active_source_keys,
                'compare' => 'NOT IN',
            ],
        ],
    ]);

    if (!empty($q->posts)) {
        foreach ($q->posts as $post_id) {
            wp_trash_post($post_id);
        }
    }
}
