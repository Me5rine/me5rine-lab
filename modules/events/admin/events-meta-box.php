<?php
// File: modules/events/admin/events-meta-box.php

if (!defined('ABSPATH')) exit;

/**
 * Helper : convertit une valeur "YYYY-MM-DD HH:MM:SS" (stockage local)
 * en valeur pour <input type="datetime-local"> => "YYYY-MM-DDTHH:MM"
 */
if (!function_exists('admin_lab_events_local_db_to_input')) {
    function admin_lab_events_local_db_to_input(?string $value): string {
        if (empty($value)) {
            return '';
        }

        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d\TH:i');
        } catch (Exception $e) {
            return '';
        }
    }
}

/**
 * Ajoute la meta box "Options Événement"
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'admin_lab_event_box',
        __('Event Options', 'me5rine-lab'),
        'admin_lab_render_event_meta_box',
        'post',
        'side',
        'high'
    );

    // Si on utilise les event types distants, on cache la meta box taxo locale
    if (admin_lab_events_use_remote_types()) {
        remove_meta_box('event_typediv', 'post', 'side');
    }
});

/**
 * Rendu de la meta box
 */
function admin_lab_render_event_meta_box(WP_Post $post) {
    wp_nonce_field('admin_lab_events_save', 'admin_lab_events_nonce');

    $enabled      = (bool) get_post_meta($post->ID, '_event_enabled', true);
    $mode         = get_post_meta($post->ID, '_event_mode', true) ?: 'local';
    $event_title  = get_post_meta($post->ID, '_event_title', true);

    $event_type_slug_meta = get_post_meta($post->ID, '_event_type_slug', true);

    // Datas "fixed" (UTC ISO) – utilisées uniquement en mode fixed
    $start_iso    = get_post_meta($post->ID, '_event_start', true);
    $end_iso      = get_post_meta($post->ID, '_event_end', true);
    $wEnd_iso     = get_post_meta($post->ID, '_event_window_end', true);

    // Datas "local" (heure flottante)
    $start_local_db = get_post_meta($post->ID, '_event_start_local', true);
    $end_local_db   = get_post_meta($post->ID, '_event_end_local', true);
    $wEnd_local_db  = get_post_meta($post->ID, '_event_window_end_local', true);

    $recurring    = (bool) get_post_meta($post->ID, '_event_recurring', true);
    $freq         = get_post_meta($post->ID, '_event_rrule_freq', true) ?: 'weekly';
    $interval     = (int) (get_post_meta($post->ID, '_event_rrule_interval', true) ?: 1);

    // Valeurs des inputs datetime-local
    $start_input = '';
    $end_input   = '';
    $wEnd_input  = '';

    if ($mode === 'local') {
        // Mode "heure flottante" : on lit uniquement les metas *_local
        $start_input = admin_lab_events_local_db_to_input($start_local_db);
        $end_input   = admin_lab_events_local_db_to_input($end_local_db);
        $wEnd_input  = admin_lab_events_local_db_to_input($wEnd_local_db);
    } else {
        // Mode "fixed" : on lit l'ISO UTC, converti via le helper existant
        $start_input = admin_lab_events_iso_to_input($start_iso, 'fixed');
        $end_input   = admin_lab_events_iso_to_input($end_iso, 'fixed');
        $wEnd_input  = admin_lab_events_iso_to_input($wEnd_iso, 'fixed');
    }

    if (admin_lab_events_use_remote_types()) {
        // Event types DISTANTS
        $types = admin_lab_events_get_remote_event_types();
        $assigned = [];
        $selected_remote_slug = $event_type_slug_meta ?: '';
    } else {
        // Event types LOCAUX (taxo)
        $types    = get_terms(['taxonomy' => 'event_type', 'hide_empty' => false]);
        $assigned = wp_get_post_terms($post->ID, 'event_type', ['fields' => 'ids']);
        $selected_remote_slug = '';
    }
    ?>
    <p>
        <label>
            <input type="checkbox" name="admin_lab_events[enabled]" value="1" <?php checked($enabled, true); ?>>
            <?php esc_html_e('Activate as event', 'me5rine-lab'); ?>
        </label>
    </p>

    <div class="admin-lab-events-fields <?php echo $enabled ? 'is-active' : 'is-hidden'; ?>">
        <p>
            <label for="admin_lab_event_title">
                <strong><?php esc_html_e('Event title', 'me5rine-lab'); ?></strong>
            </label><br>
            <input
                type="text"
                id="admin_lab_event_title"
                name="admin_lab_events[event_title]"
                class="widefat"
                value="<?php echo esc_attr($event_title); ?>"
            >
        </p>

        <p>
            <label><strong><?php esc_html_e('Event Type', 'me5rine-lab'); ?></strong></label><br>
            <select name="admin_lab_events[event_type]" class="admin-lab-event-type">
                <option value=""><?php esc_html_e('— None —', 'me5rine-lab'); ?></option>
                <?php foreach ($types as $t): ?>
                    <option value="<?php echo esc_attr($t->term_id); ?>"
                        <?php
                        if (admin_lab_events_use_remote_types()) {
                            // Comparaison via le slug stocké en meta
                            selected($selected_remote_slug === $t->slug);
                        } else {
                            selected(in_array($t->term_id, $assigned));
                        }
                        ?>
                    >
                        <?php echo esc_html($t->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!admin_lab_events_use_remote_types()): ?>
                <em class="admin-lab-event-manage-types">
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=event_type&post_type=post')); ?>" target="_blank">
                        <?php esc_html_e('Manage types', 'me5rine-lab'); ?>
                    </a>
                </em>
            <?php else: ?>
                <em class="admin-lab-event-manage-types">
                    <?php esc_html_e('Event types are managed on the remote site.', 'me5rine-lab'); ?>
                </em>
            <?php endif; ?>
        </p>

        <p>
            <label><strong><?php esc_html_e('Time Mode', 'me5rine-lab'); ?></strong></label><br>
            <label>
                <input type="radio" name="admin_lab_events[mode]" value="local" <?php checked($mode, 'local'); ?>>
                <?php esc_html_e('Local (floating time – same clock time everywhere)', 'me5rine-lab'); ?>
            </label><br>
            <label>
                <input type="radio" name="admin_lab_events[mode]" value="fixed" <?php checked($mode, 'fixed'); ?>>
                <?php esc_html_e('Fixed (absolute UTC time)', 'me5rine-lab'); ?>
            </label>
        </p>

        <div class="admin-lab-events-grid">
            <p>
                <label><?php esc_html_e('Start date/time', 'me5rine-lab'); ?></label><br>
                <input type="datetime-local" name="admin_lab_events[start]" value="<?php echo esc_attr($start_input); ?>">
            </p>
            <p>
                <label><?php esc_html_e('End date/time', 'me5rine-lab'); ?></label><br>
                <input type="datetime-local" name="admin_lab_events[end]" value="<?php echo esc_attr($end_input); ?>">
            </p>
        </div>

        <hr>

        <p>
            <label>
                <input type="checkbox" name="admin_lab_events[recurring]" value="1" <?php checked($recurring, true); ?>>
                <?php esc_html_e('Recurring event', 'me5rine-lab'); ?>
            </label>
        </p>

        <div class="admin-lab-events-recur <?php echo $recurring ? 'is-active' : 'is-hidden'; ?>">
            <div class="admin-lab-events-grid">
                <p>
                    <label><?php esc_html_e('Frequency', 'me5rine-lab'); ?></label><br>
                    <select name="admin_lab_events[freq]">
                        <option value="daily"   <?php selected($freq, 'daily'); ?>><?php esc_html_e('Daily', 'me5rine-lab'); ?></option>
                        <option value="weekly"  <?php selected($freq, 'weekly'); ?>><?php esc_html_e('Weekly', 'me5rine-lab'); ?></option>
                        <option value="monthly" <?php selected($freq, 'monthly'); ?>><?php esc_html_e('Monthly', 'me5rine-lab'); ?></option>
                    </select>
                </p>
                <p>
                    <label><?php esc_html_e('Interval', 'me5rine-lab'); ?></label><br>
                    <input type="number" min="1" step="1" name="admin_lab_events[interval]" value="<?php echo esc_attr($interval); ?>" class="admin-lab-event-interval">
                    <span class="description"><?php esc_html_e('Example: every 2 weeks', 'me5rine-lab'); ?></span>
                </p>
            </div>

            <p>
                <label><?php esc_html_e('Recurrence end', 'me5rine-lab'); ?></label><br>
                <input type="datetime-local" name="admin_lab_events[window_end]" value="<?php echo esc_attr($wEnd_input); ?>" class="admin-lab-event-window-end">
                <span class="description"><?php esc_html_e('Leave empty if it never ends', 'me5rine-lab'); ?></span>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Sauvegarde des métadonnées événement
 */
/**
 * Sauvegarde des métadonnées événement
 */
add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['admin_lab_events_nonce']) || !wp_verify_nonce($_POST['admin_lab_events_nonce'], 'admin_lab_events_save')) return;

    $in = $_POST['admin_lab_events'] ?? [];

    $enabled = !empty($in['enabled']);
    update_post_meta($post_id, '_event_enabled', $enabled);

    // Titre d'événement
    if (!empty($in['event_title'])) {
        $event_title = sanitize_text_field($in['event_title']);
        update_post_meta($post_id, '_event_title', $event_title);
    } else {
        delete_post_meta($post_id, '_event_title');
    }

    // Gestion du type d'événement
    if (admin_lab_events_use_remote_types()) {
        // 🔹 Mode remote : on ne touche pas à la taxonomie locale,
        // on stocke les infos en metas (_event_type_slug/_name/_color)

        if (!empty($in['event_type'])) {
            $remote_term_id = (int) $in['event_type'];

            $term = admin_lab_events_get_remote_event_type_by_id($remote_term_id);

            if ($term) {
                update_post_meta($post_id, '_event_type_slug',  $term->slug);
                update_post_meta($post_id, '_event_type_name',  $term->name);
                update_post_meta($post_id, '_event_type_color', $term->color);
            }
        } else {
            delete_post_meta($post_id, '_event_type_slug');
            delete_post_meta($post_id, '_event_type_name');
            delete_post_meta($post_id, '_event_type_color');
        }

        // Et on s’assure que la taxo locale ne pollue pas
        wp_set_object_terms($post_id, [], 'event_type', false);

    } else {
        // 🔸 Mode normal : comportement existant
        if (!empty($in['event_type'])) {
            wp_set_object_terms($post_id, (int) $in['event_type'], 'event_type', false);
        } else {
            wp_set_object_terms($post_id, [], 'event_type', false);
        }
    }

    // Mode
    $mode = ($in['mode'] ?? 'local') === 'fixed' ? 'fixed' : 'local';
    update_post_meta($post_id, '_event_mode', $mode);

    // Récup inputs
    $start_input = $in['start']       ?? ''; // "YYYY-MM-DDTHH:MM"
    $end_input   = $in['end']         ?? '';
    $wEnd_input  = $in['window_end']  ?? '';

    // Timestamps pour validation + tri
    $start_ts = null;
    $end_ts   = null;

    // On initialise ces variables pour s'en servir plus loin
    $start_local = '';
    $end_local   = '';

    if ($mode === 'local') {
        /**
         * MODE LOCAL : heure flottante
         * - on stocke uniquement *_local = "YYYY-MM-DD HH:MM:SS"
         * - pas de stockage UTC officiel
         * - mais on calcule un timestamp pour le tri global (_event_sort_start/_event_sort_end)
         */

        // Nettoyage des anciennes metas UTC si jamais
        delete_post_meta($post_id, '_event_start');
        delete_post_meta($post_id, '_event_end');
        delete_post_meta($post_id, '_event_window_end');

        // START
        if (!empty($start_input)) {
            $start_local = str_replace('T', ' ', $start_input);
            if (strlen($start_local) === 16) {
                $start_local .= ':00';
            }
            update_post_meta($post_id, '_event_start_local', $start_local);

            try {
                $tz = wp_timezone();
                $dt = new DateTime($start_local, $tz);
                $start_ts = $dt->getTimestamp();
            } catch (Exception $e) {
                $start_ts = null;
            }
        } else {
            delete_post_meta($post_id, '_event_start_local');
        }

        // END
        if (!empty($end_input)) {
            $end_local = str_replace('T', ' ', $end_input);
            if (strlen($end_local) === 16) {
                $end_local .= ':00';
            }
            update_post_meta($post_id, '_event_end_local', $end_local);

            try {
                $tz = wp_timezone();
                $dt = new DateTime($end_local, $tz);
                $end_ts = $dt->getTimestamp();
            } catch (Exception $e) {
                $end_ts = null;
            }
        } else {
            delete_post_meta($post_id, '_event_end_local');
        }

        // WINDOW END (uniquement local)
        if (!empty($wEnd_input)) {
            $wEnd_local = str_replace('T', ' ', $wEnd_input);
            if (strlen($wEnd_local) === 16) {
                $wEnd_local .= ':00';
            }
            update_post_meta($post_id, '_event_window_end_local', $wEnd_local);
        } else {
            delete_post_meta($post_id, '_event_window_end_local');
        }

    } else {
        /**
         * MODE FIXED : instant global (UTC)
         * - on stocke uniquement l’ISO UTC
         * - pas de *_local
         * - tri basé sur un timestamp dérivé de l’ISO
         */

        delete_post_meta($post_id, '_event_start_local');
        delete_post_meta($post_id, '_event_end_local');
        delete_post_meta($post_id, '_event_window_end_local');

        $start_iso = admin_lab_events_input_to_iso($start_input, 'fixed');
        $end_iso   = admin_lab_events_input_to_iso($end_input,   'fixed');
        $wEnd_iso  = admin_lab_events_input_to_iso($wEnd_input,  'fixed');

        if ($start_iso) {
            update_post_meta($post_id, '_event_start', $start_iso);
            $start_ts = strtotime($start_iso);
        } else {
            delete_post_meta($post_id, '_event_start');
        }

        if ($end_iso) {
            update_post_meta($post_id, '_event_end', $end_iso);
            $end_ts = strtotime($end_iso);
        } else {
            delete_post_meta($post_id, '_event_end');
        }

        if ($wEnd_iso) {
            update_post_meta($post_id, '_event_window_end', $wEnd_iso);
        } else {
            delete_post_meta($post_id, '_event_window_end');
        }
    }

    // Récurrence
    $rec = !empty($in['recurring']);
    update_post_meta($post_id, '_event_recurring', $rec);

    $freq = in_array(($in['freq'] ?? 'weekly'), ['daily', 'weekly', 'monthly'], true) ? $in['freq'] : 'weekly';
    $interval = max(1, (int)($in['interval'] ?? 1));
    update_post_meta($post_id, '_event_rrule_freq', $freq);
    update_post_meta($post_id, '_event_rrule_interval', $interval);

    // Validation : si la date de fin < début → on corrige
    if ($enabled && $start_ts && $end_ts && $end_ts <= $start_ts) {
        if ($mode === 'local') {
            // copie la valeur de début dans la fin (version locale)
            if (!empty($start_local)) {
                update_post_meta($post_id, '_event_end_local', $start_local);
                $end_ts = $start_ts;
            }
        } else {
            // Mode fixed : on copie l'ISO de début dans la fin
            $start_iso = get_post_meta($post_id, '_event_start', true);
            if (!empty($start_iso)) {
                update_post_meta($post_id, '_event_end', $start_iso);
                $end_ts = strtotime($start_iso);
            }
        }
    }

    /**
     * Tri unifié : toujours remplir _event_sort_start / _event_sort_end
     * avec les timestamps calculés ci-dessus (local ou fixed).
     */
    if ($start_ts) {
        update_post_meta($post_id, '_event_sort_start', $start_ts);
    } else {
        delete_post_meta($post_id, '_event_sort_start');
    }

    if ($end_ts) {
        update_post_meta($post_id, '_event_sort_end', $end_ts);
    } else {
        delete_post_meta($post_id, '_event_sort_end');
    }
});
