<?php
// File: modules/subscription/admin/forms/subscription-provider-account-type-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_mapping = isset($edit_mapping) ? $edit_mapping : null;
$mapping_id = $edit_mapping ? $edit_mapping['id'] : 0;
$providers = isset($providers) ? $providers : [];
$account_types = isset($account_types) ? $account_types : admin_lab_get_registered_account_types();
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_provider_account_type_action'); ?>
    <input type="hidden" name="action" value="save_mapping">
    <input type="hidden" name="mapping_id" value="<?php echo esc_attr($mapping_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="provider_slug">Provider</label></th>
            <td>
                <select name="provider_slug" id="provider_slug" required>
                    <option value="">Select...</option>
                    <?php foreach ($providers as $provider) : ?>
                        <option value="<?php echo esc_attr($provider['slug']); ?>" 
                                <?php selected($edit_mapping['provider_slug'] ?? '', $provider['slug']); ?>>
                            <?php echo esc_html($provider['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Select the provider. The account type is defined by the provider, not by the tier.</p>
            </td>
        </tr>
        <tr>
            <th><label for="account_type_slug">Account Type</label></th>
            <td>
                <select name="account_type_slug" id="account_type_slug" required>
                    <option value="">Select...</option>
                    <?php foreach ($account_types as $slug => $type) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" 
                                <?php selected($edit_mapping['account_type_slug'] ?? '', $slug); ?>>
                            <?php echo esc_html($type['label'] ?? $slug); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">WordPress account type to assign to users with subscriptions from this provider.</p>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?php checked($edit_mapping['is_active'] ?? 1, 1); ?>>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="button">Cancel</a>
    </p>
</form>



