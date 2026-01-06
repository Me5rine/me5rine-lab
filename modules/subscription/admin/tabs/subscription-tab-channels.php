<?php
// File: modules/subscription/admin/subscription-tab-channels.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Channels
 * Channels/servers management per provider
 */
function admin_lab_subscription_tab_channels() {
    global $wpdb;
    
    $table = admin_lab_getTable('subscription_channels');
    $table_providers = admin_lab_getTable('subscription_providers');
    
    // Actions
    if (isset($_POST['action']) && $_POST['action'] === 'save_channel' && check_admin_referer('subscription_channel_action')) {
        $data = [
            'id' => isset($_POST['channel_id_field']) ? intval($_POST['channel_id_field']) : 0,
            'provider_slug' => sanitize_text_field($_POST['provider_slug'] ?? ''),
            'channel_identifier' => sanitize_text_field($_POST['channel_identifier'] ?? ''),
            'channel_name' => sanitize_text_field($_POST['channel_name'] ?? ''),
            'channel_type' => sanitize_text_field($_POST['channel_type'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        admin_lab_save_subscription_channel($data);
        
        // Redirect to clean URL without edit parameter
        // Build clean URL from scratch - don't include provider filter after save
        $redirect_url = admin_url('admin.php');
        $redirect_url = add_query_arg([
            'page' => 'admin-lab-subscription',
            'tab' => 'channels',
            'saved' => '1'
        ], $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }
    
    if (isset($_GET['delete']) && check_admin_referer('delete_channel_' . $_GET['delete'])) {
        admin_lab_delete_subscription_channel(intval($_GET['delete']));
        
        // Redirect to clean URL without delete parameter
        $redirect_url = admin_url('admin.php');
        $redirect_url = add_query_arg([
            'page' => 'admin-lab-subscription',
            'tab' => 'channels',
            'deleted' => '1'
        ], $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success messages after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Channel saved successfully.</p></div>';
    }
    if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Channel deleted successfully.</p></div>';
    }
    
    // Filter by provider
    $filter_provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
    $all_providers = admin_lab_get_subscription_providers();
    $providers = $all_providers; // Use directly, already has provider_slug and provider_name
    
    $channels = admin_lab_get_subscription_channels($filter_provider ?: null);
    
    // Channel to edit
    $edit_channel = null;
    if (isset($_GET['edit']) && $_GET['edit'] !== 'new') {
        $edit_channel = admin_lab_get_subscription_channel(intval($_GET['edit']));
    } elseif (isset($_GET['edit']) && $_GET['edit'] === 'new') {
        $edit_channel = ['id' => 0, 'provider_slug' => $filter_provider ?: '', 'channel_identifier' => '', 'channel_name' => '', 'channel_type' => '', 'is_active' => 0];
    }
    
    ?>
    <div class="wrap">
        <h2>Channels / Servers</h2>
        
        <!-- Filter by provider -->
        <div class="subscription-filter-section">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
                <input type="hidden" name="tab" value="channels">
                <label>
                    Filter by provider:
                    <select name="provider">
                        <option value="">All</option>
                        <?php foreach ($providers as $provider) : ?>
                            <option value="<?php echo esc_attr($provider['provider_slug']); ?>" <?php selected($filter_provider, $provider['provider_slug']); ?>>
                                <?php echo esc_html($provider['provider_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="submit" class="button" value="Filter">
            </form>
        </div>
        
        <?php if ($edit_channel) : ?>
            <h3><?php echo $edit_channel['id'] > 0 ? 'Edit Channel' : 'Add Channel'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-channel-form.php'; ?>
        <?php else : ?>
            <p><a href="<?php echo esc_url(add_query_arg(['tab' => 'channels', 'edit' => 'new'], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-primary">Add Channel</a></p>
            
            <?php if (!empty($channels)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Identifier</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Create provider lookup
                        $provider_lookup = [];
                        foreach ($providers as $p) {
                            $provider_lookup[$p['provider_slug']] = $p['provider_name'];
                        }
                        foreach ($channels as $channel) : 
                            $provider_name = $provider_lookup[$channel['provider_slug']] ?? $channel['provider_slug'];
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($provider_name); ?></strong><br><small><code><?php echo esc_html($channel['provider_slug']); ?></code></small></td>
                                <td><code><?php echo esc_html($channel['channel_identifier']); ?></code></td>
                                <td><strong><?php echo esc_html($channel['channel_name']); ?></strong></td>
                                <td><?php echo esc_html($channel['channel_type'] ?? '-'); ?></td>
                                <td><?php echo $channel['is_active'] ? '<span class="admin-lab-status-active">✓ Active</span>' : '<span class="admin-lab-status-inactive">✗ Inactive</span>'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'channels', 'edit' => $channel['id'], 'provider' => $channel['provider_slug']], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['delete' => $channel['id'], 'provider' => $channel['provider_slug']]), 'delete_channel_' . $channel['id'])); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this channel?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No channels configured.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
