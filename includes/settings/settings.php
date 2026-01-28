<?php
// File: /includes/settings/settings.php

if (!defined('ABSPATH')) exit;

// Enregistre les options classiques (onglet General)
// Note: admin_lab_profile_base_url est maintenant une option globale (gérée dans settings-tab-general.php)
add_action('admin_init', function () {
    register_setting('admin_lab_settings', 'admin_lab_active_modules');
    register_setting('admin_lab_settings', 'admin_lab_delete_data_on_uninstall');
    // admin_lab_profile_base_url est maintenant une option globale, pas besoin de register_setting
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
    $active_modules = get_option('admin_lab_active_modules', []);
    $is_keycloak_active = is_array($active_modules) && in_array('keycloak_account_pages', $active_modules, true);

    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=admin-lab-settings&tab=general" class="nav-tab ' . ($active_tab === 'general' ? 'nav-tab-active' : '') . '">' . __('General', 'me5rine-lab') . '</a>';
    echo '<a href="?page=admin-lab-settings&tab=api" class="nav-tab ' . ($active_tab === 'api' ? 'nav-tab-active' : '') . '">' . __('API Keys', 'me5rine-lab') . '</a>';
    echo '<a href="?page=admin-lab-settings&tab=elementor_colors" class="nav-tab ' . ($active_tab === 'elementor_colors' ? 'nav-tab-active' : '') . '">' . __('Elementor Colors', 'me5rine-lab') . '</a>';
    if ($is_keycloak_active) {
        echo '<a href="?page=admin-lab-settings&tab=keycloak" class="nav-tab ' . ($active_tab === 'keycloak' ? 'nav-tab-active' : '') . '">' . __('Keycloak', 'me5rine-lab') . '</a>';
    }
    echo '</nav>';

    if ($active_tab === 'elementor_colors') {
        include __DIR__ . '/tabs/settings-tab-elementor-colors.php';
    } elseif ($active_tab === 'api') {
        include __DIR__ . '/tabs/settings-tab-api.php';
    } elseif ($active_tab === 'keycloak' && $is_keycloak_active) {
        $keycloak_settings_file = ME5RINE_LAB_PATH . 'modules/keycloak-account-pages/admin/keycloak-account-pages-settings.php';
        if (file_exists($keycloak_settings_file)) {
            include $keycloak_settings_file;
        }
    } else {
        include __DIR__ . '/tabs/settings-tab-general.php';
    }

    echo '</div>';
}
