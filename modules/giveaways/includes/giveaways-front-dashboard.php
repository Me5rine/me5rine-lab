<?php
// File: modules/giveaways/includes/giveaways-front-dashboard.php

if (!defined('ABSPATH')) exit;

if (!admin_lab_require_access('giveaways', __('Giveaways dashboard', 'me5rine-lab'))) {
    return;
}

if (
    !empty($_POST['publish_giveaway_id']) &&
    current_user_can('edit_post', (int) $_POST['publish_giveaway_id'])
) {
    $post_id = (int) $_POST['publish_giveaway_id'];
    $post = get_post($post_id);

    if ($post && $post->post_status === 'draft') {
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'publish',
        ]);
        printf(
            '<div class="me5rine-lab-form-message me5rine-lab-form-message-success"><p>%s</p></div>',
            esc_html__('Giveaway successfully published !', 'me5rine-lab')
        );
    }
}

if (!function_exists('giveaways_display_front_dashboard')) {
function giveaways_display_front_dashboard() {
    
    $user_id = get_current_user_id();    

    $context   = 'admin_giveaways';
    $meta_key  = 'admin_lab_per_page__' . $context;
    $per_page  = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : (int) get_user_meta($user_id, $meta_key, true);
    if (!$per_page) $per_page = 5;
    update_user_meta($user_id, $meta_key, $per_page);

    // Gestion des colonnes visibles
    $columns_meta_key = 'admin_lab_giveaways_visible_columns';
    $default_columns = [
        'name' => true,
        'start_date' => false, // Masqué par défaut
        'end_date' => false, // Masqué par défaut
        'prizes' => false, // Masqué par défaut
        'status' => true,
        'participants' => true,
        'entries' => true,
        'winners' => true,
    ];
    
    // Récupérer les colonnes visibles sauvegardées
    $saved_columns = get_user_meta($user_id, $columns_meta_key, true);
    if (!is_array($saved_columns)) {
        $saved_columns = $default_columns;
    } else {
        // Fusionner avec les valeurs par défaut pour les nouvelles colonnes
        $saved_columns = array_merge($default_columns, $saved_columns);
    }
    
    // Sauvegarder les colonnes si elles sont envoyées via AJAX ou formulaire
    if (isset($_POST['visible_columns']) && is_array($_POST['visible_columns'])) {
        $new_columns = [];
        foreach ($default_columns as $col_key => $default_value) {
            $new_columns[$col_key] = isset($_POST['visible_columns'][$col_key]);
        }
        update_user_meta($user_id, $columns_meta_key, $new_columns);
        $saved_columns = $new_columns;
    }

    $paged         = isset($_GET['pg']) ? max(1, (int) $_GET['pg']) : 1;
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
    $order_by      = isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'start_date';
    $order         = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'asc' : 'desc';

    $args = [
        'post_type'      => 'giveaway',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'meta_query'     => [
            [
                'key'     => '_giveaway_partner_id',
                'value'   => $user_id,
                'compare' => '='
            ]
        ],
    ];

    if ($status_filter) {
        if ($status_filter === 'Awaiting draw') {
            // Pour "Awaiting draw", on filtre les concours Finished sans gagnants
            $args['meta_query'][] = [
                'key'     => '_giveaway_status',
                'value'   => 'Finished',
                'compare' => '='
            ];
        } else {
            $args['meta_query'][] = [
                'key'     => '_giveaway_status',
                'value'   => $status_filter,
                'compare' => '='
            ];
        }
    }

    $query = new WP_Query($args);

    $posts = $query->posts;
    
    // Si le filtre est "Awaiting draw", filtrer les résultats pour ne garder que ceux sans gagnants
    if ($status_filter === 'Awaiting draw') {
        global $wpdb;
        $filtered_posts = [];
        foreach ($posts as $post) {
            $campaign_id = get_post_meta($post->ID, '_rafflepress_campaign', true);
            if ($campaign_id) {
                $winners_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d AND winner = 1",
                    $campaign_id
                ));
                // Ne garder que ceux sans gagnants
                if ($winners_count == 0) {
                    $filtered_posts[] = $post;
                }
            }
        }
        $posts = $filtered_posts;
    }

    usort($posts, function ($a, $b) use ($order_by, $order) {
        switch ($order_by) {
            case 'name':
                $valA = $a->post_title;
                $valB = $b->post_title;
                break;
            case 'start_date':
                $valA = get_post_meta($a->ID, '_giveaway_start_date', true);
                $valB = get_post_meta($b->ID, '_giveaway_start_date', true);
                break;
            case 'end_date':
                $valA = get_post_meta($a->ID, '_giveaway_end_date', true);
                $valB = get_post_meta($b->ID, '_giveaway_end_date', true);
                break;
            case 'status':
                $valA = get_post_meta($a->ID, '_giveaway_status', true);
                $valB = get_post_meta($b->ID, '_giveaway_status', true);
                break;
            case 'participants':
                $valA = (int) get_post_meta($a->ID, '_giveaway_participants_count', true);
                $valB = (int) get_post_meta($b->ID, '_giveaway_participants_count', true);
                break;
            case 'entries':
                $valA = (int) get_post_meta($a->ID, '_giveaway_entries_count', true);
                $valB = (int) get_post_meta($b->ID, '_giveaway_entries_count', true);
                break;
            default:
                $valA = $valB = '';
        }

        return ($order === 'asc') ? $valA <=> $valB : $valB <=> $valA;
    });

    $total_items  = count($posts);
    $total_pages  = ceil($total_items / $per_page);
    $paged_posts  = array_slice($posts, ($paged - 1) * $per_page, $per_page);
    ?>

    <div class="my-giveaways-dashboard me5rine-lab-dashboard">
        <h2 class="me5rine-lab-title-large"><?php esc_html_e('My Giveaways', 'me5rine-lab'); ?></h2>
        <div class="me5rine-lab-dashboard-header">
            <div class="me5rine-lab-dashboard-header-actions">
                <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary me5rine-lab-screen-options-toggle" aria-expanded="false">
                    <?php esc_html_e('Screen Options', 'me5rine-lab'); ?>
                </button>
                <a class="me5rine-lab-form-button" href="<?php echo do_shortcode('[giveaway_redirect_link]'); ?>"><?php esc_html_e('Add giveaway', 'me5rine-lab'); ?></a>
            </div>
        </div>
        
        <div class="me5rine-lab-screen-options-panel" style="display: none;">
            <div class="me5rine-lab-screen-options-panel-content">
                <h4><?php esc_html_e('Show on screen', 'me5rine-lab'); ?></h4>
                <div class="me5rine-lab-screen-options-columns">
                    <?php
                    $column_labels = [
                        'name' => __('Name', 'me5rine-lab'),
                        'start_date' => __('Start Date', 'me5rine-lab'),
                        'end_date' => __('End Date', 'me5rine-lab'),
                        'prizes' => __('Prizes', 'me5rine-lab'),
                        'status' => __('Status', 'me5rine-lab'),
                        'participants' => __('Participants', 'me5rine-lab'),
                        'entries' => __('Entries', 'me5rine-lab'),
                        'winners' => __('Winners', 'me5rine-lab'),
                    ];
                    
                    foreach ($column_labels as $col_key => $col_label):
                        $is_visible = isset($saved_columns[$col_key]) ? $saved_columns[$col_key] : true;
                        ?>
                        <label class="me5rine-lab-screen-options-column-item">
                            <input type="checkbox" 
                                   name="visible_columns[<?php echo esc_attr($col_key); ?>]" 
                                   value="1" 
                                   data-column="<?php echo esc_attr($col_key); ?>"
                                   <?php checked($is_visible, true); ?>>
                            <span><?php echo esc_html($col_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="me5rine-lab-screen-options-actions">
                    <button type="button" class="me5rine-lab-form-button me5rine-lab-screen-options-apply">
                        <?php esc_html_e('Apply', 'me5rine-lab'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="my-giveaways-dashboard-filters me5rine-lab-filters">
            <form method="get">
                <div class="me5rine-lab-filter-group">
                    <label class="me5rine-lab-form-label me5rine-lab-filter-label">
                        <span class="me5rine-lab-filter-label-mobile"><?php esc_html_e('Show:', 'me5rine-lab'); ?></span>
                        <span class="me5rine-lab-filter-label-desktop"><?php esc_html_e('Filter by status:', 'me5rine-lab'); ?></span>
                    </label>
                    <select name="status_filter" class="me5rine-lab-form-select me5rine-lab-filter-select">
                        <option value=""><?php esc_html_e('All', 'me5rine-lab'); ?></option>
                        <?php foreach (['Upcoming', 'Ongoing', 'Finished', 'Awaiting draw'] as $status): ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                                <?php echo esc_html($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="me5rine-lab-filter-group">
                    <label class="me5rine-lab-form-label me5rine-lab-filter-label">
                        <?php esc_html_e('Show:', 'me5rine-lab'); ?>
                    </label>
                    <select name="per_page" class="me5rine-lab-form-select me5rine-lab-filter-select">
                        <?php foreach ([5, 10, 20, 50] as $num): ?>
                            <option value="<?php echo esc_attr($num); ?>" <?php selected($per_page, $num); ?>>
                                <?php echo esc_html($num); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="filter_action" class="me5rine-lab-form-button me5rine-lab-form-button-secondary">
                    <?php esc_html_e('Filter', 'me5rine-lab'); ?>
                </button>
            </form>
        </div>

        <table class="me5rine-lab-table me5rine-lab-table-giveaways-dashboard striped">
            <thead>
                <tr>
                    <?php
                    $sortable_columns = [
                        'name'        => 'Name',
                        'start_date'  => 'Start Date',
                        'end_date'    => 'End Date',
                    ];

                    foreach ($sortable_columns as $key => $label):
                        $is_visible = isset($saved_columns[$key]) ? $saved_columns[$key] : true;
                        $column_class = 'column-' . esc_attr($key);
                        $hidden_class = $is_visible ? '' : 'column-hidden';
                        $new_order     = ($order_by === $key && $order === 'asc') ? 'desc' : 'asc';
                        $sorting_class = ($order_by === $key) ? 'sorted ' . $order : 'sortable desc';
                        $url           = add_query_arg([
                            'order_by'      => $key,
                            'order'         => $new_order,
                            'pg'            => $paged,
                            'status_filter' => $status_filter,
                            'per_page'      => $per_page,
                        ]);
                        ?>
                        <th class="<?php echo esc_attr($sorting_class . ' ' . $column_class . ' ' . $hidden_class); ?>" data-column="<?php echo esc_attr($key); ?>">
                            <a href="<?php echo esc_url($url); ?>">
                                <?php esc_html_e($label, 'me5rine-lab'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
                    <?php endforeach; ?>

                    <?php
                    $is_prizes_visible = isset($saved_columns['prizes']) ? $saved_columns['prizes'] : true;
                    $prizes_hidden_class = $is_prizes_visible ? '' : 'column-hidden';
                    ?>
                    <th class="column-prizes <?php echo esc_attr($prizes_hidden_class); ?>" data-column="prizes">
                        <span class="unsorted-column"><?php esc_html_e('Prizes', 'me5rine-lab'); ?></span>
                    </th>

                    <?php foreach ([
                        'status'       => 'Status',
                        'participants' => 'Participants',
                        'entries'      => 'Entries'
                    ] as $key => $label):
                        $is_visible = isset($saved_columns[$key]) ? $saved_columns[$key] : true;
                        $column_class = 'column-' . esc_attr($key);
                        $hidden_class = $is_visible ? '' : 'column-hidden';
                        $new_order     = ($order_by === $key && $order === 'asc') ? 'desc' : 'asc';
                        $sorting_class = ($order_by === $key) ? 'sorted ' . $order : 'sortable desc';
                        $url           = add_query_arg([
                            'order_by'      => $key,
                            'order'         => $new_order,
                            'pg'            => $paged,
                            'status_filter' => $status_filter,
                            'per_page'      => $per_page,
                        ]);
                        ?>
                        <th class="<?php echo esc_attr($sorting_class . ' ' . $column_class . ' ' . $hidden_class); ?>" data-column="<?php echo esc_attr($key); ?>">
                            <a href="<?php echo esc_url($url); ?>">
                                <?php esc_html_e($label, 'me5rine-lab'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
                    <?php endforeach; ?>

                    <?php
                    $is_winners_visible = isset($saved_columns['winners']) ? $saved_columns['winners'] : true;
                    $winners_hidden_class = $is_winners_visible ? '' : 'column-hidden';
                    ?>
                    <th class="column-winners <?php echo esc_attr($winners_hidden_class); ?>" data-column="winners">
                        <span class="unsorted-column"><?php esc_html_e('Winners', 'me5rine-lab'); ?></span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                if (empty($paged_posts)): 
                    // Calculer le nombre de colonnes visibles pour le colspan
                    $visible_columns_count = array_sum(array_map('intval', $saved_columns));
                    ?>
                    <tr>
                        <td colspan="<?php echo esc_attr($visible_columns_count); ?>"><?php esc_html_e('No giveaways match your filters.', 'me5rine-lab'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paged_posts as $post):
                        $campaign_id = get_post_meta($post->ID, '_rafflepress_campaign', true);
                        $start = get_post_meta($post->ID, '_giveaway_start_date', true);
                        $end = get_post_meta($post->ID, '_giveaway_end_date', true);
                        $status_raw = get_post_meta($post->ID, '_giveaway_status', true);
                        $participants = get_post_meta($post->ID, '_giveaway_participants_count', true);
                        $entries = get_post_meta($post->ID, '_giveaway_entries_count', true);
                        
                        // Déterminer le type de statut pour le système de statuts
                        $status_type = 'info'; // Par défaut
                        $status_text = $status_raw;
                        
                        switch ($status_raw) {
                            case 'Upcoming':
                                $status_text = __('Upcoming', 'me5rine-lab');
                                $status_type = 'info'; // Bleu - à venir
                                break;
                            case 'Ongoing':
                                $status_text = __('Ongoing', 'me5rine-lab');
                                $status_type = 'success'; // Vert - actif et positif
                                break;
                            case 'Finished':
                                // Vérifier si le tirage a été effectué (s'il y a des gagnants)
                                $has_winners = false;
                                if ($campaign_id) {
                                    $winners_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d AND winner = 1",
                                        $campaign_id
                                    ));
                                    $has_winners = $winners_count > 0;
                                }
                                
                                if ($has_winners) {
                                    // Concours terminé avec tirage effectué
                                    $status_text = __('Finished', 'me5rine-lab');
                                    $status_type = 'error'; // Rouge - terminé avec tirage
                                } else {
                                    // Concours terminé mais tirage pas encore effectué
                                    $status_text = __('Awaiting draw', 'me5rine-lab');
                                    $status_type = 'warning'; // Orange - tirage à venir
                                }
                                break;
                            default:
                                $status_text = $status_raw ?: __('Unknown', 'me5rine-lab');
                                $status_type = 'info';
                        }
                        ?>
                        <tr class="me5rine-lab-table-row-toggleable is-collapsed">
                            <?php
                            $is_name_visible = isset($saved_columns['name']) ? $saved_columns['name'] : true;
                            $name_hidden_class = $is_name_visible ? '' : 'column-hidden';
                            ?>
                            <td class="summary column-name <?php echo esc_attr($name_hidden_class); ?>" data-column="name" data-colname="<?php esc_attr_e('Name', 'me5rine-lab'); ?>">
                                <div class="me5rine-lab-table-summary-row">
                                    <div>
                                        <span class="me5rine-lab-table-title">
                                            <a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a>
                                        </span>
                                        <?php
                                        $edit_link = add_query_arg([
                                            'giveaway_id' => $campaign_id,
                                            'action' => 'edit',
                                            'redirect_url' => urlencode($_SERVER['REQUEST_URI']),
                                        ], home_url('/edit-giveaway/'));

                                        $actions = [];
                                        
                                        if ($post->post_status === 'draft') {
                                            $preview_link = get_preview_post_link($post);
                                            $actions['preview'] = '<a href="' . esc_url($preview_link) . '" target="_blank">' . esc_html__('Preview', 'me5rine-lab') . '</a>';
                                            $actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'me5rine-lab') . '</a>';
                                        } else {
                                            $view_link = get_permalink($post);
                                            $actions['view'] = '<a href="' . esc_url($view_link) . '" target="_blank">' . esc_html__('View', 'me5rine-lab') . '</a>';
                                            if ($status_raw === 'Upcoming') {
                                                $actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'me5rine-lab') . '</a>';
                                            }
                                        }
                                        
                                        if (!empty($actions)) {
                                            echo '<div class="row-actions">';
                                            $action_items = [];
                                            foreach ($actions as $key => $action) {
                                                $action_items[] = '<span class="' . esc_attr($key) . '">' . $action . '</span>';
                                            }
                                            echo implode('', $action_items);
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <button type="button" class="me5rine-lab-table-toggle-btn" aria-expanded="false">
                                    <span class="me5rine-lab-sr-only"><?php esc_html_e('Show more details.', 'me5rine-lab'); ?></span>
                                </button>
                            </td>
                            <?php
                            $is_start_date_visible = isset($saved_columns['start_date']) ? $saved_columns['start_date'] : false;
                            $start_date_hidden_class = $is_start_date_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-start_date <?php echo esc_attr($start_date_hidden_class); ?>" data-column="start_date" data-colname="<?php esc_attr_e('Start Date', 'me5rine-lab'); ?>"><?php echo esc_html(admin_lab_format_local_datetime($start, 'd/m/Y \à H\hi')); ?></td>
                            <?php
                            $is_end_date_visible = isset($saved_columns['end_date']) ? $saved_columns['end_date'] : false;
                            $end_date_hidden_class = $is_end_date_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-end_date <?php echo esc_attr($end_date_hidden_class); ?>" data-column="end_date" data-colname="<?php esc_attr_e('End Date', 'me5rine-lab'); ?>"><?php echo esc_html(admin_lab_format_local_datetime($end, 'd/m/Y \à H\hi')); ?></td>
                            <?php
                            $is_prizes_visible = isset($saved_columns['prizes']) ? $saved_columns['prizes'] : true;
                            $prizes_hidden_class = $is_prizes_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-prizes <?php echo esc_attr($prizes_hidden_class); ?>" data-column="prizes" data-colname="<?php esc_attr_e('Prizes', 'me5rine-lab'); ?>">
                                <?php
                                $prize_terms = get_the_terms($post->ID, 'giveaway_rewards');
                                if (!is_wp_error($prize_terms) && !empty($prize_terms)) {
                                    echo esc_html(implode(', ', wp_list_pluck($prize_terms, 'name')));
                                } else {
                                    echo '–';
                                }
                                ?>
                            </td>
                            <?php
                            $is_status_visible = isset($saved_columns['status']) ? $saved_columns['status'] : true;
                            $status_hidden_class = $is_status_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-status <?php echo esc_attr($status_hidden_class); ?>" data-column="status" data-colname="<?php esc_attr_e('Status', 'me5rine-lab'); ?>">
                                <?php echo admin_lab_render_status($status_text, $status_type); ?>
                            </td>
                            <?php
                            $is_participants_visible = isset($saved_columns['participants']) ? $saved_columns['participants'] : true;
                            $participants_hidden_class = $is_participants_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-participants <?php echo esc_attr($participants_hidden_class); ?>" data-column="participants" data-colname="<?php esc_attr_e('Participants', 'me5rine-lab'); ?>"><?php echo esc_html($participants ?: '–'); ?></td>
                            <?php
                            $is_entries_visible = isset($saved_columns['entries']) ? $saved_columns['entries'] : true;
                            $entries_hidden_class = $is_entries_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-entries <?php echo esc_attr($entries_hidden_class); ?>" data-column="entries" data-colname="<?php esc_attr_e('Entries', 'me5rine-lab'); ?>"><?php echo esc_html($entries ?: '–'); ?></td>
                            <?php
                            $is_winners_visible = isset($saved_columns['winners']) ? $saved_columns['winners'] : true;
                            $winners_hidden_class = $is_winners_visible ? '' : 'column-hidden';
                            ?>
                            <td class="details column-winners <?php echo esc_attr($winners_hidden_class); ?>" data-column="winners" data-colname="<?php esc_attr_e('Winners', 'me5rine-lab'); ?>">
                                <?php
                                if ($status_raw === 'Finished' && $campaign_id) {
                                    $winners = $wpdb->get_results($wpdb->prepare(
                                        "SELECT fname, lname, email FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d AND winner = 1",
                                        $campaign_id
                                    ));
                                    if ($winners) {
                                        $names = [];
                                        foreach ($winners as $w) {
                                            $user = get_user_by('email', $w->email);
                                            if ($user) {
                                                $url = function_exists('um_user_profile_url') ? um_user_profile_url($user->ID) : get_author_posts_url($user->ID);
                                                $names[] = '<a href="' . esc_url($url) . '">' . esc_html($user->display_name) . '</a>';
                                            } else {
                                                $name = trim($w->fname . ' ' . $w->lname);
                                                $names[] = esc_html($name ?: $w->email);
                                            }
                                        }
                                        echo implode('<br>', $names);
                                    } else {
                                        echo esc_html__('Upcoming draw', 'me5rine-lab');
                                    }
                                } else {
                                    echo '–';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        echo me5rine_lab_render_pagination([
            'total_items' => $total_items,
            'paged'       => $paged,
            'total_pages' => $total_pages,
            'page_var'    => 'pg',
            'text_domain' => 'me5rine-lab',
        ]);
        ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.me5rine-lab-table-toggle-btn');

        buttons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault(); // évite tout effet parasite
                const tr = button.closest('tr');
                const expanded = tr.classList.toggle('is-expanded');
                tr.classList.toggle('is-collapsed', !expanded);
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        });

        // Les lignes sont déjà en is-collapsed par défaut dans le HTML
        
        // Gestion des options d'écran (générique et réutilisable)
        const screenOptionsToggle = document.querySelector('.me5rine-lab-screen-options-toggle');
        const screenOptionsPanel = document.querySelector('.me5rine-lab-screen-options-panel');
        const screenOptionsApply = document.querySelector('.me5rine-lab-screen-options-apply');
        const columnCheckboxes = document.querySelectorAll('.me5rine-lab-screen-options-column-item input[type="checkbox"]');
        
        if (screenOptionsToggle && screenOptionsPanel) {
            // Toggle du panneau
            screenOptionsToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isExpanded = screenOptionsPanel.style.display !== 'none';
                screenOptionsPanel.style.display = isExpanded ? 'none' : 'block';
                screenOptionsToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            });
            
            // Application des changements
            if (screenOptionsApply) {
                screenOptionsApply.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const visibleColumns = {};
                    columnCheckboxes.forEach(checkbox => {
                        const columnName = checkbox.getAttribute('data-column');
                        visibleColumns[columnName] = checkbox.checked;
                    });
                    
                    // Sauvegarder via AJAX
                    const formData = new FormData();
                    Object.keys(visibleColumns).forEach(key => {
                        if (visibleColumns[key]) {
                            formData.append('visible_columns[' + key + ']', '1');
                        }
                    });
                    formData.append('action', 'save_giveaways_columns');
                    formData.append('nonce', '<?php echo wp_create_nonce("save_giveaways_columns"); ?>');
                    
                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Masquer/afficher les colonnes
                            Object.keys(visibleColumns).forEach(columnName => {
                                const isVisible = visibleColumns[columnName];
                                const columnElements = document.querySelectorAll('[data-column="' + columnName + '"]');
                                columnElements.forEach(el => {
                                    if (isVisible) {
                                        el.classList.remove('column-hidden');
                                    } else {
                                        el.classList.add('column-hidden');
                                    }
                                });
                            });
                            
                            // Fermer le panneau
                            screenOptionsPanel.style.display = 'none';
                            screenOptionsToggle.setAttribute('aria-expanded', 'false');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving column preferences:', error);
                    });
                });
            }
        }
    });
    </script>

    <?php
}
} // Fin de la protection function_exists