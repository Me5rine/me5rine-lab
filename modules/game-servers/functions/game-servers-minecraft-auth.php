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
            
            // Essayer d'extraire un message d'erreur plus détaillé
            if (is_array($body_response)) {
                if (isset($body_response['XErr'])) {
                    $error_msg .= ' (XErr: ' . $body_response['XErr'] . ')';
                }
                if (isset($body_response['Message'])) {
                    $error_msg .= ' - ' . $body_response['Message'];
                } elseif (isset($body_response['error'])) {
                    $error_msg .= ' - ' . $body_response['error'];
                    if (isset($body_response['error_description'])) {
                        $error_msg .= ': ' . $body_response['error_description'];
                    }
                }
            }
            
            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Xbox Live auth failed - Code: ' . $code . ', Response: ' . wp_json_encode($body_response));
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
                    2148916227 => __('Le compte est banni de Xbox.', 'me5rine-lab'),
                    2148916233 => __(
                        'Ce compte Microsoft n\'est pas encore lié à Minecraft/Xbox. Pour que la liaison fonctionne : utilisez exactement le même compte Microsoft que sur ce site ; ouvrez le Launcher Minecraft (Java ou Bedrock), connectez-vous avec ce compte et lancez le jeu au moins une fois ; si vous avez encore un ancien compte Mojang (Java), migrez-le d\'abord sur minecraft.net. Ensuite réessayez de lier votre compte ici.',
                        'me5rine-lab'
                    ),
                    2148916235 => __('Xbox Live n\'est pas disponible dans votre pays.', 'me5rine-lab'),
                    2148916236 => __('Vérification adulte requise (Corée du Sud).', 'me5rine-lab'),
                    2148916237 => __('Vérification adulte requise (Corée du Sud).', 'me5rine-lab'),
                    2148916238 => __('Le compte est un compte enfant et doit être ajouté à une Famille par un adulte.', 'me5rine-lab'),
                    2148916262 => __('Erreur inconnue.', 'me5rine-lab')
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
            $error_msg = 'Minecraft authentication failed';
            
            // Essayer d'extraire un message d'erreur plus détaillé
            if (is_array($body_response)) {
                if (isset($body_response['error'])) {
                    $error_msg .= ': ' . $body_response['error'];
                    if (isset($body_response['errorMessage'])) {
                        $error_msg .= ' - ' . $body_response['errorMessage'];
                    }
                } elseif (isset($body_response['errorMessage'])) {
                    $error_msg .= ': ' . $body_response['errorMessage'];
                } elseif (isset($body_response['message'])) {
                    $error_msg .= ': ' . $body_response['message'];
                } elseif (isset($body_response['error_description'])) {
                    $error_msg .= ': ' . $body_response['error_description'];
                }
            }
            
            // Messages d'erreur spécifiques avec instructions
            if (is_array($body_response) && isset($body_response['errorMessage'])) {
                $error_message = $body_response['errorMessage'];
                if (strpos($error_message, 'Invalid app registration') !== false || strpos($error_message, 'AppRegInfo') !== false) {
                    $error_msg = __('Configuration de l\'application Microsoft invalide. Veuillez vérifier la configuration de votre application Azure AD.', 'me5rine-lab');
                }
            }
            
            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Authentication failed - Code: ' . $code . ', Response: ' . wp_json_encode($body_response));
            }
            
            return new WP_Error('minecraft_auth_failed', $error_msg, $body_response);
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

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Requête ownership - GET ' . $url);
        }

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraft_access_token,
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Requête ownership - Erreur: ' . $response->get_error_message());
            }
            return new WP_Error('ownership_check_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Requête ownership - HTTP ' . $code . ', body: ' . $raw_body);
        }

        if ($code < 200 || $code >= 300) {
            $error_msg = 'Game ownership check failed';
            
            // Essayer d'extraire un message d'erreur plus détaillé
            if (is_array($body)) {
                if (isset($body['error'])) {
                    $error_msg .= ': ' . $body['error'];
                    if (isset($body['errorMessage'])) {
                        $error_msg .= ' - ' . $body['errorMessage'];
                    }
                } elseif (isset($body['message'])) {
                    $error_msg .= ': ' . $body['message'];
                }
            }
            
            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Ownership check failed - Code: ' . $code . ', Response: ' . wp_json_encode($body));
            }
            
            return new WP_Error('ownership_check_failed', $error_msg, $body);
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

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            $names = array_map(function ($i) { return $i['name'] ?? '?'; }, $body['items']);
            error_log('[Minecraft Auth] Requête ownership - Aucun product_minecraft/game_minecraft dans items: ' . implode(', ', $names));
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

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Requête profil - GET ' . $url);
        }

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraft_access_token,
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Requête profil - Erreur: ' . $response->get_error_message());
            }
            return new WP_Error('profile_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Requête profil - HTTP ' . $code . ', body: ' . $raw_body);
        }

        if ($code === 404 || (isset($body['error']) && $body['error'] === 'NOT_FOUND')) {
            return new WP_Error('profile_not_found', 'Minecraft profile not found. The account may not have logged into the new Minecraft Launcher yet.');
        }

        if ($code < 200 || $code >= 300) {
            $error_msg = 'Failed to get Minecraft profile';
            
            // Essayer d'extraire un message d'erreur plus détaillé
            if (is_array($body)) {
                if (isset($body['error'])) {
                    $error_msg .= ': ' . $body['error'];
                    if (isset($body['errorMessage'])) {
                        $error_msg .= ' - ' . $body['errorMessage'];
                    }
                } elseif (isset($body['message'])) {
                    $error_msg .= ': ' . $body['message'];
                }
            }
            
            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Profile failed - Code: ' . $code . ', Response: ' . wp_json_encode($body));
            }
            
            return new WP_Error('profile_failed', $error_msg, $body);
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
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte Minecraft - Début (token length: ' . strlen($microsoft_access_token) . ')');
        }

        // 1. Authentifier avec Xbox Live
        $xbl_result = self::authenticate_with_xbox_live($microsoft_access_token);
        if (is_wp_error($xbl_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 1 Xbox Live KO: ' . $xbl_result->get_error_code() . ' - ' . $xbl_result->get_error_message());
            }
            return $xbl_result;
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte - Étape 1 Xbox Live OK');
        }

        // 2. Obtenir le token XSTS
        $xsts_result = self::get_xsts_token($xbl_result['xbl_token']);
        if (is_wp_error($xsts_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 2 XSTS KO: ' . $xsts_result->get_error_code() . ' - ' . $xsts_result->get_error_message());
            }
            return $xsts_result;
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte - Étape 2 XSTS OK');
        }

        // 3. Authentifier avec Minecraft
        $mc_result = self::authenticate_with_minecraft($xsts_result['xsts_token'], $xsts_result['userhash']);
        if (is_wp_error($mc_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 3 Minecraft auth KO: ' . $mc_result->get_error_code() . ' - ' . $mc_result->get_error_message());
            }
            return $mc_result;
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte - Étape 3 Minecraft auth OK');
        }

        // 4. Vérifier la propriété du jeu
        $ownership = self::check_game_ownership($mc_result['access_token']);
        if (is_wp_error($ownership)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 4 Ownership KO: ' . $ownership->get_error_code() . ' - ' . $ownership->get_error_message());
            }
            return $ownership;
        }
        if (!$ownership) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 4 Ownership: compte ne possède pas Minecraft (items vides ou pas product_minecraft/game_minecraft)');
            }
            return new WP_Error('no_ownership', 'Le compte ne possède pas Minecraft');
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte - Étape 4 Ownership OK (compte possède Minecraft)');
        }

        // 5. Récupérer le profil
        $profile = self::get_minecraft_profile($mc_result['access_token']);
        if (is_wp_error($profile)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Demande compte - Étape 5 Profil KO: ' . $profile->get_error_code() . ' - ' . $profile->get_error_message());
            }
            return $profile;
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Demande compte - Étape 5 Profil OK - UUID: ' . ($profile['uuid'] ?? '') . ', username: ' . ($profile['username'] ?? ''));
        }

        return $profile;
    }
}
