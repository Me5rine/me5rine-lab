<?php
// File: modules/keycloak-account-pages/includes/class-keycloak-account-pages-shortcodes.php

if (!defined('ABSPATH')) exit;

class Keycloak_Account_Pages_Shortcodes {

  public static function init(): void {
    // Enregistrer les shortcodes avec des noms de fonctions (pattern standard WordPress)
    add_shortcode('admin_lab_kap_connections', 'admin_lab_kap_shortcode_connections');
    add_shortcode('admin_lab_kap_account_settings', 'admin_lab_kap_shortcode_account_settings');
    
    add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
  }

  public static function enqueue(): void {
    if (!is_user_logged_in()) return;

    // ✅ Toujours charger sur les pages UM (profil custom inclus)
    $should_load = false;

    if (function_exists('UM') && function_exists('um_is_core_page')) {
      // Profil OU Compte UM
      $should_load = um_is_core_page('user') || um_is_core_page('account');
    }

    // ✅ Si on est dans un template UM mais um_is_core_page ne match pas
    if (!$should_load && function_exists('UM')) {
      // Très souvent présent sur pages UM
      if (isset($_GET['um_action']) || isset($_GET['profiletab']) || isset($_GET['tab']) || (function_exists('um_profile_id') && um_profile_id())) {
        $should_load = true;
      }
    }

    // ✅ Si shortcodes présents dans un contenu WP standard
    if (!$should_load) {
      global $post;
      if ($post && (
        has_shortcode($post->post_content, 'admin_lab_kap_connections') ||
        has_shortcode($post->post_content, 'admin_lab_kap_account_settings')
      )) {
        $should_load = true;
      }
    }

    if (!$should_load) return;

    // IMPORTANT : les handles doivent être enregistrés (voir keycloak-account-pages.php)
    wp_enqueue_style('admin-lab-kap-css');
    wp_enqueue_script('admin-lab-kap-js');

    wp_localize_script('admin-lab-kap-js', 'AdminLabKAP', [
      'rest'  => esc_url_raw(rest_url('admin-lab-kap/v1')),
      'nonce' => wp_create_nonce('wp_rest'),
    ]);
    
    wp_localize_script('admin-lab-kap-js', 'kapStrings', [
      'connect' => __('Connect', 'me5rine-lab'),
      'disconnect' => __('Disconnect', 'me5rine-lab'),
      'noProvidersConfigured' => __('No providers configured.', 'me5rine-lab'),
      'error' => __('An error occurred', 'me5rine-lab'),
      'emailVerified' => __('Verified', 'me5rine-lab'),
      'emailNotVerified' => __('Not Verified', 'me5rine-lab'),
      'saving' => __('Saving…', 'me5rine-lab'),
      'profileUpdated' => __('Profile updated.', 'me5rine-lab'),
      'updating' => __('Updating…', 'me5rine-lab'),
      'emailUpdated' => __('Email updated. A verification email has been sent.', 'me5rine-lab'),
      'sending' => __('Sending…', 'me5rine-lab'),
      'verificationEmailSent' => __('Verification email sent successfully.', 'me5rine-lab'),
      'changing' => __('Changing…', 'me5rine-lab'),
      'passwordChanged' => __('Password changed.', 'me5rine-lab'),
      'attention' => __('Attention', 'me5rine-lab'),
      'setPasswordHint' => __('You can set a password in the "My Account" tab to be able to disconnect this provider.', 'me5rine-lab'),
      'setPassword' => __('Set Password', 'me5rine-lab'),
      'changePassword' => __('Change Password', 'me5rine-lab'),
    ]);
  }

  // Méthodes statiques conservées pour compatibilité avec Ultimate Member
  public static function connections(): string {
    return admin_lab_kap_render_connections();
  }

  public static function account_settings(): string {
    return admin_lab_kap_render_account_settings();
  }
}

/**
 * Callback pour le shortcode admin_lab_kap_connections
 */
function admin_lab_kap_shortcode_connections($atts = [], $content = null) {
  return admin_lab_kap_render_connections();
}

/**
 * Callback pour le shortcode admin_lab_kap_account_settings
 */
function admin_lab_kap_shortcode_account_settings($atts = [], $content = null) {
  return admin_lab_kap_render_account_settings();
}

/**
 * Fonction de rendu pour les connexions (pattern similaire à poke_hub_render_user_profile)
 */
function admin_lab_kap_render_connections() {
  // Rediriger vers l'onglet par défaut si l'utilisateur n'est pas connecté
  if (function_exists('admin_lab_redirect_to_default_profile_tab') && admin_lab_redirect_to_default_profile_tab()) {
    return ''; // Redirection effectuée, on ne retourne rien
  }
  
  if (!is_user_logged_in()) {
    // Protection si accès forcé (URL invalide)
    return '<p>' . __('You must be logged in.', 'me5rine-lab') . '</p>';
  }

  // S'assurer que les assets sont chargés
  Keycloak_Account_Pages_Shortcodes::enqueue();

  // Gérer les messages de statut depuis l'URL (pattern similaire à poke_hub_render_user_profile)
  $success_message = '';
  $error_message = '';
  $warning_message = '';
  
  $kap_status = isset($_GET['kap']) ? sanitize_text_field($_GET['kap']) : '';
  
  if ($kap_status === 'success' || $kap_status === 'linked') {
    // Message de succès (linking réussi)
    $provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
    if ($provider) {
      // Essayer de récupérer le label du provider depuis la config
      $providers = Keycloak_Account_Pages_Keycloak::get_providers();
      $provider_label = $providers[$provider]['label'] ?? $provider;
      $success_message = sprintf(__('Provider "%s" has been successfully linked!', 'me5rine-lab'), esc_html($provider_label));
    } else {
      $success_message = __('Provider has been successfully linked!', 'me5rine-lab');
    }
  } elseif ($kap_status === 'error') {
    // Message d'erreur
    $error = isset($_GET['error']) ? urldecode(sanitize_text_field($_GET['error'])) : '';
    $error_desc = isset($_GET['error_description']) ? urldecode(sanitize_text_field($_GET['error_description'])) : '';
    
    if ($error_desc) {
      $error_message = $error_desc;
    } elseif ($error) {
      $error_message = $error;
    } else {
      $error_message = __('An error occurred while linking the provider.', 'me5rine-lab');
    }
  } elseif ($kap_status === 'disconnected') {
    // Message de succès pour la déconnexion
    $provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
    if ($provider) {
      $providers = Keycloak_Account_Pages_Keycloak::get_providers();
      $provider_label = $providers[$provider]['label'] ?? $provider;
      $success_message = sprintf(__('Provider "%s" has been successfully unlinked.', 'me5rine-lab'), esc_html($provider_label));
    } else {
      $success_message = __('Provider has been successfully unlinked.', 'me5rine-lab');
    }
  }

  ob_start(); ?>
  <div class="me5rine-lab-profile-container">
    <h3 class="me5rine-lab-title-medium"><?php esc_html_e('Connections', 'me5rine-lab'); ?></h3>
    <h4 class="me5rine-lab-subtitle"><?php esc_html_e('Connection accounts', 'me5rine-lab'); ?></h4>
    
    <?php if (!empty($success_message)) : ?>
      <div id="admin-lab-kap-connections-message" class="me5rine-lab-form-message me5rine-lab-form-message-success">
        <p><?php echo esc_html($success_message); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)) : ?>
      <div id="admin-lab-kap-connections-message" class="me5rine-lab-form-message me5rine-lab-form-message-error">
        <p><?php echo esc_html($error_message); ?></p>
        <?php
        // Messages d'aide spécifiques selon le type d'erreur
        $error = isset($_GET['error']) ? urldecode(sanitize_text_field($_GET['error'])) : '';
        if ($error === 'invalid_redirect_uri') {
          $redirect_uri = Keycloak_Account_Pages_Keycloak::opt('kc_redirect_uri');
          $client_id = Keycloak_Account_Pages_Keycloak::opt('kc_client_id');
          ?>
          <div class="me5rine-lab-form-message me5rine-lab-form-message-info">
            <p><strong><?php esc_html_e('Solution:', 'me5rine-lab'); ?></strong></p>
            <ol>
              <li><?php esc_html_e('Log in to Keycloak administration', 'me5rine-lab'); ?></li>
              <li><?php printf(__('Go to <strong>Clients → %s</strong>', 'me5rine-lab'), esc_html($client_id)); ?></li>
              <li><?php esc_html_e('In <strong>"Valid Redirect URIs"</strong>, add:', 'me5rine-lab'); ?> <code><?php echo esc_html($redirect_uri); ?></code></li>
              <li><?php esc_html_e('Save the changes', 'me5rine-lab'); ?></li>
            </ol>
          </div>
          <?php
        } elseif ($error === 'kc_action_error' || $error === 'linking_failed') {
          ?>
          <div class="me5rine-lab-form-message me5rine-lab-form-message-info">
            <p><strong><?php esc_html_e('Solution:', 'me5rine-lab'); ?></strong></p>
            <ol>
              <li><?php esc_html_e('Log in to Keycloak administration', 'me5rine-lab'); ?></li>
              <li><?php esc_html_e('Go to <strong>Realm → Users → [your user]</strong>', 'me5rine-lab'); ?></li>
              <li><?php esc_html_e('Click on <strong>"Role mapping"</strong>', 'me5rine-lab'); ?></li>
              <li><?php esc_html_e('In <strong>"Client roles"</strong>, select <code>account</code>', 'me5rine-lab'); ?></li>
              <li><?php esc_html_e('Add the role <code>manage-account-links</code>', 'me5rine-lab'); ?></li>
              <li><?php esc_html_e('Also check that the provider is enabled: <strong>Identity Providers → [provider] → Enabled = ON</strong>', 'me5rine-lab'); ?></li>
            </ol>
          </div>
          <?php
        } else {
          ?>
          <div class="me5rine-lab-form-message me5rine-lab-form-message-info">
            <p><?php esc_html_e('Check your Keycloak client configuration (Redirect URIs, Scopes) and user roles.', 'me5rine-lab'); ?></p>
          </div>
          <?php
        }
        ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($warning_message)) : ?>
      <div id="admin-lab-kap-connections-message" class="me5rine-lab-form-message me5rine-lab-form-message-warning">
        <p><?php echo esc_html($warning_message); ?></p>
      </div>
    <?php endif; ?>
    <div id="admin-lab-kap-connections" class="me5rine-lab-profile-container">
      <p><?php esc_html_e('Loading…', 'me5rine-lab'); ?></p>
    </div>

    <?php do_action('admin_lab_kap_after_connections'); ?>
  </div>
  <?php
  return (string)ob_get_clean();
}

/**
 * Fonction de rendu pour les paramètres du compte (pattern similaire à poke_hub_render_user_profile)
 */
function admin_lab_kap_render_account_settings() {
  // Gérer les messages de statut depuis l'URL (avant la vérification de connexion)
  $kap_status = isset($_GET['kap']) ? sanitize_text_field($_GET['kap']) : '';
  $password_success_message = '';
  $email_success_message = '';
  
  if ($kap_status === 'password_changed') {
    $password_success_message = __('Password has been successfully changed!', 'me5rine-lab');
  } elseif ($kap_status === 'email_changed') {
    $email_success_message = __('Votre adresse email a été modifiée. Un email de vérification a été envoyé à votre nouvelle adresse. Veuillez vérifier votre nouveau email pour vous connecter.', 'me5rine-lab');
  }
  
  // Si l'utilisateur n'est pas connecté mais qu'on a un message de changement d'email,
  // afficher uniquement le message (pas le formulaire)
  if (!is_user_logged_in()) {
    // Si on a un message de changement d'email, l'afficher même si déconnecté
    if ($kap_status === 'email_changed' && !empty($email_success_message)) {
      ob_start();
      ?>
      <div class="me5rine-lab-profile-container">
        <h3 class="me5rine-lab-title-medium"><?php esc_html_e('My Account', 'me5rine-lab'); ?></h3>
        <div class="me5rine-lab-form-message me5rine-lab-form-message-success">
          <p><?php echo esc_html($email_success_message); ?></p>
        </div>
      </div>
      <?php
      return (string)ob_get_clean();
    }
    
    // Rediriger vers l'onglet par défaut si l'utilisateur n'est pas connecté
    if (function_exists('admin_lab_redirect_to_default_profile_tab') && admin_lab_redirect_to_default_profile_tab()) {
      return ''; // Redirection effectuée, on ne retourne rien
    }
    
    // Protection si accès forcé (URL invalide)
    return '<p>' . __('You must be logged in.', 'me5rine-lab') . '</p>';
  }

  // S'assurer que les assets sont chargés
  Keycloak_Account_Pages_Shortcodes::enqueue();

  // Récupérer le statut de l'email côté serveur pour affichage initial (fallback si JS ne fonctionne pas)
  $user_id = get_current_user_id();
  $user = get_userdata($user_id);
  $email_verified = false;
  $user_email = $user ? $user->user_email : '';
  
  // Essayer de récupérer le statut depuis Keycloak
  if (class_exists('Keycloak_Account_Pages_Keycloak')) {
    $kc_user_id = Keycloak_Account_Pages_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if ($kc_user_id) {
      try {
        $kc_user = Keycloak_Account_Pages_Keycloak::get_user($kc_user_id);
        if (!empty($kc_user['email'])) {
          $user_email = $kc_user['email'];
        }
        $email_verified = !empty($kc_user['emailVerified']) ? (bool)$kc_user['emailVerified'] : false;
      } catch (Exception $e) {
        // En cas d'erreur, utiliser les données WordPress par défaut
      }
    }
  }
  
  // Générer le HTML du badge côté serveur
  $badge_html = '';
  $badge_class = 'me5rine-lab-hidden';
  if ($email_verified) {
    $verified_text = __('Verified', 'me5rine-lab');
    $badge_html = '<span class="me5rine-lab-status me5rine-lab-status-success" title="' . esc_attr($verified_text) . '">' . esc_html($verified_text) . '</span>';
    $badge_class = 'me5rine-lab-visible';
  } else {
    $not_verified_text = __('Not Verified', 'me5rine-lab');
    $badge_html = '<span class="me5rine-lab-status me5rine-lab-status-warning" title="' . esc_attr($not_verified_text) . '">' . esc_html($not_verified_text) . '</span>';
    $badge_class = 'me5rine-lab-visible';
  }

  ob_start(); ?>
  <div class="me5rine-lab-profile-container">
    <h3 class="me5rine-lab-title-medium"><?php esc_html_e('My Account', 'me5rine-lab'); ?></h3>

    <div class="me5rine-lab-profile-container">
      <h4 class="me5rine-lab-subtitle"><?php esc_html_e('Profile', 'me5rine-lab'); ?></h4>
      <div class="me5rine-lab-form-message me5rine-lab-form-message-hidden" data-msg="profile"></div>
      <form id="admin-lab-kap-profile-form" class="me5rine-lab-form">
        <div class="me5rine-lab-form-field">
          <label for="admin-lab-kap-first-name" class="me5rine-lab-form-label"><?php esc_html_e('First Name', 'me5rine-lab'); ?></label>
          <input type="text" id="admin-lab-kap-first-name" name="first_name" class="me5rine-lab-form-input" required>
        </div>
        <div class="me5rine-lab-form-field">
          <label for="admin-lab-kap-last-name" class="me5rine-lab-form-label"><?php esc_html_e('Last Name', 'me5rine-lab'); ?></label>
          <input type="text" id="admin-lab-kap-last-name" name="last_name" class="me5rine-lab-form-input" required>
        </div>
        <div class="me5rine-lab-form-field">
          <label for="admin-lab-kap-nickname" class="me5rine-lab-form-label"><?php esc_html_e('Nickname', 'me5rine-lab'); ?></label>
          <input type="text" id="admin-lab-kap-nickname" name="nickname" class="me5rine-lab-form-input" required>
        </div>
        <div class="me5rine-lab-form-field">
          <button type="submit" class="me5rine-lab-form-button"><?php esc_html_e('Save', 'me5rine-lab'); ?></button>
        </div>
      </form>
    </div>

    <div class="me5rine-lab-profile-container">
      <div class="me5rine-lab-flex-row-center">
        <h4 class="me5rine-lab-subtitle me5rine-lab-subtitle-no-margin"><?php esc_html_e('Email Address', 'me5rine-lab'); ?></h4>
        <span id="admin-lab-kap-email-status-badge" class="<?php echo esc_attr($badge_class); ?>" data-email-verified="<?php echo $email_verified ? '1' : '0'; ?>"><?php echo $badge_html; ?></span>
      </div>
      <?php if (!empty($email_success_message)): ?>
        <div class="me5rine-lab-form-message me5rine-lab-form-message-success">
          <p><?php echo esc_html($email_success_message); ?></p>
        </div>
      <?php endif; ?>
      <div class="me5rine-lab-form-message me5rine-lab-form-message-hidden" data-msg="email"></div>
      <form id="admin-lab-kap-email-form" class="me5rine-lab-form">
        <div class="me5rine-lab-form-field">
          <label for="admin-lab-kap-email-input" class="me5rine-lab-form-label"><?php esc_html_e('Email', 'me5rine-lab'); ?></label>
          <input type="email" name="email" id="admin-lab-kap-email-input" class="me5rine-lab-form-input" value="<?php echo esc_attr($user_email); ?>" required>
        </div>
        <div class="me5rine-lab-form-field">
          <button type="submit" class="me5rine-lab-form-button"><?php esc_html_e('Update Email', 'me5rine-lab'); ?></button>
          <button type="button" id="admin-lab-kap-resend-verification" class="me5rine-lab-form-button me5rine-lab-form-button-secondary me5rine-lab-form-button-spaced me5rine-lab-form-button-inline <?php echo $email_verified ? 'me5rine-lab-hidden' : ''; ?>"><?php esc_html_e('Resend Verification Email', 'me5rine-lab'); ?></button>
        </div>
      </form>
    </div>

      <div class="me5rine-lab-profile-container">
        <h4 class="me5rine-lab-subtitle"><?php esc_html_e('Password', 'me5rine-lab'); ?></h4>
        <?php if (!empty($password_success_message)): ?>
          <div class="me5rine-lab-form-message me5rine-lab-form-message-success">
            <p><?php echo esc_html($password_success_message); ?></p>
          </div>
        <?php endif; ?>
        <div class="me5rine-lab-form-message me5rine-lab-form-message-hidden" data-msg="password"></div>
        <form id="admin-lab-kap-password-form" class="me5rine-lab-form">
          <div class="me5rine-lab-form-field">
            <label for="admin-lab-kap-password" class="me5rine-lab-form-label"><?php esc_html_e('New Password', 'me5rine-lab'); ?></label>
            <input type="password" id="admin-lab-kap-password" name="password" class="me5rine-lab-form-input" required minlength="8">
          </div>
          <div class="me5rine-lab-form-field">
            <label for="admin-lab-kap-password-confirm" class="me5rine-lab-form-label"><?php esc_html_e('Confirm New Password', 'me5rine-lab'); ?></label>
            <input type="password" id="admin-lab-kap-password-confirm" name="password_confirm" class="me5rine-lab-form-input" required minlength="8">
          </div>
          <div class="me5rine-lab-form-field">
            <button type="submit" class="me5rine-lab-form-button"><?php esc_html_e('Change Password', 'me5rine-lab'); ?></button>
          </div>
        </form>
      </div>
  </div>
  <?php
  return (string)ob_get_clean();
}


