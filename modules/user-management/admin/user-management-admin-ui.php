<?php
// File: modules/user-management/admin/user-management-admin-ui.php

if (!defined('ABSPATH')) exit;

function admin_lab_user_management_admin_ui() {
    $tab = $_GET['tab'] ?? 'display';
    $tabs = [
        'display' => __('Display & Slug', 'me5rine-lab'),
        'types'   => __('Account Types', 'me5rine-lab'),
    ];
    ?>

    <div class="wrap">
        <h2>
            <?php esc_html_e('User Management', 'me5rine-lab'); ?>
            <?php
            if ($tab === 'types' && !isset($_GET['add'])) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-user-management&tab=types&add=1')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Type', 'me5rine-lab'); ?>
                </a>
            <?php endif; ?>
        </h2>

        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $key => $label) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-user-management&tab=' . $key)); ?>" class="nav-tab <?php if ($tab === $key) echo 'nav-tab-active'; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="tab-content" style="margin-top: 20px;">
            <?php
            switch ($tab) {
                case 'types':
                    include_once __DIR__ . '/user-management-tab-types.php';
                    break;
                case 'display':
                default:
                    include_once __DIR__ . '/user-management-tab-display.php';
                    break;
            }
            ?>
        </div>
    </div>

    <?php
}
