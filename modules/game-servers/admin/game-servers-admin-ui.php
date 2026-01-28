<?php
// File: modules/game-servers/admin/game-servers-admin-ui.php

if (!defined('ABSPATH')) exit;

/**
 * Interface d'administration pour les serveurs de jeux
 */
function admin_lab_game_servers_admin_ui() {
    global $wpdb;
    
    // Vérifier que la classe DB est disponible
    if (!class_exists('Admin_Lab_DB')) {
        echo '<div class="wrap">';
        echo '<div class="notice notice-error"><p>' . __('Error: Admin_Lab_DB class is not available. The module cannot work properly.', 'me5rine-lab') . '</p></div>';
        echo '</div>';
        return;
    }
    
    // Vérifier que la fonction admin_lab_getTable existe
    if (!function_exists('admin_lab_getTable')) {
        echo '<div class="wrap">';
        echo '<div class="notice notice-error"><p>' . __('Error: admin_lab_getTable function is not available. The module cannot work properly.', 'me5rine-lab') . '</p></div>';
        echo '</div>';
        return;
    }
    
    // Gestion de la création manuelle des pages
    if (isset($_POST['admin_lab_create_game_servers_pages'])) {
        check_admin_referer('admin_lab_game_servers_pages');
        admin_lab_game_servers_create_pages();
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Game servers pages created successfully.', 'me5rine-lab') . '</p></div>';
    }
    
    // Gestion de la sauvegarde des paramètres Microsoft OAuth
    if (isset($_POST['admin_lab_save_microsoft_oauth']) && isset($_POST['microsoft_client_id'])) {
        check_admin_referer('admin_lab_microsoft_oauth_settings');
        $client_id = sanitize_text_field($_POST['microsoft_client_id']);
        $client_secret = isset($_POST['microsoft_client_secret']) ? sanitize_text_field($_POST['microsoft_client_secret']) : '';
        $api_key = isset($_POST['minecraft_api_key']) ? sanitize_text_field($_POST['minecraft_api_key']) : '';
        
        update_option('admin_lab_microsoft_client_id', $client_id);
        if (!empty($client_secret)) {
            update_option('admin_lab_microsoft_client_secret', $client_secret);
        }
        // Pour l'API key, on met à jour seulement si une valeur est fournie
        // Si le champ contient "••••••••", on ignore (c'est la valeur masquée)
        if (isset($_POST['minecraft_api_key']) && $_POST['minecraft_api_key'] !== '••••••••') {
            $api_key = sanitize_text_field($_POST['minecraft_api_key']);
            if (!empty($api_key)) {
                update_option('admin_lab_minecraft_api_key', $api_key);
            } else {
                // Si vide, supprimer la clé
                delete_option('admin_lab_minecraft_api_key');
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Microsoft OAuth settings saved successfully.', 'me5rine-lab') . '</p></div>';
    }
    
    
    // Script pour copier le token
    ?>
    <script>
    function copyToClipboard(elementId) {
        var element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999); // Pour mobile
        document.execCommand('copy');
        alert('<?php echo esc_js(__('Token copied to clipboard!', 'me5rine-lab')); ?>');
    }
    </script>
    <?php
    
    // Gestion des actions
    if (isset($_POST['action']) && isset($_POST['server_id'])) {
        check_admin_referer('admin_lab_game_servers_action');
        
        $server_id = (int) $_POST['server_id'];
        $action = sanitize_text_field($_POST['action']);
        
        if ($action === 'delete') {
            $result = admin_lab_game_servers_delete($server_id);
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . __('Server deleted successfully.', 'me5rine-lab') . '</p></div>';
            }
        }
    }
    
    // Gestion de l'édition/création
    if (isset($_GET['edit'])) {
        admin_lab_game_servers_admin_edit_form((int) $_GET['edit']);
        return;
    }
    
    if (isset($_GET['new'])) {
        admin_lab_game_servers_admin_edit_form(0);
        return;
    }
    
    // Liste des serveurs
    try {
        admin_lab_game_servers_admin_list();
    } catch (Exception $e) {
        echo '<div class="wrap">';
        echo '<div class="notice notice-error"><p><strong>' . __('Error:', 'me5rine-lab') . '</strong> ' . esc_html($e->getMessage()) . '</p></div>';
        echo '<p>' . __('If the problem persists, check WordPress PHP error logs.', 'me5rine-lab') . '</p>';
        echo '</div>';
    }
}

/**
 * Affiche la page de configuration Minecraft/Microsoft OAuth
 */
function admin_lab_game_servers_admin_minecraft_settings() {
    $client_id = get_option('admin_lab_microsoft_client_id', '');
    $client_secret = get_option('admin_lab_microsoft_client_secret', '');
    $api_key = get_option('admin_lab_minecraft_api_key', '');
    $redirect_uri = rest_url('admin-lab-game-servers/v1/minecraft/callback');
    $auth_endpoint = rest_url('me5rine-lab/v1/minecraft-auth');
    
    ?>
    <div class="wrap admin-lab-minecraft-oauth-fullwidth">
        <h1><?php esc_html_e('Configuration Minecraft / Microsoft OAuth', 'me5rine-lab'); ?></h1>
        
        <div class="card">
            <h2><?php esc_html_e('Paramètres Microsoft OAuth', 'me5rine-lab'); ?></h2>
            <p><?php esc_html_e('Pour permettre aux utilisateurs de lier leur compte Minecraft, vous devez configurer une application Microsoft Azure.', 'me5rine-lab'); ?></p>
            
            <h3><?php esc_html_e('Instructions :', 'me5rine-lab'); ?></h3>
            <ol>
                <li><?php esc_html_e('Allez sur', 'me5rine-lab'); ?> <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank">Azure Portal - App registrations</a></li>
                <li><?php esc_html_e('Cliquez sur "New registration"', 'me5rine-lab'); ?></li>
                <li><?php esc_html_e('Remplissez le formulaire :', 'me5rine-lab'); ?>
                    <ul>
                        <li><strong><?php esc_html_e('Name:', 'me5rine-lab'); ?></strong> <?php esc_html_e('Nom de votre application (ex: Minecraft Link)', 'me5rine-lab'); ?></li>
                        <li><strong><?php esc_html_e('Supported account types:', 'me5rine-lab'); ?></strong> <?php esc_html_e('"Accounts in any organizational directory and personal Microsoft accounts"', 'me5rine-lab'); ?></li>
                        <li><strong><?php esc_html_e('Redirect URI:', 'me5rine-lab'); ?></strong> <code><?php echo esc_html($redirect_uri); ?></code></li>
                    </ul>
                </li>
                <li><?php esc_html_e('Après la création, allez dans "API permissions" et ajoutez le scope "XboxLive.signin"', 'me5rine-lab'); ?></li>
                <li><?php esc_html_e('Important : Vous devez également demander l\'accès à l\'API Minecraft en remplissant', 'me5rine-lab'); ?> <a href="https://forms.office.com/pages/responsepage.aspx?id=v4j5cvGGr0GRqy180BHbR8x3Dy3mUMxLrDxfN2O6e8tUN0pWSVdOT1U2MUdBTk5aR0Y2N0pCTFhJVC4u" target="_blank"><?php esc_html_e('ce formulaire', 'me5rine-lab'); ?></a></li>
                <li><?php esc_html_e('Dans "Certificates & secrets", créez un "New client secret" et copiez la valeur', 'me5rine-lab'); ?></li>
                <li><?php esc_html_e('Copiez l\'"Application (client) ID" et le "Client secret" ci-dessous', 'me5rine-lab'); ?></li>
            </ol>
            
            <form method="post" action="">
                <?php wp_nonce_field('admin_lab_microsoft_oauth_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="microsoft_client_id"><?php esc_html_e('Client ID (Application ID)', 'me5rine-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="microsoft_client_id" name="microsoft_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('L\'Application (client) ID de votre application Azure', 'me5rine-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="microsoft_client_secret"><?php esc_html_e('Client Secret', 'me5rine-lab'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="microsoft_client_secret" name="microsoft_client_secret" value="<?php echo esc_attr($client_secret ? '••••••••' : ''); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Le Client secret créé dans Azure (laisser vide pour ne pas modifier)', 'me5rine-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Redirect URI', 'me5rine-lab'); ?></label>
                        </th>
                        <td>
                            <code><?php echo esc_html($redirect_uri); ?></code>
                            <button type="button" class="button" onclick="copyToClipboard('redirect-uri-text')"><?php esc_html_e('Copier', 'me5rine-lab'); ?></button>
                            <input type="text" id="redirect-uri-text" value="<?php echo esc_attr($redirect_uri); ?>" style="position: absolute; left: -9999px;" />
                            <p class="description"><?php esc_html_e('URL de redirection à configurer dans Azure', 'me5rine-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="minecraft_api_key"><?php esc_html_e('API Key (Optional)', 'me5rine-lab'); ?></label>
                        </th>
                        <td>
                            <?php if (!empty($api_key)) : ?>
                                <div style="margin-bottom: 10px;">
                                    <input type="text" id="minecraft_api_key_display" value="<?php echo esc_attr($api_key); ?>" class="regular-text" readonly style="background: #f0f0f0;" />
                                    <button type="button" class="button" onclick="copyToClipboard('minecraft_api_key_display')"><?php esc_html_e('Copy', 'me5rine-lab'); ?></button>
                                </div>
                                <p class="description" style="color: #d63638; font-weight: bold;">
                                    <?php esc_html_e('⚠️ Copy this key now if you need it for the mod configuration. It will be hidden after saving.', 'me5rine-lab'); ?>
                                </p>
                                <input type="password" id="minecraft_api_key" name="minecraft_api_key" value="<?php echo esc_attr('••••••••'); ?>" class="regular-text" style="display: none;" />
                            <?php else : ?>
                                <input type="password" id="minecraft_api_key" name="minecraft_api_key" value="" class="regular-text" />
                            <?php endif; ?>
                            <p class="description">
                                <?php esc_html_e('Optional API key for authenticating requests to the whitelist endpoint. If set, the mod must send this key in the X-Api-Key header or Authorization: Bearer header. Leave empty to disable authentication.', 'me5rine-lab'); ?>
                            </p>
                            <?php if (!empty($api_key)) : ?>
                                <p class="description">
                                    <strong><?php esc_html_e('Current API Key:', 'me5rine-lab'); ?></strong> <?php esc_html_e('Use this value in the mod configuration (wordpressApiKey).', 'me5rine-lab'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'me5rine-lab'), 'primary', 'admin_lab_save_microsoft_oauth'); ?>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Whitelist Endpoint', 'me5rine-lab'); ?></h2>
            <p><?php esc_html_e('The whitelist endpoint allows the Minecraft mod to check if a player UUID is authorized to connect.', 'me5rine-lab'); ?></p>
            <p><strong><?php esc_html_e('Endpoint URL:', 'me5rine-lab'); ?></strong></p>
            <code style="display: block; margin: 10px 0; padding: 10px; background: #f0f0f0;"><?php echo esc_html($auth_endpoint); ?>?uuid={uuid}</code>
            <p class="description">
                <?php esc_html_e('Method: GET', 'me5rine-lab'); ?><br>
                <?php esc_html_e('Parameter: uuid (Minecraft UUID with dashes)', 'me5rine-lab'); ?><br>
                <?php esc_html_e('Response: {"allowed": true} or {"allowed": false}', 'me5rine-lab'); ?><br>
                <?php esc_html_e('Authentication: Optional (X-Api-Key or Authorization: Bearer header if API key is configured)', 'me5rine-lab'); ?>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Formulaire d'édition/création
 */
function admin_lab_game_servers_admin_edit_form($server_id = 0) {
    $server = null;
    if ($server_id > 0) {
        $server = admin_lab_game_servers_get_by_id($server_id);
        if (!$server) {
            echo '<div class="notice notice-error"><p>' . __('Server not found.', 'me5rine-lab') . '</p></div>';
            admin_lab_game_servers_admin_list();
            return;
        }
    }
    
    // Traitement du formulaire
    if (isset($_POST['save_server'])) {
        check_admin_referer('admin_lab_game_servers_save');
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'game_id' => (int) ($_POST['game_id'] ?? 0),
            'ip_address' => $_POST['ip_address'] ?? '',
            'port' => (int) ($_POST['port'] ?? 0),
            'provider' => $_POST['provider'] ?? '',
            'provider_server_id' => $_POST['provider_server_id'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'max_players' => (int) ($_POST['max_players'] ?? 0),
            'current_players' => (int) ($_POST['current_players'] ?? 0),
            'version' => $_POST['version'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'banner_url' => $_POST['banner_url'] ?? '',
            'logo_url' => $_POST['logo_url'] ?? '',
            'enable_subscriber_whitelist' => isset($_POST['enable_subscriber_whitelist']) ? 1 : 0,
        ];
        
        if ($server_id > 0) {
            $result = admin_lab_game_servers_update($server_id, $data);
        } else {
            $result = admin_lab_game_servers_create($data);
        }
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            $redirect_url = add_query_arg([
                'page' => 'admin-lab-game-servers',
                'notice' => 'success',
            ], admin_url('admin.php'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    // Récupérer la liste des jeux depuis l'API si disponible
    $games_list = [];
    if (function_exists('admin_lab_comparator_api_request')) {
        $games_response = admin_lab_comparator_api_request('games', [
            'pagination[pageSize]' => 100,
            'sort[0]' => 'name',
        ]);
        
        if (!is_wp_error($games_response) && isset($games_response['data'])) {
            foreach ($games_response['data'] as $game) {
                $attrs = $game['attributes'] ?? [];
                $games_list[$game['id']] = $attrs['name'] ?? 'Jeu #' . $game['id'];
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $server_id > 0 ? __('Edit Server', 'me5rine-lab') : __('Add Server', 'me5rine-lab'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('admin_lab_game_servers_save'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Server Name', 'me5rine-lab'); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="text" id="name" name="name" value="<?php echo esc_attr($server['name'] ?? ''); ?>" class="regular-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="description"><?php _e('Description', 'me5rine-lab'); ?></label></th>
                    <td>
                        <?php
                        wp_editor(
                            $server['description'] ?? '',
                            'description',
                            [
                                'textarea_name' => 'description',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                            ]
                        );
                        ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="game_id"><?php _e('Associated Game (ClicksNGames)', 'me5rine-lab'); ?></label></th>
                    <td>
                        <?php if (!empty($games_list)) : ?>
                            <select id="game_id" name="game_id" class="admin-lab-select2" style="width: 300px;">
                                <option value="0"><?php _e('— None —', 'me5rine-lab'); ?></option>
                                <?php foreach ($games_list as $id => $name) : ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected($server['game_id'] ?? 0, $id); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="number" id="game_id" name="game_id" value="<?php echo esc_attr($server['game_id'] ?? 0); ?>" class="small-text" min="0">
                            <p class="description"><?php _e('Game ID in ClicksNGames', 'me5rine-lab'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="ip_address"><?php _e('IP Address', 'me5rine-lab'); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="text" id="ip_address" name="ip_address" value="<?php echo esc_attr($server['ip_address'] ?? ''); ?>" class="regular-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="port"><?php _e('Port', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="number" id="port" name="port" value="<?php echo esc_attr($server['port'] ?? 0); ?>" class="small-text" min="0" max="65535">
                        <p class="description"><?php _e('Server port (0 for default port)', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="provider"><?php _e('Provider', 'me5rine-lab'); ?></label></th>
                    <td>
                        <select id="provider" name="provider">
                            <option value=""><?php _e('— None —', 'me5rine-lab'); ?></option>
                            <option value="custom" <?php selected($server['provider'] ?? '', 'custom'); ?>><?php _e('Custom Plugin', 'me5rine-lab'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select if you are using a custom server plugin to send statistics', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="provider_server_id"><?php _e('Authentication Token', 'me5rine-lab'); ?></label></th>
                    <td>
                        <?php if ($server_id > 0) : 
                            $token = admin_lab_game_servers_get_server_token($server_id);
                            if (!is_wp_error($token)) :
                                $endpoint_url = admin_lab_game_servers_get_endpoint_url($server_id);
                        ?>
                            <div style="margin-bottom: 10px;">
                                <input type="text" id="server_token_display" value="<?php echo esc_attr($token); ?>" class="regular-text" readonly>
                                <button type="button" class="button" onclick="copyToClipboard('server_token_display')"><?php _e('Copy', 'me5rine-lab'); ?></button>
                            </div>
                            <p class="description">
                                <strong><?php _e('Endpoint URL:', 'me5rine-lab'); ?></strong><br>
                                <code style="display: block; margin-top: 5px; padding: 5px; background: #f0f0f0;"><?php echo esc_html($endpoint_url); ?></code>
                            </p>
                            <p class="description">
                                <?php _e('This token must be used in your server plugin to authenticate requests. The token is automatically generated and stored here.', 'me5rine-lab'); ?>
                            </p>
                        <?php else : ?>
                            <p class="description"><?php echo esc_html($token->get_error_message()); ?></p>
                        <?php endif; else : ?>
                            <p class="description"><?php _e('The token will be automatically generated after the server is created.', 'me5rine-lab'); ?></p>
                        <?php endif; ?>
                        <input type="hidden" id="provider_server_id" name="provider_server_id" value="<?php echo esc_attr($server['provider_server_id'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="status"><?php _e('Status', 'me5rine-lab'); ?></label></th>
                    <td>
                        <select id="status" name="status">
                            <option value="active" <?php selected($server['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'me5rine-lab'); ?></option>
                            <option value="inactive" <?php selected($server['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'me5rine-lab'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="max_players"><?php _e('Max Players', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="number" id="max_players" name="max_players" value="<?php echo esc_attr($server['max_players'] ?? 0); ?>" class="small-text" min="0">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="current_players"><?php _e('Current Players', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="number" id="current_players" name="current_players" value="<?php echo esc_attr($server['current_players'] ?? 0); ?>" class="small-text" min="0">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="version"><?php _e('Version', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="text" id="version" name="version" value="<?php echo esc_attr($server['version'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('Game version (e.g. 1.21.1)', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="tags"><?php _e('Tags', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="text" id="tags" name="tags" value="<?php echo esc_attr($server['tags'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('Tags separated by commas (e.g. PvP, Survival, RPG)', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="banner_url"><?php _e('Banner URL', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="url" id="banner_url" name="banner_url" value="<?php echo esc_attr($server['banner_url'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="logo_url"><?php _e('Logo URL', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="url" id="logo_url" name="logo_url" value="<?php echo esc_attr($server['logo_url'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                
                <?php
                // Afficher le checkbox pour la whitelist uniquement si c'est un serveur Minecraft
                // On vérifie si "minecraft" est dans les tags (insensible à la casse)
                $tags = strtolower($server['tags'] ?? '');
                $is_minecraft = (strpos($tags, 'minecraft') !== false);
                ?>
                <?php if ($is_minecraft || empty($server)) : ?>
                <tr>
                    <th><label for="enable_subscriber_whitelist"><?php _e('Minecraft Whitelist', 'me5rine-lab'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="enable_subscriber_whitelist" name="enable_subscriber_whitelist" value="1" <?php checked(!empty($server['enable_subscriber_whitelist'])); ?>>
                            <?php _e('Enable subscriber whitelist', 'me5rine-lab'); ?>
                        </label>
                        <p class="description">
                            <?php _e('If enabled, only users with an account type that has active modules will be allowed to connect to this Minecraft server.', 'me5rine-lab'); ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <p class="submit">
                <?php submit_button(__('Save', 'me5rine-lab'), 'primary', 'save_server', false); ?>
                <a href="<?php echo esc_url(add_query_arg(['page' => 'admin-lab-game-servers'], admin_url('admin.php'))); ?>" class="button">
                    <?php _e('Cancel', 'me5rine-lab'); ?>
                </a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Liste des serveurs
 */
function admin_lab_game_servers_admin_list() {
    // Onglets
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'servers';
    
    $servers = admin_lab_game_servers_get_all(['orderby' => 'name', 'order' => 'ASC']);
    
    if (isset($_GET['notice']) && $_GET['notice'] === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Server saved successfully.', 'me5rine-lab') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Game Servers', 'me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'admin-lab-game-servers', 'new' => '1'], admin_url('admin.php'))); ?>" class="page-title-action">
            <?php _e('Add Server', 'me5rine-lab'); ?>
        </a>
        
        <hr class="wp-header-end">
        
        <nav class="nav-tab-wrapper" style="margin-top: 20px;">
            <a href="?page=admin-lab-game-servers&tab=servers" class="nav-tab <?php echo $current_tab === 'servers' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Servers', 'me5rine-lab'); ?>
            </a>
            <a href="?page=admin-lab-game-servers&tab=minecraft" class="nav-tab <?php echo $current_tab === 'minecraft' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Minecraft Settings', 'me5rine-lab'); ?>
            </a>
        </nav>
        
        <?php if ($current_tab === 'minecraft') : ?>
            <?php admin_lab_game_servers_admin_minecraft_settings(); ?>
            <?php return; ?>
        <?php endif; ?>
        
        <div class="notice notice-info" style="margin-top: 20px;">
            <p><strong><?php _e('Quick Start Guide', 'me5rine-lab'); ?></strong></p>
            <ol style="margin-left: 20px;">
                <li><?php _e('Add your server using the button above', 'me5rine-lab'); ?></li>
                <li><?php _e('Select a provider if applicable', 'me5rine-lab'); ?></li>
                <li><?php _e('After saving, copy the authentication token displayed', 'me5rine-lab'); ?></li>
                <li><?php _e('Configure your server plugin/bot with this token', 'me5rine-lab'); ?></li>
                <li><?php printf(__('See the full documentation: %s', 'me5rine-lab'), '<a href="' . plugin_dir_url(dirname(dirname(__FILE__))) . 'docs/game-servers/SERVER_PLUGIN_GUIDE.md" target="_blank">' . __('Server Plugin Guide', 'me5rine-lab') . '</a>'); ?></li>
            </ol>
        </div>
        
        <hr>
        
        <h2><?php _e('Game Servers Pages', 'me5rine-lab'); ?></h2>
        <p><?php _e('Pages displaying the list of game servers are automatically created when the module is activated. You can manually recreate them if needed.', 'me5rine-lab'); ?></p>
        
        <?php
        $game_servers_pages = [
            'game-servers'     => __('Game Servers', 'me5rine-lab'),
            'minecraft-servers' => __('Minecraft Servers', 'me5rine-lab'),
        ];
        foreach ($game_servers_pages as $slug => $label) {
            $option_key = 'game_servers_page_' . $slug;
            $page_id = get_option($option_key);
            echo '<p>';
            if ($page_id && get_post_status($page_id)) {
                $page = get_post($page_id);
                if ($page) {
                    $page_url = get_permalink($page_id);
                    echo '<strong>' . esc_html($label) . ':</strong> ';
                    echo sprintf(
                        __('Page exists: %s', 'me5rine-lab'),
                        '<a href="' . esc_url($page_url) . '" target="_blank">' . esc_html($page->post_title) . '</a> (<a href="' . esc_url(admin_url('post.php?post=' . $page_id . '&action=edit')) . '">' . __('Edit', 'me5rine-lab') . '</a>)'
                    );
                }
            } else {
                echo '<strong>' . esc_html($label) . ':</strong> ' . __('Page has not been created yet.', 'me5rine-lab');
            }
            echo '</p>';
        }
        ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('admin_lab_game_servers_pages'); ?>
            <input type="submit" name="admin_lab_create_game_servers_pages" class="button button-primary" value="<?php esc_attr_e('Create Game Servers Pages', 'me5rine-lab'); ?>">
        </form>
        
        <?php if (empty($servers)) : ?>
            <p><?php _e('No servers registered.', 'me5rine-lab'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'me5rine-lab'); ?></th>
                        <th><?php _e('Game', 'me5rine-lab'); ?></th>
                        <th><?php _e('Address', 'me5rine-lab'); ?></th>
                        <th><?php _e('Players', 'me5rine-lab'); ?></th>
                        <th><?php _e('Provider', 'me5rine-lab'); ?></th>
                        <th><?php _e('Status', 'me5rine-lab'); ?></th>
                        <th><?php _e('Actions', 'me5rine-lab'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server) : 
                        $game_name = '';
                        if (!empty($server['game_id'])) {
                            $game = admin_lab_game_servers_get_game($server['game_id']);
                            if (!is_wp_error($game)) {
                                $game_name = $game['name'] ?? '';
                            }
                        }
                        $address = admin_lab_game_servers_format_address($server['ip_address'], $server['port']);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($server['name']); ?></strong></td>
                            <td><?php echo esc_html($game_name ?: '—'); ?></td>
                            <td><code><?php echo esc_html($address); ?></code></td>
                            <td>
                                <?php 
                                printf(
                                    __('%d / %d', 'me5rine-lab'),
                                    $server['current_players'],
                                    $server['max_players']
                                );
                                ?>
                            </td>
                            <td><?php echo esc_html($server['provider'] ?: '—'); ?></td>
                            <td><?php echo admin_lab_game_servers_get_status_badge($server['status']); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'admin-lab-game-servers', 'edit' => $server['id']], admin_url('admin.php'))); ?>" class="button button-small">
                                    <?php _e('Edit', 'me5rine-lab'); ?>
                                </a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this server?', 'me5rine-lab'); ?>');">
                                    <?php wp_nonce_field('admin_lab_game_servers_action'); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="server_id" value="<?php echo esc_attr($server['id']); ?>">
                                    <?php submit_button(__('Delete', 'me5rine-lab'), 'button-small button-link-delete', '', false); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

