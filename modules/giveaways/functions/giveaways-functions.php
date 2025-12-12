<?php
// File: modules/giveaways/functions/giveaways-functions.php

if (!defined('ABSPATH')) exit;

function admin_lab_add_admin_notice($message, $type = 'success') {
    set_transient('admin_lab_admin_notice', [
        'message' => $message,
        'type'    => $type,
    ], 30);
}

add_action('admin_notices', function() {
    $notice = get_transient('admin_lab_admin_notice');
    if ($notice) {
        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($notice['type']),
            esc_html($notice['message'])
        );
        delete_transient('admin_lab_admin_notice');
    }
});

/**
 * Récupère l’ID du post associé à une campagne RafflePress.
 * Optionnellement, exclut un post spécifique de la recherche.
 */
function admin_lab_get_post_id_from_rafflepress($rafflepress_id, $exclude_post_id = null) {
    global $wpdb;
    $table = admin_lab_getTable('rafflepress_index', false);

    if ($exclude_post_id !== null) {
        $query = $wpdb->prepare(
            "SELECT post_id FROM $table WHERE rafflepress_id = %d AND post_id != %d",
            $rafflepress_id,
            $exclude_post_id
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT post_id FROM $table WHERE rafflepress_id = %d",
            $rafflepress_id
        );
    }

    return $wpdb->get_var($query);
}

/**
 * Récupère l’ID de campagne RafflePress associé à un post.
 */
function admin_lab_get_rafflepress_id_from_post($post_id) {
    global $wpdb;
    $table = admin_lab_getTable('rafflepress_index', false);


    return $wpdb->get_var($wpdb->prepare(
        "SELECT rafflepress_id FROM $table WHERE post_id = %d",
        $post_id
    ));
}

/**
 * Enregistre ou met à jour l'association entre un ID de campagne RafflePress
 * et l'ID du post WordPress correspondant dans la table optimisée `rafflepress_index`.
 *
 * Cette fonction permet de remplacer les requêtes lentes sur wp_postmeta pour retrouver
 * rapidement l'article associé à une campagne RafflePress.
 *
 * @param int $rafflepress_id L'ID de la campagne RafflePress.
 * @param int $post_id        L'ID du post WordPress (type giveaway).
 * @return void
 */
function admin_lab_register_rafflepress_index($rafflepress_id, $post_id) {
    global $wpdb;
    $table = admin_lab_getTable('rafflepress_index', false);

    // On vérifie si une entrée existe déjà avec ce post_id (même si avec un autre rafflepress_id)
    $existing_by_post = $wpdb->get_var($wpdb->prepare(
        "SELECT rafflepress_id FROM $table WHERE post_id = %d",
        $post_id
    ));

    // On vérifie si une entrée existe déjà avec ce rafflepress_id
    $existing_by_raffle = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $table WHERE rafflepress_id = %d",
        $rafflepress_id
    ));

    if ($existing_by_post && intval($existing_by_post) !== intval($rafflepress_id)) {
        // Supprimer l'ancien lien (le post_id était déjà utilisé)
        $wpdb->delete($table, ['post_id' => $post_id], ['%d']);
    }

    if ($existing_by_raffle) {
        // Mettre à jour le lien existant
        $wpdb->update(
            $table,
            ['post_id' => $post_id],
            ['rafflepress_id' => $rafflepress_id],
            ['%d'],
            ['%d']
        );
    } else {
        // Créer un nouveau lien
        $wpdb->insert(
            $table,
            ['rafflepress_id' => $rafflepress_id, 'post_id' => $post_id],
            ['%d', '%d']
        );
    }
}

/**
 * Récupère les IDs des articles WordPress (type giveaway)
 * auxquels un utilisateur a déjà participé, via la table rafflepress_index.
 *
 * @param int $user_id
 * @return int[] Liste des post_ids
 */
function admin_lab_get_participated_giveaway_posts($user_id) {
    global $wpdb;

    $user = get_userdata($user_id);
    if (!$user) return [];

    $email = $user->user_email;
    $contestant_table = $wpdb->prefix . 'rafflepress_contestants';
    $index_table      = admin_lab_getTable('rafflepress_index', false);

    // 1. Récupérer les rafflepress_id auxquels l'utilisateur a participé
    $rafflepress_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT giveaway_id FROM $contestant_table WHERE email = %s",
        $email
    ));

    if (empty($rafflepress_ids)) {
        return [];
    }

    // 2. Les convertir en post_ids via rafflepress_index
    $placeholders = implode(',', array_fill(0, count($rafflepress_ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT post_id FROM $index_table WHERE rafflepress_id IN ($placeholders)",
        ...$rafflepress_ids
    );

    $post_ids = $wpdb->get_col($query);

    return array_map('intval', $post_ids);
}

/**
 * Définit l’image mise en avant d’un article de concours à partir de l’image du premier prix.
 *
 * Cette fonction récupère l’URL de l’image du premier prix définie dans les paramètres RafflePress,
 * tente de retrouver l’ID de pièce jointe correspondant (via attachment_url_to_postid),
 * puis l’associe comme image à la une de l’article WordPress du concours.
 *
 * Si l’image est déjà définie comme miniature, aucune action n’est effectuée.
 *
 * @param int   $post_id   ID de l’article WordPress représentant le concours.
 * @param array $settings  Données de configuration de la campagne RafflePress (champ "settings").
 *
 * @return void
 */
function giveaways_set_featured_image_from_prize($post_id, $settings) {
    if (empty($post_id) || empty($settings['prizes'][0]['image'])) {
        return;
    }

    $image_url = esc_url_raw($settings['prizes'][0]['image']);
    $attachment_id = attachment_url_to_postid($image_url);

    if (!$attachment_id) {
        return;
    }

    $current_thumb = get_post_thumbnail_id($post_id);
    if ((int) $current_thumb !== (int) $attachment_id) {
        set_post_thumbnail($post_id, $attachment_id);
    }
}

/**
 * Crée ou récupère un terme de récompense dans la taxonomie `giveaway_rewards`.
 *
 * Remplace certains caractères spéciaux (+, &, €, etc.) par des mots dans le slug.
 *
 * @param string $name Nom de la récompense.
 * @return int|false ID du terme si succès, false sinon.
 */
function admin_lab_register_reward_term($name) {
    $name = sanitize_text_field($name);

    // Recherche manuelle insensible à la casse
    $existing = get_terms([
        'taxonomy'   => 'giveaway_rewards',
        'hide_empty' => false,
    ]);

    foreach ($existing as $term) {
        if (strcasecmp($term->name, $name) === 0) {
            return (int) $term->term_id;
        }
    }

    // Slug avec remplacement personnalisé
    $base_slug = admin_lab_slugify_label($name);
    $slug = wp_unique_term_slug($base_slug, (object)[
        'taxonomy' => 'giveaway_rewards',
        'name'     => $name,
    ]);

    $res = wp_insert_term($name, 'giveaway_rewards', ['slug' => $slug]);

    return is_wp_error($res) ? false : (int) $res['term_id'];
}

