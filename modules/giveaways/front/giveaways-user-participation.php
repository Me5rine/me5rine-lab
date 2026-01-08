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
                
                // Le concours doit être terminé
                if (!$end || $now <= $end) continue;
                
                // Vérifier si le tirage a déjà été effectué (s'il y a des gagnants)
                $has_winners = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $contestants_table WHERE giveaway_id = %d AND winner = 1",
                    $giveaway_id
                ));
                
                // Seulement si le tirage n'a pas encore été effectué
                if (!$has_winners) {
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
        } elseif ($status_filter === 'lost') {
            // Filtre pour les concours perdus : terminés, tirage effectué, utilisateur non gagnant
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

            $lost_ids = [];
            foreach ($campaigns as $giveaway_id) {
                $post_id = admin_lab_get_post_id_from_rafflepress($giveaway_id);
                if (!$post_id) continue;
                $end = get_post_meta($post_id, '_giveaway_end_date', true);
                
                // Le concours doit être terminé
                if (!$end || $now <= $end) continue;
                
                // Vérifier si le tirage a déjà été effectué (s'il y a des gagnants)
                $has_winners = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $contestants_table WHERE giveaway_id = %d AND winner = 1",
                    $giveaway_id
                ));
                
                // Seulement si le tirage a été effectué (l'utilisateur a perdu)
                if ($has_winners) {
                    $lost_ids[] = (int) $giveaway_id;
                }
            }

            if (!empty($lost_ids)) {
                $ids_sql = implode(',', array_map('intval', $lost_ids));
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
        <div class="me5rine-lab-profile-container">
            <h2 class="me5rine-lab-title"><?php _e('My Giveaway Entries', 'giveaways'); ?></h2>
            <div class="me5rine-lab-form-container">
                <form method="get" class="me5rine-lab-filters">
                    <?php
                    // Préserver le paramètre 'tab' de l'URL actuelle (pour Ultimate Member)
                    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
                    if ($current_tab) {
                        echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '">';
                    }
                    ?>
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                    <input type="hidden" name="profiletab" value="user-giveaways">

                    <div class="me5rine-lab-filter-group">
                        <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="status_filter"><?php _e('Filter by status:', 'giveaways'); ?></label>
                        <select id="status_filter" name="status_filter" class="me5rine-lab-form-select me5rine-lab-filter-select no-select2">
                            <option value=""><?php _e('All', 'giveaways'); ?></option>
                            <option value="in_progress" <?php selected($status_filter, 'in_progress'); ?>><?php _e('In progress', 'giveaways'); ?></option>
                            <option value="awaiting" <?php selected($status_filter, 'awaiting'); ?>><?php _e('Awaiting draw', 'giveaways'); ?></option>
                            <option value="won" <?php selected($status_filter, 'won'); ?>><?php _e('Winner', 'giveaways'); ?></option>
                            <option value="lost" <?php selected($status_filter, 'lost'); ?>><?php _e('Lost', 'giveaways'); ?></option>
                        </select>
                    </div>

                    <div class="me5rine-lab-filter-group">
                        <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="per_page"><?php _e('Entries per page:', 'giveaways'); ?></label>
                        <select id="per_page" name="per_page" class="me5rine-lab-form-select me5rine-lab-filter-select no-select2">
                            <?php foreach ([1, 5, 10, 20, 50] as $val): ?>
                                <option value="<?php echo $val; ?>" <?php selected($per_page, $val); ?>><?php echo $val; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="me5rine-lab-filter-group">
                        <button type="submit" class="me5rine-lab-form-button me5rine-lab-form-button-secondary">
                            <?php _e('Apply filters', 'giveaways'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div id="giveaway-my-giveaways-table">
            <?php if (empty($participations)): ?>
                <p class="me5rine-lab-state-message">
                    <?php echo ($total_items === 0)
                        ? __('You haven\'t participated in any giveaways yet.', 'giveaways')
                        : __('No giveaways match your filters.', 'giveaways'); ?>
                </p>
            <?php else: ?>
                <table class="me5rine-lab-table me5rine-lab-table-giveaways-participations striped">
                    <thead>
                        <tr>
                            <th><span class="unsorted-column"><?php _e('Giveaway', 'giveaways'); ?></span></th>
                            <th><span class="unsorted-column"><?php _e('My Entries', 'giveaways'); ?></span></th>
                            <th><span class="unsorted-column"><?php _e('My Status', 'giveaways'); ?></span></th>
                            <th><span class="unsorted-column"><?php _e('Winner(s)', 'giveaways'); ?></span></th>
                            <th><span class="unsorted-column"><?php _e('Prizes', 'giveaways'); ?></span></th>
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
                            $end   = get_post_meta($post_id, '_giveaway_end_date', true);
                            
                            // Récupération des gagnants pour déterminer si le tirage a été effectué
                            $winner_emails = $wpdb->get_col($wpdb->prepare(
                                "SELECT email FROM $contestants_table WHERE giveaway_id = %d AND winner = 1", $giveaway_id
                            ));
                            $has_winners = !empty($winner_emails);
                            
                            // Comparaison correcte des dates (format Y-m-d\TH:i)
                            $is_ended = false;
                            if ($end) {
                                // Convertir les dates en DateTime pour une comparaison fiable
                                $now_dt = new DateTime('now', new DateTimeZone('UTC'));
                                $end_dt = DateTime::createFromFormat('Y-m-d\TH:i', $end, new DateTimeZone('UTC'));
                                
                                if ($end_dt) {
                                    $is_ended = $now_dt >= $end_dt;
                                } else {
                                    // Fallback : essayer avec le format MySQL si le format T ne fonctionne pas
                                    $end_dt = DateTime::createFromFormat('Y-m-d H:i:s', $end, new DateTimeZone('UTC'));
                                    if ($end_dt) {
                                        $is_ended = $now_dt >= $end_dt;
                                    }
                                }
                            }
                            
                            // Détermination du statut
                            // Priorité 1 : Si l'utilisateur a gagné, afficher "Winner" (même si le tirage est terminé)
                            if ($p->winner == 1 || $p->winner === true) {
                                $status_text = __('Winner', 'giveaways');
                                $status_type = 'success';
                            } elseif (!$is_ended) {
                                // Priorité 2 : Le concours est en cours (pas terminé, pas de tirage en attente)
                                // Statut info (bleu) pour indiquer que le concours est actif
                                $status_text = __('In progress', 'giveaways');
                                $status_type = 'info';
                            } elseif ($is_ended && $has_winners) {
                                // Priorité 3 : Le concours est terminé et le tirage a été effectué, l'utilisateur a perdu
                                $status_text = __('Lost', 'giveaways');
                                $status_type = 'error';
                            } else {
                                // Priorité 4 : Le concours est terminé mais le tirage n'a pas encore été effectué
                                $status_text = __('Awaiting draw', 'giveaways');
                                $status_type = 'warning';
                            }

                            $prizes = get_the_terms($post_id, 'giveaway_rewards');
                            $gift_display = (!empty($prizes) && !is_wp_error($prizes)) ? implode(', ', wp_list_pluck($prizes, 'name')) : '—';

                            $entries = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $entries_table WHERE contestant_id = %d AND giveaway_id = %d",
                                $contestant_id,
                                $giveaway_id
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
                                        <div>
                                            <span class="me5rine-lab-table-title">
                                                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" class="me5rine-lab-table-toggle-btn" aria-expanded="false">
                                        <span class="me5rine-lab-sr-only"><?php _e('Show more details', 'giveaways'); ?></span>
                                    </button>
                                </td>
                                <td class="details" data-colname="<?php esc_attr_e('My Entries', 'giveaways'); ?>"><?php echo number_format_i18n($entries); ?></td>
                                <td class="details" data-colname="<?php esc_attr_e('My Status', 'giveaways'); ?>">
                                    <?php echo admin_lab_render_status($status_text, $status_type); ?>
                                </td>
                                <td class="details" data-colname="<?php esc_attr_e('Winner(s)', 'giveaways'); ?>"><?php echo $winner_display; ?></td>
                                <td class="details" data-colname="<?php esc_attr_e('Prizes', 'giveaways'); ?>"><?php echo esc_html($gift_display); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                echo me5rine_lab_render_pagination([
                    'total_items' => $total_items,
                    'paged'       => $paged,
                    'total_pages' => $total_pages,
                    'page_var'    => 'pg',
                    'text_domain' => 'giveaways',
                    'ajax_class'  => 'giveaway-pg',
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}