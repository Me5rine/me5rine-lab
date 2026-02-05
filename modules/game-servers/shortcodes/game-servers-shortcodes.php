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
        'status' => '', // Par défaut, afficher tous les serveurs (actifs et inactifs)
        'game_id' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
        'limit' => 0,
        'template' => 'default',
    ], $atts, 'game_servers_list');
    
    $args = [
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    ];
    
    // Filtrer par statut seulement si spécifié explicitement
    if (!empty($atts['status'])) {
        $args['status'] = $atts['status'];
    }
    
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
        $default_template = __DIR__ . '/../templates/list-default.php';
        include $default_template;
    }
    
    $output = ob_get_clean();
    
    return $output;
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
        <?php
        if (function_exists('me5rine_display_profile_notice')) {
            me5rine_display_profile_notice();
        }
        ?>
        <?php if (isset($_GET['minecraft_link_error'])) : ?>
            <div class="minecraft-link-message minecraft-link-error">
                <p><strong><?php esc_html_e('Erreur:', 'me5rine-lab'); ?></strong> <?php echo esc_html(urldecode($_GET['minecraft_link_error'])); ?></p>
                <?php 
                $error_msg = urldecode($_GET['minecraft_link_error']);
                // Afficher des instructions spécifiques selon le type d'erreur
                if (strpos($error_msg, 'compte Xbox') !== false || strpos($error_msg, 'minecraft.net') !== false) : ?>
                    <div class="minecraft-link-help">
                        <p><strong><?php esc_html_e('Solution:', 'me5rine-lab'); ?></strong></p>
                        <ol>
                            <li><?php esc_html_e('Allez sur', 'me5rine-lab'); ?> <a href="https://minecraft.net" target="_blank" rel="noopener">minecraft.net</a></li>
                            <li><?php esc_html_e('Connectez-vous avec votre compte Microsoft', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Cela créera automatiquement un compte Xbox associé à votre compte Microsoft', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Une fois fait, revenez ici et réessayez de lier votre compte', 'me5rine-lab'); ?></li>
                        </ol>
                    </div>
                <?php elseif (strpos($error_msg, 'ne possède pas Minecraft') !== false) : ?>
                    <div class="minecraft-link-help">
                        <p><strong><?php esc_html_e('Information:', 'me5rine-lab'); ?></strong></p>
                        <p><?php esc_html_e('Votre compte Microsoft ne possède pas Minecraft. Vous devez acheter Minecraft pour pouvoir lier votre compte.', 'me5rine-lab'); ?></p>
                    </div>
                <?php elseif (strpos($error_msg, 'Invalid app registration') !== false || strpos($error_msg, 'AppRegInfo') !== false || strpos($error_msg, 'Configuration de l\'application Microsoft') !== false) : ?>
                    <div class="minecraft-link-help">
                        <p><strong><?php esc_html_e('Erreur de configuration:', 'me5rine-lab'); ?></strong></p>
                        <p><?php esc_html_e('L\'application Microsoft n\'est pas correctement configurée. Veuillez contacter l\'administrateur du site.', 'me5rine-lab'); ?></p>
                        <p><strong><?php esc_html_e('Pour l\'administrateur:', 'me5rine-lab'); ?></strong></p>
                        <ol>
                            <li><?php esc_html_e('Vérifiez que l\'application Azure AD est correctement enregistrée', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Assurez-vous que le scope "XboxLive.signin" est ajouté dans les API permissions', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Vérifiez que l\'URI de redirection est correctement configuré', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Consultez', 'me5rine-lab'); ?> <a href="https://aka.ms/AppRegInfo" target="_blank" rel="noopener">https://aka.ms/AppRegInfo</a> <?php esc_html_e('pour plus d\'informations', 'me5rine-lab'); ?></li>
                        </ol>
                    </div>
                <?php elseif (strpos($error_msg, 'Minecraft authentication failed') !== false || strpos($error_msg, 'authentication failed') !== false) : ?>
                    <div class="minecraft-link-help">
                        <p><strong><?php esc_html_e('Information:', 'me5rine-lab'); ?></strong></p>
                        <p><?php esc_html_e('L\'authentification avec Minecraft a échoué. Cela peut être dû à plusieurs raisons :', 'me5rine-lab'); ?></p>
                        <ul>
                            <li><?php esc_html_e('Le compte n\'a pas de compte Xbox associé', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Le compte n\'a pas encore été utilisé avec le nouveau launcher Minecraft', 'me5rine-lab'); ?></li>
                            <li><?php esc_html_e('Un problème temporaire avec les services Microsoft/Minecraft', 'me5rine-lab'); ?></li>
                        </ul>
                        <p><?php esc_html_e('Essayez de vous connecter sur minecraft.net avec votre compte Microsoft, puis réessayez.', 'me5rine-lab'); ?></p>
                    </div>
                <?php endif; ?>
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
        .minecraft-link-help {
            margin-top: 15px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
        }
        .minecraft-link-help ol {
            margin: 10px 0 0 20px;
        }
        .minecraft-link-help li {
            margin: 8px 0;
        }
        .minecraft-link-help a {
            color: #0066cc;
            text-decoration: underline;
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
                data: JSON.stringify({ return_url: window.location.href }),
                contentType: 'application/json',
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

/**
 * Affiche la section "Comptes de jeu" (Minecraft, etc.) dans l'onglet Connexions / Comptes liés (KAP).
 * Appelé via le hook admin_lab_kap_after_connections.
 */
function admin_lab_game_servers_render_linked_game_accounts_section() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $account = admin_lab_game_servers_get_minecraft_account($user_id);

    $return_url = '';
    if (function_exists('admin_lab_get_current_user_profile_url')) {
        $return_url = admin_lab_get_current_user_profile_url('connexions');
    }
    if (empty($return_url)) {
        $return_url = home_url();
    }

    wp_enqueue_script('jquery');

    if (function_exists('me5rine_display_profile_notice')) {
        me5rine_display_profile_notice();
    }

    $minecraft_connected = (bool) $account;
    $content_spacing_class = $minecraft_connected ? 'me5rine-lab-form-view-content-spaced' : 'me5rine-lab-form-view-content-no-spacing';
    $details_html = '';
    if ($minecraft_connected) {
        $parts = array_filter([
            !empty($account['minecraft_username']) ? esc_html($account['minecraft_username']) : '',
            !empty($account['minecraft_uuid']) ? '<code class="me5rine-lab-form-view-uuid">' . esc_html($account['minecraft_uuid']) . '</code>' : '',
        ]);
        $details_html = '<div class="me5rine-lab-form-view-details">' . implode(' ', $parts) . '</div>';
    }
    ?>
    <div class="me5rine-lab-profile-container me5rine-lab-game-accounts-section" style="margin-top: 1.5em;">
        <h4 class="me5rine-lab-subtitle"><?php esc_html_e('Game accounts', 'me5rine-lab'); ?></h4>

        <div id="admin-lab-kap-game-accounts" class="admin-lab-kap-connections-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="me5rine-lab-form-view-item" id="minecraft-link-kap-container">
                <div class="me5rine-lab-form-view-content <?php echo esc_attr($content_spacing_class); ?>">
                    <strong><?php esc_html_e('Minecraft', 'me5rine-lab'); ?></strong>
                    <?php if ($minecraft_connected) : ?>
                        <span class="me5rine-lab-status me5rine-lab-status-success" title="<?php esc_attr_e('Connected', 'me5rine-lab'); ?>"><?php esc_html_e('Connected', 'me5rine-lab'); ?></span>
                    <?php else : ?>
                        <span class="me5rine-lab-status me5rine-lab-status-warning" title="<?php esc_attr_e('Not Connected', 'me5rine-lab'); ?>"><?php esc_html_e('Not Connected', 'me5rine-lab'); ?></span>
                    <?php endif; ?>
                    <div class="me5rine-lab-form-view-action">
                        <?php if ($minecraft_connected) : ?>
                            <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-danger minecraft-unlink-kap" id="minecraft-unlink-kap-btn">
                                <?php esc_html_e('Disconnect', 'me5rine-lab'); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="me5rine-lab-form-button minecraft-link-kap" id="minecraft-link-kap-btn">
                                <?php esc_html_e('Connect', 'me5rine-lab'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php echo $details_html; ?>
            </div>
        </div>
    </div>

    <style>
        .admin-lab-kap-connections-grid .me5rine-lab-form-view-uuid { font-size: 0.9em; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        @media (max-width: 768px) {
            .admin-lab-kap-connections-grid { grid-template-columns: 1fr !important; }
        }
    </style>

    <script>
    (function($) {
        if (!$ || !$.ajax) return;
        var initUrl = <?php echo wp_json_encode(rest_url('admin-lab-game-servers/v1/minecraft/init-link')); ?>;
        var unlinkUrl = <?php echo wp_json_encode(rest_url('admin-lab-game-servers/v1/minecraft/unlink')); ?>;
        var returnUrl = <?php echo wp_json_encode($return_url); ?>;
        var nonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;

        $('#minecraft-link-kap-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text(<?php echo wp_json_encode(__('Loading…', 'me5rine-lab')); ?>);
            $.ajax({
                url: initUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ return_url: returnUrl }),
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
                success: function(res) {
                    if (res.success && res.auth_url) {
                        window.location.href = res.auth_url;
                    } else {
                        $btn.prop('disabled', false).text(<?php echo wp_json_encode(__('Connect', 'me5rine-lab')); ?>);
                        alert(<?php echo wp_json_encode(__('Error initializing link.', 'me5rine-lab')); ?>);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(<?php echo wp_json_encode(__('Connect', 'me5rine-lab')); ?>);
                    alert(<?php echo wp_json_encode(__('Error initializing link.', 'me5rine-lab')); ?>);
                }
            });
        });

        $('#minecraft-unlink-kap-btn').on('click', function() {
            if (!confirm(<?php echo wp_json_encode(__('Are you sure you want to disconnect your Minecraft account?', 'me5rine-lab')); ?>)) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text(<?php echo wp_json_encode(__('Disconnecting…', 'me5rine-lab')); ?>);
            $.ajax({
                url: unlinkUrl,
                method: 'POST',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
                success: function(res) {
                    if (res.success) {
                        window.location.reload();
                    } else {
                        $btn.prop('disabled', false).text(<?php echo wp_json_encode(__('Disconnect', 'me5rine-lab')); ?>);
                        alert(<?php echo wp_json_encode(__('Error disconnecting.', 'me5rine-lab')); ?>);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(<?php echo wp_json_encode(__('Disconnect', 'me5rine-lab')); ?>);
                    alert(<?php echo wp_json_encode(__('Error disconnecting.', 'me5rine-lab')); ?>);
                }
            });
        });
    })(typeof jQuery !== 'undefined' ? jQuery : null);
    </script>
    <?php
}
