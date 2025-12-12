<?php
// File: modules/user-management/admin/user-management-partner.php

if (!defined('ABSPATH')) exit;

// Affiche le champ "Sites partenaires" dans le profil utilisateur
function user_management_add_partner_sites_field($user) {
    if (
        !array_intersect(['um_partenaire', 'um_partenaire_plus'], (array) $user->roles) &&
        !(defined('DOING_AJAX') && DOING_AJAX)
    ) {
        return;
    }    

    $partner_sites = maybe_unserialize(get_user_meta($user->ID, 'partner_sites', true));
    $partner_sites = is_array($partner_sites) ? $partner_sites : [];

    $all_sites = get_user_meta($user->ID, 'partner_all_sites', true);
    $available_sites = admin_lab_get_available_sites();
    ?>
    <h3><?php _e('Partner sites', 'me5rine-lab'); ?></h3>
    <table class="form-table">
        <tr>
            <th><?php _e('Activate this partner on the sites :', 'me5rine-lab'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="partner_all_sites" value="1" <?php checked($all_sites, '1'); ?> />
                    <strong><?php _e('All current and future sites', 'me5rine-lab'); ?></strong>
                </label>
                <br><br>
                <?php foreach ($available_sites as $domain => $label) : ?>
                    <label class="user-edit-partner-sites">
                        <input type="checkbox" name="partner_sites[]" value="<?php echo esc_attr($domain); ?>"
                               <?php checked(in_array($domain, $partner_sites)); ?>>
                        <?php echo esc_html($label); ?>
                    </label><br>
                <?php endforeach; ?>
                <p class="description"><?php _e('Select the sites where this user is a partner.', 'me5rine-lab'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('user_management_render_partner_sites_field', 'user_management_add_partner_sites_field');