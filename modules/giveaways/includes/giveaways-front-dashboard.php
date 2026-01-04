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
        echo '<div class="notice notice-success"><p>' . esc_html__('Concours publié avec succès !', 'me5rine-lab') . '</p></div>';
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
        $args['meta_query'][] = [
            'key'     => '_giveaway_status',
            'value'   => $status_filter,
            'compare' => '='
        ];
    }

    $query = new WP_Query($args);

    $posts = $query->posts;

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
        <div class="my-giveaways-dashboard-header me5rine-lab-dashboard-header">
            <h3 class="me5rine-lab-title"><?php esc_html_e('My Giveaways', 'me5rine-lab'); ?></h3>
            <a class="me5rine-lab-form-button" href="<?php echo do_shortcode('[giveaway_redirect_link]'); ?>"><?php esc_html_e('Add giveaway', 'me5rine-lab'); ?></a>
        </div>
        <div class="my-giveaways-dashboard-filters me5rine-lab-filters">
            <form method="get">
                <div class="me5rine-lab-filter-group">
                    <label class="me5rine-lab-form-label me5rine-lab-filter-label">
                        <span class="giveaways-dashboard-status-filter-phone"><?php esc_html_e('Show:', 'me5rine-lab'); ?></span>
                        <span class="giveaways-dashboard-status-filter-computer"><?php esc_html_e('Filter by status:', 'me5rine-lab'); ?></span>
                    </label>
                    <select name="status_filter" class="me5rine-lab-form-select me5rine-lab-filter-select">
                        <option value=""><?php esc_html_e('All', 'me5rine-lab'); ?></option>
                        <?php foreach (['Upcoming', 'Ongoing', 'Finished'] as $status): ?>
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
                        <th class="<?php echo esc_attr($sorting_class); ?>">
                            <a href="<?php echo esc_url($url); ?>">
                                <?php esc_html_e($label, 'me5rine-lab'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
                    <?php endforeach; ?>

                    <th><span class="unsorted-column"><?php esc_html_e('Prizes', 'me5rine-lab'); ?></span></th>

                    <?php foreach ([
                        'status'       => 'Status',
                        'participants' => 'Participants',
                        'entries'      => 'Entries'
                    ] as $key => $label):
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
                        <th class="<?php echo esc_attr($sorting_class); ?>">
                            <a href="<?php echo esc_url($url); ?>">
                                <?php esc_html_e($label, 'me5rine-lab'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
                    <?php endforeach; ?>

                    <th><span class="unsorted-column"><?php esc_html_e('Winners', 'me5rine-lab'); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                if (empty($paged_posts)): ?>
                    <tr>
                        <td colspan="10"><?php esc_html_e('No giveaways match your filters.', 'me5rine-lab'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paged_posts as $post):
                        $campaign_id = get_post_meta($post->ID, '_rafflepress_campaign', true);
                        $start = get_post_meta($post->ID, '_giveaway_start_date', true);
                        $end = get_post_meta($post->ID, '_giveaway_end_date', true);
                        $status = get_post_meta($post->ID, '_giveaway_status', true);
                        $participants = get_post_meta($post->ID, '_giveaway_participants_count', true);
                        $entries = get_post_meta($post->ID, '_giveaway_entries_count', true);
                        ?>
                        <tr class="me5rine-lab-table-row-toggleable is-collapsed">
                            <td class="summary" data-colname="<?php esc_attr_e('Name', 'me5rine-lab'); ?>">
                                <div class="me5rine-lab-table-summary-row">
                                    <span class="me5rine-lab-table-title"><?php echo esc_html($post->post_title); ?></span>
                                    <?php
                                    $edit_link = add_query_arg([
                                        'giveaway_id' => $campaign_id,
                                        'action' => 'edit',
                                        'redirect_url' => urlencode($_SERVER['REQUEST_URI']),
                                    ], home_url('/edit-giveaway/'));

                                    if ($post->post_status === 'draft') {
                                        $preview_link = get_preview_post_link($post);
                                        ?>
                                        <div class="row-actions">
                                            <span class="preview">
                                                <a class="me5rine-lab-form-button me5rine-lab-form-button-secondary" href="<?php echo esc_url($preview_link); ?>" target="_blank"><?php esc_html_e('Preview', 'me5rine-lab'); ?></a>
                                            </span>
                                            <span class="edit">
                                                <a class="me5rine-lab-form-button me5rine-lab-form-button-secondary" href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'me5rine-lab'); ?></a>
                                            </span>
                                            <form method="post" class="me5rine-lab-form-inline">
                                                <input type="hidden" name="publish_giveaway_id" value="<?php echo esc_attr($post->ID); ?>">
                                                <button type="submit" class="me5rine-lab-form-button"><?php esc_html_e('Publish', 'me5rine-lab'); ?></button>
                                            </form>
                                        </div>
                                        <?php
                                    } else {
                                        $view_link = get_permalink($post);
                                        ?>
                                        <a class="me5rine-lab-form-button me5rine-lab-form-button-secondary" href="<?php echo esc_url($view_link); ?>" target="_blank"><?php esc_html_e('View', 'me5rine-lab'); ?></a>
                                        <?php if ($status === 'Upcoming'): ?>
                                            <a class="me5rine-lab-form-button me5rine-lab-form-button-secondary" href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'me5rine-lab'); ?></a>
                                        <?php endif; ?>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <button type="button" class="me5rine-lab-table-toggle-btn" aria-expanded="false">
                                    <span class="me5rine-lab-sr-only"><?php esc_html_e('Show more details.', 'me5rine-lab'); ?></span>
                                </button>
                            </td>
                            <td class="details" data-colname="<?php esc_attr_e('Start Date', 'me5rine-lab'); ?>"><?php echo esc_html(admin_lab_format_local_datetime($start, 'd/m/Y \à H\hi')); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('End Date', 'me5rine-lab'); ?>"><?php echo esc_html(admin_lab_format_local_datetime($end, 'd/m/Y \à H\hi')); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Prizes', 'me5rine-lab'); ?>">
                                <?php
                                $prize_terms = get_the_terms($post->ID, 'giveaway_rewards');
                                if (!is_wp_error($prize_terms) && !empty($prize_terms)) {
                                    echo esc_html(implode(', ', wp_list_pluck($prize_terms, 'name')));
                                } else {
                                    echo '–';
                                }
                                ?>
                            </td>
                            <td class="details" data-colname="<?php esc_attr_e('Status', 'me5rine-lab'); ?>"><?php echo esc_html($status); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Participants', 'me5rine-lab'); ?>"><?php echo esc_html($participants ?: '–'); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Entries', 'me5rine-lab'); ?>"><?php echo esc_html($entries ?: '–'); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Winners', 'me5rine-lab'); ?>">
                                <?php
                                if ($status === 'Finished' && $campaign_id) {
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

        <div class="tablenav-pages my-giveaways-admin-pagination me5rine-lab-pagination">
            <span class="displaying-num me5rine-lab-pagination-info">
                <?php
                printf(
                    _n('%s item', '%s items', $total_items, 'me5rine-lab'),
                    number_format_i18n($total_items)
                );
                ?>
            </span>
            <span class="pagination-links me5rine-lab-pagination-links">
                <?php if ($paged > 1): ?>
                    <a class="first-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', 1)); ?>"><span aria-hidden="true">«</span></a>
                    <a class="prev-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $paged - 1)); ?>"><span aria-hidden="true">‹</span></a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">«</span>
                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">‹</span>
                <?php endif; ?>

                <span class="me5rine-lab-sr-only"><?php esc_html_e('Current page', 'me5rine-lab'); ?></span>
                <span class="paging-input">
                    <span class="tablenav-paging-text me5rine-lab-pagination-text">
                        <?php echo esc_html($paged); ?> <?php esc_html_e('of', 'me5rine-lab'); ?> 
                        <span class="total-pages"><?php echo esc_html($total_pages); ?></span>
                    </span>
                </span>

                <?php if ($paged < $total_pages): ?>
                    <a class="next-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $paged + 1)); ?>"><span aria-hidden="true">›</span></a>
                    <a class="last-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $total_pages)); ?>"><span aria-hidden="true">»</span></a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">›</span>
                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">»</span>
                <?php endif; ?>
            </span>
        </div>
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
    });
    </script>

    <?php
}
} // Fin de la protection function_exists