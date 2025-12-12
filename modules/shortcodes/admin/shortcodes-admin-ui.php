<?php
// File: modules/shortcodes/admin/shortcodes-admin.php

if (!defined('ABSPATH')) exit;

/**
 * Screen options pour la page Shortcodes.
 */
function admin_lab_shortcodes_screen_options() {
    $screen = get_current_screen();

    // ID de l'écran pour la page "Shortcodes"
    if ( ! is_object( $screen ) || $screen->id !== 'me5rine-lab_page_admin-lab-shortcodes' ) {
        return;
    }

    add_screen_option(
        'per_page',
        [
            'label'   => __( 'Shortcodes per page', 'me5rine-lab' ),
            'default' => 20,
            'option'  => 'admin_lab_shortcodes_shortcodes_per_page',
        ]
    );
}

/**
 * Sauvegarde de l’option "Shortcodes per page" pour le Shortcodes.
 */
function admin_lab_set_shortcodes_screen_option( $status, $option, $value ) {
    if ( $option === 'admin_lab_shortcodes_shortcodes_per_page' ) {
        return (int) $value;
    }
    return $status;
}
add_filter( 'set-screen-option', 'admin_lab_set_shortcodes_screen_option', 10, 3 );

function admin_lab_shortcodes_page() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Shortcode management', 'me5rine-lab') ?></h1>
        <a href="<?= esc_url(admin_url('admin.php?page=admin-lab-shortcodes-edit')) ?>" class="page-title-action"><?php esc_html_e('Add a Shortcode', 'me5rine-lab') ?></a>
        <hr class="wp-header-end">

        <form method="get">
            <input type="hidden" name="page" value="admin-lab-shortcodes">
            <p class="search-box">
                <input type="search" name="s" value="<?= esc_attr($_GET['s'] ?? '') ?>" placeholder="<?= esc_attr(__('Search a shortcode', 'me5rine-lab')) ?>">
                <button type="submit" class="button"><?php esc_html_e('Search', 'me5rine-lab') ?></button>
            </p>
        </form>

        <?php if (class_exists('Admin_LAB_Shortcodes_List_Table')) :
            $shortcodes_table = new Admin_LAB_Shortcodes_List_Table();
            $shortcodes_table->prepare_items();
            ?>
            <form method="post">
                <?php $shortcodes_table->display() ?>
            </form>
        <?php else : ?>
            <div class="notice notice-error"><p><?php esc_html_e('Error: The Admin_LAB_Shortcodes_List_Table class is missing.', 'me5rine-lab') ?></p></div>
        <?php endif; ?>
    </div>
    <?php
}