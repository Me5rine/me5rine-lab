<?php
// File: modules/subscription/admin/forms/subscription-tier-form.php

if (!defined('ABSPATH')) exit;

$edit_tier = $edit_tier ?? null;
$tier_id = $edit_tier ? $edit_tier['id'] : 0;
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_tier_action'); ?>
    <input type="hidden" name="action" value="save_tier">
    <input type="hidden" name="tier_id" value="<?php echo esc_attr($tier_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="tier_slug">Tier Slug</label></th>
            <td>
                <input type="text" name="tier_slug" id="tier_slug" 
                       value="<?php echo esc_attr($edit_tier['tier_slug'] ?? ''); ?>" 
                       required <?php echo $tier_id > 0 ? 'readonly' : ''; ?>>
                <p class="description">Unique identifier (e.g., bronze, silver, gold). Cannot be changed after creation.</p>
            </td>
        </tr>
        <tr>
            <th><label for="tier_name">Tier Name</label></th>
            <td>
                <input type="text" name="tier_name" id="tier_name" 
                       value="<?php echo esc_attr($edit_tier['tier_name'] ?? ''); ?>" 
                       required>
                <p class="description">Display name (e.g., Bronze, Silver, Gold).</p>
            </td>
        </tr>
        <tr>
            <th><label for="tier_order">Order</label></th>
            <td>
                <input type="number" name="tier_order" id="tier_order" 
                       value="<?php echo esc_attr($edit_tier['tier_order'] ?? 0); ?>" 
                       min="0">
                <p class="description">Display order (lower numbers appear first).</p>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?php checked($edit_tier['is_active'] ?? 1, 1); ?>>
                <p class="description">Only active tiers can be linked to subscription types.</p>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit_tier')); ?>" class="button">Cancel</a>
    </p>
</form>
