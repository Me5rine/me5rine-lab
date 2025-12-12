<?php
// File: modules/comparator/admin/tabs/comparator-settings-tab-categories.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rend l’onglet "Categories mapping".
 *
 * @param array $settings
 */
function admin_lab_comparator_render_tab_categories(array $settings) {
    $all_categories = get_categories([
        'hide_empty' => false,
    ]);
    ?>
    <?php if ($settings['mode'] !== 'manual') : ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Category → game mapping is only used when mode is set to "Manual".', 'me5rine-lab'); ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('admin_lab_comparator_settings_group');
        ?>

        <h2><?php esc_html_e('Category → game mapping', 'me5rine-lab'); ?></h2>
        <p class="description">
            <?php esc_html_e('Add mappings only for categories that need a specific game ID. Leave Game ID empty or 0 to remove a mapping.', 'me5rine-lab'); ?>
        </p>

        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php esc_html_e('Category', 'me5rine-lab'); ?></th>
                <th><?php esc_html_e('Game ID', 'me5rine-lab'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            // Mappings existants
            if (!empty($settings['category_map'])) :
                foreach ($settings['category_map'] as $cat_id => $game_id) :
                    $cat_id  = (int) $cat_id;
                    $game_id = (int) $game_id;
                    $term    = get_term($cat_id, 'category');
                    ?>
                    <tr>
                        <td>
                            <?php
                            if ($term && !is_wp_error($term)) {
                                echo esc_html($term->name);
                                echo '<br><small>' . esc_html($term->slug . ' (#' . $cat_id . ')') . '</small>';
                            } else {
                                echo esc_html(sprintf(__('(Deleted category #%d)', 'me5rine-lab'), $cat_id));
                            }
                            ?>
                        </td>
                        <td>
                            <input type="number"
                                   name="admin_lab_comparator_settings[category_map][<?php echo esc_attr($cat_id); ?>]"
                                   value="<?php echo esc_attr($game_id); ?>"
                                   min="0"
                                   step="1">
                            <p class="description">
                                <?php esc_html_e('Set to 0 or leave empty to remove this mapping.', 'me5rine-lab'); ?>
                            </p>
                        </td>
                    </tr>
                <?php
                endforeach;
            else :
                ?>
                <tr>
                    <td colspan="2">
                        <?php esc_html_e('No mappings defined yet.', 'me5rine-lab'); ?>
                    </td>
                </tr>
            <?php endif; ?>

            <!-- Ligne pour ajouter un nouveau mapping -->
            <tr>
                <td>
                    <strong><?php esc_html_e('Add a new mapping', 'me5rine-lab'); ?></strong><br>
                    <select name="admin_lab_comparator_settings[new_category]">
                        <option value=""><?php esc_html_e('Select a category…', 'me5rine-lab'); ?></option>
                        <?php foreach ($all_categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>">
                                <?php echo esc_html($cat->name . ' (#' . $cat->term_id . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <label>
                        <?php esc_html_e('Game ID', 'me5rine-lab'); ?><br>
                        <input type="number"
                               name="admin_lab_comparator_settings[new_game_id]"
                               value=""
                               min="0"
                               step="1">
                    </label>
                    <p class="description">
                        <?php esc_html_e('If a mapping already exists for this category, it will be replaced.', 'me5rine-lab'); ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}
