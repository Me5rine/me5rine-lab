<?php
// File: modules/events/functions/helpers.php
if (!defined('ABSPATH')) exit;

/**
 * L’article est-il marqué "événement" ?
 */
function admin_lab_events_is_event(int $post_id = 0): bool {
    $post_id = $post_id ?: (int) (get_the_ID() ?: 0);
    if (!$post_id) return false;
    return (bool) get_post_meta($post_id, '_event_enabled', true);
}

/**
 * Renvoie le fuseau selon le mode ('local' => fuseau WP, 'fixed' => UTC).
 * Utilisé principalement pour les conversions techniques.
 */
function admin_lab_events_get_timezone(string $mode): DateTimeZone {
    return ($mode === 'local') ? wp_timezone() : new DateTimeZone('UTC');
}

/**
 * Formate une date ISO UTC pour affichage admin.
 * - Le paramètre $iso est toujours interprété comme UTC.
 * - Si le mode du post est "local", on la convertit dans le fuseau WP.
 */
function admin_lab_events_admin_fmt(int $post_id, string $iso): string {
    $mode = get_post_meta($post_id, '_event_mode', true) ?: 'local';

    try {
        $dt = new DateTime($iso, new DateTimeZone('UTC'));
        if ($mode === 'local') {
            $dt->setTimezone(wp_timezone());
        }
        return $dt->format('Y-m-d H:i');
    } catch (Exception $e) {
        return $iso;
    }
}

/**
 * Convertit une ISO UTC -> valeur pour <input type="datetime-local">.
 * - $iso est interprété comme UTC
 * - Si $mode='local', on convertit dans le fuseau WP avant de formater
 * - Retourne "Y-m-d\TH:i" ou '' si invalide
 *
 * Actuellement utilisée pour le mode "fixed" dans la metabox.
 */
function admin_lab_events_iso_to_input(?string $iso, string $mode): string {
    if (empty($iso)) return '';
    try {
        $dt = new DateTime($iso, new DateTimeZone('UTC'));
        if ($mode === 'local') {
            $dt->setTimezone(wp_timezone());
        }
        return $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Convertit la valeur d’un <input type="datetime-local"> -> ISO 8601 en UTC ('c').
 * - $input n’a pas de fuseau : si mode=local, on l’interprète dans le fuseau WP ;
 *   si mode=fixed, on l’interprète en UTC.
 *
 * Aujourd’hui on ne l’utilise que pour le mode "fixed".
 */
function admin_lab_events_input_to_iso(?string $input, string $mode): ?string {
    if (empty($input)) return null;
    try {
        $tz = admin_lab_events_get_timezone($mode);
        $dt = new DateTime($input, $tz);        // interprétation dans le bon fuseau
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('c');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Récupère les métas d'un événement d’un post sous forme structurée.
 *
 * Pour uniformiser :
 * - Si mode = "local" :
 *   - on lit _event_start_local / _event_end_local / _event_window_end_local
 *   - on génère des ISO UTC "virtuelles" (start_iso/end_iso/window_end) pour usage technique
 * - Si mode = "fixed" :
 *   - on lit directement _event_start / _event_end / _event_window_end (ISO UTC stockées)
 */
function admin_lab_events_get_event_meta(int $post_id): array {
    $mode    = get_post_meta($post_id, '_event_mode', true) ?: 'local';
    $enabled = (bool) get_post_meta($post_id, '_event_enabled', true);

    $start_iso      = '';
    $end_iso        = '';
    $window_start   = '';
    $window_end     = '';

    if ($mode === 'local') {
        // Nouveaux champs "heure flottante"
        $start_local = (string) get_post_meta($post_id, '_event_start_local', true);
        $end_local   = (string) get_post_meta($post_id, '_event_end_local', true);
        $wEnd_local  = (string) get_post_meta($post_id, '_event_window_end_local', true);

        $tz = wp_timezone();

        // Conversion en ISO UTC "technique" (pour les générateurs d'occurrences, comparaisons, etc.)
        if (!empty($start_local)) {
            try {
                $dt = new DateTime($start_local, $tz);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $start_iso = $dt->format('c');
            } catch (Exception $e) {
                $start_iso = '';
            }
        }

        if (!empty($end_local)) {
            try {
                $dt = new DateTime($end_local, $tz);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $end_iso = $dt->format('c');
            } catch (Exception $e) {
                $end_iso = '';
            }
        }

        if (!empty($wEnd_local)) {
            try {
                $dt = new DateTime($wEnd_local, $tz);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $window_end = $dt->format('c');
            } catch (Exception $e) {
                $window_end = '';
            }
        }

        // Pour l’instant, pas de window_start dédié : on utilise le début
        $window_start = $start_iso ?: '';

        // NOTE : on laisse les anciennes metas _event_start/_event_end tranquilles
        // (elles ne sont plus utilisées dans le nouveau système pour "local").
    } else {
        // Mode FIXED : on lit directement les ISO UTC stockées
        $start_iso    = (string) get_post_meta($post_id, '_event_start', true);
        $end_iso      = (string) get_post_meta($post_id, '_event_end', true);
        $window_start = (string) get_post_meta($post_id, '_event_window_start', true);
        $window_end   = (string) get_post_meta($post_id, '_event_window_end', true);
    }

    return [
        'enabled'       => $enabled,
        'mode'          => $mode,
        'start_iso'     => $start_iso,
        'end_iso'       => $end_iso,
        'recurring'     => (bool) get_post_meta($post_id, '_event_recurring', true),
        'freq'          => (string) (get_post_meta($post_id, '_event_rrule_freq', true) ?: 'weekly'),
        'interval'      => (int) (get_post_meta($post_id, '_event_rrule_interval', true) ?: 1),
        'window_start'  => $window_start,
        'window_end'    => $window_end,
    ];
}

/**
 * Génère des occurrences (simples) à partir d’une règle (daily/weekly/monthly).
 * Retourne un tableau d’items: start_iso_utc, end_iso_utc, start_display, end_display.
 *
 * $args = [
 *   'start_iso'        => '2025-10-01T10:00:00Z',      // ISO UTC
 *   'end_iso'          => '2025-10-01T12:00:00Z',      // ISO UTC
 *   'mode'             => 'local'|'fixed',             // influe sur le fuseau d'affichage
 *   'recurring'        => bool,
 *   'freq'             => 'daily'|'weekly'|'monthly',
 *   'interval'         => int>=1,
 *   'window_start_iso' => '...ISO UTC...',
 *   'window_end_iso'   => '...ISO UTC...',
 *   'max'              => 200
 * ]
 *
 * NB : pour un événement "local", les ISO passées ici peuvent avoir été générées
 *      à partir des heures locales + fuseau WP (via admin_lab_events_get_event_meta).
 */
function admin_lab_events_generate_occurrences(array $args): array {
    $defaults = [
        'max'              => 200,
        'interval'         => 1,
        'freq'             => 'weekly',
        'recurring'        => false,
        'mode'             => 'local',
        'start_iso'        => '',
        'end_iso'          => '',
        'window_start_iso' => '',
        'window_end_iso'   => '',
    ];
    $a = array_merge($defaults, $args);

    if (empty($a['start_iso']) || empty($a['end_iso'])) {
        return [];
    }

    // Fuseau pour l'affichage (admin/front)
    $tz = ($a['mode'] === 'local') ? wp_timezone() : new DateTimeZone('UTC');

    try {
        // Tout est en UTC pour le calcul interne
        $start = new DateTime($a['start_iso'], new DateTimeZone('UTC'));
        $end   = new DateTime($a['end_iso'],   new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return [];
    }

    $winStart = !empty($a['window_start_iso'])
        ? new DateTime($a['window_start_iso'], new DateTimeZone('UTC'))
        : clone $start;

    $winEnd = !empty($a['window_end_iso'])
        ? new DateTime($a['window_end_iso'], new DateTimeZone('UTC'))
        : (clone $end)->modify('+2 years');

    $curStart = clone $start;
    $curEnd   = clone $end;

    $out = [];
    $i = 0;

    // Si récurrent, avancer jusqu’à la fenêtre
    if ($a['recurring']) {
        while ($curEnd < $winStart && $i < $a['max']) {
            admin_lab_events_add_interval($curStart, $curEnd, (string) $a['freq'], (int) $a['interval']);
            $i++;
        }
    }

    $i = 0;
    while ($curStart <= $winEnd && $i < $a['max']) {
        if ($curEnd >= $winStart && $curStart <= $winEnd) {
            $locStart = (clone $curStart)->setTimezone($tz);
            $locEnd   = (clone $curEnd)->setTimezone($tz);
            $out[] = [
                'start_iso_utc' => $curStart->format('c'),
                'end_iso_utc'   => $curEnd->format('c'),
                'start_display' => $locStart->format('Y-m-d H:i'),
                'end_display'   => $locEnd->format('Y-m-d H:i'),
            ];
        }
        if (!$a['recurring']) break;
        admin_lab_events_add_interval($curStart, $curEnd, (string) $a['freq'], (int) $a['interval']);
        $i++;
    }

    return $out;
}

/**
 * Ajoute l’intervalle (daily/weekly/monthly) aux deux DateTime (début/fin).
 */
function admin_lab_events_add_interval(DateTime &$s, DateTime &$e, string $freq, int $interval): void {
    $interval = max(1, (int)$interval);
    $spec = match ($freq) {
        'daily'   => "P{$interval}D",
        'weekly'  => "P" . (7 * $interval) . "D",
        'monthly' => "P{$interval}M",
        default   => "P{$interval}W", // fallback hebdo
    };
    $s->add(new DateInterval($spec));
    $e->add(new DateInterval($spec));
}

/**
 * Renvoie l’URL de l’image d’un événement :
 *  - d’abord l’image mise en avant de l’article
 *  - sinon, l’image par défaut associée à son type d’événement
 *    (nouveau système : _event_type_default_image_id / _event_type_default_image_url)
 *
 * @param int    $post_id
 * @param string $size    Taille d’image WP (thumbnail, medium, large, etc.)
 * @return string|null
 */
function admin_lab_events_get_event_image_url(int $post_id = 0, string $size = 'thumbnail'): ?string {
    $post_id = $post_id ?: (int) (get_the_ID() ?: 0);
    if (!$post_id) return null;

    // 1. Image mise en avant
    $thumb_id = get_post_thumbnail_id($post_id);
    if ($thumb_id) {
        $src = wp_get_attachment_image_src($thumb_id, $size);
        if ($src && !empty($src[0])) {
            return $src[0];
        }
    }

    // 2. Image par défaut du type d’événement
    $terms = wp_get_post_terms($post_id, 'event_type', ['fields' => 'ids']);
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            // Nouveau système : ID d'attachement
            $img_id = (int) get_term_meta($term_id, '_event_type_default_image_id', true);
            if ($img_id) {
                $src = wp_get_attachment_image_src($img_id, $size);
                if ($src && !empty($src[0])) {
                    return $src[0];
                }
            }

            // Nouveau système : URL directe
            $img_url = (string) get_term_meta($term_id, '_event_type_default_image_url', true);
            if (!empty($img_url)) {
                return $img_url;
            }

            // Legacy : ancienne meta unique _event_type_default_image (ID ou URL)
            $legacy = get_term_meta($term_id, '_event_type_default_image', true);
            if (!empty($legacy)) {
                if (ctype_digit((string) $legacy)) {
                    $src = wp_get_attachment_image_src((int) $legacy, $size);
                    if ($src && !empty($src[0])) {
                        return $src[0];
                    }
                } else {
                    return (string) $legacy;
                }
            }
        }
    }

    // 3. Rien trouvé
    return null;
}

/**
 * Est-ce que ce site doit utiliser les EVENT TYPES distants (JV Actu) ?
 *
 * On s’appuie sur l’option Poké HUB "poke_hub_events_remote_prefix".
 * - Si l’option est vide → on considère que ce site est le site principal,
 *   donc event_type local normal.
 * - Si l’option = $wpdb->prefix → idem : le "remote" est en fait le site courant.
 * - Si l’option != $wpdb->prefix → on considère que ce site est un consommateur,
 *   donc on désactive la taxo locale et on travaille avec les metas / types distants.
 */
function admin_lab_events_use_remote_types(): bool {
    global $wpdb;

    // Option gérée par Poké HUB, mais lisible par Me5rine LAB
    $prefix = get_option('poke_hub_events_remote_prefix', '');
    $prefix = trim((string) $prefix);

    if ($prefix === '' || $prefix === $wpdb->prefix) {
        // Pas de remote ou remote = local → site principal (JV Actu)
        return false;
    }

    // Remote différent → site consommateur
    return true;
}

/**
 * Récupère les event types (taxo event_type) dans les tables distantes.
 *
 * @return array Liste d'objets ayant ->term_id, ->slug, ->name
 */
function admin_lab_events_get_remote_event_types(): array {
    global $wpdb;

    $prefix = trim((string) get_option('poke_hub_events_remote_prefix', ''));
    if ($prefix === '') {
        return [];
    }

    $terms_table = $prefix . 'terms';
    $tt_table    = $prefix . 'term_taxonomy';

    $sql = "
        SELECT t.term_id, t.slug, t.name
        FROM {$terms_table} AS t
        INNER JOIN {$tt_table} AS tt
            ON tt.term_id = t.term_id
        WHERE tt.taxonomy = 'event_type'
        ORDER BY t.name ASC
    ";

    $rows = $wpdb->get_results($sql);
    return $rows ?: [];
}

/**
 * Récupère un event_type distant + sa couleur depuis termmeta distante.
 *
 * @param int $term_id
 * @return object|null { term_id, slug, name, color }
 */
function admin_lab_events_get_remote_event_type_by_id(int $term_id): ?object {
    global $wpdb;

    $term_id = (int) $term_id;
    if ($term_id <= 0) {
        return null;
    }

    $prefix          = trim((string) get_option('poke_hub_events_remote_prefix', ''));
    if ($prefix === '') {
        return null;
    }

    $terms_table     = $prefix . 'terms';
    $tt_table        = $prefix . 'term_taxonomy';
    $termmeta_table  = $prefix . 'termmeta';

    // Term de base
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT t.term_id, t.slug, t.name
            FROM {$terms_table} t
            INNER JOIN {$tt_table} tt
                ON tt.term_id = t.term_id
            WHERE tt.taxonomy = %s
                AND t.term_id = %d
            LIMIT 1
            ",
            'event_type',
            $term_id
        )
    );

    if (!$row) {
        return null;
    }

    // Couleur éventuelle en termmeta distante
    $color = '';
    if ($termmeta_table) {
        $meta_rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT meta_key, meta_value
                FROM {$termmeta_table}
                WHERE term_id = %d
                ",
                $term_id
            )
        );
        if ($meta_rows) {
            $meta = [];
            foreach ($meta_rows as $m) {
                $meta[$m->meta_key] = $m->meta_value;
            }

            if (!empty($meta['event_type_color'])) {
                $color = trim((string) $meta['event_type_color']);
            } elseif (!empty($meta['_event_type_color'])) {
                $color = trim((string) $meta['_event_type_color']);
            }

            if ($color !== '' && $color[0] !== '#') {
                $color = '#' . $color;
            }
        }
    }

    return (object) [
        'term_id' => (int) $row->term_id,
        'slug'    => (string) $row->slug,
        'name'    => (string) $row->name,
        'color'   => $color,
    ];
}

