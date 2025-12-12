<?php
// File: modules/events/includes/taxonomy.php

if (!defined('ABSPATH')) exit;

/**
 * Register taxonomy "event_type"
 */
add_action('init', function () {

    // Si ce site consomme les types distants → on NE registre PAS la taxonomie.
    if (function_exists('admin_lab_events_use_remote_types') && admin_lab_events_use_remote_types()) {
        return;
    }

    $labels = [
        'name'                       => __('Event Types', 'me5rine-lab'),
        'singular_name'              => __('Event Type', 'me5rine-lab'),
        'search_items'               => __('Search Event Types', 'me5rine-lab'),
        'all_items'                  => __('All Event Types', 'me5rine-lab'),
        'edit_item'                  => __('Edit Event Type', 'me5rine-lab'),
        'view_item'                  => __('View Event Type', 'me5rine-lab'),
        'update_item'                => __('Update Event Type', 'me5rine-lab'),
        'add_new_item'               => __('Add New Event Type', 'me5rine-lab'),
        'new_item_name'              => __('New Event Type Name', 'me5rine-lab'),
        'not_found'                  => __('No event types found', 'me5rine-lab'),
        'back_to_items'              => __('← Back to Event Types', 'me5rine-lab'),
        'menu_name'                  => __('Event Types', 'me5rine-lab'),
    ];

    register_taxonomy('event_type', ['post'], [
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'event-type'],
    ]);
});

/**
 * Template Underscore pour l’onglet "Insert from URL" de la modale Media
 * (comme pour marketing)
 */
function admin_lab_event_type_media_url_template() {
    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'event_type') {
        return;
    }
    ?>
    <script type="text/html" id="tmpl-events-tax-url-template">
        <div style="padding:20px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">
                <?php echo esc_html__('Image URL:', 'me5rine-lab'); ?>
            </label>
            <input type="url" id="events_tax_url_input" class="widefat"
                   placeholder="https://example.com/image.jpg" />

            <p class="description" style="margin-top:8px;">
                <?php echo esc_html__('Enter a direct image URL to use instead of the media library.', 'me5rine-lab'); ?>
            </p>

            <div style="margin:15px 0; text-align:center;">
                <img id="events_tax_url_preview"
                     src=""
                     style="display:none;max-height:200px;width:auto;margin:10px 0;border:1px solid #ddd;padding:5px;" />
            </div>

            <p>
                <button type="button" class="button button-primary events-tax-url-apply">
                    <?php echo esc_html__('Use this URL', 'me5rine-lab'); ?>
                </button>
            </p>
        </div>
    </script>
    <?php
}
add_action('admin_footer-edit-tags.php', 'admin_lab_event_type_media_url_template');
add_action('admin_footer-term.php', 'admin_lab_event_type_media_url_template');

/**
 * Champ "Image par défaut" + "Couleur" sur l’ajout d’un type d’événement
 */
add_action('event_type_add_form_fields', function ($taxonomy) {
    // Valeurs par défaut (ajout → vide)
    $img_id   = 0;
    $img_url  = '';
    $preview  = '';

    ?>
    <div class="form-field term-default-image-wrap">
        <label for="event_type_default_image_preview">
            <?php esc_html_e('Default event image', 'me5rine-lab'); ?>
        </label>

        <div class="event-type-image-field">
            <div class="event-type-image-preview-wrapper" style="margin-bottom:8px;">
                <img src="<?php echo esc_url($preview); ?>"
                     class="event-type-image-preview"
                     style="max-width:150px;height:auto;<?php echo $preview ? '' : 'display:none;'; ?>"
                     id="event_type_default_image_preview"
                     alt="">
            </div>

            <input type="hidden"
                   name="event_type_default_image_id"
                   class="event-type-image-id"
                   value="<?php echo esc_attr($img_id); ?>">

            <input type="hidden"
                   name="event_type_default_image_url"
                   class="event-type-image-url"
                   value="<?php echo esc_attr($img_url); ?>">

            <button type="button"
                    class="button event-type-image-select">
                <?php esc_html_e('Choose Image', 'me5rine-lab'); ?>
            </button>

            <button type="button"
                    class="button event-type-image-remove"
                    disabled>
                <?php esc_html_e('Remove', 'me5rine-lab'); ?>
            </button>

            <p class="description">
                <?php esc_html_e('Choose a default image for events of this type from the media library or insert a direct URL.', 'me5rine-lab'); ?>
            </p>
        </div>
    </div>

    <?php
    // Champ couleur (ajout)
    $color = '';
    ?>
    <div class="form-field term-event-color-wrap">
        <label for="event_type_color">
            <?php esc_html_e('Event color', 'me5rine-lab'); ?>
        </label>
        <input type="text"
               name="event_type_color"
               id="event_type_color"
               class="event-type-color-field"
               value="<?php echo esc_attr($color); ?>"
        >
        <p class="description">
            <?php esc_html_e('Choose a color used to represent this event type (calendar, badges, etc.).', 'me5rine-lab'); ?>
        </p>
    </div>
    <?php
});

/**
 * Champ "Image par défaut" + "Couleur" sur l’édition d’un type d’événement
 */
add_action('event_type_edit_form_fields', function ($term) {
    // Lecture des metas image
    $img_id  = (int) get_term_meta($term->term_id, '_event_type_default_image_id', true);
    $img_url = (string) get_term_meta($term->term_id, '_event_type_default_image_url', true);

    $preview = '';
    if ($img_id) {
        $preview = wp_get_attachment_image_url($img_id, 'medium');
    } elseif ($img_url) {
        $preview = $img_url;
    }

    // Couleur
    $color = (string) get_term_meta($term->term_id, '_event_type_color', true);
    ?>
    <tr class="form-field term-default-image-wrap">
        <th scope="row">
            <label for="event_type_default_image_preview">
                <?php esc_html_e('Default event image', 'me5rine-lab'); ?>
            </label>
        </th>
        <td>
            <div class="event-type-image-field">
                <div class="event-type-image-preview-wrapper" style="margin-bottom:8px;">
                    <img src="<?php echo esc_url($preview); ?>"
                         class="event-type-image-preview"
                         style="max-width:150px;height:auto;<?php echo $preview ? '' : 'display:none;'; ?>"
                         id="event_type_default_image_preview"
                         alt="">
                </div>

                <input type="hidden"
                       name="event_type_default_image_id"
                       class="event-type-image-id"
                       value="<?php echo esc_attr($img_id); ?>">

                <input type="hidden"
                       name="event_type_default_image_url"
                       class="event-type-image-url"
                       value="<?php echo esc_attr($img_url); ?>">

                <button type="button"
                        class="button event-type-image-select">
                    <?php esc_html_e('Choose Image', 'me5rine-lab'); ?>
                </button>

                <button type="button"
                        class="button event-type-image-remove"
                        <?php disabled(!$preview); ?>>
                    <?php esc_html_e('Remove', 'me5rine-lab'); ?>
                </button>

                <p class="description">
                    <?php esc_html_e('Choose a default image for events of this type from the media library or insert a direct URL.', 'me5rine-lab'); ?>
                </p>
            </div>
        </td>
    </tr>

    <tr class="form-field term-event-color-wrap">
        <th scope="row">
            <label for="event_type_color">
                <?php esc_html_e('Event color', 'me5rine-lab'); ?>
            </label>
        </th>
        <td>
            <input type="text"
                   name="event_type_color"
                   id="event_type_color"
                   class="event-type-color-field"
                   value="<?php echo esc_attr($color); ?>"
            >
            <p class="description">
                <?php esc_html_e('Choose a color used to represent this event type (calendar, badges, etc.).', 'me5rine-lab'); ?>
            </p>
        </td>
    </tr>
    <?php
});

/**
 * Sauvegarde des metas
 * - _event_type_default_image_id (int)
 * - _event_type_default_image_url (string)
 * - _event_type_color (string hex)
 */
function admin_lab_save_event_type_meta(int $term_id): void {
    // ID (médiathèque)
    $img_id = isset($_POST['event_type_default_image_id'])
        ? (int) $_POST['event_type_default_image_id']
        : 0;

    // URL (tab URL dans la modale)
    $img_url = isset($_POST['event_type_default_image_url'])
        ? esc_url_raw($_POST['event_type_default_image_url'])
        : '';

    if ($img_id) {
        update_term_meta($term_id, '_event_type_default_image_id', $img_id);
    } else {
        delete_term_meta($term_id, '_event_type_default_image_id');
    }

    if ($img_url !== '') {
        update_term_meta($term_id, '_event_type_default_image_url', $img_url);
    } else {
        delete_term_meta($term_id, '_event_type_default_image_url');
    }

    // Couleur
    if (isset($_POST['event_type_color'])) {
        $color = $_POST['event_type_color'];

        if (function_exists('sanitize_hex_color')) {
            $color = sanitize_hex_color($color);
        } else {
            $color = sanitize_text_field($color);
        }

        if (!empty($color)) {
            update_term_meta($term_id, '_event_type_color', $color);
        } else {
            delete_term_meta($term_id, '_event_type_color');
        }
    }
}
add_action('created_event_type', 'admin_lab_save_event_type_meta');
add_action('edited_event_type',  'admin_lab_save_event_type_meta');
