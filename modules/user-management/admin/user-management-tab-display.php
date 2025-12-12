<?php
// File: modules/user-management/admin/user-management-tab-display.php

if (!defined('ABSPATH')) exit;

$name_type = get_option('admin_lab_display_name_type', 'display_name');

$types = [
    'user_login'   => __('Username (Login)', 'me5rine-lab'),
    'first_name'   => __('First Name', 'me5rine-lab'),
    'last_name'    => __('Last Name', 'me5rine-lab'),
    'nickname'     => __('Nickname', 'me5rine-lab'),
    'display_name' => __('Display Name (Custom)', 'me5rine-lab'),
    'first_last'   => __('First + Last', 'me5rine-lab'),
    'last_first'   => __('Last + First', 'me5rine-lab')
];

$progress_current = (int) get_option('admin_lab_progress_current', 0);
$progress_total   = (int) get_option('admin_lab_progress_total', 0);
$percent = ($progress_total > 0) ? min(100, round(($progress_current / $progress_total) * 100, 2)) : 0;

$start_batch = isset($_GET['batch']) && $_GET['batch'] === '1';
?>

<h2><?php esc_html_e('Display Name Format', 'me5rine-lab'); ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('admin_lab_update_display_name_type'); ?>
    <input type="hidden" name="action" value="admin_lab_update_display_name_type" />
    <select name="name-type">
        <?php foreach ($types as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($name_type, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'me5rine-lab'); ?></button>
</form>

<h2><?php esc_html_e('Update All Display Names', 'me5rine-lab'); ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('admin_lab_trigger_display_update'); ?>
    <input type="hidden" name="action" value="admin_lab_trigger_display_update" />
    <button type="submit" class="button button-primary"><?php esc_html_e('Update Display Names', 'me5rine-lab'); ?></button>
</form>

<?php if ($progress_total > 0 && $progress_current < $progress_total && $start_batch) : ?>
    <h3><?php esc_html_e('Update Progress', 'me5rine-lab'); ?></h3>
    <div class="progress-container" style="background:#e5e5e5; height: 30px; width: 100%; border-radius: 4px; overflow: hidden;">
        <div class="progress-bar" style="background:var(--admin-lab-color-secondary); color: var(--admin-lab-color-white); height: 100%; width: <?php echo esc_attr($percent); ?>%; line-height: 30px; padding-left: 10px;">
            <?php echo esc_html("{$progress_current} / {$progress_total} ({$percent}%)"); ?>
        </div>
    </div>
<?php endif; ?>

<?php
if (is_admin() && $start_batch) {
    wp_enqueue_script(
        'admin-lab-user-batch',
        plugins_url('assets/js/user-management-batch.js', WP_PLUGIN_DIR . '/me5rine-lab/me5rine-lab.php'),
        ['jquery'],
        null,
        true
    );

    wp_localize_script('admin-lab-user-batch', 'adminLabBatch', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'start'   => true,
    ]);
}
?>
