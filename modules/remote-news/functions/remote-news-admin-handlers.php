<?php
// File: modules/remote-news/functions/remote-news-admin-handlers.php

if (!defined('ABSPATH')) exit;

// Redirection vers page Remote News avec notice
function admin_lab_remote_news_redirect($type = 'updated', $msg = 'Saved.') {
    $page = defined('ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG') ? ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG : 'admin-lab-remote-news';
    $tab  = isset($_REQUEST['tab']) ? sanitize_key($_REQUEST['tab'])
           : (isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview');

    $url = add_query_arg([
        'page'      => $page,
        'tab'       => $tab,
        'rn_notice' => $type,
        'rn_msg'    => rawurlencode($msg),
    ], admin_url('admin.php'));

    wp_safe_redirect($url);
    exit;
}

// == DELETE SOURCE ==
function admin_lab_remote_news_handle_delete_source() {
    if (!current_user_can('manage_options')) wp_die('Forbidden');
    $key = sanitize_key($_GET['key'] ?? '');
    check_admin_referer('remote_news_delete_source_'.$key);
    if ($key) {
        global $wpdb; $t = admin_lab_getTable('remote_news_sources', false);
        $wpdb->delete($t, ['source_key' => $key]);
    }
    admin_lab_remote_news_redirect('updated', __('Source deleted.', 'me5rine-lab'));
}
add_action('admin_post_remote_news_delete_source', 'admin_lab_remote_news_handle_delete_source');

// == DELETE MAPPING ==
function admin_lab_remote_news_handle_delete_mapping() {
    if (!current_user_can('manage_options')) wp_die('Forbidden');

    $src    = sanitize_key($_GET['src'] ?? '');
    $rem    = sanitize_title($_GET['remote'] ?? '');
    $return = isset($_GET['return'])
        ? esc_url_raw(rawurldecode($_GET['return']))
        : admin_url('admin.php?page=admin-lab-remote-news&tab=mappings');

    check_admin_referer('remote_news_delete_mapping_'.$src.'__'.$rem);

    if ($src && $rem) {
        global $wpdb; 
        $t = admin_lab_getTable('remote_news_category_map', false);
        $wpdb->delete($t, ['source_key' => $src, 'remote_slug' => $rem]);
    }

    wp_safe_redirect($return);
    exit;
}
add_action('admin_post_remote_news_delete_mapping', 'admin_lab_remote_news_handle_delete_mapping');


// == DELETE QUERY ==
function admin_lab_remote_news_handle_delete_query() {
    if (!current_user_can('manage_options')) wp_die('Forbidden');
    $qid = sanitize_key($_GET['id'] ?? '');
    check_admin_referer('remote_news_delete_query_'.$qid);
    if ($qid) {
        global $wpdb; $t = admin_lab_getTable('remote_news_queries', false);
        $wpdb->delete($t, ['query_id' => $qid]);
    }
    admin_lab_remote_news_redirect('updated', __('Query deleted.', 'me5rine-lab'));
}
add_action('admin_post_remote_news_delete_query', 'admin_lab_remote_news_handle_delete_query');

// Suppression d'une source (add/edit)
add_action('admin_post_remote_news_save_source_single', function(){
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    check_admin_referer('remote_news_save_source_single');

    $return = isset($_POST['return']) ? esc_url_raw($_POST['return']) : admin_url('admin.php?page=admin-lab-remote-news&tab=sources');
    $mode   = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'add';
    $orig   = isset($_POST['orig_key']) ? sanitize_key($_POST['orig_key']) : '';

    $r   = $_POST['source'] ?? [];
    $key = sanitize_key($r['key'] ?? '');
    if ($mode==='add' && !$key) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Missing key.','me5rine-lab'))], $return));

    $row = [
        'source_key'      => $key,
        'table_prefix'    => sanitize_key($r['table_prefix'] ?? ''),
        'site_url'        => esc_url_raw($r['site_url'] ?? ''),
        'include_cats'    => sanitize_text_field($r['include_cats'] ?? ''),
        'limit_items'     => max(1, (int)($r['limit'] ?? 10)),
        'max_age_days'    => max(1, (int)($r['max_age_days'] ?? 14)),
        'sideload_images' => empty($r['sideload_images']) ? 0 : 1,
    ];

    if ($mode==='edit') {
        if (!$orig) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Invalid original key.','me5rine-lab'))], $return));
        if (function_exists('remote_news_source_exists') && $key !== $orig && remote_news_source_exists($key)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This key already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_sources_update_row($orig, $row);
        wp_safe_redirect(add_query_arg(['rn_notice'=>'updated','rn_msg'=>rawurlencode(__('Source updated.','me5rine-lab'))], $return)); exit;
    } else {
        if (function_exists('remote_news_source_exists') && remote_news_source_exists($key)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This key already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_sources_insert_row($row);
        wp_safe_redirect(add_query_arg(['rn_notice'=>'updated','rn_msg'=>rawurlencode(__('Source created.','me5rine-lab'))], $return)); exit;
    }
});

// Suppression d'un mapping (add/edit)
add_action('admin_post_remote_news_save_mapping_single', function(){
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    check_admin_referer('remote_news_save_mapping_single');

    $return = isset($_POST['return'])
        ? esc_url_raw($_POST['return'])
        : admin_url('admin.php?page=admin-lab-remote-news&tab=mappings');
    $mode   = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'add';
    $orig_s = isset($_POST['orig_src']) ? sanitize_key($_POST['orig_src']) : '';
    $orig_r = isset($_POST['orig_remote']) ? sanitize_title($_POST['orig_remote']) : '';

    $m = $_POST['mapping'] ?? [];
    $src = sanitize_key($m['source'] ?? '');
    $rem = sanitize_title($m['remote'] ?? '');
    $loc = sanitize_title($m['local'] ?? '');
    if (!$src || !$rem || !$loc) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Missing fields.','me5rine-lab'))], $return));

    if ($mode==='edit') {
        if (!$orig_s || !$orig_r) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Invalid original mapping.','me5rine-lab'))], $return));
        if (($src !== $orig_s || $rem !== $orig_r) && function_exists('remote_news_mapping_exists') && remote_news_mapping_exists($src, $rem)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This mapping already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_mappings_update_row($orig_s, $orig_r, ['source_key'=>$src,'remote_slug'=>$rem,'local_slug'=>$loc]);
        wp_safe_redirect(add_query_arg(['rn_notice'=>'updated','rn_msg'=>rawurlencode(__('Mapping updated.','me5rine-lab'))], $return)); exit;
    } else {
        if (function_exists('remote_news_mapping_exists') && remote_news_mapping_exists($src, $rem)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This mapping already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_mappings_insert_row(['source_key'=>$src,'remote_slug'=>$rem,'local_slug'=>$loc]);
        wp_safe_redirect(add_query_arg([
            'rn_notice' => 'updated',
            'rn_msg'    => rawurlencode(__('Mapping created.','me5rine-lab')),
        ], $return));
        exit;
    }
});

// Suppression d'une query (add/edit)
add_action('admin_post_remote_news_save_query_single', function(){
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    check_admin_referer('remote_news_save_query_single');

    $return = isset($_POST['return']) ? esc_url_raw($_POST['return']) : admin_url('admin.php?page=admin-lab-remote-news&tab=queries');
    $mode   = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'add';
    $orig   = isset($_POST['orig_id']) ? sanitize_key($_POST['orig_id']) : '';

    $q = $_POST['query'] ?? [];
    $qid = sanitize_key($q['id'] ?? '');
    if ($mode==='add' && !$qid) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Missing ID.','me5rine-lab'))], $return));

    $row = [
        'query_id'     => $qid,
        'label'        => sanitize_text_field($q['label'] ?? ''),
        'sources'      => array_values(array_unique(array_filter(array_map('sanitize_key', (array)($q['sources'] ?? []))))),
        'include_cats' => sanitize_text_field($q['include_cats'] ?? ''),
        'exclude_cats' => sanitize_text_field($q['exclude_cats'] ?? ''),
        'limit_items'  => max(1, (int)($q['limit'] ?? 12)),
        'orderby'      => in_array(($q['orderby'] ?? 'date'), ['date','modified','title','rand'], true) ? $q['orderby'] : 'date',
        'sort_order'   => (strtoupper($q['order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC',
        'post_type'    => 'remote_news',
    ];

    if ($mode==='edit') {
        if (!$orig) wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('Invalid original ID.','me5rine-lab'))], $return));
        if ($qid !== $orig && function_exists('remote_news_query_exists') && remote_news_query_exists($qid)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This ID already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_queries_update_row($orig, $row);
        wp_safe_redirect(add_query_arg(['rn_notice'=>'updated','rn_msg'=>rawurlencode(__('Query updated.','me5rine-lab'))], $return)); exit;
    } else {
        if (function_exists('remote_news_query_exists') && remote_news_query_exists($qid)) {
            wp_safe_redirect(add_query_arg(['rn_notice'=>'error','rn_msg'=>rawurlencode(__('This ID already exists.','me5rine-lab'))], $return)); exit;
        }
        remote_news_queries_insert_row($row);
        wp_safe_redirect(add_query_arg(['rn_notice'=>'updated','rn_msg'=>rawurlencode(__('Query created.','me5rine-lab'))], $return)); exit;
    }
});

