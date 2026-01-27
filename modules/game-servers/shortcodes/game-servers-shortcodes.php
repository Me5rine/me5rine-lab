<?php
// File: modules/game-servers/shortcodes/game-servers-shortcodes.php

if (!defined('ABSPATH')) exit;

/**
 * Enregistre les shortcodes pour les serveurs de jeux
 */
function admin_lab_game_servers_register_shortcodes() {
    add_shortcode('game_servers_list', 'admin_lab_game_servers_shortcode_list');
    add_shortcode('game_server', 'admin_lab_game_servers_shortcode_single');
    add_shortcode('minecraft_link', 'admin_lab_game_servers_shortcode_minecraft_link');
}

/**
 * Shortcode pour afficher la liste des serveurs
 *
 * @param array $atts {
 *   @type string $status Filtrer par statut (active, inactive)
 *   @type int    $game_id Filtrer par jeu
 *   @type string $orderby Champ de tri
 *   @type string $order Direction (ASC, DESC)
 *   @type int    $limit Nombre de serveurs à afficher
 *   @type string $template Template à utiliser (default, compact)
 * }
 */
function admin_lab_game_servers_shortcode_list($atts) {
    $atts = shortcode_atts([
        'status' => 'active',
        'game_id' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
        'limit' => 0,
        'template' => 'default',
    ], $atts, 'game_servers_list');
    
    $args = [
        'status' => $atts['status'],
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    ];
    
    if (!empty($atts['game_id'])) {
        $args['game_id'] = (int) $atts['game_id'];
    }
    
    if (!empty($atts['limit'])) {
        $args['limit'] = (int) $atts['limit'];
    }
    
    $servers = admin_lab_game_servers_get_all($args);
    
    if (empty($servers)) {
        return '<p>' . __('No servers found.', 'me5rine-lab') . '</p>';
    }
    
    ob_start();
    
    $template = sanitize_file_name($atts['template']);
    $template_file = __DIR__ . '/../templates/list-' . $template . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Template par défaut
        include __DIR__ . '/../templates/list-default.php';
    }
    
    return ob_get_clean();
}

/**
 * Shortcode pour afficher un serveur unique
 *
 * @param array $atts {
 *   @type int $id ID du serveur
 *   @type string $template Template à utiliser
 * }
 */
function admin_lab_game_servers_shortcode_single($atts) {
    $atts = shortcode_atts([
        'id' => 0,
        'template' => 'default',
    ], $atts, 'game_server');
    
    $server_id = (int) $atts['id'];
    if ($server_id <= 0) {
        return '<p>' . __('Invalid server ID.', 'me5rine-lab') . '</p>';
    }
    
    $server = admin_lab_game_servers_get_by_id($server_id);
    
    if (!$server) {
        return '<p>' . __('Server not found.', 'me5rine-lab') . '</p>';
    }
    
    ob_start();
    
    $template = sanitize_file_name($atts['template']);
    $template_file = __DIR__ . '/../templates/single-' . $template . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Template par défaut
        include __DIR__ . '/../templates/single-default.php';
    }
    
    return ob_get_clean();
}

/**
 * Shortcode pour lier un compte Minecraft
 *
 * @param array $atts Attributs du shortcode
 * @return string HTML du shortcode
 */
function admin_lab_game_servers_shortcode_minecraft_link($atts) {
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>' . __('You must be logged in to link your Minecraft account.', 'me5rine-lab') . '</p>';
    }
    
    $user_id = get_current_user_id();
    
    // Charger les fonctions nécessaires
    require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
    
    // Récupérer le compte Minecraft lié
    $account = admin_lab_game_servers_get_minecraft_account($user_id);
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>
    <div class="minecraft-link-container" id="minecraft-link-container">
        <?php if (isset($_GET['minecraft_link_success'])) : ?>
            <div class="minecraft-link-message minecraft-link-success">
                <p><?php esc_html_e('Votre compte Minecraft a été lié avec succès !', 'me5rine-lab'); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['minecraft_link_error'])) : ?>
            <div class="minecraft-link-message minecraft-link-error">
                <p><?php echo esc_html(urldecode($_GET['minecraft_link_error'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($account) : ?>
            <div class="minecraft-account-linked">
                <h3><?php esc_html_e('Compte Minecraft lié', 'me5rine-lab'); ?></h3>
                <div class="minecraft-account-info">
                    <p><strong><?php esc_html_e('UUID:', 'me5rine-lab'); ?></strong> <code><?php echo esc_html($account['minecraft_uuid']); ?></code></p>
                    <?php if (!empty($account['minecraft_username'])) : ?>
                        <p><strong><?php esc_html_e('Username:', 'me5rine-lab'); ?></strong> <?php echo esc_html($account['minecraft_username']); ?></p>
                    <?php endif; ?>
                    <p><strong><?php esc_html_e('Lié le:', 'me5rine-lab'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($account['linked_at']))); ?></p>
                </div>
                <button type="button" class="button minecraft-unlink-button" id="minecraft-unlink-btn">
                    <?php esc_html_e('Délier le compte', 'me5rine-lab'); ?>
                </button>
            </div>
        <?php else : ?>
            <div class="minecraft-account-not-linked">
                <h3><?php esc_html_e('Lier votre compte Minecraft', 'me5rine-lab'); ?></h3>
                <p><?php esc_html_e('Cliquez sur le bouton ci-dessous pour lier votre compte Minecraft à votre compte WordPress. Vous serez redirigé vers Microsoft pour vous authentifier.', 'me5rine-lab'); ?></p>
                <button type="button" class="button button-primary minecraft-link-button" id="minecraft-link-btn">
                    <?php esc_html_e('Lier mon compte Minecraft', 'me5rine-lab'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .minecraft-link-container {
            max-width: 600px;
            margin: 20px 0;
        }
        .minecraft-link-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .minecraft-link-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .minecraft-link-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .minecraft-account-linked,
        .minecraft-account-not-linked {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .minecraft-account-info {
            margin: 15px 0;
        }
        .minecraft-account-info p {
            margin: 10px 0;
        }
        .minecraft-account-info code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .minecraft-link-button,
        .minecraft-unlink-button {
            margin-top: 15px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Bouton pour lier le compte
        $('#minecraft-link-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('<?php echo esc_js(__('Chargement...', 'me5rine-lab')); ?>');
            
            $.ajax({
                url: '<?php echo esc_url(rest_url('admin-lab-game-servers/v1/minecraft/init-link')); ?>',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                },
                success: function(response) {
                    if (response.success && response.auth_url) {
                        // Rediriger vers Microsoft OAuth
                        window.location.href = response.auth_url;
                    } else {
                        alert('<?php echo esc_js(__('Erreur lors de l\'initialisation de la liaison.', 'me5rine-lab')); ?>');
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Lier mon compte Minecraft', 'me5rine-lab')); ?>');
                    }
                },
                error: function(xhr) {
                    var errorMsg = '<?php echo esc_js(__('Erreur lors de l\'initialisation de la liaison.', 'me5rine-lab')); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Lier mon compte Minecraft', 'me5rine-lab')); ?>');
                }
            });
        });
        
        // Bouton pour délier le compte
        $('#minecraft-unlink-btn').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir délier votre compte Minecraft ?', 'me5rine-lab')); ?>')) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('<?php echo esc_js(__('Suppression...', 'me5rine-lab')); ?>');
            
            $.ajax({
                url: '<?php echo esc_url(rest_url('admin-lab-game-servers/v1/minecraft/unlink')); ?>',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        // Recharger la page pour afficher le formulaire de liaison
                        window.location.reload();
                    } else {
                        alert('<?php echo esc_js(__('Erreur lors de la suppression de la liaison.', 'me5rine-lab')); ?>');
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Délier le compte', 'me5rine-lab')); ?>');
                    }
                },
                error: function(xhr) {
                    var errorMsg = '<?php echo esc_js(__('Erreur lors de la suppression de la liaison.', 'me5rine-lab')); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Délier le compte', 'me5rine-lab')); ?>');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

