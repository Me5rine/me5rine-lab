<?php
// File: modules/subscription/admin/remote-news-admin-ui.php

if (!defined('ABSPATH')) exit;

if (!defined('ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG')) {
    define('ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG', 'admin-lab-remote-news');
}

// Enregistrer la valeur du Screen Option (par utilisateur)
add_filter('set-screen-option', function ($status, $option, $value) {
    if ($option === 'remote_news_sources_per_page')  return (int)$value;
    if ($option === 'remote_news_mappings_per_page') return (int)$value;
    if ($option === 'remote_news_queries_per_page')  return (int)$value;
    return $status;
}, 10, 3);

// Ajouter le Screen Option "Sources per page"
add_action('current_screen', function ($screen) {
    if (!current_user_can('manage_options')) return;
    if (empty($screen) || empty($screen->id)) return;
    if (strpos($screen->id, ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG) === false) return;

    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
    if ($tab === 'sources') {
        add_screen_option('per_page', [
            'label'   => __('Sources per page', 'me5rine-lab'),
            'default' => 20,
            'option'  => 'remote_news_sources_per_page',
        ]);
    } elseif ($tab === 'mappings') {
        add_screen_option('per_page', [
            'label'   => __('Mappings per page', 'me5rine-lab'),
            'default' => 20,
            'option'  => 'remote_news_mappings_per_page',
        ]);
    } elseif ($tab === 'queries') {
        add_screen_option('per_page', [
            'label'   => __('Queries per page', 'me5rine-lab'),
            'default' => 20,
            'option'  => 'remote_news_queries_per_page',
        ]);
    }
});

// UI principale
function admin_lab_remote_news_admin_ui() {
    if (!current_user_can('manage_options')) return;

    // Notifications après redirection
    static $rn_notice_rendered = false;
    if (!$rn_notice_rendered && !empty($_GET['rn_notice']) && isset($_GET['rn_msg'])) {
        $type  = sanitize_key($_GET['rn_notice']);
        $msg   = urldecode((string)$_GET['rn_msg']);
        $class = in_array($type, ['success','updated'], true) ? 'notice notice-success'
            : ($type === 'error' ? 'notice notice-error' : 'notice notice-info');
        echo '<div class="'.esc_attr($class).' is-dismissible"><p>'.esc_html($msg).'</p></div>';
        $rn_notice_rendered = true;
    }

    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
    $url_tab = function ($t) {
        return esc_url(add_query_arg([
            'page' => ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG,
            'tab'  => $t,
        ], admin_url('admin.php')));
    };

    // Données
    $sources = remote_news_sources_all();
    $queries = remote_news_queries_all();
    $maps    = remote_news_map_all();

    // Index pratique pour select/affichage
    $sources_by_key = [];
    foreach ($sources as $row) $sources_by_key[$row['source_key']] = $row;

    // Titre + bouton "Add …"
    $titles = [
        'overview' => __('Remote News', 'me5rine-lab'),
        'sources'  => __('Remote News › Sources', 'me5rine-lab'),
        'mappings' => __('Remote News › Mappings', 'me5rine-lab'),
        'queries'  => __('Remote News › Queries',  'me5rine-lab'),
    ];
    $heading = $titles[$tab] ?? $titles['overview'];

    // URL du bouton Add selon l’onglet
    $add_urls = [];

    // Sources
    $add_urls['sources'] = admin_url(
        'admin.php?page=admin-lab-remote-news-edit-source'
        . '&mode=add'
        . '&return=' . rawurlencode( admin_url('admin.php?page='.ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG.'&tab=sources') )
    );

    // Mappings
    $add_urls['mappings'] = admin_url(
        'admin.php?page=admin-lab-remote-news-edit-mapping'
        . '&mode=add'
        . '&return=' . rawurlencode( admin_url('admin.php?page='.ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG.'&tab=mappings') )
    );

    // Queries
    $add_urls['queries'] = admin_url(
        'admin.php?page=admin-lab-remote-news-edit-query'
        . '&mode=add'
        . '&return=' . rawurlencode( admin_url('admin.php?page='.ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG.'&tab=queries') )
    );

    $can_add = in_array($tab, ['sources','mappings','queries'], true);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html($heading); ?></h1>
        <?php if ($can_add): ?>
            <a href="<?php echo esc_url($add_urls[$tab]); ?>" class="page-title-action">
                <?php echo $tab==='sources' ? esc_html__('Add source','me5rine-lab') : ($tab==='mappings' ? esc_html__('Add mapping','me5rine-lab') : esc_html__('Add query','me5rine-lab')); ?>
            </a>
        <?php endif; ?>
        <hr class="wp-header-end">

        <h2 class="nav-tab-wrapper" style="margin-top:12px;">
            <a class="nav-tab <?php echo $tab==='overview'?'nav-tab-active':''; ?>" href="<?php echo $url_tab('overview'); ?>">Overview</a>
            <a class="nav-tab <?php echo $tab==='sources'?'nav-tab-active':''; ?>" href="<?php echo $url_tab('sources'); ?>">Sources</a>
            <a class="nav-tab <?php echo $tab==='mappings'?'nav-tab-active':''; ?>" href="<?php echo $url_tab('mappings'); ?>">Mappings</a>
            <a class="nav-tab <?php echo $tab==='queries'?'nav-tab-active':''; ?>" href="<?php echo $url_tab('queries'); ?>">Queries</a>
        </h2>

        <?php if ($tab === 'overview'): ?>
            <div class="card" style="max-width:900px;">
                <h2><?php _e('Résumé', 'me5rine-lab'); ?></h2>
                <ul>
                    <li><?php echo number_format_i18n(count($sources)); ?> <?php _e('source(s)', 'me5rine-lab'); ?></li>
                    <li><?php echo number_format_i18n(count($maps)); ?> <?php _e('mapping(s)', 'me5rine-lab'); ?></li>
                    <li><?php echo number_format_i18n(count($queries)); ?> <?php _e('query ID(s)', 'me5rine-lab'); ?></li>
                </ul>
                <p>
                    <?php $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview'; ?>
                    <a class="button" href="<?php echo esc_url(
                        wp_nonce_url(
                            add_query_arg([
                                'action' => 'remote_news_sync_now',
                                'tab'    => $current_tab,
                            ], admin_url('admin-post.php')),
                            'remote_news_sync_now'
                        )
                    ); ?>">
                        <?php _e('Sync now','me5rine-lab'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'sources'): ?>
            <h2 style="margin-top:16px"><?php _e('Sources', 'me5rine-lab'); ?></h2>
            <p class="description"><?php _e('Declare your remote sites. The categories are REMOTE slugs (optional).', 'me5rine-lab'); ?></p>

            <?php
            // Table WP_List_Table (pagination + recherche + bulk)
            require_once __DIR__ . '/tables/class-remote-news-sources-table.php';
            $table = new Admin_Lab_Remote_News_Sources_Table();
            $table->prepare_items();
            ?>
            <form method="get" style="margin-bottom:8px;">
                <input type="hidden" name="page" value="<?php echo esc_attr(ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG); ?>" />
                <input type="hidden" name="tab" value="sources" />
                <?php $table->search_box(__('Search sources','me5rine-lab'), 'rn-sources'); ?>
            </form>

            <form method="post">
                <?php $table->display(); ?>
            </form>
        <?php endif; ?>

        <?php if ($tab === 'mappings'): ?>
            <h2 style="margin-top:16px"><?php _e('Category mappings', 'me5rine-lab'); ?></h2>
            <p class="description"><?php _e('Map each remote category slug to a local category slug.', 'me5rine-lab'); ?></p>
            <?php
            require_once __DIR__ . '/tables/class-remote-news-mappings-table.php';
            $mappings_table = new Admin_Lab_Remote_News_Mappings_Table();
            $mappings_table->prepare_items();
            ?>

            <form method="get" style="margin-bottom:8px;">
                <input type="hidden" name="page" value="<?php echo esc_attr(ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG); ?>" />
                <input type="hidden" name="tab" value="mappings" />
                <?php $mappings_table->search_box(__('Search mappings', 'me5rine-lab'), 'rn-mappings'); ?>
            </form>

            <form method="post">
                <?php $mappings_table->display(); ?>
            </form>
        <?php endif; ?>

        <?php if ($tab === 'queries'): ?>
            <h2 style="margin-top:16px"><?php _e('Elementor queries (Query IDs)', 'me5rine-lab'); ?></h2>
            <p class="description"><?php _e('Create Query IDs to use in Elementor (e.g. remote_pokemon_go).', 'me5rine-lab'); ?></p>

            <?php
            require_once __DIR__ . '/tables/class-remote-news-queries-table.php';
            $queries_table = new Admin_Lab_Remote_News_Queries_Table();
            $queries_table->prepare_items();
            ?>

            <form method="get" style="margin-bottom:8px;">
                <input type="hidden" name="page" value="<?php echo esc_attr(ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG); ?>" />
                <input type="hidden" name="tab" value="queries" />
                <?php $queries_table->search_box(__('Search queries', 'me5rine-lab'), 'rn-queries'); ?>
            </form>

            <form method="post">
                <?php $queries_table->display(); ?>
            </form>
        <?php endif; ?>
    </div>

    <script>
    (function(){
        function addRow(tableId){
            var t = document.querySelector('#'+tableId+' .rn-template');
            if(!t) return;
            var tbody = document.querySelector('#'+tableId+' tbody');
            var idx = tbody.querySelectorAll('tr:not(.rn-template)').length + 1;
            var html = t.outerHTML.replace(/__i__/g, idx).replace('style="display:none;"','');
            tbody.insertAdjacentHTML('beforeend', html);
        }
        function onClick(e){
            if (e.target.matches('#rn-add-mapping')) { e.preventDefault(); addRow('rn-mappings'); }
            if (e.target.matches('#rn-add-query'))   { e.preventDefault(); addRow('rn-queries'); }
            if (e.target.closest('.rn-remove-row')) {
                e.preventDefault();
                var tr = e.target.closest('tr');
                if (tr && !tr.classList.contains('rn-template')) tr.remove();
            }
        }
        document.addEventListener('click', onClick);
    })();
    </script>
    <?php
}
