<?php
// File: modules/subscription/admin/forms/subscription-level-mapping-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_level = isset($edit_level) ? $edit_level : null;
$mapping_id = $edit_level ? $edit_level['id'] : 0;
$tiers = isset($tiers) ? $tiers : admin_lab_get_subscription_tiers();
$twitch_providers = isset($twitch_providers) ? $twitch_providers : [];
$discord_providers = isset($discord_providers) ? $discord_providers : [];
$other_providers = isset($other_providers) ? $other_providers : [];
$types_by_provider = isset($types_by_provider) ? $types_by_provider : [];
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_level_action'); ?>
    <input type="hidden" name="action" value="save_level">
    <input type="hidden" name="mapping_id" value="<?php echo esc_attr($mapping_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="tier_slug">Internal Level (Tier)</label></th>
            <td>
                <select name="tier_slug" id="tier_slug" required>
                    <option value="">Select...</option>
                    <?php foreach ($tiers as $tier) : ?>
                        <option value="<?php echo esc_attr($tier['tier_slug']); ?>" 
                                <?php selected($edit_level['tier_slug'] ?? '', $tier['tier_slug']); ?>>
                            <?php echo esc_html($tier['tier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Select the internal tier to link.</p>
            </td>
        </tr>
        <tr>
            <th><label for="provider_slug">Provider</label></th>
            <td>
                <select name="provider_slug" id="provider_slug" required onchange="updateSubscriptionTypes()">
                    <option value="">Select...</option>
                    <?php 
                    // For Twitch: show first provider but value will be normalized to 'twitch'
                    if (!empty($twitch_providers)) : ?>
                        <optgroup label="Twitch (Global)">
                            <?php foreach ($twitch_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="twitch"
                                        <?php selected($edit_level['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?> (applies to all Twitch providers)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php 
                    // For Discord: show first provider but value will be normalized to 'discord'
                    if (!empty($discord_providers)) : ?>
                        <optgroup label="Discord (Global)">
                            <?php foreach ($discord_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="discord"
                                        <?php selected($edit_level['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?> (applies to all Discord providers)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php 
                    // For others: show each provider
                    if (!empty($other_providers)) : ?>
                        <optgroup label="Other Providers (Per Provider)">
                            <?php foreach ($other_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="<?php echo esc_attr($provider['provider_slug']); ?>"
                                        <?php selected($edit_level['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <p class="description">
                    <strong>Note:</strong> For Twitch and Discord, any provider selection will link globally to all providers of that type. 
                    For other providers (YouTube, Patreon, Tipeee), select the specific provider.
                </p>
            </td>
        </tr>
        <tr>
            <th><label for="subscription_type_slug">Subscription Type</label></th>
            <td>
                <select name="subscription_type_slug" id="subscription_type_slug" required>
                    <option value="">Select provider first...</option>
                </select>
                <p class="description">Select the subscription type from the provider to link to the tier.</p>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?php checked($edit_level['is_active'] ?? 1, 1); ?>>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="button">Cancel</a>
    </p>
</form>

<script>
const typesByProvider = <?php echo json_encode(array_map(function($types) {
    return array_map(function($t) {
        return ['level_slug' => $t['level_slug'], 'level_name' => $t['level_name']];
    }, $types);
}, $types_by_provider)); ?>;
const currentProvider = '<?php echo esc_js($edit_level['provider_slug'] ?? ''); ?>';
const currentProviderNormalized = '<?php echo esc_js($edit_level['provider_slug_normalized'] ?? ($edit_level['provider_slug'] ?? '')); ?>';
const currentType = '<?php echo esc_js($edit_level['subscription_type_slug'] ?? ''); ?>';

function updateSubscriptionTypes() {
    const providerSelect = document.getElementById('provider_slug');
    const provider = providerSelect.value;
    const selectedOption = providerSelect.options[providerSelect.selectedIndex];
    const normalizedProvider = selectedOption ? selectedOption.getAttribute('data-normalized') : null;
    
    const typeSelect = document.getElementById('subscription_type_slug');
    typeSelect.innerHTML = '<option value="">Select...</option>';
    
    // Use normalized provider (twitch/discord) or the provider itself
    const checkProvider = normalizedProvider || provider;
    
    if (checkProvider && typesByProvider[checkProvider]) {
        typesByProvider[checkProvider].forEach(type => {
            const option = document.createElement('option');
            option.value = type.level_slug;
            option.textContent = type.level_name;
            // Check if this is the current type being edited
            if (currentType === type.level_slug) {
                option.selected = true;
            }
            typeSelect.appendChild(option);
        });
    }
}

// Initialize on page load
if (currentProvider) {
    document.addEventListener('DOMContentLoaded', function() {
        updateSubscriptionTypes();
    });
}
</script>



