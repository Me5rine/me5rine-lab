<?php
// File: modules/subscription/admin/tabs/subscription-tab-subscription-types.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Subscription Types
 * Lists subscription types retrieved from providers, grouped by provider
 */
function admin_lab_subscription_tab_subscription_types() {
    global $wpdb;
    
    $table = admin_lab_getTable('subscription_levels');
    
    // Handle save action (create or update subscription type)
    if (isset($_POST['action']) && $_POST['action'] === 'save_subscription_type') {
        // Debug: log POST data
        error_log('Subscription Type Save - POST data: ' . print_r($_POST, true));
        error_log('Subscription Type Save - Nonce check: ' . (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : 'missing'));
        
        if (!check_admin_referer('subscription_type_action')) {
            echo '<div class="notice notice-error"><p>Security check failed. Please try again. Nonce: ' . (isset($_POST['_wpnonce']) ? 'present' : 'missing') . '</p></div>';
            error_log('Subscription Type Save - Nonce verification failed');
        } else {
            $level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
            $provider_slug = sanitize_text_field($_POST['provider_slug'] ?? '');
            $level_slug = sanitize_text_field($_POST['level_slug'] ?? '');
            $level_name = sanitize_text_field($_POST['level_name'] ?? '');
            // level_tier: store tier_slug as string (for Tipeee and manual creation)
            $level_tier = isset($_POST['level_tier']) && $_POST['level_tier'] !== '' ? sanitize_text_field($_POST['level_tier']) : null;
            // Get discord_role_id from visible field or hidden field (if row is hidden)
            $discord_role_id = null;
            if (isset($_POST['discord_role_id']) && $_POST['discord_role_id'] !== '') {
                $discord_role_id = sanitize_text_field($_POST['discord_role_id']);
            } elseif (isset($_POST['discord_role_id_hidden']) && $_POST['discord_role_id_hidden'] !== '') {
                $discord_role_id = sanitize_text_field($_POST['discord_role_id_hidden']);
            }
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Debug: log processed data
            error_log('Subscription Type Save - Processed data: provider=' . $provider_slug . ', slug=' . $level_slug . ', name=' . $level_name . ', discord_role_id=' . ($discord_role_id ?? 'null'));
            
            if ($provider_slug && $level_slug && $level_name) {
                $data = [
                    'id' => $level_id,
                    'provider_slug' => $provider_slug,
                    'level_slug' => $level_slug,
                    'level_name' => $level_name,
                    'level_tier' => $level_tier,
                    'discord_role_id' => $discord_role_id,
                    'is_active' => $is_active,
                ];
                
                $result = admin_lab_save_subscription_level($data);
                
                // Debug: log result
                error_log('Subscription Type Save - Result: ' . ($result ? $result : 'false'));
                
                if ($result) {
                    // Redirect to avoid resubmission, preserve tab parameter, remove edit_type
                    // Build clean URL without edit_type
                    $redirect_url = admin_url('admin.php');
                    $redirect_url = add_query_arg([
                        'page' => 'admin-lab-subscription',
                        'tab' => 'subscription_types',
                        'saved' => '1'
                    ], $redirect_url);
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    global $wpdb;
                    $error_msg = $wpdb->last_error ? $wpdb->last_error : 'Unknown error';
                    echo '<div class="notice notice-error"><p>Error saving subscription type: ' . esc_html($error_msg) . '</p></div>';
                    error_log('Subscription Type Save - Database error: ' . $error_msg);
                    error_log('Subscription Type Save - Last query: ' . $wpdb->last_query);
                }
            } else {
                echo '<div class="notice notice-error"><p>Please fill all required fields (Provider, Slug, Name). Missing: ' . 
                     (!$provider_slug ? 'Provider ' : '') . 
                     (!$level_slug ? 'Slug ' : '') . 
                     (!$level_name ? 'Name' : '') . 
                     '</p></div>';
            }
        }
    }
    
    // Show success message after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Subscription type saved successfully.</p></div>';
        // Clean up URL by removing saved parameter after display
        echo '<script>
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location);
                url.searchParams.delete("saved");
                window.history.replaceState({}, "", url);
            }
        </script>';
    }
    
    // Handle delete action
    if (isset($_GET['delete_type']) && check_admin_referer('delete_subscription_type_' . $_GET['delete_type'])) {
        $level_id = intval($_GET['delete_type']);
        $level = admin_lab_get_subscription_level($level_id);
        if ($level) {
            $deleted = admin_lab_delete_subscription_level($level_id);
            if ($deleted) {
                echo '<div class="notice notice-success"><p>Subscription type deleted.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Cannot delete: subscription type is in use (has active subscriptions or mappings).</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Subscription type not found.</p></div>';
        }
    }
    
    // Handle sync action
    if (isset($_POST['sync_types']) && check_admin_referer('sync_subscription_types')) {
        $results = admin_lab_sync_subscription_types_from_providers();
        
        if (!empty($results['success'])) {
            echo '<div class="notice notice-success"><p><strong>Sync completed:</strong><br>';
            foreach ($results['success'] as $provider => $message) {
                echo esc_html(ucfirst($provider) . ': ' . $message) . '<br>';
            }
            echo '</p></div>';
        }
        
        if (!empty($results['errors'])) {
            echo '<div class="notice notice-error"><p><strong>Errors:</strong><br>';
            foreach ($results['errors'] as $provider => $message) {
                echo esc_html(ucfirst($provider) . ': ' . $message) . '<br>';
            }
            echo '</p></div>';
        }
    }
    
    // Get level to edit
    $edit_level = null;
    if (isset($_GET['edit_type']) && $_GET['edit_type'] !== 'new') {
        $edit_level = admin_lab_get_subscription_level(intval($_GET['edit_type']));
    } elseif (isset($_GET['edit_type']) && $_GET['edit_type'] === 'new') {
        $edit_level = ['id' => 0, 'provider_slug' => '', 'level_slug' => '', 'level_name' => '', 'level_tier' => '', 'discord_role_id' => '', 'is_active' => 1];
    }
    
    // Get providers
    $providers = admin_lab_get_subscription_providers();
    
    // Get subscription types (levels) from database
    // For Twitch/Discord: show types under 'twitch'/'discord' (global)
    // For others: show types under each specific provider_slug
    $all_types = $wpdb->get_results("SELECT * FROM {$table} ORDER BY provider_slug ASC, level_name ASC", ARRAY_A);
    
    // Get count of active subscriptions per level_slug
    $table_subscriptions = admin_lab_getTable('user_subscriptions');
    $subscription_counts = [];
    if (!empty($all_types)) {
        $level_slugs = array_unique(array_column($all_types, 'level_slug'));
        $provider_slugs = array_unique(array_column($all_types, 'provider_slug'));
        
        foreach ($level_slugs as $level_slug) {
            foreach ($provider_slugs as $provider_slug) {
                $key = $provider_slug . '_' . $level_slug;
                // For counting, check with base provider_slug for Twitch/Discord/Tipeee
                $count_provider_slug = $provider_slug;
                if (strpos($provider_slug, 'twitch') === 0) {
                    $count_provider_slug = 'twitch';
                } elseif (strpos($provider_slug, 'discord') === 0) {
                    $count_provider_slug = 'discord';
                } elseif (strpos($provider_slug, 'tipeee') === 0) {
                    $count_provider_slug = 'tipeee';
                }
                // Count subscriptions matching the level_slug and any provider starting with the base
                if ($count_provider_slug === 'twitch') {
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_subscriptions} WHERE provider_slug LIKE 'twitch%' AND level_slug = %s AND status = 'active'",
                        $level_slug
                    ));
                } elseif ($count_provider_slug === 'discord') {
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_subscriptions} WHERE provider_slug LIKE 'discord%' AND level_slug = %s AND status = 'active'",
                        $level_slug
                    ));
                } elseif ($count_provider_slug === 'tipeee' || strpos($count_provider_slug, 'tipeee') === 0) {
                    // For Tipeee, count all tipeee providers
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_subscriptions} WHERE provider_slug LIKE 'tipeee%' AND level_slug = %s AND status = 'active'",
                        $level_slug
                    ));
                } else {
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_subscriptions} WHERE provider_slug = %s AND level_slug = %s AND status = 'active'",
                        $provider_slug,
                        $level_slug
                    ));
                }
                $subscription_counts[$key] = (int)$count;
            }
        }
    }
    
    // Group by provider
    // For Twitch: group all under 'twitch'
    // For Discord: group all under 'discord'
    // For others: group by specific provider_slug
    $types_by_provider = [];
    foreach ($all_types as $type) {
        $group_key = $type['provider_slug'];
        // Normalize Twitch/Discord/Tipeee to base provider_slug
        if (strpos($group_key, 'twitch') === 0) {
            $group_key = 'twitch';
        } elseif (strpos($group_key, 'discord') === 0) {
            $group_key = 'discord';
        } elseif (strpos($group_key, 'tipeee') === 0) {
            $group_key = 'tipeee';
        }
        
        if (!isset($types_by_provider[$group_key])) {
            $types_by_provider[$group_key] = [];
        }
        // Avoid duplicates (if multiple twitch providers have same types)
        $exists = false;
        foreach ($types_by_provider[$group_key] as $existing) {
            if ($existing['level_slug'] === $type['level_slug']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $types_by_provider[$group_key][] = $type;
        }
    }
    
    // Note: subscription_type is NOT stored in subscription_levels anymore
    // It's stored in user_subscriptions metadata
    // This column in subscription_levels is kept for backward compatibility but should be null
    $subscription_type_labels = [
        'payant' => 'Paid',
        'gratuit' => 'Free',
        'gift' => 'Gift',
        'trial' => 'Trial',
    ];
    
    // Get tiers for form
    $tiers = admin_lab_get_subscription_tiers();
    
    ?>
    <div class="wrap">
        <h2>Subscription Types</h2>
        <p class="description">This tab lists all subscription types retrieved from configured providers, grouped by provider. You can also create subscription types manually for providers like Tipeee.</p>
        
        <?php if ($edit_level) : ?>
            <h3><?php echo $edit_level['id'] > 0 ? 'Edit Subscription Type' : 'Add Subscription Type'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-type-form.php'; ?>
        <?php else : ?>
            <div class="subscription-filter-section subscription-sync-types-section">
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'subscription_types', 'edit_type' => 'new'], remove_query_arg(['saved', 'delete_type']))); ?>" class="button button-primary">Add Subscription Type</a>
                <form method="post" action="" class="subscription-sync-types-form">
                    <?php wp_nonce_field('sync_subscription_types'); ?>
                    <input type="submit" name="sync_types" class="button" value="Sync Types from Providers">
                    <span class="description subscription-sync-types-description">Click to retrieve and update subscription types from all active providers.</span>
                </form>
            </div>
        
        <?php if (!empty($types_by_provider)) : ?>
            <?php foreach ($types_by_provider as $provider_slug => $types) : 
                $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
                $provider_name = $provider ? $provider['provider_name'] : $provider_slug;
            ?>
                <h3><?php echo esc_html($provider_name); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Slug</th>
                            <th>Name</th>
                            <th>Tier</th>
                            <th>Discord Role ID</th>
                            <th>Active Subscriptions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type) : 
                            $count_key = $type['provider_slug'] . '_' . $type['level_slug'];
                            $active_count = $subscription_counts[$count_key] ?? 0;
                            
                            // For Tipeee and other providers that allow manual creation, show edit/delete
                            $can_edit = (strpos($type['provider_slug'], 'tipeee') === 0 || strpos($type['provider_slug'], 'patreon') === 0 || strpos($type['provider_slug'], 'youtube') === 0);
                        ?>
                            <tr>
                                <td><code><?php echo esc_html($type['level_slug']); ?></code></td>
                                <td><strong><?php echo esc_html($type['level_name']); ?></strong></td>
                                <td>
                                    <?php 
                                    $tier_display = '-';
                                    if (!empty($type['level_tier'])) {
                                        // If it's a tier slug, get the tier name
                                        $tier = admin_lab_get_subscription_tier_by_slug($type['level_tier']);
                                        if ($tier) {
                                            $tier_display = $tier['tier_name'];
                                        } else {
                                            // If it's numeric, it might be an old format
                                            $tier_display = $type['level_tier'];
                                        }
                                    }
                                    echo esc_html($tier_display);
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $discord_role_id = $type['discord_role_id'] ?? '';
                                    if (!empty($discord_role_id)) {
                                        echo '<code>' . esc_html($discord_role_id) . '</code>';
                                    } else {
                                        echo '<span class="description">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format_i18n($active_count); ?></strong>
                                    <?php if ($active_count > 0) : ?>
                                        <span class="status-active">active subscription(s)</span>
                                    <?php else : ?>
                                        <span class="subscription-empty-state">no active subscriptions</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $type['is_active'] ? '<span class="status-active">✓ Active</span>' : '<span class="status-inactive">✗ Inactive</span>'; ?></td>
                                <td>
                                    <?php if ($can_edit) : ?>
                                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'subscription_types', 'edit_type' => $type['id']], remove_query_arg(['saved', 'delete_type']))); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['tab' => 'subscription_types', 'delete_type' => $type['id']], remove_query_arg(['saved', 'edit_type'])), 'delete_subscription_type_' . $type['id'])); ?>" 
                                           class="button button-small" 
                                           onclick="return confirm('Are you sure you want to delete this subscription type?');">
                                            Delete
                                        </a>
                                    <?php else : ?>
                                        <span class="description">Auto-synced</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No subscription types found. Click "Sync Types from Providers" to retrieve types from configured providers.</p>
        <?php endif; ?>
        <?php endif; // Close if ($edit_level) ?>
    </div>
    <?php
}
