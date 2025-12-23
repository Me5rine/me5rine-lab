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
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[SUBSCRIPTION SYNC] Manual sync triggered from admin', 'subscription-sync.log');
        }
        error_log('[SUBSCRIPTION SYNC] Manual sync triggered from admin');
        $results = admin_lab_sync_subscriptions_from_providers();
        $results_json = json_encode($results);
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[SUBSCRIPTION SYNC] Sync completed. Results: ' . $results_json, 'subscription-sync.log');
        }
        error_log('[SUBSCRIPTION SYNC] Sync completed. Results: ' . $results_json);
        
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
    
    // Filter: linked or not linked
    $filter_linked = isset($_GET['filter_linked']) ? sanitize_text_field($_GET['filter_linked']) : 'all';
    $filter_linked = in_array($filter_linked, ['all', 'linked', 'unlinked']) ? $filter_linked : 'all';
    
    // Pagination
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    
    // Filters
    $filter_provider = isset($_GET['filter_provider']) ? sanitize_text_field($_GET['filter_provider']) : '';
    $filter_provider_target = isset($_GET['filter_provider_target']) ? sanitize_text_field($_GET['filter_provider_target']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Get providers for filter
    $providers = admin_lab_get_subscription_providers();
    
    // Group providers by base provider
    $providers_by_base = [];
    $base_providers = ['twitch', 'youtube', 'discord', 'tipeee', 'patreon'];
    foreach ($providers as $provider) {
        $base = null;
        foreach ($base_providers as $base_provider) {
            if (strpos($provider['provider_slug'], $base_provider) === 0) {
                $base = $base_provider;
                break;
            }
        }
        if ($base) {
            if (!isset($providers_by_base[$base])) {
                $providers_by_base[$base] = [];
            }
            $providers_by_base[$base][] = $provider;
        }
    }
    
    // Build query
    $where_conditions = ["us.status = 'active'"];
    
    if ($filter_linked === 'linked') {
        $where_conditions[] = "us.user_id > 0";
    } elseif ($filter_linked === 'unlinked') {
        $where_conditions[] = "(us.user_id = 0 OR us.user_id IS NULL)";
    }
    
    // Filter by base provider (global)
    if ($filter_provider) {
        $where_conditions[] = $wpdb->prepare("us.provider_slug = %s", $filter_provider);
    }
    
    // Filter by provider target (specific)
    if ($filter_provider_target) {
        $where_conditions[] = $wpdb->prepare("us.provider_target_slug = %s", $filter_provider_target);
    }
    
    if ($search) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        // Search only in WordPress user fields and external username (from metadata), NOT in channel names
        $where_conditions[] = $wpdb->prepare(
            "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s OR us.external_subscription_id LIKE %s OR JSON_EXTRACT(us.metadata, '$.external_username') LIKE %s)",
            $search_like, $search_like, $search_like, $search_like, $search_like
        );
    }
    
    // Filter by subscription_type (gratuit/payant)
    $filter_subscription_type = isset($_GET['filter_subscription_type']) ? sanitize_text_field($_GET['filter_subscription_type']) : '';
    if ($filter_subscription_type && in_array($filter_subscription_type, ['gratuit', 'payant'])) {
        $where_conditions[] = $wpdb->prepare(
            "JSON_EXTRACT(us.metadata, '$.subscription_type') = %s",
            $filter_subscription_type
        );
    }
    
    $where = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get subscriptions grouped by user
    // First, get all subscriptions with user info
    $order_by = "us.user_id DESC, us.provider_slug ASC, us.started_at DESC";
    
    // Debug: log the query if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG && $filter_provider) {
        error_log('[USER SUBSCRIPTIONS] Filter provider: ' . $filter_provider);
        error_log('[USER SUBSCRIPTIONS] WHERE clause: ' . $where);
    }
    
    $sql = "
        SELECT 
            us.*,
            u.user_login,
            u.user_email,
            u.display_name,
            sa.external_username as account_username,
            sl.level_name,
            sl.level_slug as level_slug_from_table,
            sl.subscription_type
        FROM {$table_subscriptions} us
        LEFT JOIN {$wpdb->users} u ON us.user_id = u.ID
        LEFT JOIN {$table_accounts} sa ON us.account_id = sa.id
        LEFT JOIN {$table_levels} sl ON (sl.provider_slug = us.provider_slug AND sl.level_slug = us.level_slug)
        {$where}
        ORDER BY {$order_by}
    ";
    
    // Debug: log the SQL query if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG && $filter_provider) {
        error_log('[USER SUBSCRIPTIONS] SQL query: ' . $sql);
    }
    
    $all_subscriptions = $wpdb->get_results($sql, ARRAY_A);
    
    // Debug: log results count
    if (defined('WP_DEBUG') && WP_DEBUG && $filter_provider) {
        error_log('[USER SUBSCRIPTIONS] Found ' . count($all_subscriptions) . ' subscriptions with filter provider=' . $filter_provider);
        if (count($all_subscriptions) > 0) {
            $first_sub = $all_subscriptions[0];
            error_log('[USER SUBSCRIPTIONS] First subscription provider_slug: ' . ($first_sub['provider_slug'] ?? 'N/A'));
        }
    }
    
    // Group subscriptions by user_id (0 for unlinked)
    $grouped_subscriptions = [];
    foreach ($all_subscriptions as $sub) {
        $user_id = intval($sub['user_id'] ?? 0);
        $key = $user_id > 0 ? 'user_' . $user_id : 'unlinked_' . $sub['id'];
        
        if (!isset($grouped_subscriptions[$key])) {
            $grouped_subscriptions[$key] = [
                'user_id' => $user_id,
                'user_login' => $sub['user_login'] ?? null,
                'user_email' => $sub['user_email'] ?? null,
                'display_name' => $sub['display_name'] ?? null,
                'subscriptions' => [],
            ];
        }
        
        $grouped_subscriptions[$key]['subscriptions'][] = $sub;
    }
    
    // Count total groups (for pagination)
    $total = count($grouped_subscriptions);
    
    // Paginate grouped results
    $grouped_subscriptions = array_slice($grouped_subscriptions, $offset, $per_page, true);
    
    // Pagination
    $total_pages = ceil($total / $per_page);
    
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
        
        <!-- Filters -->
        <div class="subscription-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
                <input type="hidden" name="tab" value="user_subscriptions">
                
                <label for="filter_linked">
                    Linked Status:
                    <select name="filter_linked" id="filter_linked">
                        <option value="all" <?php selected($filter_linked, 'all'); ?>>All</option>
                        <option value="linked" <?php selected($filter_linked, 'linked'); ?>>Linked to WordPress</option>
                        <option value="unlinked" <?php selected($filter_linked, 'unlinked'); ?>>Not Linked</option>
                    </select>
                </label>
                
                <label for="filter_provider">
                    Provider (Global):
                    <select name="filter_provider" id="filter_provider">
                        <option value="">All Providers</option>
                        <?php foreach ($base_providers as $base) : ?>
                            <option value="<?php echo esc_attr($base); ?>" <?php selected($filter_provider, $base); ?>>
                                <?php echo esc_html(ucfirst($base)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label for="filter_provider_target">
                    Provider (Specific):
                    <select name="filter_provider_target" id="filter_provider_target" <?php echo !$filter_provider ? 'disabled' : ''; ?>>
                        <option value="">All Specific Providers</option>
                        <?php if ($filter_provider && isset($providers_by_base[$filter_provider])) : ?>
                            <?php foreach ($providers_by_base[$filter_provider] as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" <?php selected($filter_provider_target, $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const filterProvider = document.getElementById('filter_provider');
                    const filterProviderTarget = document.getElementById('filter_provider_target');
                    
                    if (filterProvider && filterProviderTarget) {
                        filterProvider.addEventListener('change', function() {
                            // Reload page with new filter
                            const url = new URL(window.location);
                            if (this.value) {
                                url.searchParams.set('filter_provider', this.value);
                                url.searchParams.delete('filter_provider_target'); // Reset specific filter
                            } else {
                                url.searchParams.delete('filter_provider');
                                url.searchParams.delete('filter_provider_target');
                            }
                            window.location.href = url.toString();
                        });
                    }
                });
                </script>
                
                <label for="filter_subscription_type">
                    Subscription Type:
                    <select name="filter_subscription_type" id="filter_subscription_type">
                        <option value="">All Types</option>
                        <option value="payant" <?php selected($filter_subscription_type, 'payant'); ?>>Paid</option>
                        <option value="gratuit" <?php selected($filter_subscription_type, 'gratuit'); ?>>Free/Gift</option>
                    </select>
                </label>
                
                <label for="s">
                    Search:
                    <input type="text" name="s" id="s" value="<?php echo esc_attr($search); ?>" placeholder="WordPress user or external username">
                </label>
                
                <input type="submit" class="button" value="Filter">
                <?php if ($filter_provider || $filter_provider_target || $search || $filter_linked !== 'all' || $filter_subscription_type) : ?>
                    <a href="<?php echo esc_url(remove_query_arg(['filter_provider', 'filter_provider_target', 's', 'filter_linked', 'filter_subscription_type', 'paged'])); ?>" class="button">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="subscription-stats">
            <p>
                <strong><?php echo number_format_i18n($total); ?></strong> user(s) with subscriptions
                <?php 
                $total_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_subscriptions} WHERE status = 'active'");
                $linked_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_subscriptions} WHERE status = 'active' AND user_id > 0");
                $unlinked_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_subscriptions} WHERE status = 'active' AND (user_id = 0 OR user_id IS NULL)");
                echo ' (' . number_format_i18n($total_subs) . ' total subscriptions, ' . number_format_i18n($linked_users) . ' linked users, ' . number_format_i18n($unlinked_subs) . ' unlinked subscriptions)';
                ?>
            </p>
        </div>
        
        <!-- Subscriptions table (grouped by user) -->
        <?php if (!empty($grouped_subscriptions)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>WordPress User</th>
                        <th>Subscriptions</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grouped_subscriptions as $group) : 
                        $is_linked = $group['user_id'] > 0;
                        $subscriptions_count = count($group['subscriptions']);
                    ?>
                        <tr>
                            <td>
                                <?php if ($is_linked && $group['display_name']) : ?>
                                    <strong><?php echo esc_html($group['display_name']); ?></strong><br>
                                    <small><?php echo esc_html($group['user_email']); ?></small><br>
                                    <small>User ID: <?php echo esc_html($group['user_id']); ?></small>
                                <?php else : ?>
                                    <em class="subscription-empty-state">Not Linked</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($group['subscriptions'])) : ?>
                                    <div class="subscription-subscriptions-list-container">
                                        <?php foreach ($group['subscriptions'] as $sub) : 
                                            $provider_name = '';
                                            foreach ($providers as $p) {
                                                if ($p['provider_slug'] === $sub['provider_slug']) {
                                                    $provider_name = $p['provider_name'];
                                                    break;
                                                }
                                            }
                                            
                                            // Get level name from joined data or fallback
                                            $level_name = $sub['level_name'] ?? $sub['level_slug'];
                                            $subscription_type = $sub['subscription_type'] ?? '';
                                            
                                            // Extract metadata
                                            $metadata = [];
                                            if (!empty($sub['metadata'])) {
                                                $metadata = is_string($sub['metadata']) ? json_decode($sub['metadata'], true) : $sub['metadata'];
                                                if (!is_array($metadata)) {
                                                    $metadata = [];
                                                }
                                            }
                                            // Get external username from metadata or from account_username (for linked accounts)
                                            $external_username = $metadata['external_username'] ?? $sub['account_username'] ?? '';
                                            $channel_name = $metadata['channel_name'] ?? $metadata['guild_name'] ?? '';
                                        ?>
                                            <div class="subscription-subscription-item">
                                                <strong><?php echo esc_html($provider_name ?: $sub['provider_slug']); ?></strong>
                                                <?php if ($channel_name) : ?>
                                                    <br><small>Channel: <?php echo esc_html($channel_name); ?></small>
                                                <?php endif; ?>
                                                <br><strong>Level:</strong> <?php echo esc_html($level_name); ?>
                                                <?php if ($subscription_type) : ?>
                                                    <small>(<?php echo esc_html($subscription_type); ?>)</small>
                                                <?php endif; ?>
                                                <?php if ($external_username) : ?>
                                                    <br><small>External: <?php echo esc_html($external_username); ?></small>
                                                <?php endif; ?>
                                                <?php if ($sub['started_at']) : ?>
                                                    <br><small>Started: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub['started_at']))); ?></small>
                                                <?php endif; ?>
                                                <?php if ($sub['expires_at']) : ?>
                                                    <br><small>Expires: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub['expires_at']))); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <em class="subscription-empty-state">No subscriptions</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                // Display subscription type (gratuit/payant) for each subscription
                                $types_display = [];
                                foreach ($group['subscriptions'] as $sub) {
                                    $metadata = [];
                                    if (!empty($sub['metadata'])) {
                                        $metadata = is_string($sub['metadata']) ? json_decode($sub['metadata'], true) : $sub['metadata'];
                                        if (!is_array($metadata)) {
                                            $metadata = [];
                                        }
                                    }
                                    $sub_type = $metadata['subscription_type'] ?? '';
                                    if ($sub_type) {
                                        $types_display[] = $sub_type === 'payant' ? 'Paid' : 'Free/Gift';
                                    }
                                }
                                if (!empty($types_display)) {
                                    echo esc_html(implode(', ', array_unique($types_display)));
                                } else {
                                    echo '<span class="subscription-empty-state">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($is_linked) : ?>
                                    <span class="status-active">✓ Linked</span>
                                <?php else : ?>
                                    <span class="status-inactive">✗ Not Linked</span>
                                <?php endif; ?>
                                <br>
                                <small><?php echo number_format_i18n($subscriptions_count); ?> subscription(s)</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $paged,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p>No subscriptions found. Click "Sync Subscriptions from Providers" to retrieve subscriptions from configured channels.</p>
        <?php endif; ?>
    </div>
    <?php
}
