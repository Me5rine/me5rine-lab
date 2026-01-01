<?php
// File: modules/giveaways/front/giveaways-user-participation.php

if (!defined('ABSPATH')) exit;

if (!function_exists('admin_lab_render_participation_table')) {
    function admin_lab_render_participation_table($user_id, $status_filter, $per_page) {
        $current_user_id = get_current_user_id();
        if (!is_user_logged_in() || $user_id !== $current_user_id) {
            return '<p class="me5rine-lab-form-text">' . __('You are not allowed to view this content.', 'giveaways') . '</p>';
        }

        ob_start();
        global $wpdb;

        $contestants_table = $wpdb->prefix . 'rafflepress_contestants';
        $entries_table     = $wpdb->prefix . 'rafflepress_entries';

        $context   = 'my_giveaway_participations';
        $meta_key  = 'admin_lab_per_page__' . $context;

        $paged         = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
        $status_filter = $status_filter ?: (isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '');
        $per_page      = $per_page ?: (isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : (int) get_user_meta($user_id, $meta_key, true));
        if (!$per_page) $per_page = 10;
        update_user_meta($user_id, $meta_key, $per_page);
        $offset = ($paged - 1) * $per_page;

        $user = get_userdata($user_id);
        $email = $user ? $user->user_email : '';
        $sql = $wpdb->prepare("SELECT * FROM $contestants_table WHERE email = %s", $email);
        $count_sql = $sql;

        if ($status_filter === 'won') {
            $sql      .= " AND winner = 1";
            $count_sql .= " AND winner = 1";
        } elseif ($status_filter === 'awaiting') {
            $sql      .= " AND winner = 0";
            $count_sql .= " AND winner = 0";
            $now = current_time('mysql');
            $campaigns = $wpdb->get_col($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_rafflepress_campaign' 
                 AND meta_value IN (
                     SELECT giveaway_id FROM $contestants_table WHERE email = %s AND winner = 0
                 )", 
                $email
            ));

            $awaiting_ids = [];
            foreach ($campaigns as $giveaway_id) {
                $post_id = admin_lab_get_post_id_from_rafflepress($giveaway_id);
                if (!$post_id) continue;
                $end = get_post_meta($post_id, '_giveaway_end_date', true);
                if ($end && $now > $end) {
                    $awaiting_ids[] = (int) $giveaway_id;
                }
            }

            if (!empty($awaiting_ids)) {
                $ids_sql = implode(',', array_map('intval', $awaiting_ids));
                $sql      .= " AND giveaway_id IN ($ids_sql)";
                $count_sql .= " AND giveaway_id IN ($ids_sql)";
            } else {
                $sql      .= " AND 1=0";
                $count_sql .= " AND 1=0";
            }
        }

        $participations = $wpdb->get_results($sql);

        // Supprime les participations liées à des campagnes orphelines ou à des posts supprimés
        $participations = array_filter($participations, function($p) {
            $post_id = admin_lab_get_post_id_from_rafflepress($p->giveaway_id);
            return $post_id && get_post_status($post_id) === 'publish';
        });

        // Si filtre 'in_progress' → applique aussi la condition de date
        if ($status_filter === 'in_progress') {
            $now = current_time('mysql');
            $participations = array_filter($participations, function($p) use ($now) {
                $post_id = admin_lab_get_post_id_from_rafflepress($p->giveaway_id);
                $end = get_post_meta($post_id, '_giveaway_end_date', true);
                return $end && $now < $end;
            });
        }

        // Recalcul total/pagination après tous les filtres
        $total_items = count($participations);
        $total_pages = ceil($total_items / $per_page);
        $participations = array_slice($participations, $offset, $per_page);

        ?>
        <div class="giveaway-my-giveaways me5rine-lab-form-block">
            <div class="me5rine-lab-form-section">
                <h2 class="me5rine-lab-form-title"><?php _e('My Giveaway Entries', 'giveaways'); ?></h2>
                <div class="me5rine-lab-form-container">
                    <form method="get" onsubmit="return false;" class="me5rine-lab-filters">
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                        <input type="hidden" name="profiletab" value="user-giveaways">

                        <div class="me5rine-lab-filter-group">
                            <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="status_filter"><?php _e('Filter by status:', 'giveaways'); ?></label>
                            <select id="status_filter" name="status_filter" class="me5rine-lab-form-select me5rine-lab-filter-select">
                                <option value=""><?php _e('All', 'giveaways'); ?></option>
                                <option value="in_progress" <?php selected($status_filter, 'in_progress'); ?>><?php _e('In progress', 'giveaways'); ?></option>
                                <option value="awaiting" <?php selected($status_filter, 'awaiting'); ?>><?php _e('Awaiting draw', 'giveaways'); ?></option>
                                <option value="won" <?php selected($status_filter, 'won'); ?>><?php _e('Winner', 'giveaways'); ?></option>
                            </select>
                        </div>

                        <div class="me5rine-lab-filter-group">
                            <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="per_page"><?php _e('Entries per page:', 'giveaways'); ?></label>
                            <select id="per_page" name="per_page" class="me5rine-lab-form-select me5rine-lab-filter-select">
                                <?php foreach ([1, 5, 10, 20, 50] as $val): ?>
                                    <option value="<?php echo $val; ?>" <?php selected($per_page, $val); ?>><?php echo $val; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <div id="giveaway-my-giveaways-table">
                <?php if (empty($participations)): ?>
                    <p class="me5rine-lab-form-text">
                        <?php echo ($total_items === 0)
                            ? __('You haven\'t participated in any giveaways yet.', 'giveaways')
                            : __('No giveaways match your filters.', 'giveaways'); ?>
                    </p>
                <?php else: ?>
                    <table class="me5rine-lab-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><span class="unsorted-column my-giveaways-names-column"><?php _e('Giveaway', 'giveaways'); ?></span></th>
                                <th><span class="unsorted-column my-giveaways-entries-column"><?php _e('My Entries', 'giveaways'); ?></span></th>
                                <th><span class="unsorted-column my-giveaways-status-column"><?php _e('My Status', 'giveaways'); ?></span></th>
                                <th><span class="unsorted-column my-giveaways-winner-column"><?php _e('Winner(s)', 'giveaways'); ?></span></th>
                                <th><span class="unsorted-column my-giveaways-prizes-column"><?php _e('Prizes', 'giveaways'); ?></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participations as $p): ?>
                                <?php
                                $giveaway_id   = $p->giveaway_id;
                                $contestant_id = $p->id;
                                $post_id = admin_lab_get_post_id_from_rafflepress($giveaway_id);
                                if (!$post_id || get_post_status($post_id) !== 'publish') continue;

                                $title = get_the_title($post_id);
                                $url   = get_permalink($post_id);
                                $now   = current_time('mysql');
                                $end   = get_post_meta($post_id, '_giveaway_end_date', true);
                                $status = ($now < $end)
                                    ? __('In progress', 'giveaways')
                                    : ($p->winner ? __('Winner', 'giveaways') : __('Awaiting draw', 'giveaways'));

                                $prizes = get_the_terms($post_id, 'giveaway_rewards');
                                $gift_display = (!empty($prizes) && !is_wp_error($prizes)) ? implode(', ', wp_list_pluck($prizes, 'name')) : '—';

                                $entries = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM $entries_table WHERE contestant_id = %d AND giveaway_id = %d",
                                    $contestant_id,
                                    $giveaway_id
                                ));

                                $winner_emails = $wpdb->get_col($wpdb->prepare(
                                    "SELECT email FROM $contestants_table WHERE giveaway_id = %d AND winner = 1", $giveaway_id
                                ));

                                $winner_links = [];
                                foreach ($winner_emails as $w_email) {
                                    $winner_user = get_user_by('email', $w_email);
                                    if ($winner_user && $winner_user->ID !== $user_id) {
                                        $winner_links[] = '<a href="' . esc_url(um_user_profile_url($winner_user->ID)) . '">' . esc_html($winner_user->display_name) . '</a>';
                                    }
                                }

                                $winner_display = !empty($winner_links) ? implode(', ', $winner_links) : '—';
                                ?>
                                <tr class="me5rine-lab-table-row-toggleable is-collapsed">
                                    <td class="summary" data-colname="<?php esc_attr_e('Giveaway', 'giveaways'); ?>">
                                        <div class="me5rine-lab-table-summary-row">
                                            <span class="me5rine-lab-table-title">
                                                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
                                            </span>
                                        </div>
                                        <button type="button" class="me5rine-lab-table-toggle-btn" aria-expanded="false">
                                            <span class="me5rine-lab-sr-only"><?php _e('Show more details', 'giveaways'); ?></span>
                                        </button>
                                    </td>
                                    <td class="details" data-colname="<?php esc_attr_e('My Entries', 'giveaways'); ?>"><?php echo number_format_i18n($entries); ?></td>
                                    <td class="details" data-colname="<?php esc_attr_e('My Status', 'giveaways'); ?>"><?php echo esc_html($status); ?></td>
                                    <td class="details" data-colname="<?php esc_attr_e('Winner(s)', 'giveaways'); ?>"><?php echo $winner_display; ?></td>
                                    <td class="details" data-colname="<?php esc_attr_e('Prizes', 'giveaways'); ?>"><?php echo esc_html($gift_display); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav-pages my-giveaways-um-pagination me5rine-lab-pagination">
                            <span class="displaying-num me5rine-lab-pagination-info">
                                <?php echo sprintf(_n('%s entry', '%s entries', $total_items, 'giveaways'), number_format_i18n($total_items)); ?>
                            </span>
                            <span class="pagination-links me5rine-lab-pagination-links">
                                <?php if ($paged > 1): ?>
                                    <a class="first-page me5rine-lab-pagination-button giveaway-pg active" data-pg="1" href="#">«</a>
                                    <a class="prev-page me5rine-lab-pagination-button giveaway-pg active" data-pg="<?php echo $paged - 1; ?>" href="#">‹</a>
                                <?php else: ?>
                                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled">«</span>
                                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled">‹</span>
                                <?php endif; ?>

                                <span class="paging-input">
                                    <span class="tablenav-paging-text me5rine-lab-pagination-text">
                                        <?php echo esc_html($paged); ?> <?php _e('of', 'giveaways'); ?> <span class="total-pages"><?php echo esc_html($total_pages); ?></span>
                                    </span>
                                </span>

                                <?php if ($paged < $total_pages): ?>
                                    <a class="next-page me5rine-lab-pagination-button giveaway-pg active" data-pg="<?php echo $paged + 1; ?>" href="#">›</a>
                                    <a class="last-page me5rine-lab-pagination-button giveaway-pg active" data-pg="<?php echo $total_pages; ?>" href="#">»</a>
                                <?php else: ?>
                                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled">›</span>
                                    <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled">»</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}