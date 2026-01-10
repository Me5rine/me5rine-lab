<?php
// File: modules/subscription/functions/subscription-sync-keycloak.php

if (!defined('ABSPATH')) exit;

/**
 * SUPPRIMÉ: Cette fonction a été remplacée par admin_lab_sync_keycloak_federated_identities()
 * dans includes/functions/admin-lab-helpers.php
 * 
 * La nouvelle fonction utilise l'API Admin Keycloak comme source principale et le JSON
 * comme source de vérité pour déterminer quels providers enregistrer.
 * 
 * Cette fonction est supprimée car elle ne respectait pas les règles d'unicité et
 * n'utilisait pas le JSON comme source de vérité pour les providers.
 */

/**
 * Get access token from Keycloak session
 * This attempts to retrieve the access token from the OpenID Connect session
 */
function admin_lab_get_keycloak_access_token($user_id = null) {
    // Try to get token from OpenID Connect Generic plugin
    if (function_exists('openid_connect_generic_get_access_token')) {
        return openid_connect_generic_get_access_token($user_id);
    }
    
    // Try to get from session/transient
    if ($user_id) {
        $token = get_transient('keycloak_access_token_' . $user_id);
        if ($token) {
            return $token;
        }
    }
    
    // Try to get from current session
    if (isset($_SESSION['openid-connect-generic-access-token'])) {
        return $_SESSION['openid-connect-generic-access-token'];
    }
    
    // Try to get from user meta (if stored)
    if ($user_id) {
        $token = get_user_meta($user_id, 'keycloak_access_token', true);
        if ($token) {
            return $token;
        }
    }
    
    return null;
}

/**
 * Sync Keycloak identities on user login
 * 
 * NOTE: Les hooks sont maintenant gérés par la fonction unifiée admin_lab_sync_keycloak_federated_identities()
 * dans includes/functions/admin-lab-helpers.php qui utilise l'API Admin Keycloak (plus fiable).
 * 
 * Cette fonction est conservée pour la rétrocompatibilité et comme fallback si l'API Admin n'est pas disponible.
 */
add_action('openid-connect-generic-update-user-using-current-claim', function($user, $user_claim) {
    if (!$user || !$user->ID) {
        return;
    }
    
    // Marquer que la synchronisation est en cours (pour éviter les doubles appels)
    set_transient('admin_lab_kap_sync_' . $user->ID, time(), 60);
    
    // Utiliser la méthode unifiée (API Admin Keycloak + JSON comme source de vérité)
    if (function_exists('admin_lab_sync_keycloak_federated_identities')) {
        $kc_user_id = $user_claim['sub'] ?? null;
        admin_lab_sync_keycloak_federated_identities($user->ID, $kc_user_id, $user_claim);
    }
    // Note: Si la fonction unifiée n'existe pas, on ne fait rien (le module keycloak-account-pages doit être actif)
}, 10, 2); // Priorité 10 pour s'exécuter avant les hooks dans admin-lab-helpers.php (priorité 30)

/**
 * Sync Keycloak identities on WordPress login (fallback)
 * 
 * NOTE: Les hooks sont maintenant gérés par la fonction unifiée admin_lab_sync_keycloak_federated_identities()
 * dans includes/functions/admin-lab-helpers.php qui utilise l'API Admin Keycloak (plus fiable).
 * 
 * Cette fonction est conservée pour la rétrocompatibilité et comme fallback si l'API Admin n'est pas disponible.
 */
add_action('wp_login', function($user_login, $user) {
    if (!$user || !$user->ID) {
        return;
    }
    
    // Marquer que la synchronisation est en cours (pour éviter les doubles appels)
    set_transient('admin_lab_kap_sync_' . $user->ID, time(), 60);
    
    // Utiliser la méthode unifiée (API Admin Keycloak + JSON comme source de vérité)
    if (function_exists('admin_lab_sync_keycloak_federated_identities')) {
        // Essayer de récupérer les claims
        $user_claim = null;
        if (function_exists('openid_connect_generic_get_user_claim')) {
            $user_claim = openid_connect_generic_get_user_claim($user->ID);
        }
        
        $kc_user_id = $user_claim['sub'] ?? null;
        admin_lab_sync_keycloak_federated_identities($user->ID, $kc_user_id, $user_claim);
    }
    // Note: Si la fonction unifiée n'existe pas, on ne fait rien (le module keycloak-account-pages doit être actif)
}, 10, 2); // Priorité 10 pour s'exécuter avant les hooks dans admin-lab-helpers.php (priorité 30)

