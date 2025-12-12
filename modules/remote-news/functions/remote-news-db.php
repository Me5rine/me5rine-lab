<?php
// File: modules/remote-news/functions/remote-news-db.php

if (!defined('ABSPATH')) exit;

// ===== READ =====
function remote_news_sources_all() {
    global $wpdb;
    $t = admin_lab_getTable('remote_news_sources', false);
    return $wpdb->get_results("SELECT * FROM {$t} ORDER BY source_key ASC", ARRAY_A) ?: [];
}

function remote_news_queries_all() {
    global $wpdb;
    $t = admin_lab_getTable('remote_news_queries', false);
    $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY query_id ASC", ARRAY_A) ?: [];
    foreach ($rows as &$r) {
        $r['sources'] = $r['sources'] ? json_decode($r['sources'], true) : [];
    }
    return $rows;
}

function remote_news_map_all() {
    global $wpdb;
    $t = admin_lab_getTable('remote_news_category_map', false);
    return $wpdb->get_results("SELECT * FROM {$t} ORDER BY source_key, remote_slug", ARRAY_A) ?: [];
}

/* ============================================================
 *  LISTES PAGINÉES
 * ============================================================ */

/**
 * Sources paginées
 */
function remote_news_sources_paginated($paged = 1, $per_page = 20, $search = '') {
    global $wpdb;

    $t        = admin_lab_getTable('remote_news_sources', false);
    $paged    = max(1, (int) $paged);
    $per_page = max(1, (int) $per_page);
    $offset   = ($paged - 1) * $per_page;

    $where  = 'WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $like     = '%' . $wpdb->esc_like($search) . '%';
        $where   .= ' AND (source_key LIKE %s OR table_prefix LIKE %s OR site_url LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // COUNT total
    $sql_count = "SELECT COUNT(*) FROM {$t} {$where}";
    if ($params) {
        $total = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));
    } else {
        $total = (int) $wpdb->get_var($sql_count);
    }

    // ITEMS
    $sql_items      = "SELECT * FROM {$t} {$where} ORDER BY source_key ASC LIMIT %d OFFSET %d";
    $params_items   = array_merge($params, [ (int) $per_page, (int) $offset ]);
    $items          = $wpdb->get_results($wpdb->prepare($sql_items, $params_items), ARRAY_A) ?: [];

    return [ $items, $total ];
}

/**
 * Mappings paginés
 */
function remote_news_mappings_paginated($paged = 1, $per_page = 20, $search = '', $source_filter = '') {
    global $wpdb;

    $t        = admin_lab_getTable('remote_news_category_map', false);
    $paged    = max(1, (int) $paged);
    $per_page = max(1, (int) $per_page);
    $offset   = ($paged - 1) * $per_page;

    $where  = 'WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $like     = '%' . $wpdb->esc_like($search) . '%';
        $where   .= ' AND (source_key LIKE %s OR remote_slug LIKE %s OR local_slug LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($source_filter !== '') {
        $where   .= ' AND source_key = %s';
        $params[] = sanitize_key($source_filter);
    }

    // COUNT total
    $sql_count = "SELECT COUNT(*) FROM {$t} {$where}";
    if ($params) {
        $total = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));
    } else {
        $total = (int) $wpdb->get_var($sql_count);
    }

    // ITEMS
    $sql_items    = "SELECT * FROM {$t} {$where} ORDER BY source_key ASC, remote_slug ASC LIMIT %d OFFSET %d";
    $params_items = array_merge($params, [ (int) $per_page, (int) $offset ]);
    $items        = $wpdb->get_results($wpdb->prepare($sql_items, $params_items), ARRAY_A) ?: [];

    return [ $items, $total ];
}

/**
 * Queries paginées
 */
function remote_news_queries_paginated($paged = 1, $per_page = 20, $search = '') {
    global $wpdb;

    $t        = remote_news_table_queries();
    $paged    = max(1, (int) $paged);
    $per_page = max(1, (int) $per_page);

    $where  = 'WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $like     = '%' . $wpdb->esc_like($search) . '%';
        $where   .= ' AND (query_id LIKE %s OR label LIKE %s)';
        $params[] = $like;
        $params[] = $like;
    }

    // COUNT total
    $sql_count = "SELECT COUNT(*) FROM {$t} {$where}";
    if ($params) {
        $total = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));
    } else {
        $total = (int) $wpdb->get_var($sql_count);
    }

    // ITEMS
    $offset       = ($paged - 1) * $per_page;
    $sql_items    = "SELECT * FROM {$t} {$where} ORDER BY query_id ASC LIMIT %d OFFSET %d";
    $params_items = array_merge($params, [ (int) $per_page, (int) $offset ]);
    $items        = $wpdb->get_results($wpdb->prepare($sql_items, $params_items), ARRAY_A) ?: [];

    return [ $items, $total ];
}

/* ============================================================
 *  TABLE HELPERS
 * ============================================================ */

function remote_news_table_sources()  { return admin_lab_getTable('remote_news_sources', false); }
function remote_news_table_queries()  { return admin_lab_getTable('remote_news_queries', false); }
function remote_news_table_mappings() { return admin_lab_getTable('remote_news_category_map', false); }

/* ============================================================
 *  EXISTS HELPERS
 * ============================================================ */

function remote_news_source_exists($source_key){
    global $wpdb;
    $t   = remote_news_table_sources();
    $sql = $wpdb->prepare("SELECT 1 FROM {$t} WHERE source_key = %s LIMIT 1", sanitize_key($source_key));
    return (bool) $wpdb->get_var($sql);
}

function remote_news_query_exists($query_id){
    global $wpdb;
    $t   = remote_news_table_queries();
    $sql = $wpdb->prepare("SELECT 1 FROM {$t} WHERE query_id = %s LIMIT 1", sanitize_key($query_id));
    return (bool) $wpdb->get_var($sql);
}

function remote_news_mapping_exists($source_key, $remote_slug){
    global $wpdb;
    $t   = remote_news_table_mappings();
    $sql = $wpdb->prepare(
        "SELECT 1 FROM {$t} WHERE source_key = %s AND remote_slug = %s LIMIT 1",
        sanitize_key($source_key),
        sanitize_title($remote_slug)
    );
    return (bool) $wpdb->get_var($sql);
}

/* ============================================================
 *  INSERT / UPDATE SOURCES
 * ============================================================ */

function remote_news_sources_insert_row(array $row){
    global $wpdb;
    $t = remote_news_table_sources();
    return (bool) $wpdb->insert($t, [
        'source_key'      => sanitize_key($row['source_key']),
        'table_prefix'    => sanitize_key($row['table_prefix'] ?? ''),
        'site_url'        => esc_url_raw($row['site_url'] ?? ''),
        'include_cats'    => sanitize_text_field($row['include_cats'] ?? ''),
        'limit_items'     => max(1, (int)($row['limit_items'] ?? 10)),
        'max_age_days'    => max(1, (int)($row['max_age_days'] ?? 14)),
        'sideload_images' => empty($row['sideload_images']) ? 0 : 1,
    ], ['%s','%s','%s','%s','%d','%d','%d']);
}

function remote_news_sources_update_row($orig_key, array $row){
    global $wpdb;
    $t    = remote_news_table_sources();
    $data = [
        'source_key'      => sanitize_key($row['source_key']),
        'table_prefix'    => sanitize_key($row['table_prefix'] ?? ''),
        'site_url'        => esc_url_raw($row['site_url'] ?? ''),
        'include_cats'    => sanitize_text_field($row['include_cats'] ?? ''),
        'limit_items'     => max(1, (int)($row['limit_items'] ?? 10)),
        'max_age_days'    => max(1, (int)($row['max_age_days'] ?? 14)),
        'sideload_images' => empty($row['sideload_images']) ? 0 : 1,
    ];
    return (bool) $wpdb->update(
        $t,
        $data,
        [ 'source_key' => sanitize_key($orig_key) ],
        ['%s','%s','%s','%s','%d','%d','%d'],
        ['%s']
    );
}

/* ============================================================
 *  INSERT / UPDATE QUERIES
 * ============================================================ */

function remote_news_queries_insert_row(array $row){
    global $wpdb;
    $t = remote_news_table_queries();

    $sources_json = wp_json_encode(
        array_values(
            array_unique(
                array_map('sanitize_key', (array)($row['sources'] ?? []))
            )
        )
    );

    $orderby = in_array(($row['orderby'] ?? 'date'), ['date','modified','title','rand'], true)
        ? $row['orderby']
        : 'date';

    $sort = (strtoupper($row['sort_order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';

    return (bool) $wpdb->insert($t, [
        'query_id'     => sanitize_key($row['query_id']),
        'label'        => sanitize_text_field($row['label'] ?? ''),
        'include_cats' => sanitize_text_field($row['include_cats'] ?? ''),
        'exclude_cats' => sanitize_text_field($row['exclude_cats'] ?? ''),
        'sources'      => $sources_json,
        'limit_items'  => max(1, (int)($row['limit_items'] ?? 12)),
        'orderby'      => $orderby,
        'sort_order'   => $sort,
        'post_type'    => sanitize_key($row['post_type'] ?? 'remote_news'),
    ], ['%s','%s','%s','%s','%s','%d','%s','%s','%s']);
}

function remote_news_queries_update_row($orig_id, array $row){
    global $wpdb;
    $t = remote_news_table_queries();

    $sources_json = wp_json_encode(
        array_values(
            array_unique(
                array_map('sanitize_key', (array)($row['sources'] ?? []))
            )
        )
    );

    $orderby = in_array(($row['orderby'] ?? 'date'), ['date','modified','title','rand'], true)
        ? $row['orderby']
        : 'date';

    $sort = (strtoupper($row['sort_order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';

    $data = [
        'query_id'     => sanitize_key($row['query_id']),
        'label'        => sanitize_text_field($row['label'] ?? ''),
        'include_cats' => sanitize_text_field($row['include_cats'] ?? ''),
        'exclude_cats' => sanitize_text_field($row['exclude_cats'] ?? ''),
        'sources'      => $sources_json,
        'limit_items'  => max(1, (int)($row['limit_items'] ?? 12)),
        'orderby'      => $orderby,
        'sort_order'   => $sort,
        'post_type'    => sanitize_key($row['post_type'] ?? 'remote_news'),
    ];

    return (bool) $wpdb->update(
        $t,
        $data,
        [ 'query_id' => sanitize_key($orig_id) ],
        ['%s','%s','%s','%s','%s','%d','%s','%s','%s'],
        ['%s']
    );
}

/* ============================================================
 *  INSERT / UPDATE MAPPINGS
 * ============================================================ */

function remote_news_mappings_insert_row($row_or_src, $remote_slug = null, $local_slug = null){
    if (is_array($row_or_src)) {
        $src = sanitize_key($row_or_src['source_key'] ?? '');
        $rem = sanitize_title($row_or_src['remote_slug'] ?? '');
        $loc = sanitize_title($row_or_src['local_slug'] ?? '');
    } else {
        $src = sanitize_key($row_or_src);
        $rem = sanitize_title((string) $remote_slug);
        $loc = sanitize_title((string) $local_slug);
    }

    if (!$src || !$rem || !$loc) {
        return false;
    }

    global $wpdb;
    $t = remote_news_table_mappings();

    return (bool) $wpdb->insert($t, [
        'source_key'  => $src,
        'remote_slug' => $rem,
        'local_slug'  => $loc,
    ], ['%s','%s','%s']);
}

function remote_news_mappings_update_row($orig_src, $orig_remote, array $new_row){
    global $wpdb;
    $t = remote_news_table_mappings();

    $data = [
        'source_key'  => sanitize_key($new_row['source_key'] ?? ''),
        'remote_slug' => sanitize_title($new_row['remote_slug'] ?? ''),
        'local_slug'  => sanitize_title($new_row['local_slug'] ?? ''),
    ];

    if (!$data['source_key'] || !$data['remote_slug'] || !$data['local_slug']) {
        return false;
    }

    return (bool) $wpdb->update(
        $t,
        $data,
        [
            'source_key'  => sanitize_key($orig_src),
            'remote_slug' => sanitize_title($orig_remote),
        ],
        ['%s','%s','%s'],
        ['%s','%s']
    );
}

/* ============================================================
 *  GET ROWS (EDIT FORMS)
 * ============================================================ */

function remote_news_source_get($source_key) {
    global $wpdb;
    $t = remote_news_table_sources();

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$t} WHERE source_key = %s LIMIT 1",
            sanitize_key($source_key)
        ),
        ARRAY_A
    );

    return $row ?: null;
}

function remote_news_mapping_get($source_key, $remote_slug) {
    global $wpdb;
    $t   = remote_news_table_mappings();
    $src = sanitize_key($source_key);
    $rem = sanitize_title($remote_slug);

    if (!$src || !$rem) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM {$t} WHERE source_key = %s AND remote_slug = %s LIMIT 1",
        $src,
        $rem
    );

    $row = $wpdb->get_row($sql, ARRAY_A);
    return $row ?: null;
}

function remote_news_query_get($query_id) {
    global $wpdb;
    $t = remote_news_table_queries();

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$t} WHERE query_id = %s LIMIT 1",
            sanitize_key($query_id)
        ),
        ARRAY_A
    );

    if (!$row) {
        return null;
    }

    $row['sources'] = !empty($row['sources'])
        ? (json_decode($row['sources'], true) ?: [])
        : [];

    return $row;
}
