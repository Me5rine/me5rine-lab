<?php
// File: /includes/settings/settings.php

if (!defined('ABSPATH')) exit;

// Enregistre les options classiques (onglet General)
add_action('admin_init', function () {
    register_setting('admin_lab_settings', 'admin_lab_active_modules');
    register_setting('admin_lab_settings', 'admin_lab_delete_data_on_uninstall');
    register_setting('admin_lab_partnership_settings', 'admin_lab_account_id', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 0,
    ]);
    register_setting('admin_lab_settings', 'admin_lab_elementor_kit_id', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 0,
    ]);
});

// Affichage de la page avec les onglets dynamiques
function admin_lab_admin_ui() {
    $active_tab = $_GET['tab'] ?? 'general';

    echo '<div class="wrap">';
    echo '<h1>' . __('Me5rine LAB – Settings', 'me5rine-lab') . '</h1>';
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=admin-lab-settings&tab=general" class="nav-tab ' . ($active_tab === 'general' ? 'nav-tab-active' : '') . '">' . __('General', 'me5rine-lab') . '</a>';
    echo '<a href="?page=admin-lab-settings&tab=api" class="nav-tab ' . ($active_tab === 'api' ? 'nav-tab-active' : '') . '">' . __('API Keys', 'me5rine-lab') . '</a>';
    echo '<a href="?page=admin-lab-settings&tab=elementor_colors" class="nav-tab ' . ($active_tab === 'elementor_colors' ? 'nav-tab-active' : '') . '">' . __('Elementor Colors', 'me5rine-lab') . '</a>';
    echo '</nav>';

    if ($active_tab === 'elementor_colors') {
        include __DIR__ . '/tabs/settings-tab-elementor-colors.php';
    } elseif ($active_tab === 'api') {
        include __DIR__ . '/tabs/settings-tab-api.php';
    } else {
        include __DIR__ . '/tabs/settings-tab-general.php';
    }

    echo '</div>';
}
