<?php
// File: modules/comparator/admin/tabs/comparator-settings-tab-general.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rend l’onglet "General".
 *
 * @param array $settings
 */
function admin_lab_comparator_render_tab_general(array $settings) {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields('admin_lab_comparator_settings_group');
        ?>

        <h2><?php esc_html_e('General', 'me5rine-lab'); ?></h2>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="admin_lab_comparator_mode"><?php esc_html_e('Mode', 'me5rine-lab'); ?></label>
                </th>
                <td>
                    <select name="admin_lab_comparator_settings[mode]" id="admin_lab_comparator_mode">
                        <option value="auto" <?php selected($settings['mode'], 'auto'); ?>>
                            <?php esc_html_e('Automatic (via wordpress_category_id in API)', 'me5rine-lab'); ?>
                        </option>
                        <option value="manual" <?php selected($settings['mode'], 'manual'); ?>>
                            <?php esc_html_e('Manual (category → game mapping)', 'me5rine-lab'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="admin_lab_comparator_frontend_base">
                        <?php esc_html_e('Comparator base URL', 'me5rine-lab'); ?>
                    </label>
                </th>
                <td>
                    <input type="text"
                        name="admin_lab_comparator_settings[frontend_base]"
                        id="admin_lab_comparator_frontend_base"
                        class="regular-text"
                        value="<?php echo esc_attr($settings['frontend_base']); ?>">
                    <p class="description">
                        <?php esc_html_e('Example: https://hub-segment-comparator.vercel.app', 'me5rine-lab'); ?>
                        <br>
                        <?php esc_html_e('The comparator link will be generated like: {base}/game/{gameId}', 'me5rine-lab'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p class="description" style="margin: 0;">
                        <?php esc_html_e('ClicksNGames API (base URL and token): configure only in Me5rine LAB → Settings → API Keys.', 'me5rine-lab'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}
