<?php
// File: modules/subscription/admin/subscription-tab-tiers.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Tiers
 * Management of generic tiers (Bronze, Silver, Gold, etc.) and their mappings
 */
function admin_lab_subscription_tab_tiers() {
    global $wpdb;
    
    $table_tiers = admin_lab_getTable('subscription_tiers');
    
    // Actions
    if (isset($_POST['action']) && $_POST['action'] === 'save_tier' && check_admin_referer('subscription_tier_action')) {
        $data = [
            'id' => isset($_POST['tier_id']) ? intval($_POST['tier_id']) : 0,
            'tier_slug' => sanitize_text_field($_POST['tier_slug'] ?? ''),
            'tier_name' => sanitize_text_field($_POST['tier_name'] ?? ''),
            'tier_order' => intval($_POST['tier_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 1,
        ];
        
        admin_lab_save_subscription_tier($data);
        
        // Redirect to remove edit_tier parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'tiers', 'saved' => '1'], remove_query_arg(['edit_tier', 'delete_tier']));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success message after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Tier saved successfully.</p></div>';
    }
    
    // Handle delete tier
    if (isset($_GET['delete_tier']) && check_admin_referer('delete_tier_' . $_GET['delete_tier'])) {
        $tier_id = intval($_GET['delete_tier']);
        $tier = admin_lab_get_subscription_tier($tier_id);
        if ($tier) {
            $deleted = false;
            // Check if tier is used in mappings
            $table_mappings = admin_lab_getTable('subscription_tier_mappings');
            $mappings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_mappings} WHERE tier_slug = %s",
                $tier['tier_slug']
            ));
            if ($mappings_count == 0) {
                $wpdb->delete($table_tiers, ['id' => $tier_id]);
                $deleted = true;
            } else {
                $deleted = false;
            }
        }
        
        // Redirect to remove delete_tier parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'tiers'], remove_query_arg(['delete_tier', 'edit_tier']));
        if ($deleted) {
            $redirect_url = add_query_arg('deleted', '1', $redirect_url);
        } else {
            $redirect_url = add_query_arg('error', '1', $redirect_url);
        }
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success/error messages after redirect
    if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Tier deleted successfully.</p></div>';
    }
    if (isset($_GET['error']) && $_GET['error'] === '1') {
        echo '<div class="notice notice-error is-dismissible"><p>Cannot delete tier: it is used in mapping(s). Manage mappings in the "Subscription Levels" tab.</p></div>';
    }
    
    // Get data
    $tiers = admin_lab_get_subscription_tiers();
    
    // Check if editing a tier
    $edit_tier = null;
    if (isset($_GET['edit_tier']) && $_GET['edit_tier'] !== 'new') {
        $tier_id = intval($_GET['edit_tier']);
        $edit_tier = admin_lab_get_subscription_tier($tier_id);
    }
    
    ?>
    <div class="wrap">
        <h2>Subscription Tiers</h2>
        <p class="description">Manage internal subscription tiers (Bronze, Silver, Gold, Platinum, Emerald, Diamond, etc.). To link tiers to provider subscription types, use the "Subscription Levels" tab.</p>
        
        <?php if ($edit_tier || (isset($_GET['edit_tier']) && $_GET['edit_tier'] === 'new')) : ?>
            <h3><?php echo $edit_tier ? 'Edit Tier' : 'Add Tier'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-tier-form.php'; ?>
        <?php else : ?>
            <h3>Tiers</h3>
            <p><a href="<?php echo esc_url(add_query_arg(['tab' => 'tiers', 'edit_tier' => 'new'], remove_query_arg(['saved', 'deleted', 'error', 'delete_tier']))); ?>" class="button button-primary">Add Tier</a></p>
        
            <?php if (!empty($tiers)) : 
                // Remove duplicates by id (safety check in case of database inconsistency)
                $seen_ids = [];
                $unique_tiers = [];
                foreach ($tiers as $tier) {
                    $tier_id = intval($tier['id']);
                    if (!in_array($tier_id, $seen_ids)) {
                        $seen_ids[] = $tier_id;
                        $unique_tiers[] = $tier;
                    }
                }
                $tiers = $unique_tiers;
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $tier) : ?>
                            <tr>
                                <td><?php echo esc_html($tier['id']); ?></td>
                                <td><strong><?php echo esc_html($tier['tier_name']); ?></strong></td>
                                <td><code><?php echo esc_html($tier['tier_slug']); ?></code></td>
                                <td><?php echo esc_html($tier['tier_order']); ?></td>
                                <td><?php echo $tier['is_active'] ? '<span class="status-active">✓ Active</span>' : '<span class="status-inactive">✗ Inactive</span>'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'tiers', 'edit_tier' => $tier['id']], remove_query_arg(['saved', 'deleted', 'error', 'delete_tier']))); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['delete_tier' => $tier['id']]), 'delete_tier_' . $tier['id'])); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this tier?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No tiers configured. <a href="<?php echo esc_url(add_query_arg(['tab' => 'tiers', 'edit_tier' => 'new'], remove_query_arg(['saved', 'deleted', 'error', 'delete_tier']))); ?>">Add your first tier</a>.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php
}
