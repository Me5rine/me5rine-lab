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
    
    // Migration : vérifier et ajouter le champ enable_subscriber_whitelist si manquant
    $table_name = admin_lab_getTable('game_servers', true);
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'enable_subscriber_whitelist'",
        DB_NAME, $table_name
    ));
    
    if (empty($column_exists)) {
        $db = Admin_Lab_DB::getInstance();
        $db->createGameServersTable(); // Exécute la migration
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Database table updated successfully.', 'me5rine-lab') . '</p></div>';
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
    
    
    // Script pour copier dans le presse-papier (API key, redirect URI, etc.)
    ?>
    <script>
    function copyToClipboard(elementId) {
        var element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999); // Pour mobile
        document.execCommand('copy');
        alert('<?php echo esc_js(__('Copied to clipboard!', 'me5rine-lab')); ?>');
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
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Récupération des stats depuis le mod', 'me5rine-lab'); ?></h2>
            <p><?php esc_html_e('Le mod Minecraft expose un serveur HTTP sur le port 25566 (par défaut) qui sert les stats en JSON. WordPress récupère automatiquement ces stats toutes les minutes via un cron.', 'me5rine-lab'); ?></p>
            <p><strong><?php esc_html_e('Comment ça fonctionne :', 'me5rine-lab'); ?></strong></p>
            <ol>
                <li><?php esc_html_e('Le mod doit avoir activé le serveur HTTP des stats dans me5rinelab.json :', 'me5rine-lab'); ?>
                    <ul>
                        <li><code>statsEnabled: true</code></li>
                        <li><code>statsPort: 25566</code> (ou le port que vous utilisez)</li>
                        <li><code>statsSecret: "..."</code> (optionnel, si défini, configurer dans chaque serveur)</li>
                    </ul>
                </li>
                <li><?php esc_html_e('WordPress appelle automatiquement <code>http://IP_SERVEUR_MC:PORT/stats</code> toutes les minutes', 'me5rine-lab'); ?></li>
                <li><?php esc_html_e('Les stats sont mises à jour dans la base de données et affichées sur le site', 'me5rine-lab'); ?></li>
            </ol>
            <p><strong><?php esc_html_e('Configuration par serveur :', 'me5rine-lab'); ?></strong></p>
            <p><?php esc_html_e('Pour chaque serveur, configurez dans le formulaire d\'édition :', 'me5rine-lab'); ?></p>
            <ul>
                <li><strong><?php esc_html_e('Stats Port (Mod HTTP):', 'me5rine-lab'); ?></strong> <?php esc_html_e('Port du serveur HTTP du mod (par défaut: 25566)', 'me5rine-lab'); ?></li>
                <li><strong><?php esc_html_e('Stats Secret (Optional):', 'me5rine-lab'); ?></strong> <?php esc_html_e('Secret défini dans me5rinelab.json (statsSecret). Si défini, sera envoyé dans Authorization: Bearer (pull et push).', 'me5rine-lab'); ?></li>
            </ul>
            
            <h3><?php esc_html_e('Push automatique (recommandé)', 'me5rine-lab'); ?></h3>
            <p><?php esc_html_e('Pour que les stats se mettent à jour sans que WordPress appelle le mod (sans cron pull), activez le push dans le mod.', 'me5rine-lab'); ?></p>
            <p><?php esc_html_e('Dans <code>config/me5rinelab.json</code> du mod :', 'me5rine-lab'); ?></p>
            <ul>
                <li><code>statsPushEnabled: true</code></li>
                <li><code>statsPushUrl: "<?php echo esc_html(rest_url('me5rine-lab/v1/game-servers/stats')); ?>"</code></li>
                <li><code>statsPushIntervalSeconds: 60</code> (minimum 10)</li>
            </ul>
            <p class="description">
                <?php esc_html_e('Le mod envoie alors le même JSON (online, max, version) en POST vers cette URL. Le serveur est identifié par son IP (REMOTE_ADDR). Si vous avez défini Stats Secret sur le serveur, le mod envoie aussi Authorization: Bearer &lt;statsSecret&gt;.', 'me5rine-lab'); ?>
            </p>
            
            <?php
            // Bouton pour tester la récupération manuelle
            if (isset($_POST['admin_lab_test_fetch_stats']) && isset($_POST['server_id'])) {
                check_admin_referer('admin_lab_test_fetch_stats');
                $test_server_id = (int) $_POST['server_id'];
                require_once __DIR__ . '/../functions/game-servers-stats-fetcher.php';
                $result = admin_lab_game_servers_update_stats_from_mod($test_server_id);
                if (is_wp_error($result)) {
                    echo '<div class="notice notice-error"><p><strong>' . __('Error:', 'me5rine-lab') . '</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>' . __('Stats fetched and updated successfully!', 'me5rine-lab') . '</p></div>';
                }
            }
            
            // Récupérer les serveurs actifs pour le test
            $servers_for_test = admin_lab_game_servers_get_all(['status' => 'active']);
            ?>
            <?php if (!empty($servers_for_test)) : ?>
                <h3 style="margin-top: 20px;"><?php esc_html_e('Test manuel de récupération', 'me5rine-lab'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('admin_lab_test_fetch_stats'); ?>
                    <p>
                        <select name="server_id" required>
                            <option value=""><?php esc_html_e('— Select a server —', 'me5rine-lab'); ?></option>
                            <?php foreach ($servers_for_test as $srv) : ?>
                                <option value="<?php echo esc_attr($srv['id']); ?>">
                                    <?php echo esc_html($srv['name']); ?> 
                                    (<?php echo esc_html($srv['ip_address'] . ':' . (!empty($srv['stats_port']) ? $srv['stats_port'] : 25566)); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="admin_lab_test_fetch_stats" class="button button-primary" style="margin-left: 10px;">
                            <?php esc_html_e('Fetch Stats Now', 'me5rine-lab'); ?>
                        </button>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Teste la récupération des stats depuis le mod pour le serveur sélectionné. L\'URL appelée sera : http://IP:PORT/stats', 'me5rine-lab'); ?>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Vérification des mises à jour', 'me5rine-lab'); ?></h2>
            <p><?php esc_html_e('Dernières mises à jour récupérées depuis le mod (basé sur updated_at dans la base de données) :', 'me5rine-lab'); ?></p>
            <?php
            global $wpdb;
            $table_name = admin_lab_getTable('game_servers', true);
            $servers = $wpdb->get_results(
                "SELECT id, name, ip_address, port, current_players, max_players, version, status, updated_at 
                 FROM {$table_name} 
                 WHERE status IN ('active', 'inactive') 
                 ORDER BY updated_at DESC 
                 LIMIT 10",
                ARRAY_A
            );
            ?>
            <?php if (!empty($servers)) : ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Server', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('IP:Port', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Players', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Version', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Status', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Last Update', 'me5rine-lab'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $server) : 
                            $last_update = strtotime($server['updated_at']);
                            $time_diff = time() - $last_update;
                            $is_recent = $time_diff < 300; // Moins de 5 minutes
                            $time_ago = human_time_diff($last_update, current_time('timestamp'));
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($server['name']); ?></strong></td>
                                <td><code><?php echo esc_html($server['ip_address'] . ':' . $server['port']); ?></code></td>
                                <td><?php printf(__('%d / %d', 'me5rine-lab'), $server['current_players'], $server['max_players']); ?></td>
                                <td><?php echo esc_html($server['version'] ?: '—'); ?></td>
                                <td>
                                    <?php 
                                    $status_class = $server['status'] === 'active' ? 'status-active' : 'status-inactive';
                                    $status_text = $server['status'] === 'active' ? __('Online', 'me5rine-lab') : __('Offline', 'me5rine-lab');
                                    echo '<span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo $is_recent ? '#46b450' : '#999'; ?>;">
                                        <?php 
                                        if ($time_diff < 60) {
                                            echo __('Just now', 'me5rine-lab');
                                        } else {
                                            printf(__('%s ago', 'me5rine-lab'), $time_ago);
                                        }
                                        ?>
                                    </span>
                                    <br>
                                    <small style="color: #999;"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update)); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description" style="margin-top: 10px;">
                    <?php esc_html_e('Note: Les mises à jour sont affichées en vert si elles ont été récupérées il y a moins de 5 minutes. Le cron WordPress récupère les stats automatiquement toutes les minutes.', 'me5rine-lab'); ?>
                </p>
            <?php else : ?>
                <p><?php esc_html_e('Aucun serveur trouvé.', 'me5rine-lab'); ?></p>
            <?php endif; ?>
            
            <h3 style="margin-top: 20px;"><?php esc_html_e('Test manuel de l\'URL du mod', 'me5rine-lab'); ?></h3>
            <p><?php esc_html_e('Vous pouvez tester directement l\'URL du mod avec cette commande cURL (remplacez l\'IP et le port stats par ceux d\'un serveur) :', 'me5rine-lab'); ?></p>
            <textarea readonly style="width: 100%; height: 100px; font-family: monospace; font-size: 12px; padding: 10px; background: #f0f0f0;"><?php 
            $test_ip = '51.68.102.178';
            $test_stats_port = 25566;
            echo 'curl "http://' . $test_ip . ':' . $test_stats_port . '/stats"' . "\n";
            echo '# Ou avec secret (si configuré) :' . "\n";
            echo 'curl -H "Authorization: Bearer VOTRE_SECRET" "http://' . $test_ip . ':' . $test_stats_port . '/stats"';
            ?></textarea>
            <p class="description">
                <?php esc_html_e('Cette commande teste directement le serveur HTTP du mod. La réponse devrait être du JSON avec online, max, version, motd, players, etc.', 'me5rine-lab'); ?>
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
            'page_url' => $_POST['page_url'] ?? '',
            'enable_subscriber_whitelist' => isset($_POST['enable_subscriber_whitelist']) ? 1 : 0,
            'stats_port' => (int) ($_POST['stats_port'] ?? 25566),
            'stats_secret' => sanitize_text_field($_POST['stats_secret'] ?? ''),
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
                        <p class="description"><?php _e('Server port (0 for default port, e.g. 25565 for Minecraft)', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="stats_port"><?php _e('Stats Port (Mod HTTP)', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="number" id="stats_port" name="stats_port" value="<?php echo esc_attr($server['stats_port'] ?? 25566); ?>" class="small-text" min="1" max="65535">
                        <p class="description"><?php _e('Port du serveur HTTP du mod pour récupérer les stats (par défaut: 25566). URL: http://IP:PORT/stats', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="stats_secret"><?php _e('Stats Secret (Optional)', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="password" id="stats_secret" name="stats_secret" value="<?php echo esc_attr($server['stats_secret'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('Secret défini dans me5rinelab.json (statsSecret). Si défini, sera envoyé dans le header Authorization: Bearer', 'me5rine-lab'); ?></p>
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
                    <th><label for="banner_url"><?php _e('Banner Image', 'me5rine-lab'); ?></label></th>
                    <td>
                        <?php
                        wp_enqueue_media();
                        $banner_url = $server['banner_url'] ?? '';
                        ?>
                        <input type="hidden" id="banner_url" name="banner_url" value="<?php echo esc_attr($banner_url); ?>">
                        <button type="button" class="button upload_banner_image" style="margin-right: 5px;">
                            <?php _e('Select Image', 'me5rine-lab'); ?>
                        </button>
                        <button type="button" class="button remove_banner_image" <?php echo empty($banner_url) ? 'style="display:none;"' : ''; ?>>
                            <?php _e('Remove', 'me5rine-lab'); ?>
                        </button>
                        <div style="margin-top: 10px;">
                            <img id="banner_url_preview" src="<?php echo esc_url($banner_url); ?>" style="max-width: 300px; max-height: 150px; <?php echo empty($banner_url) ? 'display:none;' : ''; ?> border: 1px solid #ddd; padding: 5px;">
                        </div>
                        <p class="description"><?php _e('Banner image displayed on the front-end server card. Select from the media library.', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="logo_url"><?php _e('Logo Image', 'me5rine-lab'); ?></label></th>
                    <td>
                        <?php $logo_url = $server['logo_url'] ?? ''; ?>
                        <input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr($logo_url); ?>">
                        <button type="button" class="button upload_logo_image" style="margin-right: 5px;">
                            <?php _e('Select Image', 'me5rine-lab'); ?>
                        </button>
                        <button type="button" class="button remove_logo_image" <?php echo empty($logo_url) ? 'style="display:none;"' : ''; ?>>
                            <?php _e('Remove', 'me5rine-lab'); ?>
                        </button>
                        <div style="margin-top: 10px;">
                            <img id="logo_url_preview" src="<?php echo esc_url($logo_url); ?>" style="max-width: 120px; max-height: 120px; <?php echo empty($logo_url) ? 'display:none;' : ''; ?> border: 1px solid #ddd; padding: 5px;">
                        </div>
                        <p class="description"><?php _e('Logo displayed on the server card (e.g. game logo). Select from the media library.', 'me5rine-lab'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="page_url"><?php _e('Server Page URL', 'me5rine-lab'); ?></label></th>
                    <td>
                        <input type="url" id="page_url" name="page_url" value="<?php echo esc_attr($server['page_url'] ?? ''); ?>" class="regular-text" placeholder="<?php echo esc_attr(home_url('/game-servers')); ?>">
                        <p class="description">
                            <?php _e('Custom URL for the "View server page" link. Can be a page on this site or an external URL. If empty, WordPress will use the default server list page.', 'me5rine-lab'); ?>
                            <br>
                            <?php _e('Examples:', 'me5rine-lab'); ?> 
                            <code><?php echo esc_html(home_url('/game-servers')); ?></code> 
                            <?php _e('or', 'me5rine-lab'); ?> 
                            <code>https://example.com/server-page</code>
                        </p>
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
        
        <script>
        jQuery(function($) {
            var bannerFrame;
            $('.upload_banner_image').on('click', function(e) {
                e.preventDefault();
                if (bannerFrame) {
                    bannerFrame.open();
                    return;
                }
                bannerFrame = wp.media({
                    title: '<?php echo esc_js(__('Select or Upload Server Banner Image', 'me5rine-lab')); ?>',
                    button: { text: '<?php echo esc_js(__('Use this image', 'me5rine-lab')); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                bannerFrame.on('select', function() {
                    var attachment = bannerFrame.state().get('selection').first().toJSON();
                    $('#banner_url').val(attachment.url);
                    $('#banner_url_preview').attr('src', attachment.url).show();
                    $('.remove_banner_image').show();
                });
                bannerFrame.open();
            });
            $('.remove_banner_image').on('click', function(e) {
                e.preventDefault();
                $('#banner_url').val('');
                $('#banner_url_preview').attr('src', '').hide();
                $(this).hide();
            });

            var logoFrame;
            $('.upload_logo_image').on('click', function(e) {
                e.preventDefault();
                if (logoFrame) {
                    logoFrame.open();
                    return;
                }
                logoFrame = wp.media({
                    title: '<?php echo esc_js(__('Select or Upload Logo Image', 'me5rine-lab')); ?>',
                    button: { text: '<?php echo esc_js(__('Use this image', 'me5rine-lab')); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                logoFrame.on('select', function() {
                    var attachment = logoFrame.state().get('selection').first().toJSON();
                    $('#logo_url').val(attachment.url);
                    $('#logo_url_preview').attr('src', attachment.url).show();
                    $('.remove_logo_image').show();
                });
                logoFrame.open();
            });
            $('.remove_logo_image').on('click', function(e) {
                e.preventDefault();
                $('#logo_url').val('');
                $('#logo_url_preview').attr('src', '').hide();
                $(this).hide();
            });
        });
        </script>
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
                <li><?php _e('For Minecraft: configure Microsoft OAuth and API key in the Minecraft tab, then use the mod to sync stats and whitelist.', 'me5rine-lab'); ?></li>
                <li><?php printf(__('See the documentation: %s', 'me5rine-lab'), '<a href="' . plugin_dir_url(dirname(dirname(__FILE__))) . 'docs/game-servers/QUICK_START.md" target="_blank">' . __('Quick Start', 'me5rine-lab') . '</a>'); ?></li>
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

