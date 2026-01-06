<?php
// File: modules/subscription/admin/subscription-admin-ui.php

if (!defined('ABSPATH')) exit;

// Load list table classes
require_once __DIR__ . '/classes/subscription-keycloak-identities-list-table.php';
require_once __DIR__ . '/classes/subscription-user-subscriptions-list-table.php';

// Load tab files
require_once __DIR__ . '/tabs/subscription-tab-providers.php';
require_once __DIR__ . '/tabs/subscription-tab-channels.php';
require_once __DIR__ . '/tabs/subscription-tab-provider-account-types.php';
require_once __DIR__ . '/tabs/subscription-tab-subscription-types.php';
require_once __DIR__ . '/tabs/subscription-tab-tiers.php';
require_once __DIR__ . '/tabs/subscription-tab-subscription-levels.php';
require_once __DIR__ . '/tabs/subscription-tab-keycloak-identities.php';
require_once __DIR__ . '/tabs/subscription-tab-user-subscriptions.php';

// Enqueue admin styles
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'admin-lab-subscription') !== false || (isset($_GET['page']) && $_GET['page'] === 'admin-lab-subscription')) {
        /* Styles unifiés dans admin-unified.css */
    }
});

function admin_lab_subscription_admin_ui() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'providers';
    
    $tabs = [
        'providers' => 'Providers',
        'channels' => 'Channels/Servers',
        'provider_account_types' => 'Providers → Account Types',
        'subscription_types' => 'Subscription Types',
        'tiers' => 'Tiers',
        'subscription_levels' => 'Subscription Levels',
        'keycloak_identities' => 'Keycloak Identities',
        'user_subscriptions' => 'User Subscriptions',
    ];
    
    ?>
    <div class="wrap">
        <h1>Subscriptions</h1>
        
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, remove_query_arg(['paged', 's', 'filter_provider', 'view_mode', 'edit', 'edit_type', 'edit_tier', 'delete', 'delete_type', 'delete_tier', 'provider', 'user_id', 'sync_levels', 'saved', 'deleted', 'error']))); ?>" 
                   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="subscription-tab-content">
            <?php
            switch ($active_tab) {
                case 'providers':
                    admin_lab_subscription_tab_providers();
                    break;
                case 'channels':
                    admin_lab_subscription_tab_channels();
                    break;
                case 'provider_account_types':
                    admin_lab_subscription_tab_provider_account_types();
                    break;
                case 'subscription_types':
                    admin_lab_subscription_tab_subscription_types();
                    break;
                case 'tiers':
                    admin_lab_subscription_tab_tiers();
                    break;
                case 'subscription_levels':
                    admin_lab_subscription_tab_subscription_levels();
                    break;
                case 'keycloak_identities':
                    admin_lab_subscription_tab_keycloak_identities();
                    break;
                case 'user_subscriptions':
                    admin_lab_subscription_tab_user_subscriptions();
                    break;
                default:
                    echo '<p>Tab not found.</p>';
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}
