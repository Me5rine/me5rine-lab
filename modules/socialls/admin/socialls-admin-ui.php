<?php
// File: modules/socialls/admin/socialls-admin-ui.php

if (!defined('ABSPATH')) exit;

function admin_lab_socialls_labels_page() {
    $socials_data = admin_lab_get_global_option('admin_lab_socials_list');
    $socials = $socials_data ? unserialize($socials_data) : [];

    if ($socials) {
        foreach ($socials as $key => $data) {
        }
    } else {
        echo '<p>No social networks found.</p>';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('admin_lab_save_socials');

        $new = $_POST['socials'] ?? [];
        $cleaned = [];

        foreach ($new as $key => $data) {
            if (empty($data['key']) || !preg_match('/^[a-z0-9_\-]+$/', $data['key'])) continue;

            if (!empty($data['__delete'])) continue;

            $key_clean = sanitize_key($data['key']);
            if (empty($key_clean)) continue;

            $defaults = admin_lab_get_um_social_defaults($key_clean);
            $cleaned[$key_clean] = [
                'meta_key' => $key_clean,
                'icon'     => $defaults['icon'] ?? '',
                'fa'       => sanitize_text_field($data['fa'] ?? ''),
                'label'    => sanitize_text_field($data['label'] ?? ucfirst($key_clean)),
                'enabled'  => !empty($data['enabled']) ? true : false,
                'order'    => (int)($data['order'] ?? 0),
                'type'     => in_array($data['type'] ?? 'social', ['social', 'support']) ? $data['type'] : 'social',
                'color'    => sanitize_hex_color($data['color'] ?? '#000000'),
            ];
        }

        admin_lab_save_global_option('admin_lab_socials_list', serialize($cleaned));

        echo '<div class="updated"><p>' . esc_html__('Socials updated globally.', 'me5rine-lab') . '</p></div>';

        $socials = $cleaned;
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Social media management', 'me5rine-lab') ?></h1>
        <a href="#" id="add-social-row" class="page-title-action"><?php esc_html_e('Add Network', 'me5rine-lab'); ?></a>
        <hr class="wp-header-end">

        <template id="social-row-template">
            <tr>
                <td class="handle-column"><input type="hidden" class="social-order" name="" value="0"><span class="dashicons dashicons-move handle"></span></td>
                <td><input type="text" name="" class="key-field" placeholder="key"></td>
                <td><input type="text" name="" class="regular-text" placeholder="Label"></td>
                <td><input type="color" name="" value="#000000"></td>
                <td><input type="text" name="" placeholder="fa class (ex: fa-twitter)"></td>
                <td></td>
                <td><input type="checkbox" name="" value="1"></td>
                <td>
                    <select name="">
                        <option value="social">Follow</option>
                        <option value="support">Support</option>
                    </select>
                </td>
                <td td class="column-actions"><button type="button" class="button button-secondary delete-social-button"><?php _e('Delete', 'me5rine-lab'); ?></button></td>
            </tr>
        </template>
        <form method="post">
            <?php wp_nonce_field('admin_lab_save_socials'); ?>
            <div id="socials-new">
                <table class="widefat admin-lab-socials-table admin-lab-socials-new">
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php _e('Key', 'me5rine-lab'); ?></th>
                            <th><?php _e('Label', 'me5rine-lab'); ?></th>
                            <th><?php _e('Color', 'me5rine-lab'); ?></th>
                            <th><?php _e('FA class', 'me5rine-lab'); ?></th>
                            <th><?php _e('Preview', 'me5rine-lab'); ?></th>
                            <th><?php _e('Enabled', 'me5rine-lab'); ?></th>
                            <th><?php _e('Type', 'me5rine-lab'); ?></th>
                            <th><?php _e('Delete', 'me5rine-lab'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="socials-new-wrapper"></tbody>
                </table>
                <p id="socials-new-save" class="me5rine-lab-hidden">
                    <button type="submit" class="button button-primary">
                        <?php _e('Save new socials', 'me5rine-lab'); ?>
                    </button>
                </p>
            </div>
            <?php
            $socials_follow = array_filter($socials, fn($s) => ($s['type'] ?? 'social') === 'social');
            $socials_support = array_filter($socials, fn($s) => ($s['type'] ?? '') === 'support');
            ?>
            <h2><?php _e('Follow Networks', 'me5rine-lab'); ?></h2>
            <table class="widefat admin-lab-socials-table">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php _e('Key', 'me5rine-lab'); ?></th>
                        <th><?php _e('Label', 'me5rine-lab'); ?></th>
                        <th><?php _e('Color', 'me5rine-lab'); ?></th>
                        <th><?php _e('FA class', 'me5rine-lab'); ?></th>
                        <th><?php _e('Preview', 'me5rine-lab'); ?></th>
                        <th><?php _e('Enabled', 'me5rine-lab'); ?></th>
                        <th><?php _e('Type', 'me5rine-lab'); ?></th>
                        <th><?php _e('Delete', 'me5rine-lab'); ?></th>
                    </tr>
                </thead>
                <tbody class="socials-sortable">
                    <?php foreach ($socials as $key => $data) :
                        if (($data['type'] ?? 'social') !== 'social') continue;
                        $defaults = admin_lab_get_um_social_defaults($key); ?>
                        <tr>
                            <td class="handle-column" data-colname="">
                                <input type="hidden" class="social-order" name="<?php echo esc_html($key); ?>">
                                <span class="dashicons dashicons-move handle ui-sortable-handle"></span>
                                <div class="social-header">
                                    <img src="<?php echo esc_url(ME5RINE_LAB_URL . 'assets/icons/' . $defaults['icon']); ?>" width="20" height="20" alt="">
                                    <span class="social-key"><?php echo esc_html($key); ?></span>
                                    <button type="button" class="toggle-row" aria-expanded="false">
                                        <span class="screen-reader-text"><?php _e('Show more details', 'me5rine-lab'); ?></span>
                                    </button>
                                </div>
                            </td>
                            <td data-colname="<?php esc_attr_e('Key', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($key); ?>" readonly>
                            </td>
                            <td data-colname="<?php esc_attr_e('Label', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($data['label'] ?? ''); ?>" placeholder="Label">
                            </td>
                            <td data-colname="<?php esc_attr_e('Color', 'me5rine-lab'); ?>">
                                <input type="color" name="socials[<?php echo esc_attr($key); ?>][color]" value="<?php echo esc_attr($data['color'] ?? '#000000'); ?>">
                            </td>
                            <td data-colname="<?php esc_attr_e('FA class', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][fa]" value="<?php echo esc_attr($data['fa'] ?? ''); ?>" placeholder="fa-twitter">
                            </td>
                            <td data-colname="<?php esc_attr_e('Preview', 'me5rine-lab'); ?>">
                                <?php if (!empty($defaults['icon'])) : ?>
                                    <img src="<?php echo esc_url(ME5RINE_LAB_URL . 'assets/icons/' . $defaults['icon']); ?>" width="20" height="20" alt="">
                                <?php endif; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Enabled', 'me5rine-lab'); ?>">
                                <input type="checkbox" name="socials[<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked($data['enabled'] ?? false); ?>>
                            </td>
                            <td data-colname="<?php esc_attr_e('Type', 'me5rine-lab'); ?>">
                                <select name="socials[<?php echo esc_attr($key); ?>][type]">
                                    <option value="social" <?php selected($data['type'] ?? 'social', 'social'); ?>><?php _e('Follow', 'me5rine-lab'); ?></option>
                                    <option value="support" <?php selected($data['type'] ?? '', 'support'); ?>><?php _e('Support', 'me5rine-lab'); ?></option>
                                </select>
                            </td>
                            <td class="column-actions" data-colname="<?php esc_attr_e('Delete', 'me5rine-lab'); ?>">
                                <button type="button" class="button button-secondary delete-social-button"><?php _e('Delete', 'me5rine-lab'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="submit" class="button button-primary"><?php _e('Save Changes', 'me5rine-lab'); ?></button>
            </p>
            <h2><?php _e('Support Networks', 'me5rine-lab'); ?></h2>
            <table class="widefat admin-lab-socials-table">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php _e('Key', 'me5rine-lab'); ?></th>
                        <th><?php _e('Label', 'me5rine-lab'); ?></th>
                        <th><?php _e('Color', 'me5rine-lab'); ?></th>
                        <th><?php _e('FA class', 'me5rine-lab'); ?></th>
                        <th><?php _e('Preview', 'me5rine-lab'); ?></th>
                        <th><?php _e('Enabled', 'me5rine-lab'); ?></th>
                        <th><?php _e('Type', 'me5rine-lab'); ?></th>
                        <th><?php _e('Delete', 'me5rine-lab'); ?></th>
                    </tr>
                </thead>
                <tbody class="socials-sortable">
                    <?php foreach ($socials as $key => $data) :
                        if (($data['type'] ?? '') !== 'support') continue;
                        $defaults = admin_lab_get_um_social_defaults($key); ?>
                        <tr>
                            <td class="handle-column" data-colname="">
                                <input type="hidden" class="social-order" name="<?php echo esc_html($key); ?>">
                                <span class="dashicons dashicons-move handle ui-sortable-handle"></span>
                                <div class="social-header">
                                    <img src="<?php echo esc_url(ME5RINE_LAB_URL . 'assets/icons/' . $defaults['icon']); ?>" width="20" height="20" alt="">
                                    <span class="social-key"><?php echo esc_html($key); ?></span>
                                    <button type="button" class="toggle-row" aria-expanded="false">
                                        <span class="screen-reader-text"><?php _e('Show more details', 'me5rine-lab'); ?></span>
                                    </button>
                                </div>
                            </td>
                            <td data-colname="<?php esc_attr_e('Key', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($key); ?>" readonly>
                            </td>
                            <td data-colname="<?php esc_attr_e('Label', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($data['label'] ?? ''); ?>" placeholder="Label">
                            </td>
                            <td data-colname="<?php esc_attr_e('Color', 'me5rine-lab'); ?>">
                                <input type="color" name="socials[<?php echo esc_attr($key); ?>][color]" value="<?php echo esc_attr($data['color'] ?? '#000000'); ?>">
                            </td>
                            <td data-colname="<?php esc_attr_e('FA class', 'me5rine-lab'); ?>">
                                <input type="text" name="socials[<?php echo esc_attr($key); ?>][fa]" value="<?php echo esc_attr($data['fa'] ?? ''); ?>" placeholder="fa-twitter">
                            </td>
                            <td data-colname="<?php esc_attr_e('Preview', 'me5rine-lab'); ?>">
                                <?php if (!empty($defaults['icon'])) : ?>
                                    <img src="<?php echo esc_url(ME5RINE_LAB_URL . 'assets/icons/' . $defaults['icon']); ?>" width="20" height="20" alt="">
                                <?php endif; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Enabled', 'me5rine-lab'); ?>">
                                <input type="checkbox" name="socials[<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked($data['enabled'] ?? false); ?>>
                            </td>
                            <td data-colname="<?php esc_attr_e('Type', 'me5rine-lab'); ?>">
                                <select name="socials[<?php echo esc_attr($key); ?>][type]">
                                    <option value="social" <?php selected($data['type'] ?? 'social', 'social'); ?>><?php _e('Follow', 'me5rine-lab'); ?></option>
                                    <option value="support" <?php selected($data['type'] ?? '', 'support'); ?>><?php _e('Support', 'me5rine-lab'); ?></option>
                                </select>
                            </td>
                            <td class="column-actions" data-colname="<?php esc_attr_e('Delete', 'me5rine-lab'); ?>">
                                <button type="button" class="button button-secondary delete-social-button"><?php _e('Delete', 'me5rine-lab'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="submit" class="button button-primary"><?php _e('Save Changes', 'me5rine-lab'); ?></button>
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            // Drag & drop
            $('.socials-sortable').each(function () {
                $(this).sortable({
                    items: 'tr',
                    handle: '.handle',
                    cursor: 'move',
                    helper: function(e, ui) {
                        // Fixer la largeur de chaque cellule du clone à celle de la ligne originale
                        ui.children().each(function(index) {
                            $(this).width($(this).outerWidth());
                        });
                        return ui;
                    },
                    update: function () {
                        $(this).find('tr').each(function (index) {
                            $(this).find('input.social-order').val(index);
                        });
                    }
                });
            });

            // Suppression logique
            $(document).on('click', '.delete-social-button', function() {
                const $row = $(this).closest('tr');
                $row.addClass('social-hidden');
                $row.append('<input type="hidden" name="' + $row.find('input[name*="[key]"]').attr('name').replace('[key]', '[__delete]') + '" value="1">');
            });

            // Ajout dynamique d'une ligne
            let counter = 0;
            $('#add-social-row').on('click', function () {
                const $tbody = $('#socials-new-wrapper');
                $('#socials-new').show();
                const template = $('#social-row-template').html();
                const $row = $(template);
                $row.addClass('row-expanded'); 

                const key = 'new_' + counter++;
                const namePrefix = 'socials[' + key + ']';

                $row.find('input, select').each(function () {
                    const $input = $(this);
                    const placeholder = $input.attr('placeholder') || '';
                    const type = $input.attr('type') || '';

                    if ($input.hasClass('social-order')) {
                        $input.attr('name', namePrefix + '[order]');
                    } else if ($input.hasClass('key-field')) {
                        $input.attr('name', namePrefix + '[key]');
                    } else if (type === 'checkbox') {
                        $input.attr('name', namePrefix + '[enabled]');
                    } else if (placeholder === 'Color') {
                        $input.attr('name', namePrefix + '[label]');
                    } else if ($input.is('select')) {
                        $input.attr('name', namePrefix + '[type]');
                    }
                });

                $tbody.append($row);
                $('#socials-new-save').show();
            });

            // Toggle détail
            $(document).on('click', '.toggle-row', function () {
                const $row = $(this).closest('tr');
                const isExpanded = $row.hasClass('row-expanded');
                $row.toggleClass('row-expanded');
                $(this).attr('aria-expanded', !isExpanded);
            });
        });
        </script>
    </div>
    <?php
}
