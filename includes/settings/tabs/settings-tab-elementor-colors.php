<?php
// File: includes/settings/tabs/settings-tab-elementor-colors.php
if (!defined('ABSPATH')) exit;

// Save Elementor kit ID if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_lab_elementor_kit_id']) && check_admin_referer('admin_lab_elementor_colors_action')) {
    $new_id = absint($_POST['admin_lab_elementor_kit_id']);
    update_option('admin_lab_elementor_kit_id', $new_id);
    echo '<div class="updated"><p>' . esc_html__('Elementor kit ID updated successfully.', 'me5rine-lab') . '</p></div>';
}

// Load current kit ID
$kit_id = (int) get_option('admin_lab_elementor_kit_id');
$css_file_url  = $kit_id ? content_url("uploads/elementor/css/post-{$kit_id}.css") : '';
$css_file_path = $kit_id ? wp_normalize_path(WP_CONTENT_DIR . "/uploads/elementor/css/post-{$kit_id}.css") : '';
$css_exists = $kit_id && file_exists($css_file_path);

// Get colors from helper
$colors_assoc = function_exists('admin_lab_get_elementor_kit_colors') ? admin_lab_get_elementor_kit_colors() : [];
?>

<h2><?php esc_html_e('Elementor Global Colors', 'me5rine-lab'); ?></h2>

<form method="post">
    <?php wp_nonce_field('admin_lab_elementor_colors_action'); ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="admin_lab_elementor_kit_id"><?php _e('Elementor Kit ID', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="number" name="admin_lab_elementor_kit_id" id="admin_lab_elementor_kit_id" value="<?php echo esc_attr($kit_id); ?>" class="small-text" />
                <p class="description"><?php _e('The post ID of the Elementor kit containing global styles (e.g., 174).', 'me5rine-lab'); ?></p>

                <?php if ($kit_id): ?>
                    <div style="margin-top: 10px;">
                        <a href="<?php echo esc_url(admin_url("post.php?post={$kit_id}&action=elementor")); ?>" target="_blank" class="button button-primary" style="margin-right: 10px;">
                            🎨 <?php _e('Edit colors in Elementor', 'me5rine-lab'); ?>
                        </a>

                        <a href="<?php echo esc_url($css_file_url); ?>" target="_blank" class="button button-secondary">
                            <?php _e('View generated CSS file', 'me5rine-lab'); ?>
                        </a>

                        <div style="margin-top: 5px;">
                            <code><?php echo esc_html($css_file_url); ?></code>
                        </div>

                        <?php if (!$css_exists): ?>
                            <p style="color: var(--admin-lab-color-red); margin-top: 5px;">
                                ⚠️ <?php _e('The CSS file does not exist yet (Elementor may not have generated it).', 'me5rine-lab'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <p><input type="submit" class="button button-primary" value="<?php esc_attr_e('Save changes', 'me5rine-lab'); ?>"></p>
</form>

<?php if ($css_exists && !empty($colors_assoc)) : ?>
    <h3><?php esc_html_e('Available CSS Variables', 'me5rine-lab'); ?></h3>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Slug', 'me5rine-lab'); ?></th>
                <th><?php _e('Color', 'me5rine-lab'); ?></th>
                <th><?php _e('CSS Variable', 'me5rine-lab'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colors_assoc as $slug => $hex): ?>
                <tr>
                    <td><code><?php echo esc_html($slug); ?></code></td>
                    <td>
                        <span style="display:inline-block;width:20px;height:20px;background:<?php echo esc_attr($hex); ?>;border-radius:3px;margin-right:5px;"></span>
                        <code><?php echo esc_html($hex); ?></code>
                    </td>
                    <td><code><?php echo esc_html("var(--e-global-color-{$slug})"); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($kit_id && $css_exists) : ?>
    <p><?php esc_html_e('No CSS variables found in the file. Is it a valid Elementor kit?', 'me5rine-lab'); ?></p>
<?php endif; ?>