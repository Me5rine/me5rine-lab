<?php
// File: modules/subscription/functions/subscription-user-display.php

if (!defined('ABSPATH')) exit;

/**
 * Enregistre le shortcode pour les abonnements
 */
function admin_lab_subscription_shortcodes_init() {
    add_shortcode('admin_lab_subscriptions', 'admin_lab_subscription_shortcode');
}
add_action('init', 'admin_lab_subscription_shortcodes_init');

/**
 * Callback pour le shortcode admin_lab_subscriptions
 * 
 * @param array $atts Attributs du shortcode
 * @param string|null $content Contenu du shortcode
 * @return string HTML du rendu
 */
function admin_lab_subscription_shortcode($atts = [], $content = null) {
    return admin_lab_render_subscriptions();
}

/**
 * Génère l'URL de subscription pour un channel donné
 * 
 * @param array $channel Channel data avec provider_slug et settings
 * @return string URL de subscription ou chaîne vide si non configuré
 */
function admin_lab_get_subscription_url($channel) {
    $provider_slug = $channel['provider_slug'] ?? '';
    $settings = !empty($channel['settings']) ? maybe_unserialize($channel['settings']) : [];
    $url_identifier = $settings['subscription_url_identifier'] ?? '';
    
    if (empty($url_identifier)) {
        return '';
    }
    
    // Normaliser le provider_slug pour gérer les variants (twitch, twitch_me5rine, etc.)
    $base_provider = preg_replace('/^([^_]+).*$/', '$1', $provider_slug);
    
    switch ($base_provider) {
        case 'twitch':
            // URL format: https://www.twitch.tv/subs/username
            return 'https://www.twitch.tv/subs/' . esc_attr($url_identifier);
            
        case 'youtube':
        case 'youtube_no_api':
            // URL format: https://www.youtube.com/channel/CHANNEL_ID/join
            return 'https://www.youtube.com/channel/' . esc_attr($url_identifier) . '/join';
            
        case 'discord':
            // URL directe du Discord (peut être une invite URL complète ou juste l'ID)
            // Si c'est déjà une URL complète, la retourner telle quelle
            if (strpos($url_identifier, 'http') === 0) {
                return esc_url($url_identifier);
            }
            // Sinon, construire l'URL d'invite (format standard)
            return 'https://discord.gg/' . esc_attr($url_identifier);
            
        case 'tipeee':
            // URL format: https://fr.tipeee.com/username
            return 'https://fr.tipeee.com/' . esc_attr($url_identifier);
            
        case 'patreon':
            // URL format: https://www.patreon.com/c/username/membership
            return 'https://www.patreon.com/c/' . esc_attr($url_identifier) . '/membership';
            
        default:
            return '';
    }
}

/**
 * Récupère les abonnements actifs d'un utilisateur pour un channel donné
 * 
 * @param int $user_id ID de l'utilisateur WordPress
 * @param array $channel Channel data avec provider_slug et channel_identifier
 * @return array|null Subscription data ou null si pas d'abonnement actif
 */
function admin_lab_get_user_active_subscription_for_channel($user_id, $channel) {
    global $wpdb;
    
    $table = admin_lab_getTable('user_subscriptions');
    $provider_slug = $channel['provider_slug'] ?? '';
    $channel_identifier = $channel['channel_identifier'] ?? '';
    
    if (empty($provider_slug) || empty($channel_identifier)) {
        return null;
    }
    
    // Vérifier si la colonne provider_target_slug existe
    $columns = $wpdb->get_col("DESCRIBE {$table}");
    $has_provider_target_slug = in_array('provider_target_slug', $columns);
    
    // Construire la requête selon la structure de la table
    $where = $wpdb->prepare(
        "user_id = %d AND status = 'active'",
        $user_id
    );
    
    // Gérer les provider_slug avec variants (twitch_me5rine, youtube_me5rine, etc.)
    if ($has_provider_target_slug) {
        // Chercher soit provider_target_slug qui correspond, soit provider_slug qui correspond
        $where .= $wpdb->prepare(
            " AND ((provider_target_slug LIKE %s OR provider_target_slug = %s) OR (provider_target_slug IS NULL AND provider_slug LIKE %s))",
            $provider_slug . '%',
            $provider_slug,
            $provider_slug . '%'
        );
    } else {
        $where .= $wpdb->prepare(
            " AND provider_slug LIKE %s",
            $provider_slug . '%'
        );
    }
    
    // Chercher une correspondance avec le channel_identifier dans les metadata
    // Note: Le channel_identifier peut être stocké dans metadata ou dans une colonne dédiée
    // On cherche dans metadata au cas où
    $subscription = $wpdb->get_row(
        "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT 1",
        ARRAY_A
    );
    
    // Si on trouve une subscription, vérifier si elle correspond au channel
    // Le channel_identifier peut être stocké dans metadata selon le provider :
    // - Discord/YouTube No API/Tipeee : 'guild_id' dans metadata
    // - Twitch/YouTube : 'channel_id' dans metadata
    if ($subscription) {
        $metadata = !empty($subscription['metadata']) ? json_decode($subscription['metadata'], true) : [];
        
        // Chercher le channel identifier dans metadata selon le provider
        $metadata_channel_id = null;
        if (!empty($metadata)) {
            // Essayer différents noms de clés selon le provider
            $metadata_channel_id = $metadata['channel_id'] ?? $metadata['guild_id'] ?? $metadata['channel_identifier'] ?? null;
        }
        
        // Normaliser le base_provider pour la logique de matching
        $base_provider = preg_replace('/^([^_]+).*$/', '$1', $provider_slug);
        
        // Pour certains providers (Discord, YouTube No API, Tipeee), le matching se fait via guild_id
        // Pour Twitch et YouTube standard, c'est via channel_id
        // Si on trouve une correspondance dans metadata, c'est bon
        if ($metadata_channel_id === $channel_identifier) {
            return $subscription;
        }
        
        // Si pas de metadata ou metadata_channel_id null, pour certains providers on peut accepter
        // toute subscription active du provider (cas où il n'y a qu'un channel par provider)
        // Mais on préfère quand même avoir une correspondance précise
        // Pour l'instant, on retourne null si pas de correspondance précise
    }
    
    return null;
}

/**
 * Récupère tous les channels actifs pour affichage dans le profil utilisateur
 * avec leurs abonnements actifs pour un utilisateur donné
 * 
 * @param int $user_id ID de l'utilisateur WordPress
 * @return array Liste des channels avec statut d'abonnement
 */
function admin_lab_get_user_subscriptions_display_data($user_id) {
    // Récupérer tous les channels actifs
    $channels = admin_lab_get_subscription_channels();
    $active_channels = array_filter($channels, function($channel) {
        return !empty($channel['is_active']);
    });
    
    $result = [];
    
    foreach ($active_channels as $channel) {
        $subscription = admin_lab_get_user_active_subscription_for_channel($user_id, $channel);
        
        // Préparer les données d'affichage
        $provider_slug = $channel['provider_slug'] ?? '';
        $base_provider = preg_replace('/^([^_]+).*$/', '$1', $provider_slug);
        
        $result[] = [
            'channel' => $channel,
            'provider_slug' => $provider_slug,
            'base_provider' => $base_provider,
            'channel_name' => $channel['channel_name'] ?? '',
            'subscription' => $subscription,
            'is_active' => $subscription !== null,
            'subscription_url' => admin_lab_get_subscription_url($channel),
            'level_slug' => $subscription['level_slug'] ?? null,
            'level_name' => null, // Sera rempli plus tard si nécessaire
        ];
    }
    
    // Trier par provider puis par nom de channel
    usort($result, function($a, $b) {
        if ($a['base_provider'] !== $b['base_provider']) {
            return strcmp($a['base_provider'], $b['base_provider']);
        }
        return strcmp($a['channel_name'], $b['channel_name']);
    });
    
    return $result;
}

/**
 * Récupère le nom d'affichage d'un provider
 * 
 * @param string $base_provider Provider slug de base (twitch, youtube, discord, etc.)
 * @return string Nom d'affichage du provider
 */
function admin_lab_get_provider_display_name($base_provider) {
    $names = [
        'twitch' => 'Twitch',
        'youtube' => 'YouTube',
        'discord' => 'Discord',
        'tipeee' => 'Tipeee',
        'patreon' => 'Patreon',
    ];
    
    return $names[$base_provider] ?? ucfirst($base_provider);
}

/**
 * Récupère le nom du niveau d'abonnement pour affichage
 * 
 * @param string|null $level_slug Slug du niveau
 * @param string $base_provider Provider slug de base
 * @return string Nom d'affichage du niveau
 */
function admin_lab_get_subscription_level_display_name($level_slug, $base_provider) {
    if (empty($level_slug)) {
        return '';
    }
    
    // Noms par défaut selon le provider
    $default_names = [
        'tier1' => 'Tier 1',
        'tier2' => 'Tier 2',
        'tier3' => 'Tier 3',
        'booster' => 'Booster',
    ];
    
    // Si on a un nom par défaut, l'utiliser
    if (isset($default_names[$level_slug])) {
        return $default_names[$level_slug];
    }
    
    // Sinon, essayer de récupérer depuis la base de données
    if (function_exists('admin_lab_get_subscription_level')) {
        // Chercher le level par slug et provider
        global $wpdb;
        $table = admin_lab_getTable('subscription_levels');
        $level = $wpdb->get_row($wpdb->prepare(
            "SELECT level_name FROM {$table} WHERE level_slug = %s AND provider_slug LIKE %s LIMIT 1",
            $level_slug,
            $base_provider . '%'
        ), ARRAY_A);
        
        if ($level && !empty($level['level_name'])) {
            return $level['level_name'];
        }
    }
    
    // Fallback : retourner le slug avec majuscule
    return ucfirst(str_replace('_', ' ', $level_slug));
}

/**
 * Fonction de rendu pour afficher les abonnements dans le profil utilisateur
 * Reprend le même style que les connexions
 * 
 * @return string HTML du rendu
 */
function admin_lab_render_subscriptions() {
    // Rediriger vers l'onglet par défaut si l'utilisateur n'est pas connecté
    if (function_exists('admin_lab_redirect_to_default_profile_tab') && admin_lab_redirect_to_default_profile_tab()) {
        return ''; // Redirection effectuée, on ne retourne rien
    }
    
    if (!is_user_logged_in()) {
        // Protection si accès forcé (URL invalide)
        return '<p>' . __('You must be logged in.', 'me5rine-lab') . '</p>';
    }
    
    $current_user_id = get_current_user_id();
    $subscriptions_data = admin_lab_get_user_subscriptions_display_data($current_user_id);
    
    ob_start(); ?>
    <div class="me5rine-lab-profile-container">
        <h3 class="me5rine-lab-title-medium"><?php esc_html_e('Abonnements', 'me5rine-lab'); ?></h3>
        
        <?php if (empty($subscriptions_data)) : ?>
            <div class="me5rine-lab-form-message">
                <p><?php esc_html_e('Aucun abonnement configuré.', 'me5rine-lab'); ?></p>
            </div>
        <?php else : ?>
            <div class="me5rine-lab-subscriptions-list">
                <?php 
                $current_provider = null;
                foreach ($subscriptions_data as $item) : 
                    $channel = $item['channel'];
                    $channel_name = $item['channel_name'];
                    $provider_name = admin_lab_get_provider_display_name($item['base_provider']);
                    $subscription = $item['subscription'];
                    $is_active = $item['is_active'];
                    $subscription_url = $item['subscription_url'];
                    $level_name = admin_lab_get_subscription_level_display_name($item['level_slug'], $item['base_provider']);
                    
                    // Grouper par provider avec un titre
                    if ($current_provider !== $item['base_provider']) :
                        $current_provider = $item['base_provider'];
                        if ($current_provider !== reset($subscriptions_data)['base_provider']) :
                            echo '</div>'; // Fermer le groupe précédent
                        endif;
                ?>
                        <div class="me5rine-lab-subscriptions-provider-group">
                            <h4 class="me5rine-lab-subtitle"><?php echo esc_html($provider_name); ?></h4>
                <?php endif; ?>
                
                <div class="me5rine-lab-subscription-item me5rine-lab-form-view-item">
                    <div class="me5rine-lab-subscription-info" style="flex: 1;">
                        <div class="me5rine-lab-subscription-name" style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">
                            <?php echo esc_html($channel_name); ?>
                        </div>
                        <?php if ($is_active && !empty($level_name)) : ?>
                            <div class="me5rine-lab-subscription-level" style="font-size: 13px; color: var(--me5rine-lab-text-light, #5D697D); margin-bottom: 4px;">
                                <?php echo esc_html($level_name); ?>
                            </div>
                        <?php endif; ?>
                        <div class="me5rine-lab-subscription-status" style="display: inline-flex; align-items: center; gap: 6px;">
                            <?php if ($is_active) : ?>
                                <span class="admin-lab-status-active" style="color: #00b894; font-weight: 500; font-size: 13px;">
                                    ✓ <?php esc_html_e('Actif', 'me5rine-lab'); ?>
                                </span>
                            <?php else : ?>
                                <span class="admin-lab-status-inactive" style="color: #636e72; font-weight: 500; font-size: 13px;">
                                    ✗ <?php esc_html_e('Non abonné', 'me5rine-lab'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="me5rine-lab-subscription-action" style="margin-left: 16px;">
                        <?php if (!empty($subscription_url)) : ?>
                            <?php if ($is_active) : ?>
                                <a href="<?php echo esc_url($subscription_url); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="me5rine-lab-form-button me5rine-lab-form-button-secondary"
                                   style="white-space: nowrap;">
                                    <?php esc_html_e('Upgrade', 'me5rine-lab'); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url($subscription_url); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="me5rine-lab-form-button"
                                   style="white-space: nowrap;">
                                    <?php esc_html_e('S\'abonner', 'me5rine-lab'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <span style="font-size: 12px; color: var(--me5rine-lab-text-light, #5D697D);">
                                <?php esc_html_e('URL non configurée', 'me5rine-lab'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                        </div> <!-- Fermer le dernier groupe de provider -->
            </div>
        <?php endif; ?>
    </div>
    <?php
    return (string)ob_get_clean();
}

