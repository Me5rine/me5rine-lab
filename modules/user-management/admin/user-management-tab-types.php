<?php
// File: modules/user-management/admin/user-management-tab-types.php

if (!defined('ABSPATH')) exit;

// Charger les fonctions des types de comptes si pas encore chargées
if (!function_exists('admin_lab_get_account_types')) {
    require_once plugin_dir_path(__DIR__) . 'functions/account-types-functions.php';
}

$account_types = admin_lab_get_registered_account_types();
$edit_slug = $_GET['edit'] ?? null;
$show_form = isset($_GET['add']) || isset($_GET['edit']);
$type_data = $edit_slug && isset($account_types[$edit_slug]) ? $account_types[$edit_slug] : null;

global $wp_roles;
$available_roles = $wp_roles->get_names();
?>

<?php if (!$show_form) : ?>
    <h2><?php esc_html_e('Registered Account Types', 'me5rine-lab'); ?></h2>

    <?php
    $types_table = new Admin_LAB_Account_Types_List_Table();
    $types_table->prepare_items();
    $types_table->display();
    ?>

<?php else : ?>
    <h2><?php echo $edit_slug ? esc_html__('Edit Account Type', 'me5rine-lab') : esc_html__('Add a New Account Type', 'me5rine-lab'); ?></h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('admin_lab_add_account_type'); ?>
        <input type="hidden" name="action" value="<?php echo $edit_slug ? 'admin_lab_update_account_type' : 'admin_lab_add_account_type'; ?>" />

        <table class="form-table">
            <tr>
                <th><label for="type_label"><?php esc_html_e('Label', 'me5rine-lab'); ?></label></th>
                <td>
                    <input name="type_label" id="type_label" type="text" class="regular-text" required
                        value="<?php echo esc_attr($type_data['label'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('Visible label for this account type.', 'me5rine-lab'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="type_slug"><?php esc_html_e('Type Slug', 'me5rine-lab'); ?></label></th>
                <td><input name="type_slug" id="type_slug" type="text" class="regular-text" required value="<?php echo esc_attr($edit_slug ?? ''); ?>" <?php echo $edit_slug ? 'readonly' : ''; ?>></td>
            </tr>
            <tr>
                <th><label for="type_role"><?php esc_html_e('Associated Role', 'me5rine-lab'); ?></label></th>
                <td>
                    <select name="type_role" id="type_role" required>
                        <?php foreach ($available_roles as $role_key => $role_label) : ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($type_data['role'] ?? '', $role_key); ?>><?php echo esc_html($role_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <?php $available_domains = admin_lab_get_available_sites(); 
                $is_custom = isset($type_data['scope']) && is_array($type_data['scope']);
                $selected_scope = $is_custom ? $type_data['scope'] : []; ?>
                <th><label for="type_scope"><?php esc_html_e('Default Scope', 'me5rine-lab'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="type_scope_mode" value="global" <?php checked(!$is_custom); ?>>
                        <?php esc_html_e('Global', 'me5rine-lab'); ?>
                    </label><br>

                    <label>
                        <input type="radio" name="type_scope_mode" value="custom" <?php checked($is_custom); ?>>
                        <?php esc_html_e('Custom domains', 'me5rine-lab'); ?>
                    </label><br>

                    <select name="type_scope_custom[]" multiple size="4" style="width: 100%; max-width: 400px;" id="custom-scope-list">
                        <?php foreach ($available_domains as $domain => $label) : ?>
                            <option value="<?php echo esc_attr($domain); ?>" <?php echo in_array($domain, $selected_scope) ? 'selected' : ''; ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="type_modules"><?php esc_html_e('Enabled Modules', 'me5rine-lab'); ?></label></th>
                <td>
                    <?php 
                    $available_modules = admin_lab_get_modules();
                    $selected_modules = isset($type_data['modules']) ? (is_array($type_data['modules']) ? $type_data['modules'] : maybe_unserialize($type_data['modules'])) : [];
                    ?>
                    <?php foreach ($available_modules as $module_slug => $module_label) : ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="type_modules[]" value="<?php echo esc_attr($module_slug); ?>" <?php checked(in_array($module_slug, (array) $selected_modules)); ?>>
                            <?php echo esc_html($module_label); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Select the modules accessible by default for this account type.', 'me5rine-lab'); ?></p>
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-primary"><?php echo $edit_slug ? esc_html__('Update Account Type', 'me5rine-lab') : esc_html__('Add Account Type', 'me5rine-lab'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-user-management&tab=types')); ?>" class="button"><?php esc_html_e('Cancel', 'me5rine-lab'); ?></a>
        </p>
    </form>

    <script>
        (function($){
            function toggleScopeList() {
                const isCustom = $('input[name="type_scope_mode"]:checked').val() === 'custom';
                $('#custom-scope-list').closest('tr').find('select').toggle(isCustom);
            }
            $(document).ready(function() {
                toggleScopeList();
                $('input[name="type_scope_mode"]').on('change', toggleScopeList);
            });
        })(jQuery);
    </script>
<?php endif; ?>