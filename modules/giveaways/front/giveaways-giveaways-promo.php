<?php
// File: modules/giveaways/front/giveaways-giveaways-promo.php

if (!defined('ABSPATH')) exit;

function admin_lab_render_giveaway_promo_table($args = []) {
    $user_id = um_get_requested_user();    
    $current_user_id = get_current_user_id();

    if (!is_user_logged_in() || $user_id !== $current_user_id) {
        ?>
        <p class="me5rine-lab-form-text"><?php _e('You are not allowed to view this tab.', 'giveaways'); ?></p>
        <?php
        return;
    }

    global $wpdb;

    $user = get_userdata($user_id);
    $email = $user->user_email;
    $now = current_time('mysql');

    ?>
    <div class="giveaway-profil-promo-container me5rine-lab-form-block">
        <div class="me5rine-lab-form-section">
            <h2 class="me5rine-lab-form-title"><?php _e('All Giveaways', 'giveaways'); ?></h2>
            <p class="me5rine-lab-form-subtitle"><?php _e('Participate and win great prizes!', 'giveaways'); ?></p>
    <?php

    $active_posts = get_posts([
        'post_type'      => 'giveaway',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_giveaway_start_date',
                'value'   => $now,
                'compare' => '<=',
                'type'    => 'DATETIME'
            ],
            [
                'key'     => '_giveaway_end_date',
                'value'   => $now,
                'compare' => '>=',
                'type'    => 'DATETIME'
            ]
        ]
    ]);

    if (empty($active_posts)) {
        ?>
        <p class="me5rine-lab-form-text"><?php _e('No active giveaways available.', 'giveaways'); ?></p>
        <?php
    } else {
        $excluded_ids = admin_lab_get_participated_giveaway_posts($user_id);
        $remaining_ids = array_diff($active_posts, $excluded_ids);

        if (empty($remaining_ids)) {
            ?>
            <p class="me5rine-lab-form-text"><?php _e('You\'ve already participated in all our current giveaways. Check back soon!', 'giveaways'); ?></p>
            <?php
        } else {
            $posts = get_posts([
                'post_type'      => 'giveaway',
                'post__in'       => $remaining_ids,
                'posts_per_page' => 3,
                'orderby'        => 'rand',
                'post_status'    => 'publish',
                'fields'         => 'ids'
            ]);
            ?>

            <table class="giveaway-profil-promo-table">
                <thead>
                    <tr>
                        <th><?php _e('Giveaway', 'giveaways'); ?></th>
                        <th><?php _e('Time Left', 'giveaways'); ?></th>
                        <th><?php _e('Prizes', 'giveaways'); ?></th>
                        <th><?php _e('Entries', 'giveaways'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post_id): 
                        $title = get_the_title($post_id);
                        $url = get_permalink($post_id);
                        $end_ts = strtotime(get_post_meta($post_id, '_giveaway_end_date', true));
                        $time_left = admin_lab_format_time_remaining($end_ts) ?? __('Finished', 'giveaways');
                        $prizes = get_the_terms($post_id, 'giveaway_rewards');
                        $gift_display = (!empty($prizes) && !is_wp_error($prizes)) ? implode(', ', wp_list_pluck($prizes, 'name')) : '—';
                        $entries = (int) get_post_meta($post_id, '_giveaway_entries_count', true);
                        ?>
                        <tr class="toggle-row is-collapsed">
                            <td class="summary" data-colname="<?php esc_attr_e('Giveaway', 'giveaways'); ?>">
                                <div class="giveaway-summary-row">
                                    <span class="giveaway-title">
                                        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row-btn">
                                    <span class="screen-reader-text"><?php _e('Show more details.', 'giveaways'); ?></span>
                                </button>
                            </td>
                            <td class="details" data-colname="<?php esc_attr_e('Time Left', 'giveaways'); ?>"><?php echo esc_html($time_left); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Prizes', 'giveaways'); ?>"><?php echo esc_html($gift_display); ?></td>
                            <td class="details" data-colname="<?php esc_attr_e('Entries', 'giveaways'); ?>"><?php echo number_format_i18n($entries); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="me5rine-lab-form-field">
                <a href="<?php echo esc_url(site_url('/giveaways/')); ?>" class="me5rine-lab-form-button">
                    <?php _e('See all giveaways', 'giveaways'); ?>
                </a>
            </div>

            <?php
        }
    }
    ?>
        </div>
    </div>
    <?php
}
