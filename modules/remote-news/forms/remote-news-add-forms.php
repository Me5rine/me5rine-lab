<?php
// File: modules/remote-news/forms/remote-news-add-forms.php

if (!defined('ABSPATH')) exit;

// Ajout au menu du plugin
add_action('admin_menu', function () {
    if ( ! current_user_can('manage_options') ) {
        return;
    }

    $parent = 'admin-lab-remote-news';

    $pages = [
        'admin-lab-remote-news-edit-source' => [
            'title' => __('Edit Source', 'me5rine-lab'),
            'cb'    => 'admin_lab_remote_news_edit_source_screen',
        ],
        'admin-lab-remote-news-edit-mapping' => [
            'title' => __('Edit Mapping', 'me5rine-lab'),
            'cb'    => 'admin_lab_remote_news_edit_mapping_screen',
        ],
        'admin-lab-remote-news-edit-query' => [
            'title' => __('Edit Query', 'me5rine-lab'),
            'cb'    => 'admin_lab_remote_news_edit_query_screen',
        ],
    ];

    global $title;

    foreach ($pages as $slug => $data) {
        add_submenu_page(
            $parent,
            $data['title'],
            $data['title'],
            'manage_options',
            $slug,
            $data['cb']
        );

        remove_submenu_page($parent, $slug);
    }

    $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    if ($current_page && isset($pages[$current_page])) {
        $title = $pages[$current_page]['title'];
    }
}, 20);

function admin_lab_remote_news_edit_source_screen() {
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    $mode = isset($_GET['mode']) ? sanitize_key($_GET['mode']) : 'add';
    $return = isset($_GET['return'])
        ? esc_url_raw( rawurldecode( wp_unslash($_GET['return']) ) )
        : admin_url('admin.php?page=admin-lab-remote-news&tab=mappings');

    $orig_key = isset($_GET['key']) ? sanitize_key($_GET['key']) : '';
    $row = $orig_key && function_exists('remote_news_source_get') ? remote_news_source_get($orig_key) : null;
    if ($mode === 'edit' && !$row) wp_die(__('Item not found.','me5rine-lab'));
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $mode==='edit' ? esc_html__('Edit source','me5rine-lab') : esc_html__('Add source','me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url($return); ?>" class="page-title-action"><?php _e('Back','me5rine-lab'); ?></a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('remote_news_save_source_single'); ?>
            <input type="hidden" name="action" value="remote_news_save_source_single">
            <input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
            <input type="hidden" name="return" value="<?php echo esc_attr($return); ?>">
            <?php if ($mode==='edit'): ?>
                <input type="hidden" name="orig_key" value="<?php echo esc_attr($row['source_key']); ?>">
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th><label for="rn_key"><?php _e('Key','me5rine-lab'); ?></label></th>
                        <td><input name="source[key]" id="rn_key" class="regular-text" value="<?php echo esc_attr($row['source_key'] ?? ''); ?>" <?php echo $mode==='add' ? 'required' : ''; ?>></td>
                    </tr>
                    <tr>
                        <th><label for="rn_prefix"><?php _e('Table prefix','me5rine-lab'); ?></label></th>
                        <td><input name="source[table_prefix]" id="rn_prefix" class="regular-text" value="<?php echo esc_attr($row['table_prefix'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="rn_site"><?php _e('Site URL','me5rine-lab'); ?></label></th>
                        <td><input name="source[site_url]" id="rn_site" class="regular-text" value="<?php echo esc_attr($row['site_url'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="rn_inc"><?php _e('Include categories (REMOTE slugs, CSV)','me5rine-lab'); ?></label></th>
                        <td><input name="source[include_cats]" id="rn_inc" class="regular-text" value="<?php echo esc_attr($row['include_cats'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="rn_limit"><?php _e('Limit','me5rine-lab'); ?></label></th>
                        <td><input name="source[limit]" id="rn_limit" type="number" min="1" value="<?php echo esc_attr($row['limit_items'] ?? 10); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="rn_age"><?php _e('Max age (days)','me5rine-lab'); ?></label></th>
                        <td><input name="source[max_age_days]" id="rn_age" type="number" min="1" value="<?php echo esc_attr($row['max_age_days'] ?? 14); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php _e('Sideload images','me5rine-lab'); ?></th>
                        <td><label><input type="checkbox" name="source[sideload_images]" <?php checked(!empty($row['sideload_images'])); ?>> <?php _e('Download and attach thumbnails locally','me5rine-lab'); ?></label></td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button($mode==='edit' ? __('Update','me5rine-lab') : __('Create','me5rine-lab')); ?>
        </form>
    </div>
    <?php
}

function admin_lab_remote_news_edit_mapping_screen() {
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    $mode   = isset($_GET['mode']) ? sanitize_key($_GET['mode']) : 'add';
    $return = isset($_GET['return']) ? esc_url_raw($_GET['return']) : admin_url('admin.php?page=admin-lab-remote-news&tab=mappings');

    $src  = isset($_GET['src']) ? sanitize_key($_GET['src']) : '';
    $rem  = isset($_GET['remote']) ? sanitize_title($_GET['remote']) : '';

    $row = null;
    if ($mode==='edit' && $src && $rem && function_exists('remote_news_mapping_get')) {
        $row = remote_news_mapping_get($src, $rem);
        if (!$row) wp_die(__('Item not found.','me5rine-lab'));
    }

    $sources = function_exists('remote_news_sources_all') ? remote_news_sources_all() : [];
    $sources_by_key = [];
    foreach ($sources as $r) $sources_by_key[$r['source_key']] = true;
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $mode==='edit' ? esc_html__('Edit mapping','me5rine-lab') : esc_html__('Add mapping','me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url($return); ?>" class="page-title-action"><?php _e('Back','me5rine-lab'); ?></a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('remote_news_save_mapping_single'); ?>
            <input type="hidden" name="action" value="remote_news_save_mapping_single">
            <input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
            <input type="hidden" name="return" value="<?php echo esc_attr($return); ?>">
            <?php if ($mode==='edit'): ?>
                <input type="hidden" name="orig_src" value="<?php echo esc_attr($row['source_key']); ?>">
                <input type="hidden" name="orig_remote" value="<?php echo esc_attr($row['remote_slug']); ?>">
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th><label for="m_src"><?php _e('Source','me5rine-lab'); ?></label></th>
                        <td>
                            <select name="mapping[source]" id="m_src" required>
                                <option value=""><?php _e('— Source —','me5rine-lab'); ?></option>
                                <?php foreach ($sources_by_key as $sk => $_): ?>
                                    <option value="<?php echo esc_attr($sk); ?>" <?php selected(($row['source_key'] ?? '') === $sk); ?>><?php echo esc_html($sk); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="m_remote"><?php _e('Remote slug','me5rine-lab'); ?></label></th>
                        <td><input name="mapping[remote]" id="m_remote" class="regular-text" value="<?php echo esc_attr($row['remote_slug'] ?? ''); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="m_local"><?php _e('Local slug','me5rine-lab'); ?></label></th>
                        <td><input name="mapping[local]" id="m_local" class="regular-text" value="<?php echo esc_attr($row['local_slug'] ?? ''); ?>" required></td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button($mode==='edit' ? __('Update','me5rine-lab') : __('Create','me5rine-lab')); ?>
        </form>
    </div>
    <?php
}

function admin_lab_remote_news_edit_query_screen() {
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized','me5rine-lab'));
    $mode   = isset($_GET['mode']) ? sanitize_key($_GET['mode']) : 'add';
    $return = isset($_GET['return']) ? esc_url_raw($_GET['return']) : admin_url('admin.php?page=admin-lab-remote-news&tab=queries');
    $qid    = isset($_GET['id']) ? sanitize_key($_GET['id']) : '';

    $row = ($mode==='edit' && $qid && function_exists('remote_news_query_get')) ? remote_news_query_get($qid) : null;
    if ($mode === 'edit' && !$row) wp_die(__('Item not found.','me5rine-lab'));

    $sources = function_exists('remote_news_sources_all') ? remote_news_sources_all() : [];
    $src_keys = array_map(fn($r)=>$r['source_key'], $sources);
    $selected_sources = [];
    if (!empty($row['sources'])) {
        $selected_sources = is_array($row['sources']) ? $row['sources'] : (json_decode((string)$row['sources'], true) ?: []);
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $mode==='edit' ? esc_html__('Edit query','me5rine-lab') : esc_html__('Add query','me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url($return); ?>" class="page-title-action"><?php _e('Back','me5rine-lab'); ?></a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('remote_news_save_query_single'); ?>
            <input type="hidden" name="action" value="remote_news_save_query_single">
            <input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
            <input type="hidden" name="return" value="<?php echo esc_attr($return); ?>">
            <?php if ($mode==='edit'): ?>
                <input type="hidden" name="orig_id" value="<?php echo esc_attr($row['query_id']); ?>">
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th><label for="q_id"><?php _e('ID (Query ID)','me5rine-lab'); ?></label></th>
                        <td><input name="query[id]" id="q_id" class="regular-text" value="<?php echo esc_attr($row['query_id'] ?? ''); ?>" <?php echo $mode==='add'?'required':''; ?>></td>
                    </tr>
                    <tr>
                        <th><label for="q_label"><?php _e('Label','me5rine-lab'); ?></label></th>
                        <td><input name="query[label]" id="q_label" class="regular-text" value="<?php echo esc_attr($row['label'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Sources','me5rine-lab'); ?></label></th>
                        <td>
                            <select name="query[sources][]" multiple size="4" style="min-width:220px;">
                                <?php foreach ($src_keys as $sk): ?>
                                    <option value="<?php echo esc_attr($sk); ?>" <?php selected(in_array($sk, $selected_sources, true)); ?>>
                                        <?php echo esc_html($sk); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="q_inc"><?php _e('Include categories (local slugs, CSV)','me5rine-lab'); ?></label></th>
                        <td><input name="query[include_cats]" id="q_inc" class="regular-text" value="<?php echo esc_attr($row['include_cats'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="q_exc"><?php _e('Exclude categories (local slugs, CSV)','me5rine-lab'); ?></label></th>
                        <td><input name="query[exclude_cats]" id="q_exc" class="regular-text" value="<?php echo esc_attr($row['exclude_cats'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="q_limit"><?php _e('Limit','me5rine-lab'); ?></label></th>
                        <td><input name="query[limit]" id="q_limit" type="number" min="1" value="<?php echo esc_attr($row['limit_items'] ?? 12); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="q_ob"><?php _e('Orderby','me5rine-lab'); ?></label></th>
                        <td>
                            <select name="query[orderby]" id="q_ob">
                                <?php foreach (['date','modified','title','rand'] as $ob): ?>
                                    <option value="<?php echo esc_attr($ob); ?>" <?php selected(($row['orderby'] ?? 'date') === $ob); ?>><?php echo esc_html($ob); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="q_ord"><?php _e('Order','me5rine-lab'); ?></label></th>
                        <td>
                            <select name="query[order]" id="q_ord">
                                <option value="DESC" <?php selected(($row['sort_order'] ?? 'DESC') === 'DESC'); ?>>DESC</option>
                                <option value="ASC"  <?php selected(($row['sort_order'] ?? 'DESC') === 'ASC');  ?>>ASC</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button($mode==='edit' ? __('Update','me5rine-lab') : __('Create','me5rine-lab')); ?>
        </form>
    </div>
    <?php
}
