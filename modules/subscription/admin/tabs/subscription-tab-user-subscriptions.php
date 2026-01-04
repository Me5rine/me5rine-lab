<?php
// File: modules/subscription/admin/tabs/subscription-tab-user-subscriptions.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: User Subscriptions
 * Lists all subscriptions from providers, linked or not to WordPress accounts
 */
function admin_lab_subscription_tab_user_subscriptions() {
    global $wpdb;
    
    $table_subscriptions = admin_lab_getTable('user_subscriptions');
    $table_accounts = admin_lab_getTable('subscription_accounts');
    $table_providers = admin_lab_getTable('subscription_providers');
    $table_levels = admin_lab_getTable('subscription_levels');
    
    // Handle automatic sync toggle
    if (isset($_POST['toggle_auto_sync']) && check_admin_referer('toggle_auto_sync')) {
        $enable = isset($_POST['auto_sync_enabled']) && $_POST['auto_sync_enabled'] === '1';
        if ($enable) {
            if (function_exists('admin_lab_schedule_subscription_sync')) {
                admin_lab_schedule_subscription_sync();
                echo '<div class="notice notice-success"><p>Synchronisation automatique activée (toutes les heures).</p></div>';
            }
        } else {
            if (function_exists('admin_lab_unschedule_subscription_sync')) {
                admin_lab_unschedule_subscription_sync();
                echo '<div class="notice notice-success"><p>Synchronisation automatique désactivée.</p></div>';
            }
        }
    }
    
    // Handle sync action
    if (isset($_POST['sync_subscriptions']) && check_admin_referer('sync_subscriptions')) {
        $results = admin_lab_sync_subscriptions_from_providers();
        
        if (!empty($results['success'])) {
            echo '<div class="notice notice-success"><p><strong>Sync completed:</strong><br>';
            foreach ($results['success'] as $provider => $message) {
                echo esc_html(ucfirst($provider) . ': ' . $message) . '<br>';
            }
            echo 'Total: ' . number_format_i18n($results['total_synced']) . ' subscriptions synced.</p></div>';
        }
        
        if (!empty($results['errors'])) {
            echo '<div class="notice notice-error"><p><strong>Errors:</strong><br>';
            foreach ($results['errors'] as $provider => $message) {
                echo esc_html(ucfirst($provider) . ': ' . $message) . '<br>';
            }
            echo '</p></div>';
        }
        
        // Show info if no results
        if (empty($results['success']) && empty($results['errors'])) {
            echo '<div class="notice notice-info"><p><strong>No subscriptions found.</strong><br>';
            echo 'Make sure:<br>';
            echo '1. The provider is configured with a valid Client ID<br>';
            echo '2. A Twitch account is linked (see "Keycloak Identities" tab) with a valid access token<br>';
            echo '3. The access token belongs to the channel owner (broadcaster) and has the "channel:read:subscriptions" scope<br>';
            echo '4. The channel identifier is the broadcaster\'s User ID (not username)</p></div>';
        }
    }
    
    // Create list table instance
    $list_table = new Subscription_User_Subscriptions_List_Table();
    $list_table->prepare_items();
    
    // Get statistics for display
    $total_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_subscriptions} WHERE status = 'active'");
    $linked_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_subscriptions} WHERE status = 'active' AND user_id > 0");
    $unlinked_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_subscriptions} WHERE status = 'active' AND (user_id = 0 OR user_id IS NULL)");
    
    ?>
    <div class="wrap">
        <h2>User Subscriptions</h2>
        <p class="description">List all subscriptions retrieved from providers. Subscriptions can be linked or not linked to WordPress accounts.</p>
        
        <!-- Sync Button -->
        <div class="subscription-filter-section">
            <form method="post" action="">
                <?php wp_nonce_field('sync_subscriptions'); ?>
                <input type="submit" name="sync_subscriptions" class="button button-primary" value="Sync Subscriptions from Providers">
            </form>
            
            <?php
            // Display automatic sync status
            if (function_exists('admin_lab_get_subscription_sync_schedule_status')) {
                $schedule_status = admin_lab_get_subscription_sync_schedule_status();
                require_once __DIR__ . '/../forms/subscription-auto-sync-form.php';
                admin_lab_subscription_auto_sync_form($schedule_status);
            }
            ?>
                <p class="description">
                    Click to retrieve all subscriptions from configured channels/servers.<br>
                    <strong>For Twitch:</strong> You need a linked Twitch account (see "Keycloak Identities" tab) with a valid access token. 
                    The token must belong to the channel owner (broadcaster) and have the "channel:read:subscriptions" scope.<br>
                    <?php 
                    $upload_dir = wp_upload_dir();
                    $log_file = trailingslashit($upload_dir['basedir']) . 'admin-lab-logs/subscription-sync.log';
                    if (file_exists($log_file)) {
                        $log_url = trailingslashit($upload_dir['baseurl']) . 'admin-lab-logs/subscription-sync.log';
                        echo '<strong>Debug Log:</strong> <a href="' . esc_url($log_url) . '" target="_blank">View sync log file</a> (last modified: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($log_file)) . ')';
                    }
                    ?>
                </p>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="subscription-stats">
            <p>
                <strong><?php echo number_format_i18n($list_table->get_pagination_arg('total_items')); ?></strong> user(s) with subscriptions
                <?php 
                echo ' (' . number_format_i18n($total_subs) . ' total subscriptions, ' . number_format_i18n($linked_users) . ' linked users, ' . number_format_i18n($unlinked_subs) . ' unlinked subscriptions)';
                ?>
            </p>
        </div>
        
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
            <input type="hidden" name="tab" value="user_subscriptions">
            <?php $list_table->search_box(__('Search', 'me5rine-lab'), 'subscription'); ?>
        </form>
        
        <?php $list_table->display(); ?>
    </div>
    <?php
}
