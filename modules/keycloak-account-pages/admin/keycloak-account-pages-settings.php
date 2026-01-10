<?php
// File: modules/keycloak-account-pages/admin/keycloak-account-pages-settings.php

if (!defined('ABSPATH')) exit;

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('keycloak_account_pages', $active_modules, true)) {
    echo '<p>' . __('Ce module n\'est pas activé.', 'me5rine-lab') . '</p>';
    return;
}

$notice = null;

if (is_admin() && current_user_can('manage_options') && isset($_POST['save_keycloak_settings'])) {
    // Sauvegarder les settings Keycloak
    update_option('admin_lab_kap_kc_base_url', sanitize_text_field($_POST['admin_lab_kap_kc_base_url'] ?? ''));
    update_option('admin_lab_kap_kc_realm', sanitize_text_field($_POST['admin_lab_kap_kc_realm'] ?? ''));
    update_option('admin_lab_kap_kc_client_id', sanitize_text_field($_POST['admin_lab_kap_kc_client_id'] ?? ''));
    update_option('admin_lab_kap_kc_client_secret', sanitize_text_field($_POST['admin_lab_kap_kc_client_secret'] ?? ''));
    update_option('admin_lab_kap_kc_admin_client_id', sanitize_text_field($_POST['admin_lab_kap_kc_admin_client_id'] ?? ''));
    update_option('admin_lab_kap_kc_admin_secret', sanitize_text_field($_POST['admin_lab_kap_kc_admin_secret'] ?? ''));
    update_option('admin_lab_kap_kc_redirect_uri', esc_url_raw($_POST['admin_lab_kap_kc_redirect_uri'] ?? ''));
    
    // Valider et sauvegarder le JSON des providers (sans wp_kses_post qui corrompt le JSON)
    $providers_json_raw = stripslashes($_POST['admin_lab_kap_providers_json'] ?? '{}');
    $providers_json_decoded = json_decode($providers_json_raw, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($providers_json_decoded)) {
        // JSON valide, le sauvegarder (wp_json_encode pour s'assurer qu'il est bien formaté)
        update_option('admin_lab_kap_providers_json', wp_json_encode($providers_json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $notice = [
            'message' => __('Paramètres Keycloak enregistrés avec succès.', 'me5rine-lab'),
            'type' => 'success'
        ];
    } else {
        $notice = [
            'message' => __('Erreur : Le JSON des providers n\'est pas valide. ' . json_last_error_msg(), 'me5rine-lab'),
            'type' => 'error'
        ];
    }
    
    update_option('admin_lab_kap_prevent_last_disconnect', isset($_POST['admin_lab_kap_prevent_last_disconnect']) ? 1 : 0);
}

if (!empty($notice) && is_array($notice)): ?>
    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
        <p><?php echo esc_html($notice['message']); ?></p>
    </div>
<?php endif; ?>

<form method="post">

    <h2><?php _e('Keycloak Configuration', 'me5rine-lab'); ?></h2>
    <p><?php _e('Configurez les paramètres de connexion à Keycloak (Version 26.2.4).', 'me5rine-lab'); ?></p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_base_url"><?php _e('Keycloak Base URL', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="text" name="admin_lab_kap_kc_base_url" id="admin_lab_kap_kc_base_url" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_base_url')); ?>" 
                       class="regular-text" placeholder="https://keycloak.example.com">
                <p class="description"><?php _e('URL de base de votre instance Keycloak.', 'me5rine-lab'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_realm"><?php _e('Realm', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="text" name="admin_lab_kap_kc_realm" id="admin_lab_kap_kc_realm" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_realm')); ?>" 
                       class="regular-text" placeholder="your-realm">
            </td>
        </tr>

        <tr><th colspan="2"><h2><?php _e('Client (pour le linking)', 'me5rine-lab'); ?></h2></th></tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_client_id"><?php _e('Client ID', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="text" name="admin_lab_kap_kc_client_id" id="admin_lab_kap_kc_client_id" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_client_id')); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_client_secret"><?php _e('Client Secret', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="password" name="admin_lab_kap_kc_client_secret" id="admin_lab_kap_kc_client_secret" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_client_secret')); ?>" 
                       class="regular-text">
                <p class="description"><?php _e('Optionnel selon la configuration de votre client.', 'me5rine-lab'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_redirect_uri"><?php _e('Redirect URI (callback)', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="text" name="admin_lab_kap_kc_redirect_uri" id="admin_lab_kap_kc_redirect_uri" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_redirect_uri')); ?>" 
                       class="regular-text">
                <p class="description"><?php _e('URL de callback après la liaison d\'un provider.', 'me5rine-lab'); ?></p>
            </td>
        </tr>

        <tr><th colspan="2"><h2><?php _e('Admin API (obligatoire pour déliaison / profil / mot de passe)', 'me5rine-lab'); ?></h2></th></tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_admin_client_id"><?php _e('Admin Client ID', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="text" name="admin_lab_kap_kc_admin_client_id" id="admin_lab_kap_kc_admin_client_id" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_admin_client_id')); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_kc_admin_secret"><?php _e('Admin Client Secret', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="password" name="admin_lab_kap_kc_admin_secret" id="admin_lab_kap_kc_admin_secret" 
                       value="<?php echo esc_attr(get_option('admin_lab_kap_kc_admin_secret')); ?>" 
                       class="regular-text">
            </td>
        </tr>

        <tr><th colspan="2"><h2><?php _e('WordPress mapping', 'me5rine-lab'); ?></h2></th></tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_prevent_last_disconnect"><?php _e('Empêcher déconnexion du dernier provider', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="admin_lab_kap_prevent_last_disconnect" id="admin_lab_kap_prevent_last_disconnect" 
                           value="1" <?php checked((int)get_option('admin_lab_kap_prevent_last_disconnect', 1), 1); ?>>
                    <?php _e('Oui', 'me5rine-lab'); ?>
                </label>
                <p class="description"><?php _e('Empêche la déconnexion du dernier provider pour éviter le blocage de l\'utilisateur.', 'me5rine-lab'); ?></p>
            </td>
        </tr>

        <tr><th colspan="2"><h2><?php _e('Providers (JSON)', 'me5rine-lab'); ?></h2></th></tr>
        <tr>
            <th scope="row">
                <label for="admin_lab_kap_providers_json"><?php _e('Configuration', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <textarea name="admin_lab_kap_providers_json" id="admin_lab_kap_providers_json" 
                          rows="12" class="large-text code"><?php echo esc_textarea(get_option('admin_lab_kap_providers_json')); ?></textarea>
                <p class="description">
                    <?php _e('Format JSON : {"google":{"label":"Google","kc_alias":"google"}, ...}', 'me5rine-lab'); ?>
                </p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <?php submit_button(__('Enregistrer les modifications', 'me5rine-lab'), 'primary', 'save_keycloak_settings', false); ?>
    </p>
</form>

