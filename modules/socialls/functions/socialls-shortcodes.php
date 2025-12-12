<?php
// File: modules/user-management/functions/user-management-shortcodes.php

if (!defined('ABSPATH')) exit;

function me5rine_lab_render_socials_as_linktree_shortcode($atts = []) {
    wp_enqueue_style(
        'admin-lab-linktree-like-socials-style',
        ME5RINE_LAB_URL . 'assets/css/socialls-front-list.css',
        [],
        ME5RINE_LAB_VERSION
    );

    $atts = shortcode_atts([
        'user_id' => 0,
        'type'    => 'social',
        'label'   => 'custom',
    ], $atts);

    global $post;

    $user_id = (int) $atts['user_id'];
    if (!$user_id && isset($post->post_author)) {
        $user_id = (int) $post->post_author;
    }

    if (!$user_id) {
        return '';
    }

    $type = in_array($atts['type'], ['social', 'support']) ? $atts['type'] : 'social';
    $use_global_label = ($atts['label'] === 'global');

    $socials = admin_lab_get_user_socials_full_info($user_id, $type, $use_global_label);
    if (empty($socials)) {
        return '';
    }

    uasort($socials, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    $html = '<div class="admin-lab-linktree-like-socials-list">';

    foreach ($socials as $social) {
        if (empty($social['url']) || empty($social['user_enabled'])) {
            continue;
        }

        $label = esc_html($social['label'] ?? ucfirst($social['meta_key']));
        $url   = esc_url($social['url']);
        $icon  = $social['icon'] ?? '';
        $icon_path = ME5RINE_LAB_PATH . 'assets/icons/' . $icon;

        $html .= '<a class="admin-lab-linktree-like-social-button" href="' . $url . '" target="_blank" rel="noopener">';
        if ($icon && file_exists($icon_path)) {
            $svg_content = file_get_contents($icon_path);
            $html .= '<span class="admin-lab-linktree-like-social-icon">' . $svg_content . '</span>';
        }
        $html .= '<span class="admin-lab-linktree-like-social-label">' . $label . '</span>';
        $html .= '</a>';
    }

    $html .= '</div>';
    return $html;
}

add_shortcode('me5rine_lab_socials', 'me5rine_lab_render_socials_as_linktree_shortcode');

function admin_lab_socials_dashboard_shortcode() {
    if (!admin_lab_require_access('socialls', __('Access socials dashboard', 'me5rine-lab'))) {
        return;
    }

    wp_enqueue_style(
        'admin-lab-socials-dashboard-style',
        ME5RINE_LAB_URL . 'assets/css/socialls-front-dashboard.css',
        [],
        ME5RINE_LAB_VERSION
    );

    $user_id = get_current_user_id();
    $socials = admin_lab_get_user_socials_list($user_id, ['social', 'support']);

    $socials_follow = array_filter($socials, fn($s) => $s['type'] === 'social');
    $socials_support = array_filter($socials, fn($s) => $s['type'] === 'support');

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['socials'])) {
        check_admin_referer('admin_lab_save_socials_labels');

        foreach ($_POST['socials'] as $key => $values) {
            if (!isset($socials[$key])) continue;

            // Label
            $label = sanitize_text_field($values['label'] ?? '');
            update_user_meta($user_id, $key . '_label', $label);

            // Enabled
            $enabled = isset($values['enabled']) && $values['enabled'] == '1' ? '1' : '0';
            update_user_meta($user_id, $key . '_enabled', $enabled);
        }

        set_transient('admin_lab_socials_updated_' . $user_id, true, 30);
        wp_safe_redirect(add_query_arg('socials_updated', '1', wp_get_referer() ?: get_permalink()));
        exit;
    }

    ob_start();
    ?>
    <div class="socials-dashboard">
        <h3><?php esc_html_e('My Socialls', 'me5rine-lab'); ?></h3>
        <?php if (get_transient('admin_lab_socials_updated_' . $user_id)) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Your social labels have been updated.', 'me5rine-lab'); ?></p>
            </div>
            <?php delete_transient('admin_lab_socials_updated_' . $user_id); ?>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('admin_lab_save_socials_labels'); ?>
            <table class="widefat striped admin-lab-socials-table">
                <thead>
                    <tr>
                        <th><?php _e('Social Network', 'me5rine-lab'); ?></th>
                        <th><?php _e('Label', 'me5rine-lab'); ?></th>
                        <th><?php _e('Enabled', 'me5rine-lab'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['Follow Networks' => $socials_follow, 'Support Networks' => $socials_support] as $section_title => $socials_group) : ?>
                        <tr><td class="social-type-title" colspan="3"><strong><?php echo esc_html__($section_title, 'me5rine-lab'); ?></strong></td></tr>
                        <?php foreach ($socials_group as $key => $data) : 
                            $label = get_user_meta($user_id, $key . '_label', true);
                            $enabled = get_user_meta($user_id, $key . '_enabled', true);
                            ?>
                            <tr class="is-collapsed">
                                <td data-colname="<?php _e('Social Network', 'me5rine-lab'); ?>">
                                    <span class="social-label"><?php echo esc_html($data['label'] ?? $key); ?></span>
                                    <button type="button" class="toggle-row-btn" aria-expanded="false"><span class="screen-reader-text"><?php _e('Show/hide options', 'me5rine-lab'); ?></span></button>
                                </td>
                                <td data-colname="<?php _e('Label', 'me5rine-lab'); ?>">
                                    <input type="text" name="socials[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($label); ?>" />
                                </td>
                                <td data-colname="<?php _e('Enabled', 'me5rine-lab'); ?>">
                                    <input type="checkbox" name="socials[<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked($enabled, '1'); ?> />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary"><?php _e('Save Changes', 'me5rine-lab'); ?></button></p>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-row-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const row = btn.closest('tr');
                const expanded = row.classList.contains('is-expanded');
                row.classList.toggle('is-expanded', !expanded);
                row.classList.toggle('is-collapsed', expanded);
                btn.setAttribute('aria-expanded', !expanded);
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('socials_dashboard', 'admin_lab_socials_dashboard_shortcode');

function me5rine_lab_render_author_socials_shortcode($atts = []) {

    wp_enqueue_style(
        'admin-lab-author-socials-style',
        ME5RINE_LAB_URL . 'assets/css/autor-socials-style.css',
        [],
        ME5RINE_LAB_VERSION
    );

    $atts = shortcode_atts([
        'size'   => '24',
        'color'  => '#000000',
        'layout' => 'horizontal',
    ], $atts);

    global $post;
    $user_id = $post->post_author ?? 0;
    if (!$user_id) return '';

    $allowed_keys = ['twitter', 'facebook', 'threads', 'instagram', 'bluesky', 'website-url'];

    $socials = admin_lab_get_user_socials_full_info($user_id, ['support', 'social'], false, true);

    $filtered = array_filter($socials, fn($s) => in_array($s['meta_key'], $allowed_keys) && !empty($s['url']));
    if (empty($filtered)) return '';

    uasort($filtered, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    $size  = absint($atts['size']) ?: 24;
    $color = sanitize_hex_color($atts['color']) ?: '#000000';
    $layout_class = ($atts['layout'] === 'vertical') ? 'vertical' : 'horizontal';

    $html = '<div class="admin-lab-author-socials-list ' . esc_attr($layout_class) . '">';

    foreach ($filtered as $social) {
        $url  = esc_url($social['url']);
        $icon = $social['icon'] ?? '';
        $icon_path = ME5RINE_LAB_PATH . 'assets/icons/' . $icon;

        if (!$icon || !file_exists($icon_path)) continue;

        $svg = file_get_contents($icon_path);
        $html .= '<a class="admin-lab-author-social-button" href="' . $url . '" target="_blank" rel="noopener">';
        $html .= '<span class="admin-lab-author-social-icon" style="height:' . $size . 'px;color:' . $color . ';">' . $svg . '</span>';
        $html .= '</a>';
    }

    $html .= '</div>';
    return $html;
}
add_shortcode('me5rine_lab_author_socials', 'me5rine_lab_render_author_socials_shortcode');




