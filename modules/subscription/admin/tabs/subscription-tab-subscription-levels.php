<?php
// File: modules/subscription/admin/subscription-tab-subscription-levels.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Subscription Levels
 * Links custom internal levels to subscription types retrieved from providers
 */
function admin_lab_subscription_tab_subscription_levels() {
    global $wpdb;
    
    $table_levels = admin_lab_getTable('subscription_levels');
    $table_tiers = admin_lab_getTable('subscription_tiers');
    $table_mappings = admin_lab_getTable('subscription_tier_mappings');
    $table_providers = admin_lab_getTable('subscription_providers');
    
    // Actions
    if (isset($_POST['action']) && $_POST['action'] === 'save_level' && check_admin_referer('subscription_level_action')) {
        $provider_slug = sanitize_text_field($_POST['provider_slug'] ?? '');
        
        // Get normalized provider_slug from data-normalized attribute (sent via hidden field or normalize here)
        // For Twitch/Discord, normalize to base provider_slug
        if (strpos($provider_slug, 'twitch') === 0) {
            $provider_slug = 'twitch';
        } elseif (strpos($provider_slug, 'discord') === 0) {
            $provider_slug = 'discord';
        }
        
        $data = [
            'id' => isset($_POST['mapping_id']) ? intval($_POST['mapping_id']) : 0,
            'tier_slug' => sanitize_text_field($_POST['tier_slug'] ?? ''),
            'provider_slug' => $provider_slug, // Normalized provider_slug
            'level_slug' => sanitize_text_field($_POST['subscription_type_slug'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 1,
        ];
        
        // Save level mapping
        if ($data['tier_slug'] && $data['provider_slug'] && $data['level_slug']) {
            admin_lab_save_subscription_tier_mapping($data);
            
            // Redirect to remove edit parameter
            $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'subscription_levels', 'saved' => '1'], remove_query_arg(['edit', 'delete']));
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    // Show success message after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Level mapping saved successfully.</p></div>';
    }
    
    if (isset($_GET['delete']) && check_admin_referer('delete_level_' . $_GET['delete'])) {
        $wpdb->delete($table_mappings, ['id' => intval($_GET['delete'])]);
        
        // Redirect to remove delete parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'subscription_levels', 'deleted' => '1'], remove_query_arg(['delete', 'edit']));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success message after redirect
    if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Level mapping deleted successfully.</p></div>';
    }
    
    // Get data
    $tiers = admin_lab_get_subscription_tiers();
    $providers = admin_lab_get_subscription_providers();
    
    // Get subscription types grouped by provider type
    // For Twitch/Discord: use 'twitch'/'discord' as base (global)
    // For others: use specific provider_slug
    $all_subscription_types = admin_lab_get_subscription_levels();
    
    // Group providers by type
    $twitch_providers = [];
    $discord_providers = [];
    $other_providers = [];
    
    foreach ($providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        if (strpos($provider_slug, 'twitch') === 0) {
            $twitch_providers[] = $provider;
        } elseif (strpos($provider_slug, 'discord') === 0) {
            $discord_providers[] = $provider;
        } else {
            $other_providers[] = $provider;
        }
    }
    
    // Build types_by_provider:
    // - For Twitch: show types under 'twitch' (global)
    // - For Discord: show types under 'discord' (global)
    // - For others: show types under each specific provider_slug
    $types_by_provider = [];
    
    // Add Twitch types (global)
    $twitch_types = admin_lab_get_subscription_levels('twitch');
    if (!empty($twitch_types)) {
        $types_by_provider['twitch'] = $twitch_types;
    }
    
    // Add Discord types (global)
    $discord_types = admin_lab_get_subscription_levels('discord');
    if (!empty($discord_types)) {
        $types_by_provider['discord'] = $discord_types;
    }
    
    // Add other provider types (per provider)
    foreach ($other_providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        $provider_types = admin_lab_get_subscription_levels($provider_slug);
        if (!empty($provider_types)) {
            $types_by_provider[$provider_slug] = $provider_types;
        }
    }
    
    // Get existing mappings
    $mappings = admin_lab_get_subscription_tier_mappings();
    
    // Enrich mappings with tier and level names (use key-based access to avoid reference issues)
    foreach ($mappings as $key => $mapping) {
        $tier = admin_lab_get_subscription_tier_by_slug($mapping['tier_slug']);
        $mappings[$key]['tier_name'] = $tier ? $tier['tier_name'] : '';
        // For Twitch/Discord, check with base provider_slug
        $check_provider_slug = $mapping['provider_slug'];
        if (strpos($check_provider_slug, 'twitch') === 0) {
            $check_provider_slug = 'twitch';
        } elseif (strpos($check_provider_slug, 'discord') === 0) {
            $check_provider_slug = 'discord';
        }
        $level = admin_lab_get_subscription_level_by_slug($check_provider_slug, $mapping['level_slug']);
        $mappings[$key]['level_name'] = $level ? $level['level_name'] : '';
    }
    unset($mapping); // Important: unset reference after foreach
    
    // Remove duplicates by id (safety check)
    $seen_ids = [];
    $unique_mappings = [];
    foreach ($mappings as $mapping) {
        $mapping_id = intval($mapping['id']);
        if (!in_array($mapping_id, $seen_ids)) {
            $seen_ids[] = $mapping_id;
            $unique_mappings[] = $mapping;
        }
    }
    $mappings = $unique_mappings;
    
    // Level to edit
    $edit_level = null;
    if (isset($_GET['edit']) && $_GET['edit'] !== 'new') {
        $mapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_mappings} WHERE id = %d", intval($_GET['edit'])), ARRAY_A);
        if ($mapping) {
            $tier = admin_lab_get_subscription_tier_by_slug($mapping['tier_slug']);
            
            // For display in form, if provider_slug is 'twitch' or 'discord', find the first matching provider
            $display_provider_slug = $mapping['provider_slug'];
            if ($mapping['provider_slug'] === 'twitch' && !empty($twitch_providers)) {
                $display_provider_slug = $twitch_providers[0]['provider_slug'];
            } elseif ($mapping['provider_slug'] === 'discord' && !empty($discord_providers)) {
                $display_provider_slug = $discord_providers[0]['provider_slug'];
            }
            
            $edit_level = [
                'id' => $mapping['id'],
                'tier_slug' => $mapping['tier_slug'],
                'tier_name' => $tier ? $tier['tier_name'] : '',
                'provider_slug' => $display_provider_slug, // Use first matching provider for display
                'provider_slug_normalized' => $mapping['provider_slug'], // Keep normalized for saving
                'subscription_type_slug' => $mapping['level_slug'],
            ];
        }
    } elseif (isset($_GET['edit']) && $_GET['edit'] === 'new') {
        $edit_level = ['id' => 0, 'tier_slug' => '', 'tier_name' => '', 'provider_slug' => '', 'provider_slug_normalized' => '', 'subscription_type_slug' => ''];
    }
    
    ?>
    <div class="wrap">
        <h2>Subscription Levels</h2>
        <p class="description">Link custom internal levels (tiers) to subscription types retrieved from providers.</p>
        
        <?php if ($edit_level) : ?>
            <h3><?php echo $edit_level['id'] > 0 ? 'Edit Level Mapping' : 'Add Level Mapping'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-level-mapping-form.php'; ?>
        <?php else : ?>
            <p><a href="<?php echo esc_url(add_query_arg(['tab' => 'subscription_levels', 'edit' => 'new'], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-primary">Add Level Mapping</a></p>
            
            <?php if (!empty($mappings)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Internal Level (Tier)</th>
                            <th>Provider</th>
                            <th>Subscription Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $mapping) : 
                            // Display provider name nicely
                            $display_provider = $mapping['provider_slug'];
                            if ($mapping['provider_slug'] === 'twitch') {
                                $display_provider = 'Twitch (all providers)';
                            } elseif ($mapping['provider_slug'] === 'discord') {
                                $display_provider = 'Discord (all providers)';
                            } else {
                                // Try to get provider name
                                $provider = admin_lab_get_subscription_provider_by_slug($mapping['provider_slug']);
                                if ($provider) {
                                    $display_provider = $provider['provider_name'];
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo esc_html($mapping['id']); ?></td>
                                <td><strong><?php echo esc_html($mapping['tier_name'] ?: $mapping['tier_slug']); ?></strong></td>
                                <td><?php echo esc_html($display_provider); ?></td>
                                <td><?php echo esc_html($mapping['level_name'] ?: $mapping['level_slug']); ?></td>
                                <td><?php echo $mapping['is_active'] ? '<span class="status-active">✓ Active</span>' : '<span class="status-inactive">✗ Inactive</span>'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'subscription_levels', 'edit' => $mapping['id']], remove_query_arg(['saved', 'deleted', 'delete']))); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('delete', $mapping['id']), 'delete_level_' . $mapping['id'])); ?>" 
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
                <p>No level mappings configured. Create mappings to link your internal levels to provider subscription types.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
