<?php
// File: modules/game-servers/functions/game-servers-minecraft-auth.php

if (!defined('ABSPATH')) exit;

/**
 * Classe pour gérer l'authentification Microsoft/Minecraft et récupérer l'UUID Minecraft
 */
class Game_Servers_Minecraft_Auth {

    /**
     * Récupère l'ID Microsoft depuis Keycloak pour un utilisateur WordPress
     * Récupère depuis la table keycloak_accounts, comme pour Twitch, Discord, etc.
     *
     * @param int $user_id ID de l'utilisateur WordPress
     * @return string|null ID Microsoft (external_user_id) ou null si non trouvé
     */
    public static function get_microsoft_id_from_keycloak($user_id) {
        // Vérifier que la classe DB est disponible
        if (!class_exists('Admin_Lab_DB')) {
            return null;
        }

        try {
            // Essayer d'abord avec 'microsoft' comme provider_slug
            $connection = Admin_Lab_DB::getInstance()->get_keycloak_connection($user_id, 'microsoft');
            
            if ($connection && !empty($connection['external_user_id'])) {
                return (string) $connection['external_user_id'];
            }
            
            // Fallback : essayer avec 'azure' (certaines configurations Keycloak utilisent 'azure')
            $connection = Admin_Lab_DB::getInstance()->get_keycloak_connection($user_id, 'azure');
            
            if ($connection && !empty($connection['external_user_id'])) {
                return (string) $connection['external_user_id'];
            }

            return null;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Erreur lors de la récupération de l\'ID Microsoft: ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Authentifie avec Xbox Live en utilisant un access token Microsoft
     *
     * @param string $microsoft_access_token Access token Microsoft
     * @return array|WP_Error Token XBL et userhash, ou erreur
     */
    public static function authenticate_with_xbox_live($microsoft_access_token) {
        $url = 'https://user.auth.xboxlive.com/user/authenticate';

        $body = [
            'Properties' => [
                'AuthMethod' => 'RPS',
                'SiteName' => 'user.auth.xboxlive.com',
                'RpsTicket' => 'd=' . $microsoft_access_token
            ],
            'RelyingParty' => 'http://auth.xboxlive.com',
            'TokenType' => 'JWT'
        ];

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('xbox_auth_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $error_msg = 'Xbox Live authentication failed';
            if (isset($body_response['XErr'])) {
                $error_msg .= ' (XErr: ' . $body_response['XErr'] . ')';
            }
            return new WP_Error('xbox_auth_failed', $error_msg, $body_response);
        }

        if (empty($body_response['Token']) || empty($body_response['DisplayClaims']['xui'][0]['uhs'])) {
            return new WP_Error('xbox_auth_invalid', 'Invalid Xbox Live response');
        }

        return [
            'xbl_token' => $body_response['Token'],
            'userhash' => $body_response['DisplayClaims']['xui'][0]['uhs']
        ];
    }

    /**
     * Obtient un token XSTS pour Minecraft
     *
     * @param string $xbl_token Token XBL
     * @return array|WP_Error Token XSTS et userhash, ou erreur
     */
    public static function get_xsts_token($xbl_token) {
        $url = 'https://xsts.auth.xboxlive.com/xsts/authorize';

        $body = [
            'Properties' => [
                'SandboxId' => 'RETAIL',
                'UserTokens' => [$xbl_token]
            ],
            'RelyingParty' => 'rp://api.minecraftservices.com/',
            'TokenType' => 'JWT'
        ];

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('xsts_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $error_msg = 'XSTS token request failed';
            if (isset($body_response['XErr'])) {
                $xerr = $body_response['XErr'];
                $error_msg .= ' (XErr: ' . $xerr . ')';
                
                // Messages d'erreur spécifiques selon la documentation
                $xerr_messages = [
                    2148916227 => 'Le compte est banni de Xbox',
                    2148916233 => 'Le compte n\'a pas de compte Xbox. Veuillez vous connecter via minecraft.net pour en créer un.',
                    2148916235 => 'Xbox Live n\'est pas disponible dans votre pays',
                    2148916236 => 'Vérification adulte requise (Corée du Sud)',
                    2148916237 => 'Vérification adulte requise (Corée du Sud)',
                    2148916238 => 'Le compte est un compte enfant et doit être ajouté à une Famille par un adulte',
                    2148916262 => 'Erreur inconnue'
                ];
                
                if (isset($xerr_messages[$xerr])) {
                    $error_msg = $xerr_messages[$xerr];
                }
            }
            return new WP_Error('xsts_failed', $error_msg, $body_response);
        }

        if (empty($body_response['Token']) || empty($body_response['DisplayClaims']['xui'][0]['uhs'])) {
            return new WP_Error('xsts_invalid', 'Invalid XSTS response');
        }

        return [
            'xsts_token' => $body_response['Token'],
            'userhash' => $body_response['DisplayClaims']['xui'][0]['uhs']
        ];
    }

    /**
     * Authentifie avec Minecraft en utilisant un token XSTS
     *
     * @param string $xsts_token Token XSTS
     * @param string $userhash Userhash
     * @return array|WP_Error Access token Minecraft, ou erreur
     */
    public static function authenticate_with_minecraft($xsts_token, $userhash) {
        $url = 'https://api.minecraftservices.com/authentication/login_with_xbox';

        $body = [
            'identityToken' => 'XBL3.0 x=' . $userhash . ';' . $xsts_token
        ];

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('minecraft_auth_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('minecraft_auth_failed', 'Minecraft authentication failed', $body_response);
        }

        if (empty($body_response['access_token'])) {
            return new WP_Error('minecraft_auth_invalid', 'Invalid Minecraft response');
        }

        return [
            'access_token' => $body_response['access_token'],
            'expires_in' => $body_response['expires_in'] ?? 86400
        ];
    }

    /**
     * Vérifie si le compte possède Minecraft
     *
     * @param string $minecraft_access_token Access token Minecraft
     * @return bool|WP_Error True si le compte possède Minecraft, false sinon, ou erreur
     */
    public static function check_game_ownership($minecraft_access_token) {
        $url = 'https://api.minecraftservices.com/entitlements/mcstore';

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraft_access_token,
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('ownership_check_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('ownership_check_failed', 'Game ownership check failed', $body);
        }

        // Vérifier si le compte possède Minecraft
        if (empty($body['items']) || !is_array($body['items'])) {
            return false;
        }

        // Chercher product_minecraft ou game_minecraft
        foreach ($body['items'] as $item) {
            if (isset($item['name']) && 
                ($item['name'] === 'product_minecraft' || $item['name'] === 'game_minecraft')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère le profil Minecraft (UUID et username)
     *
     * @param string $minecraft_access_token Access token Minecraft
     * @return array|WP_Error UUID et username, ou erreur
     */
    public static function get_minecraft_profile($minecraft_access_token) {
        $url = 'https://api.minecraftservices.com/minecraft/profile';

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraft_access_token,
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('profile_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 404 || (isset($body['error']) && $body['error'] === 'NOT_FOUND')) {
            return new WP_Error('profile_not_found', 'Minecraft profile not found. The account may not have logged into the new Minecraft Launcher yet.');
        }

        if ($code < 200 || $code >= 300) {
            return new WP_Error('profile_failed', 'Failed to get Minecraft profile', $body);
        }

        if (empty($body['id']) || empty($body['name'])) {
            return new WP_Error('profile_invalid', 'Invalid Minecraft profile response');
        }

        return [
            'uuid' => $body['id'],
            'username' => $body['name']
        ];
    }

    /**
     * Récupère l'UUID Minecraft complet depuis l'ID Microsoft
     * 
     * Cette fonction nécessite un access token Microsoft valide.
     * Pour obtenir cet access token, il faut utiliser le flow OAuth2 Microsoft.
     * 
     * @param string $microsoft_access_token Access token Microsoft (obtenu via OAuth2)
     * @return array|WP_Error UUID et username Minecraft, ou erreur
     */
    public static function get_minecraft_uuid_from_microsoft_token($microsoft_access_token) {
        // 1. Authentifier avec Xbox Live
        $xbl_result = self::authenticate_with_xbox_live($microsoft_access_token);
        if (is_wp_error($xbl_result)) {
            return $xbl_result;
        }

        // 2. Obtenir le token XSTS
        $xsts_result = self::get_xsts_token($xbl_result['xbl_token']);
        if (is_wp_error($xsts_result)) {
            return $xsts_result;
        }

        // 3. Authentifier avec Minecraft
        $mc_result = self::authenticate_with_minecraft($xsts_result['xsts_token'], $xsts_result['userhash']);
        if (is_wp_error($mc_result)) {
            return $mc_result;
        }

        // 4. Vérifier la propriété du jeu
        $ownership = self::check_game_ownership($mc_result['access_token']);
        if (is_wp_error($ownership)) {
            return $ownership;
        }
        if (!$ownership) {
            return new WP_Error('no_ownership', 'Le compte ne possède pas Minecraft');
        }

        // 5. Récupérer le profil
        $profile = self::get_minecraft_profile($mc_result['access_token']);
        if (is_wp_error($profile)) {
            return $profile;
        }

        return $profile;
    }
}
