<?php
// File: modules/subscription/admin/tabs/subscription-tab-keycloak-identities.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Keycloak Identities
 * Lists users with their linked Keycloak identities and allows per-row synchronization
 */
function admin_lab_subscription_tab_keycloak_identities() {
    global $wpdb;
    
    $table_accounts = admin_lab_getTable('subscription_accounts');
    
    // Check if we're viewing a specific user detail
    $view_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($view_user_id > 0) {
        // Show user detail page
        $view_user = get_userdata($view_user_id);
        if (!$view_user) {
            wp_die('User not found.');
        }
        
        $view_identities = admin_lab_get_user_subscription_accounts($view_user_id);
        $keycloak_claims = [];
        if (function_exists('openid_connect_generic_get_user_claim')) {
            $keycloak_claims = openid_connect_generic_get_user_claim($view_user_id);
        }
        
        // Action: Force sync
        if (isset($_GET['force_sync']) && check_admin_referer('force_sync_' . $view_user_id)) {
            // Extract and sync identities from Keycloak claims
            if (function_exists('admin_lab_extract_keycloak_identities')) {
                admin_lab_extract_keycloak_identities($view_user_id, $keycloak_claims);
            }
            // Also trigger the standard hook
            do_action('openid-connect-generic-update-user-using-current-claim', $view_user, $keycloak_claims);
            echo '<div class="notice notice-success"><p>Synchronization forced. Identities updated from Keycloak claims.</p></div>';
            $view_identities = admin_lab_get_user_subscription_accounts($view_user_id); // Refresh
            $keycloak_claims = function_exists('openid_connect_generic_get_user_claim') ? openid_connect_generic_get_user_claim($view_user_id) : [];
        }
        
        ?>
        <div class="wrap">
            <h2>Keycloak Identity Details</h2>
            
            <p>
                <a href="<?php echo esc_url(remove_query_arg(['user_id', 'force_sync'])); ?>" class="button">← Back to List</a>
            </p>
            
            <h3>User: <?php echo esc_html($view_user->display_name . ' (' . $view_user->user_email . ')'); ?></h3>
            
            <form method="get" action="" class="subscription-form-section">
                <?php wp_nonce_field('force_sync_' . $view_user_id); ?>
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
                <input type="hidden" name="tab" value="keycloak_identities">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($view_user_id); ?>">
                <input type="submit" class="button button-secondary" name="force_sync" value="Force Synchronization">
            </form>
            
            <?php if (!empty($view_identities)) : ?>
                <h4>Linked Accounts</h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>External ID</th>
                            <th>External Username</th>
                            <th>Keycloak Identity ID</th>
                            <th>Access Token</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($view_identities as $identity) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($identity['provider_slug']); ?></strong></td>
                                <td><code><?php echo esc_html($identity['external_user_id']); ?></code></td>
                                <td><?php echo esc_html($identity['external_username'] ?: '-'); ?></td>
                                <td><code><?php echo esc_html($identity['keycloak_identity_id'] ?: '-'); ?></code></td>
                                <td>
                                    <span class="status-active">✓ Active</span>
                                </td>
                                <td><?php echo $identity['is_active'] ? '<span class="status-active">✓ Active</span>' : '<span class="status-inactive">✗ Inactive</span>'; ?></td>
                                <td><?php echo $identity['last_sync_at'] ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($identity['last_sync_at']))) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No linked accounts found for this user.</p>
            <?php endif; ?>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG && !empty($keycloak_claims)) : ?>
                <h4>Keycloak Claims (Debug)</h4>
                <pre class="subscription-debug-section"><?php echo esc_html(print_r($keycloak_claims, true)); ?></pre>
            <?php endif; ?>
        </div>
        <?php
        return;
    }
    
    // Main list view
    // Pagination
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    
    // Search
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Action: Sync single user
    if (isset($_GET['sync_user']) && check_admin_referer('sync_user_' . $_GET['sync_user'])) {
        $user_id = intval($_GET['sync_user']);
        $user = get_userdata($user_id);
        if ($user) {
            // Trigger sync
            if (function_exists('openid_connect_generic_get_user_claim')) {
                $claims = openid_connect_generic_get_user_claim($user_id);
                // Extract identities from claims
                if (function_exists('admin_lab_extract_keycloak_identities')) {
                    admin_lab_extract_keycloak_identities($user_id, $claims);
                }
                do_action('openid-connect-generic-update-user-using-current-claim', $user, $claims);
            }
            echo '<div class="notice notice-success"><p>User synchronized.</p></div>';
        }
    }
    
    // Get users with linked accounts
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $where_conditions[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
    }
    
    $where = !empty($where_conditions) ? 'WHERE ' . implode(' OR ', $where_conditions) : '';
    
    // Count total
    $count_sql = "
        SELECT COUNT(DISTINCT sa.user_id)
        FROM {$table_accounts} sa
        LEFT JOIN {$wpdb->users} u ON sa.user_id = u.ID
        {$where}
    ";
    if ($params) {
        $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));
    } else {
        $total = (int) $wpdb->get_var($count_sql);
    }
    
    // Get users with identity counts (only count discord, twitch, youtube)
    $sql = "
        SELECT 
            sa.user_id,
            u.user_login,
            u.user_email,
            u.display_name,
            SUM(CASE WHEN sa.provider_slug IN ('discord', 'twitch', 'youtube') THEN 1 ELSE 0 END) as identity_count,
            MAX(sa.last_sync_at) as last_sync_at
        FROM {$table_accounts} sa
        LEFT JOIN {$wpdb->users} u ON sa.user_id = u.ID
        {$where}
        GROUP BY sa.user_id
        HAVING identity_count > 0
        ORDER BY u.display_name ASC
        LIMIT %d OFFSET %d
    ";
    
    $params_with_limit = array_merge($params, [$per_page, $offset]);
    $users = $wpdb->get_results($wpdb->prepare($sql, $params_with_limit), ARRAY_A);
    
    // Pagination
    $total_pages = ceil($total / $per_page);
    
    ?>
    <div class="wrap">
        <h2>Keycloak Identities</h2>
        <p class="description">List of users with linked Keycloak identities (Discord, Twitch, YouTube). Click on a user to view details or synchronize.</p>
        
        <!-- Search -->
        <div class="subscription-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
                <input type="hidden" name="tab" value="keycloak_identities">
                <label>
                    Search users:
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Name, email, login...">
                </label>
                <input type="submit" class="button" value="Search">
                <?php if ($search) : ?>
                    <a href="<?php echo esc_url(remove_query_arg('s')); ?>" class="button">Reset</a>
                <?php endif; ?>
            </form>
        </div>
        
        <p><strong><?php echo number_format_i18n($total); ?></strong> users with linked identities</p>
        
        <?php if (!empty($users)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Identities Count</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user_data) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user_data['display_name'] ?: $user_data['user_login']); ?></strong><br>
                                <small><?php echo esc_html($user_data['user_email']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($user_data['identity_count']); ?></strong>
                            </td>
                            <td>
                                <?php if ($user_data['last_sync_at']) : ?>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user_data['last_sync_at']))); ?>
                                <?php else : ?>
                                    <em class="subscription-empty-state">Never</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['user_id' => $user_data['user_id']])); ?>" class="button button-small">View Details</a>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('sync_user', $user_data['user_id']), 'sync_user_' . $user_data['user_id'])); ?>" 
                                   class="button button-small">Sync</a>
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
            <p>No users found.</p>
        <?php endif; ?>
    </div>
    <?php
}
