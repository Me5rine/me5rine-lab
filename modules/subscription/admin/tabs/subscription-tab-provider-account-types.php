<?php
// File: modules/subscription/admin/subscription-tab-provider-account-types.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Provider Account Types
 * Mapping between providers and WordPress account types
 */
function admin_lab_subscription_tab_provider_account_types() {
    global $wpdb;
    
    $table = admin_lab_getTable('subscription_provider_account_types');
    $table_providers = admin_lab_getTable('subscription_providers');
    
    // Get available account types
    $account_types = admin_lab_get_registered_account_types();
    
    // Actions
    if (isset($_POST['action']) && $_POST['action'] === 'save_mapping' && check_admin_referer('subscription_provider_account_type_action')) {
        $data = [
            'id' => isset($_POST['mapping_id']) ? intval($_POST['mapping_id']) : 0,
            'provider_slug' => sanitize_text_field($_POST['provider_slug'] ?? ''),
            'account_type_slug' => sanitize_text_field($_POST['account_type_slug'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 1,
        ];
        
        admin_lab_save_provider_account_type($data);
        
        // Redirect to remove edit parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'provider_account_types', 'saved' => '1'], remove_query_arg(['edit', 'delete']));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success message after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Mapping saved successfully.</p></div>';
    }
    
    if (isset($_GET['delete']) && check_admin_referer('delete_mapping_' . $_GET['delete'])) {
        admin_lab_delete_provider_account_type(intval($_GET['delete']));
        
        // Redirect to remove delete parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'provider_account_types', 'deleted' => '1'], remove_query_arg(['delete', 'edit']));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success message after redirect
    if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Mapping deleted successfully.</p></div>';
    }
    
    // Get providers and mappings
    $all_providers = admin_lab_get_subscription_providers();
    $providers = array_map(function($p) { return ['slug' => $p['provider_slug'], 'name' => $p['provider_name']]; }, $all_providers);
    $all_mappings = admin_lab_get_provider_account_types();
    
    // Enrich mappings with provider names
    $mappings = [];
    foreach ($all_mappings as $mapping) {
        $provider = admin_lab_get_subscription_provider_by_slug($mapping['provider_slug']);
        $mapping['provider_name'] = $provider ? $provider['provider_name'] : $mapping['provider_slug'];
        $mappings[] = $mapping;
    }
    
    // Mapping to edit
    $edit_mapping = null;
    if (isset($_GET['edit']) && $_GET['edit'] !== 'new') {
        $edit_mapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($_GET['edit'])), ARRAY_A);
    } elseif (isset($_GET['edit']) && $_GET['edit'] === 'new') {
        $edit_mapping = ['id' => 0, 'provider_slug' => '', 'account_type_slug' => ''];
    }
    
    ?>
    <div class="wrap">
        <h2>Providers → Account Types</h2>
        <p class="description">Associate each provider to a WordPress account type. Account type is defined by the provider, not by the tier.</p>
        
        <?php if ($edit_mapping) : ?>
            <h3><?php echo $edit_mapping['id'] > 0 ? 'Edit Mapping' : 'Add Mapping'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-provider-account-type-form.php'; ?>
        <?php else : ?>
            <p><a href="<?php echo esc_url(add_query_arg(['tab' => 'provider_account_types', 'edit' => 'new'], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-primary">Add Mapping</a></p>
            
            <?php if (!empty($mappings)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Account Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $mapping) : 
                            $account_type_label = $account_types[$mapping['account_type_slug']]['label'] ?? $mapping['account_type_slug'];
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($mapping['provider_name'] ?? $mapping['provider_slug']); ?></strong></td>
                                <td><?php echo esc_html($account_type_label); ?></td>
                                <td><?php echo $mapping['is_active'] ? '<span class="admin-lab-status-active">✓ Active</span>' : '<span class="admin-lab-status-inactive">✗ Inactive</span>'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'provider_account_types', 'edit' => $mapping['id']], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('delete', $mapping['id']), 'delete_mapping_' . $mapping['id'])); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this mapping?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No mappings configured.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
