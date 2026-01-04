<?php
// File: modules/subscription/admin/forms/subscription-type-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_level = isset($edit_level) ? $edit_level : null;
$level_id = $edit_level ? (int) ($edit_level['id'] ?? 0) : 0;
$tiers = isset($tiers) ? $tiers : admin_lab_get_subscription_tiers();
$providers = admin_lab_get_subscription_providers();
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_type_action'); ?>
    <input type="hidden" name="action" value="save_subscription_type">
    <input type="hidden" name="level_id" value="<?php echo esc_attr($level_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="provider_slug">Provider</label></th>
            <td>
                <select name="provider_slug" id="provider_slug" required <?php echo $level_id > 0 ? 'disabled' : ''; ?>>
                    <option value="">Select...</option>
                    <?php foreach ($providers as $provider) : 
                        // For Tipeee, show it; for others, show if they allow manual creation
                        $show_provider = false;
                        if (strpos($provider['provider_slug'], 'tipeee') === 0) {
                            $show_provider = true;
                        } elseif (strpos($provider['provider_slug'], 'patreon') === 0 || strpos($provider['provider_slug'], 'youtube') === 0) {
                            $show_provider = true;
                        }
                        
                        if ($show_provider) :
                    ?>
                        <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                <?php selected($edit_level['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                            <?php echo esc_html($provider['provider_name']); ?>
                        </option>
                    <?php 
                        endif;
                    endforeach; ?>
                </select>
                <?php if ($level_id > 0) : ?>
                    <input type="hidden" name="provider_slug" value="<?php echo esc_attr($edit_level['provider_slug']); ?>">
                    <p class="description">Provider cannot be changed after creation.</p>
                <?php else : ?>
                    <p class="description">Select the provider for this subscription type (e.g., Tipeee).</p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="level_slug">Slug</label></th>
            <td>
                <input type="text" name="level_slug" id="level_slug" 
                       value="<?php echo esc_attr($edit_level['level_slug'] ?? ''); ?>"
                       placeholder="e.g., tipeee_basic" 
                       required 
                       pattern="[a-z0-9_]+"
                       <?php echo $level_id > 0 ? 'readonly' : ''; ?>>
                <?php if ($level_id > 0) : ?>
                    <p class="description">Slug cannot be changed after creation.</p>
                <?php else : ?>
                    <p class="description">Unique identifier for this subscription type (lowercase, letters, numbers, underscores only).</p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="level_name">Name</label></th>
            <td>
                <input type="text" name="level_name" id="level_name" 
                       value="<?php echo esc_attr($edit_level['level_name'] ?? ''); ?>"
                       placeholder="e.g., Tipeur Basic" 
                       required>
                <p class="description">Display name for this subscription type.</p>
            </td>
        </tr>
        <tr>
            <th><label for="level_tier">Tier</label></th>
            <td>
                <select name="level_tier" id="level_tier">
                    <option value="">None</option>
                    <?php foreach ($tiers as $tier) : 
                        // level_tier is stored as tier slug in the database
                        $current_tier = $edit_level['level_tier'] ?? '';
                    ?>
                        <option value="<?php echo esc_attr($tier['tier_slug']); ?>" 
                                <?php selected($current_tier, $tier['tier_slug']); ?>>
                            <?php echo esc_html($tier['tier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Optional: Link this subscription type to an internal tier.</p>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?php checked($edit_level['is_active'] ?? 1, 1); ?>>
                <p class="description">Active subscription types are available for mapping and synchronization.</p>
            </td>
        </tr>
        <?php 
        // Show Discord Role ID field for Tipeee and YouTube No API providers
        $is_tipeee = !empty($edit_level['provider_slug']) && strpos($edit_level['provider_slug'], 'tipeee') === 0;
        $is_youtube_no_api = !empty($edit_level['provider_slug']) && strpos($edit_level['provider_slug'], 'youtube_no_api') === 0;
        $show_discord_role = $is_tipeee || $is_youtube_no_api;
        $current_discord_role_id = $edit_level['discord_role_id'] ?? '';
        ?>
        <tr id="discord_role_id_row" style="display: <?php echo $show_discord_role ? '' : 'none'; ?>;">
            <th><label for="discord_role_id">Discord Role ID</label></th>
            <td>
                <input type="text" name="discord_role_id" id="discord_role_id" 
                       value="<?php echo esc_attr($current_discord_role_id); ?>"
                       placeholder="e.g., 1112753968179851405" 
                       pattern="[0-9]+">
                <p class="description">Discord Role ID to associate with this subscription type. Members with this role will be synced as subscribers.</p>
            </td>
        </tr>
        <!-- Hidden field to ensure discord_role_id is always sent, even when row is hidden -->
        <input type="hidden" name="discord_role_id_hidden" id="discord_role_id_hidden" value="<?php echo esc_attr($current_discord_role_id); ?>">
    </table>
    
    <script>
    // Show/hide Discord Role ID field based on provider selection
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('provider_slug');
        const discordRoleRow = document.getElementById('discord_role_id_row');
        const discordRoleInput = document.getElementById('discord_role_id');
        const discordRoleHidden = document.getElementById('discord_role_id_hidden');
        
        function toggleDiscordRoleField() {
            const selectedProvider = providerSelect ? providerSelect.value : '';
            const isTipeee = selectedProvider && selectedProvider.indexOf('tipeee') === 0;
            const isYoutubeNoApi = selectedProvider && selectedProvider.indexOf('youtube_no_api') === 0;
            const showField = isTipeee || isYoutubeNoApi;
            if (discordRoleRow) {
                discordRoleRow.style.display = showField ? '' : 'none';
            }
            // Make field required/optional based on provider
            if (discordRoleInput) {
                discordRoleInput.required = showField;
            }
        }
        
        // Sync hidden field with visible field
        if (discordRoleInput && discordRoleHidden) {
            discordRoleInput.addEventListener('input', function() {
                discordRoleHidden.value = this.value;
            });
            discordRoleInput.addEventListener('change', function() {
                discordRoleHidden.value = this.value;
            });
        }
        
        if (providerSelect) {
            providerSelect.addEventListener('change', toggleDiscordRoleField);
            toggleDiscordRoleField(); // Initial check
        }
    });
    </script>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(add_query_arg('tab', 'subscription_types', remove_query_arg('edit_type'))); ?>" class="button">Cancel</a>
    </p>
</form>

