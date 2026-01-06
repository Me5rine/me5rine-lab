<?php
// File: modules/subscription/admin/classes/subscription-user-subscriptions-list-table.php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for User Subscriptions
 */
class Subscription_User_Subscriptions_List_Table extends WP_List_Table {

    protected $providers = [];
    protected $providers_by_base = [];
    
    public function __construct($args = []) {
        parent::__construct([
            'singular' => 'subscription',
            'plural'   => 'subscriptions',
            'ajax'     => false
        ]);
        
        // Get providers for filters
        $this->providers = admin_lab_get_subscription_providers();
        
        // Group providers by base provider
        $base_providers = ['twitch', 'youtube_no_api', 'youtube', 'discord', 'tipeee', 'patreon'];
        foreach ($this->providers as $provider) {
            $base = null;
            foreach ($base_providers as $base_provider) {
                if (strpos($provider['provider_slug'], $base_provider) === 0) {
                    $base = $base_provider;
                    break;
                }
            }
            if ($base) {
                if (!isset($this->providers_by_base[$base])) {
                    $this->providers_by_base[$base] = [];
                }
                $this->providers_by_base[$base][] = $provider;
            }
        }
    }

    public function get_items_per_page_option() {
        return 'subscription_user_subscriptions_per_page';
    }

    public function get_columns() {
        $columns = [
            'wp_user'       => __('WordPress User', 'me5rine-lab'),
            'subscriptions' => __('Subscriptions', 'me5rine-lab'),
            'type'          => __('Type', 'me5rine-lab'),
            'status'        => __('Status', 'me5rine-lab'),
        ];
        return $columns;
    }

    public function get_sortable_columns() {
        return [
            'wp_user'       => ['display_name', false],
            'subscriptions' => ['subscriptions_count', false],
            'type'          => ['subscription_type', false],
            'status'        => ['is_linked', false],
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'wp_user':
                $is_linked = $item['user_id'] > 0;
                if ($is_linked && !empty($item['display_name'])) {
                    $output = '<strong>' . esc_html($item['display_name']) . '</strong><br>';
                    $output .= '<small>' . esc_html($item['user_email']) . '</small><br>';
                    $output .= '<small>User ID: ' . esc_html($item['user_id']) . '</small>';
                    return $output;
                }
                return '<em class="subscription-empty-state">Not Linked</em>';
            
            case 'subscriptions':
                if (!empty($item['subscriptions'])) {
                    $output = '<div class="subscription-subscriptions-list-container">';
                    foreach ($item['subscriptions'] as $sub) {
                        $provider_slug = $sub['provider_slug'] ?? '';
                        
                        // Get base provider name for display (YouTube, Tipeee, etc.)
                        $base_provider_name = '';
                        if (strpos($provider_slug, 'twitch') === 0) {
                            $base_provider_name = 'Twitch';
                        } elseif (strpos($provider_slug, 'youtube_no_api') === 0) {
                            $base_provider_name = 'YouTube (No API)';
                        } elseif (strpos($provider_slug, 'youtube') === 0) {
                            $base_provider_name = 'YouTube';
                        } elseif (strpos($provider_slug, 'discord') === 0) {
                            $base_provider_name = 'Discord';
                        } elseif (strpos($provider_slug, 'tipeee') === 0) {
                            $base_provider_name = 'Tipeee';
                        } elseif (strpos($provider_slug, 'patreon') === 0) {
                            $base_provider_name = 'Patreon';
                        } else {
                            // Fallback: try to get from providers list
                            foreach ($this->providers as $p) {
                                if ($p['provider_slug'] === $provider_slug) {
                                    $base_provider_name = $p['provider_name'];
                                    break;
                                }
                            }
                        }
                        
                        $level_name = $sub['level_name'] ?? $sub['level_slug'] ?? '';
                        $subscription_type = $sub['subscription_type'] ?? '';
                        
                        $metadata = [];
                        if (!empty($sub['metadata'])) {
                            $metadata = is_string($sub['metadata']) ? json_decode($sub['metadata'], true) : $sub['metadata'];
                            if (!is_array($metadata)) {
                                $metadata = [];
                            }
                        }
                        
                        $external_username = $metadata['external_username'] ?? $sub['account_username'] ?? '';
                        $channel_name = $metadata['channel_name'] ?? $metadata['guild_name'] ?? '';
                        
                        $output .= '<div class="subscription-subscription-item">';
                        $output .= '<strong>' . esc_html($base_provider_name ?: $provider_slug) . '</strong>';
                        if ($channel_name) {
                            $output .= '<br><small>Channel: ' . esc_html($channel_name) . '</small>';
                        }
                        $output .= '<br><strong>Level:</strong> ' . esc_html($level_name);
                        if ($subscription_type) {
                            $output .= ' <small>(' . esc_html($subscription_type) . ')</small>';
                        }
                        if ($external_username) {
                            $output .= '<br><small>External: ' . esc_html($external_username) . '</small>';
                        }
                        if (!empty($sub['started_at'])) {
                            $output .= '<br><small>Started: ' . esc_html(date_i18n(get_option('date_format'), strtotime($sub['started_at']))) . '</small>';
                        }
                        if (!empty($sub['expires_at'])) {
                            $output .= '<br><small>Expires: ' . esc_html(date_i18n(get_option('date_format'), strtotime($sub['expires_at']))) . '</small>';
                        }
                        $output .= '</div>';
                    }
                    $output .= '</div>';
                    return $output;
                }
                return '<em class="subscription-empty-state">No subscriptions</em>';
            
            case 'type':
                $types_display = [];
                if (!empty($item['subscriptions'])) {
                    foreach ($item['subscriptions'] as $sub) {
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
                }
                if (!empty($types_display)) {
                    return esc_html(implode(', ', array_unique($types_display)));
                }
                return '<span class="subscription-empty-state">-</span>';
            
            case 'status':
                $is_linked = $item['user_id'] > 0;
                $subscriptions_count = count($item['subscriptions'] ?? []);
                $output = '';
                if ($is_linked) {
                    $output .= '<span class="admin-lab-status-active">✓ Linked</span>';
                } else {
                    $output .= '<span class="admin-lab-status-inactive">✗ Not Linked</span>';
                }
                $output .= '<br><small>' . number_format_i18n($subscriptions_count) . ' subscription(s)</small>';
                return $output;
            
            default:
                return '';
        }
    }

    public function prepare_items() {
        global $wpdb;
        
        $table_subscriptions = admin_lab_getTable('user_subscriptions');
        $table_accounts = admin_lab_getTable('keycloak_accounts');
        $table_levels = admin_lab_getTable('subscription_levels');
        
        // Pagination
        $per_page = $this->get_items_per_page($this->get_items_per_page_option(), 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Filters
        $filter_linked = isset($_GET['filter_linked']) ? sanitize_text_field($_GET['filter_linked']) : 'all';
        $filter_linked = in_array($filter_linked, ['all', 'linked', 'unlinked']) ? $filter_linked : 'all';
        $filter_provider = isset($_GET['filter_provider']) ? sanitize_text_field($_GET['filter_provider']) : '';
        $filter_provider_target = isset($_GET['filter_provider_target']) ? sanitize_text_field($_GET['filter_provider_target']) : '';
        $filter_subscription_type = isset($_GET['filter_subscription_type']) ? sanitize_text_field($_GET['filter_subscription_type']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Build WHERE conditions
        $where_conditions = ["us.status = 'active'"];
        $params = [];
        
        if ($filter_linked === 'linked') {
            $where_conditions[] = "us.user_id > 0";
        } elseif ($filter_linked === 'unlinked') {
            $where_conditions[] = "(us.user_id = 0 OR us.user_id IS NULL)";
        }
        
        if ($filter_provider) {
            // For base providers (twitch, youtube_no_api, tipeee, etc.), filter by LIKE to include all specific providers
            // For specific providers, use exact match
            if (in_array($filter_provider, ['twitch', 'youtube_no_api', 'youtube', 'discord', 'tipeee', 'patreon'])) {
                $where_conditions[] = $wpdb->prepare("us.provider_slug LIKE %s", $filter_provider . '%');
            } else {
                $where_conditions[] = $wpdb->prepare("us.provider_slug = %s", $filter_provider);
            }
        }
        
        if ($filter_provider_target) {
            $where_conditions[] = $wpdb->prepare("us.provider_target_slug = %s", $filter_provider_target);
        }
        
        if ($search) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_conditions[] = $wpdb->prepare(
                "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s OR us.external_subscription_id LIKE %s OR JSON_EXTRACT(us.metadata, '$.external_username') LIKE %s)",
                $search_like, $search_like, $search_like, $search_like, $search_like
            );
        }
        
        if ($filter_subscription_type && in_array($filter_subscription_type, ['gratuit', 'payant'])) {
            $where_conditions[] = $wpdb->prepare(
                "JSON_EXTRACT(us.metadata, '$.subscription_type') = %s",
                $filter_subscription_type
            );
        }
        
        $where = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get all subscriptions (we'll group them after)
        $order_by = "us.user_id DESC, us.provider_slug ASC, us.started_at DESC";
        
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
        
        $all_subscriptions = $wpdb->get_results($sql, ARRAY_A);
        
        // Group subscriptions by user_id
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
                    'subscriptions_count' => 0,
                    'subscription_type' => '',
                    'is_linked' => 0,
                ];
            }
            
            $grouped_subscriptions[$key]['subscriptions'][] = $sub;
        }
        
        // Calculate sortable fields for each group
        foreach ($grouped_subscriptions as $key => &$group) {
            $group['subscriptions_count'] = count($group['subscriptions']);
            $group['is_linked'] = $group['user_id'] > 0 ? 1 : 0;
            
            // Get primary subscription type (first non-empty type found)
            $types = [];
            foreach ($group['subscriptions'] as $sub) {
                $metadata = [];
                if (!empty($sub['metadata'])) {
                    $metadata = is_string($sub['metadata']) ? json_decode($sub['metadata'], true) : $sub['metadata'];
                    if (!is_array($metadata)) {
                        $metadata = [];
                    }
                }
                $sub_type = $metadata['subscription_type'] ?? '';
                if ($sub_type && !in_array($sub_type, $types)) {
                    $types[] = $sub_type;
                }
            }
            $group['subscription_type'] = !empty($types) ? $types[0] : '';
        }
        unset($group);
        
        // Convert to array for sorting
        $items = array_values($grouped_subscriptions);
        
        // Get sorting parameters
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'display_name';
        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Sort items
        usort($items, function($a, $b) use ($orderby, $order) {
            $result = 0;
            
            switch ($orderby) {
                case 'subscriptions_count':
                    $result = $a['subscriptions_count'] - $b['subscriptions_count'];
                    break;
                
                case 'subscription_type':
                    $a_type = $a['subscription_type'] ?? '';
                    $b_type = $b['subscription_type'] ?? '';
                    $result = strcmp($a_type, $b_type);
                    break;
                
                case 'is_linked':
                    $result = $a['is_linked'] - $b['is_linked'];
                    // If same linked status, sort by subscriptions count
                    if ($result === 0) {
                        $result = $a['subscriptions_count'] - $b['subscriptions_count'];
                    }
                    break;
                
                case 'display_name':
                default:
                    $a_name = $a['display_name'] ?? $a['user_login'] ?? '';
                    $b_name = $b['display_name'] ?? $b['user_login'] ?? '';
                    $result = strcmp($a_name, $b_name);
                    break;
            }
            
            return $order === 'DESC' ? -$result : $result;
        });
        
        // Count total before pagination
        $total_items = count($items);
        
        // Paginate
        $items = array_slice($items, $offset, $per_page);
        
        $this->items = $items;
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
        
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    public function no_items() {
        _e('No subscriptions found. Click "Sync Subscriptions from Providers" to retrieve subscriptions from configured channels.', 'me5rine-lab');
    }

    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        $filter_linked = isset($_GET['filter_linked']) ? sanitize_text_field($_GET['filter_linked']) : 'all';
        $filter_provider = isset($_GET['filter_provider']) ? sanitize_text_field($_GET['filter_provider']) : '';
        $filter_provider_target = isset($_GET['filter_provider_target']) ? sanitize_text_field($_GET['filter_provider_target']) : '';
        $filter_subscription_type = isset($_GET['filter_subscription_type']) ? sanitize_text_field($_GET['filter_subscription_type']) : '';
        $base_providers = ['twitch', 'youtube_no_api', 'youtube', 'discord', 'tipeee', 'patreon'];
        
        ?>
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'admin-lab-subscription'); ?>">
            <input type="hidden" name="tab" value="user_subscriptions">
            <div class="alignleft actions">
                <label for="filter_linked">
                    <?php _e('Linked Status:', 'me5rine-lab'); ?>
                    <select name="filter_linked" id="filter_linked">
                        <option value="all" <?php selected($filter_linked, 'all'); ?>><?php _e('All', 'me5rine-lab'); ?></option>
                        <option value="linked" <?php selected($filter_linked, 'linked'); ?>><?php _e('Linked to WordPress', 'me5rine-lab'); ?></option>
                        <option value="unlinked" <?php selected($filter_linked, 'unlinked'); ?>><?php _e('Not Linked', 'me5rine-lab'); ?></option>
                    </select>
                </label>
                
                <label for="filter_provider">
                    <?php _e('Provider (Global):', 'me5rine-lab'); ?>
                    <select name="filter_provider" id="filter_provider">
                        <option value=""><?php _e('All Providers', 'me5rine-lab'); ?></option>
                        <?php foreach ($base_providers as $base) : 
                            $display_name = ucfirst($base);
                            if ($base === 'youtube_no_api') {
                                $display_name = 'YouTube No API';
                            }
                        ?>
                            <option value="<?php echo esc_attr($base); ?>" <?php selected($filter_provider, $base); ?>>
                                <?php echo esc_html($display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label for="filter_provider_target">
                    <?php _e('Provider (Specific):', 'me5rine-lab'); ?>
                    <select name="filter_provider_target" id="filter_provider_target" <?php echo !$filter_provider ? 'disabled' : ''; ?>>
                        <option value=""><?php _e('All Specific Providers', 'me5rine-lab'); ?></option>
                        <?php if ($filter_provider && isset($this->providers_by_base[$filter_provider])) : ?>
                            <?php foreach ($this->providers_by_base[$filter_provider] as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" <?php selected($filter_provider_target, $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>
                
                <label for="filter_subscription_type">
                    <?php _e('Subscription Type:', 'me5rine-lab'); ?>
                    <select name="filter_subscription_type" id="filter_subscription_type">
                        <option value=""><?php _e('All Types', 'me5rine-lab'); ?></option>
                        <option value="payant" <?php selected($filter_subscription_type, 'payant'); ?>><?php _e('Paid', 'me5rine-lab'); ?></option>
                        <option value="gratuit" <?php selected($filter_subscription_type, 'gratuit'); ?>><?php _e('Free/Gift', 'me5rine-lab'); ?></option>
                    </select>
                </label>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const filterProvider = document.getElementById('filter_provider');
                    const filterProviderTarget = document.getElementById('filter_provider_target');
                    
                    if (filterProvider && filterProviderTarget) {
                        filterProvider.addEventListener('change', function() {
                            if (!this.value) {
                                filterProviderTarget.disabled = true;
                                filterProviderTarget.value = '';
                            } else {
                                filterProviderTarget.disabled = false;
                            }
                        });
                    }
                });
                </script>
                
                <?php submit_button(__('Filter', 'me5rine-lab'), 'secondary', 'filter_action', false); ?>
                <?php
                if ($filter_provider || $filter_provider_target || $filter_linked !== 'all' || $filter_subscription_type) {
                    $reset_url = remove_query_arg(['filter_provider', 'filter_provider_target', 'filter_linked', 'filter_subscription_type', 'paged', 's']);
                    echo ' <a href="' . esc_url($reset_url) . '" class="button">' . __('Reset', 'me5rine-lab') . '</a>';
                }
                ?>
            </div>
        </form>
        <?php
    }

    protected function get_views() {
        return [];
    }
}

