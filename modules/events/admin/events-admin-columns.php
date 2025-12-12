<?php
// File: modules/events/admin/events-admin-columns.php

if (!defined('ABSPATH')) exit;

// Ajouter les colonnes personnalisées
add_filter('manage_post_posts_columns', function ($columns) {
    $inserts = [
        'admin_lab_event' => __('Event', 'me5rine-lab'),
        'admin_lab_start' => __('Start Date', 'me5rine-lab'),
        'admin_lab_end'   => __('End Date', 'me5rine-lab'),
        'admin_lab_recur' => __('Recurrence', 'me5rine-lab'),
    ];

    $new = [];
    foreach ($columns as $key => $value) {
        $new[$key] = $value;
        if ($key === 'title') {
            $new = array_merge($new, $inserts);
        }
    }
    return $new;
});

// Afficher les valeurs dans les colonnes
add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'admin_lab_event':
            echo admin_lab_events_is_event($post_id) ? '✅' : '—';
            break;

        case 'admin_lab_start':
        case 'admin_lab_end':
            if (!admin_lab_events_is_event($post_id)) {
                echo '—';
                break;
            }

            // Mode (local ou fixed)
            $mode = get_post_meta($post_id, '_event_mode', true) ?: 'local';

            if ($mode === 'local') {
                // Heures flottantes : on lit *_local
                $meta_key = ($column === 'admin_lab_start')
                    ? '_event_start_local'
                    : '_event_end_local';

                $value = get_post_meta($post_id, $meta_key, true);

                if (!$value) {
                    echo '—';
                    break;
                }

                // On formate en timezone du site (admin)
                try {
                    $tz = wp_timezone();
                    $dt = new DateTime($value, $tz);
                    echo esc_html($dt->format('Y-m-d H:i'));
                } catch (Exception $e) {
                    // En cas de souci, on affiche brut
                    echo esc_html($value);
                }
            } else {
                // Mode fixed : on lit l'ISO UTC comme avant
                $meta_key = ($column === 'admin_lab_start')
                    ? '_event_start'
                    : '_event_end';

                $value = get_post_meta($post_id, $meta_key, true);

                if ($value) {
                    // Helper existant, supposé formatter une date ISO pour l’admin
                    echo esc_html(admin_lab_events_admin_fmt($post_id, $value));
                } else {
                    echo '—';
                }
            }
            break;

        case 'admin_lab_recur':
            $r = get_post_meta($post_id, '_event_recurring', true);
            if (!$r) {
                echo '—';
                break;
            }
            $freq = get_post_meta($post_id, '_event_rrule_freq', true) ?: __('N/A', 'me5rine-lab');
            $int  = max(1, (int) get_post_meta($post_id, '_event_rrule_interval', true) ?: 1);
            echo esc_html(sprintf(__('%s x%d', 'me5rine-lab'), $freq, $int));
            break;
    }
}, 10, 2);

// Rendre Start et End triables
add_filter('manage_edit-post_sortable_columns', function ($columns) {
    $columns['admin_lab_start'] = 'admin_lab_start';
    $columns['admin_lab_end']   = 'admin_lab_end';
    return $columns;
});

// Gestion du tri
// Gestion du tri
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    // Tri sur la date de début
    if ($query->get('orderby') === 'admin_lab_start') {
        $query->set('meta_key', '_event_sort_start');
        $query->set('orderby', 'meta_value_num');
    }

    // Tri sur la date de fin
    if ($query->get('orderby') === 'admin_lab_end') {
        $query->set('meta_key', '_event_sort_end');
        $query->set('orderby', 'meta_value_num');
    }
});


// Dropdown natif "Event Type" basé sur la taxonomie locale
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'post') {
        return;
    }

    if (function_exists('admin_lab_events_use_remote_types') && admin_lab_events_use_remote_types()) {
        return;
    }

    if (!taxonomy_exists('event_type')) {
        return;
    }

    $taxonomy = 'event_type';
    $selected = isset($_GET[$taxonomy]) ? sanitize_text_field($_GET[$taxonomy]) : '';

    wp_dropdown_categories([
        'show_option_all' => __('All Event Types', 'me5rine-lab'),
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'hierarchical'    => true,
        'show_count'      => false,
        'hide_empty'      => false,
        'value_field'     => 'slug',
    ]);
});

// Appliquer le filtre par taxonomie "event_type" (site avec types locaux)
add_action('pre_get_posts', function ($query) {
    global $pagenow;

    if (!is_admin() || !$query->is_main_query() || $pagenow !== 'edit.php') {
        return;
    }

    if (function_exists('admin_lab_events_use_remote_types') && admin_lab_events_use_remote_types()) {
        return;
    }

    if (!taxonomy_exists('event_type')) {
        return;
    }

    if (!empty($_GET['event_type']) && $_GET['event_type'] !== '0') {
        $value = sanitize_text_field($_GET['event_type']);

        $tax_query = [[
            'taxonomy' => 'event_type',
            'field'    => 'slug',
            'terms'    => $value,
        ]];

        if (is_numeric($value)) {
            $tax_query[0]['field'] = 'term_id';
            $tax_query[0]['terms'] = (int) $value;
        }

        $query->set('tax_query', $tax_query);
    }
});

/**
 * Colonne custom "Event type" pour les sites qui consomment les types distants.
 */
add_filter('manage_edit-post_columns', function (array $columns): array {

    if (!function_exists('admin_lab_events_use_remote_types') || !admin_lab_events_use_remote_types()) {
        return $columns;
    }

    $new = [];

    foreach ($columns as $key => $label) {
        $new[$key] = $label;

        if ($key === 'tags') {
            $new['admin_lab_event_type'] = __('Event type', 'me5rine-lab');
        }
    }

    if (!isset($new['admin_lab_event_type'])) {
        $new['admin_lab_event_type'] = __('Event type', 'me5rine-lab');
    }

    return $new;
});


add_action('manage_posts_custom_column', function (string $column, int $post_id) {
    if ($column !== 'admin_lab_event_type') {
        return;
    }

    if (!function_exists('admin_lab_events_use_remote_types') || !admin_lab_events_use_remote_types()) {
        return;
    }

    // On n'affiche un type que si le post est marqué comme "event"
    $enabled = get_post_meta($post_id, '_event_enabled', true);
    if (!$enabled) {
        echo '—';
        return;
    }

    $name = (string) get_post_meta($post_id, '_event_type_name', true);
    $slug = (string) get_post_meta($post_id, '_event_type_slug', true);

    if ($name === '' && $slug === '') {
        echo '—';
        return;
    }

    $label = ($name !== '') ? $name : $slug;

    // Lien vers la liste des posts filtrée par ce type distant
    $url = add_query_arg(
        [
            'post_type'                   => 'post',
            'admin_lab_event_type_filter' => $slug,
        ],
        admin_url('edit.php')
    );

    printf(
        '<a href="%s">%s</a>',
        esc_url($url),
        esc_html($label)
    );
}, 10, 2);


add_filter('manage_edit-post_sortable_columns', function (array $columns): array {
    if (!function_exists('admin_lab_events_use_remote_types') || !admin_lab_events_use_remote_types()) {
        return $columns;
    }

    $columns['admin_lab_event_type'] = 'admin_lab_event_type';
    return $columns;
});


add_action('pre_get_posts', function (WP_Query $query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!function_exists('admin_lab_events_use_remote_types') || !admin_lab_events_use_remote_types()) {
        return;
    }

    if ($query->get('post_type') !== 'post') {
        return;
    }

    // Tri
    if ('admin_lab_event_type' === $query->get('orderby')) {
        $query->set('meta_key', '_event_type_name');
        $query->set('orderby', 'meta_value');
    }

    // Filtre
    $filter = isset($_GET['admin_lab_event_type_filter'])
        ? sanitize_text_field(wp_unslash($_GET['admin_lab_event_type_filter']))
        : '';

    if ($filter !== '') {
        $meta_query   = (array) $query->get('meta_query');
        $meta_query[] = [
            'key'   => '_event_type_slug',
            'value' => $filter,
        ];
        $query->set('meta_query', $meta_query);
    }
});


add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'post') {
        return;
    }

    if (!function_exists('admin_lab_events_use_remote_types') || !admin_lab_events_use_remote_types()) {
        return;
    }

    global $wpdb;

    $current = isset($_GET['admin_lab_event_type_filter'])
        ? sanitize_text_field(wp_unslash($_GET['admin_lab_event_type_filter']))
        : '';

    $meta_table = $wpdb->postmeta;

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT DISTINCT m_slug.meta_value AS slug, m_name.meta_value AS name
            FROM {$meta_table} AS m_slug
            LEFT JOIN {$meta_table} AS m_name
                ON m_name.post_id = m_slug.post_id
               AND m_name.meta_key = %s
            WHERE m_slug.meta_key = %s
              AND m_slug.meta_value <> ''
            ORDER BY m_name.meta_value ASC
            ",
            '_event_type_name',
            '_event_type_slug'
        )
    );

    if (!$rows) {
        return;
    }

    echo '<select name="admin_lab_event_type_filter" id="admin_lab_event_type_filter" class="postform">';
    echo '<option value="">' . esc_html__('All event types', 'me5rine-lab') . '</option>';

    foreach ($rows as $row) {
        $slug = (string) $row->slug;
        $name = (string) ($row->name ?: $row->slug);

        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($slug),
            selected($current, $slug, false),
            esc_html($name)
        );
    }

    echo '</select>';
});