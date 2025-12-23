<?php
// File: modules/subscription/admin/forms/subscription-channel-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_channel = isset($edit_channel) ? $edit_channel : null;
$channel_id = $edit_channel ? $edit_channel['id'] : 0;
$providers = isset($providers) ? $providers : admin_lab_get_subscription_providers();
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_channel_action'); ?>
    <input type="hidden" name="action" value="save_channel">
    <input type="hidden" name="channel_id_field" value="<?php echo esc_attr($channel_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="provider_slug">Provider</label></th>
            <td>
                <select name="provider_slug" id="provider_slug" required>
                    <option value="">Select...</option>
                    <?php foreach ($providers as $provider) : ?>
                        <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                <?php selected($edit_channel['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                            <?php echo esc_html($provider['provider_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="channel_name">Channel/Server Name</label></th>
            <td>
                <input type="text" name="channel_name" id="channel_name" 
                       value="<?php echo esc_attr($edit_channel['channel_name'] ?? ''); ?>" 
                       required>
                <p class="description">Display name for this channel/server.</p>
            </td>
        </tr>
        <tr>
            <th><label for="channel_identifier">Channel/Server Identifier</label></th>
            <td>
                <input type="text" name="channel_identifier" id="channel_identifier" 
                       value="<?php echo esc_attr($edit_channel['channel_identifier'] ?? ''); ?>" 
                       required>
                <p class="description">
                    Unique identifier for this channel/server (e.g., Twitch User ID, Discord Server ID, YouTube Channel ID).<br>
                    <strong>For Twitch:</strong> Use the broadcaster's User ID (numeric), not the username.
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="channel_type">Channel Type</label></th>
            <td>
                <input type="text" name="channel_type" id="channel_type" 
                       value="<?php echo esc_attr($edit_channel['channel_type'] ?? ''); ?>" 
                       placeholder="channel, server, etc.">
                <p class="description">Optional: Type of channel (e.g., "channel", "server", "page").</p>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?php checked($edit_channel['is_active'] ?? 0, 1); ?>>
                <p class="description">Only active channels/servers will be synchronized.</p>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="button">Cancel</a>
    </p>
</form>




