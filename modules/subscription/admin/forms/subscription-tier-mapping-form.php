<?php
// File: modules/subscription/admin/forms/subscription-tier-mapping-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_mapping = isset($edit_mapping) ? $edit_mapping : null;
$mapping_id = $edit_mapping ? $edit_mapping['id'] : 0;
$tiers = isset($tiers) ? $tiers : admin_lab_get_subscription_tiers();
$providers = isset($providers) ? $providers : admin_lab_get_subscription_providers();
$levels = isset($levels) ? $levels : admin_lab_get_subscription_levels();

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

// Build types_by_provider
$types_by_provider = [];
$twitch_types = admin_lab_get_subscription_levels('twitch');
if (!empty($twitch_types)) {
    $types_by_provider['twitch'] = $twitch_types;
}
$discord_types = admin_lab_get_subscription_levels('discord');
if (!empty($discord_types)) {
    $types_by_provider['discord'] = $discord_types;
}
foreach ($other_providers as $provider) {
    $provider_slug = $provider['provider_slug'];
    $provider_types = admin_lab_get_subscription_levels($provider_slug);
    if (!empty($provider_types)) {
        $types_by_provider[$provider_slug] = $provider_types;
    }
}
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_mapping_action'); ?>
    <input type="hidden" name="action" value="save_mapping">
    <input type="hidden" name="mapping_id" value="<?php echo esc_attr($mapping_id); ?>">
    
    <table class="form-table">
        <tr>
            <th><label for="mapping_tier_slug">Tier</label></th>
            <td>
                <select name="tier_slug" id="mapping_tier_slug" required>
                    <option value="">Select...</option>
                    <?php foreach ($tiers as $tier) : ?>
                        <option value="<?php echo esc_attr($tier['tier_slug']); ?>" 
                                <?php selected($edit_mapping['tier_slug'] ?? '', $tier['tier_slug']); ?>>
                            <?php echo esc_html($tier['tier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="mapping_provider_slug">Provider</label></th>
            <td>
                <select name="provider_slug" id="mapping_provider_slug" required onchange="updateLevels()">
                    <option value="">Select...</option>
                    <?php if (!empty($twitch_providers)) : ?>
                        <optgroup label="Twitch (Global)">
                            <?php foreach ($twitch_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="twitch"
                                        <?php 
                                        $check_provider = $edit_mapping['provider_slug'] ?? '';
                                        if (strpos($check_provider, 'twitch') === 0) {
                                            $check_provider = $provider['provider_slug'];
                                        }
                                        selected($check_provider, $provider['provider_slug']); 
                                        ?>>
                                    <?php echo esc_html($provider['provider_name']); ?> (applies to all Twitch providers)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($discord_providers)) : ?>
                        <optgroup label="Discord (Global)">
                            <?php foreach ($discord_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="discord"
                                        <?php 
                                        $check_provider = $edit_mapping['provider_slug'] ?? '';
                                        if (strpos($check_provider, 'discord') === 0) {
                                            $check_provider = $provider['provider_slug'];
                                        }
                                        selected($check_provider, $provider['provider_slug']); 
                                        ?>>
                                    <?php echo esc_html($provider['provider_name']); ?> (applies to all Discord providers)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($other_providers)) : ?>
                        <optgroup label="Other Providers (Per Provider)">
                            <?php foreach ($other_providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider['provider_slug']); ?>" 
                                        data-normalized="<?php echo esc_attr($provider['provider_slug']); ?>"
                                        <?php selected($edit_mapping['provider_slug'] ?? '', $provider['provider_slug']); ?>>
                                    <?php echo esc_html($provider['provider_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="mapping_level_slug">Subscription Type</label></th>
            <td>
                <select name="level_slug" id="mapping_level_slug" required>
                    <option value="">Select provider first...</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="mapping_is_active">Active</label></th>
            <td>
                <input type="checkbox" name="is_active" id="mapping_is_active" value="1" 
                       <?php checked($edit_mapping['is_active'] ?? 1, 1); ?>>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit_mapping')); ?>" class="button">Cancel</a>
    </p>
</form>

<script>
const typesByProvider = <?php echo json_encode(array_map(function($types) {
    return array_map(function($t) {
        return ['level_slug' => $t['level_slug'], 'level_name' => $t['level_name']];
    }, $types);
}, $types_by_provider)); ?>;
const currentProvider = '<?php echo esc_js($edit_mapping['provider_slug'] ?? ''); ?>';
const currentProviderNormalized = '<?php 
    $check = $edit_mapping['provider_slug'] ?? '';
    if (strpos($check, 'twitch') === 0) {
        echo 'twitch';
    } elseif (strpos($check, 'discord') === 0) {
        echo 'discord';
    } else {
        echo esc_js($check);
    }
?>';
const currentLevel = '<?php echo esc_js($edit_mapping['level_slug'] ?? ''); ?>';

function updateLevels() {
    const providerSelect = document.getElementById('mapping_provider_slug');
    const provider = providerSelect.value;
    const selectedOption = providerSelect.options[providerSelect.selectedIndex];
    const normalizedProvider = selectedOption ? selectedOption.getAttribute('data-normalized') : null;
    
    const levelSelect = document.getElementById('mapping_level_slug');
    levelSelect.innerHTML = '<option value="">Select...</option>';
    
    const checkProvider = normalizedProvider || provider;
    
    if (checkProvider && typesByProvider[checkProvider]) {
        typesByProvider[checkProvider].forEach(type => {
            const option = document.createElement('option');
            option.value = type.level_slug;
            option.textContent = type.level_name;
            if (currentLevel === type.level_slug) {
                option.selected = true;
            }
            levelSelect.appendChild(option);
        });
    }
}

// Initialize on page load
if (currentProvider) {
    document.addEventListener('DOMContentLoaded', function() {
        updateLevels();
    });
}
</script>



