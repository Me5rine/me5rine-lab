<?php
/**
 * Template personnalisé Me5rine LAB pour le rendu des giveaways RafflePress
 * Basé sur rafflepress-pro/resources/views/rafflepress-giveaway.php
 * avec modifications intégrées directement dans les données et le template
 */

if (!defined('ABSPATH')) exit;

// Récupérer l'ID du giveaway depuis la variable de requête
$rafflepress_id = get_query_var('me5rine_lab_giveaway_id');
if (empty($rafflepress_id) && !empty($_GET['me5rine_lab_giveaway_id'])) {
    $rafflepress_id = absint($_GET['me5rine_lab_giveaway_id']);
}

if (empty($rafflepress_id)) {
    wp_die(__('No Giveaway ID provided', 'me5rine-lab'));
}

// Récupérer l'URL de la page parente pour les redirections après connexion/inscription
$parent_url = '';
if (!empty($_GET['parent_url'])) {
    $parent_url = esc_url_raw(urldecode($_GET['parent_url']));
} elseif (!empty(get_query_var('parent_url'))) {
    $parent_url = esc_url_raw(urldecode(get_query_var('parent_url')));
}

// Si pas d'URL parente, utiliser l'URL actuelle de la requête
if (empty($parent_url)) {
    $parent_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
}

// Nettoyer l'URL pour retirer les fragments (comme #_=_)
$parent_url_parts = parse_url($parent_url);
$parent_url = '';
if (!empty($parent_url_parts['scheme']) && !empty($parent_url_parts['host'])) {
    $parent_url = $parent_url_parts['scheme'] . '://' . $parent_url_parts['host'];
    if (!empty($parent_url_parts['port'])) {
        $parent_url .= ':' . $parent_url_parts['port'];
    }
    if (!empty($parent_url_parts['path'])) {
        $parent_url .= $parent_url_parts['path'];
    }
    if (!empty($parent_url_parts['query'])) {
        $parent_url .= '?' . $parent_url_parts['query'];
    }
    // Ne pas inclure le fragment (#_=_)
}

// Récupérer les données utilisateur pour les personnalisations
// IMPORTANT: Vérifier explicitement l'état de connexion et forcer les valeurs vides si non connecté
// Utiliser get_current_user_id() qui est plus fiable que wp_get_current_user() pour vérifier la connexion
$user_id = get_current_user_id();
$is_logged_in = ($user_id > 0 && is_user_logged_in());

// IMPORTANT: Si l'utilisateur n'est pas connecté, forcer la déconnexion dans le contexte de l'iframe
// Cela empêche RafflePress de détecter l'utilisateur via wp_get_current_user() ou is_user_logged_in()
// MAIS si l'utilisateur EST connecté, on doit laisser les paramètres rp-name et rp-email dans l'URL
if (!$is_logged_in || $user_id === 0) {
    // Forcer la déconnexion en vidant l'utilisateur courant
    wp_set_current_user(0);
    global $current_user;
    $current_user = null;
    
    // Supprimer les cookies WordPress de session pour cette requête
    // (sans les supprimer réellement du navigateur)
    $cookie_hash = defined('COOKIEHASH') ? COOKIEHASH : '';
    if ($cookie_hash) {
        unset($_COOKIE['wordpress_logged_in_' . $cookie_hash]);
        unset($_COOKIE['wordpress_' . $cookie_hash]);
        unset($_COOKIE['wordpress_sec_' . $cookie_hash]);
    }
    
    // IMPORTANT: Supprimer le cookie RafflePress qui identifie l'utilisateur
    // RafflePress utilise rafflepress_hash_{giveaway_id} pour identifier l'utilisateur même s'il n'est pas connecté
    $rafflepress_cookie_name = 'rafflepress_hash_' . $rafflepress_id;
    unset($_COOKIE[$rafflepress_cookie_name]);
    
    // Supprimer aussi toutes les variantes possibles du cookie
    $all_cookies = array_keys($_COOKIE);
    foreach ($all_cookies as $cookie_name) {
        if (strpos($cookie_name, 'rafflepress_hash_') === 0) {
            unset($_COOKIE[$cookie_name]);
        }
    }
    
    // Supprimer le cookie du navigateur en envoyant un setcookie avec expiration dans le passé
    // On doit le faire avant que RafflePress ne le lise
    if (headers_sent() === false) {
        // Supprimer le cookie spécifique au giveaway
        setcookie($rafflepress_cookie_name, '', time() - 3600, '/', '', is_ssl(), true);
        setcookie($rafflepress_cookie_name, '', time() - 3600, '/', '', is_ssl(), false);
        
        // Supprimer aussi toutes les variantes possibles
        foreach ($all_cookies as $cookie_name) {
            if (strpos($cookie_name, 'rafflepress_hash_') === 0) {
                setcookie($cookie_name, '', time() - 3600, '/', '', is_ssl(), true);
                setcookie($cookie_name, '', time() - 3600, '/', '', is_ssl(), false);
            }
        }
    }
    
    // Supprimer les paramètres utilisateur de l'URL seulement si l'utilisateur n'est pas connecté
    // IMPORTANT: Les supprimer aussi de $_REQUEST pour être sûr
    unset($_GET['rp-name']);
    unset($_GET['rp-email']);
    unset($_GET['rp_name']);
    unset($_GET['rp_email']);
    unset($_REQUEST['rp-name']);
    unset($_REQUEST['rp-email']);
    unset($_REQUEST['rp_name']);
    unset($_REQUEST['rp_email']);
    
    // Vérifier aussi dans l'URL de la requête actuelle
    if (isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        unset($query_params['rp-name']);
        unset($query_params['rp-email']);
        unset($query_params['rp_name']);
        unset($query_params['rp_email']);
        $_SERVER['QUERY_STRING'] = http_build_query($query_params);
    }
} else {
    // Si l'utilisateur est connecté, s'assurer que rp-name et rp-email sont dans $_GET
    // pour que RafflePress puisse les détecter
    if (!empty($user_name) && !empty($user_email)) {
        $_GET['rp-name'] = $user_name;
        $_GET['rp-email'] = $user_email;
        $_REQUEST['rp-name'] = $user_name;
        $_REQUEST['rp-email'] = $user_email;
    } else {
        // Si les données utilisateur sont vides, supprimer les paramètres
        unset($_GET['rp-name']);
        unset($_GET['rp-email']);
        unset($_REQUEST['rp-name']);
        unset($_REQUEST['rp-email']);
    }
}

// Simuler les paramètres RafflePress pour réutiliser leur logique
$_GET['rafflepress_page'] = 'rafflepress_render';
$_GET['rafflepress_id'] = $rafflepress_id;
$_GET['iframe'] = '1';

// Définir les variables de requête pour RafflePress
global $wp_query;
$wp_query->set('rafflepress_page', 'rafflepress_render');
$wp_query->set('rafflepress_id', $rafflepress_id);

// Charger les dépendances RafflePress nécessaires
if (!class_exists('rafflepress_lessc')) {
    if (defined('RAFFLEPRESS_PRO_PLUGIN_PATH')) {
        require_once RAFFLEPRESS_PRO_PLUGIN_PATH . 'app/vendor/rafflepress_lessc.inc.php';
    }
}

if (defined('RAFFLEPRESS_PRO_PLUGIN_PATH')) {
    require_once RAFFLEPRESS_PRO_PLUGIN_PATH . 'resources/giveaway-templates/google-fonts.php';
    require_once RAFFLEPRESS_PRO_PLUGIN_PATH . 'resources/views/frontend-translations.php';
}

// Si l'utilisateur n'est pas connecté, forcer les valeurs à vide
if (!$is_logged_in || $user_id === 0) {
    $is_logged_in = false;
    $user_name = '';
    $user_email = '';
    $current_user = null;
} else {
    // Récupérer les données utilisateur seulement si vraiment connecté
    $current_user = wp_get_current_user();
    
    // Vérification stricte : l'utilisateur doit avoir un ID valide ET des données valides
    if (!$current_user || $current_user->ID === 0 || $current_user->ID !== $user_id) {
        $is_logged_in = false;
        $user_name = '';
        $user_email = '';
    } else {
        // Vérifier que les données utilisateur existent vraiment et sont valides
        $user_name = isset($current_user->display_name) && !empty($current_user->display_name) ? trim($current_user->display_name) : '';
        $user_email = isset($current_user->user_email) && !empty($current_user->user_email) ? trim($current_user->user_email) : '';
        
        // Si les valeurs sont vides ou invalides, considérer comme non connecté
        if (empty($user_name) || empty($user_email) || !is_email($user_email)) {
            $is_logged_in = false;
            $user_name = '';
            $user_email = '';
        }
    }
}

// Log pour debug (à retirer en production si tout fonctionne)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[Me5rine LAB Giveaway] État connexion: ' . ($is_logged_in ? 'CONNECTÉ' : 'NON CONNECTÉ') . ' - User ID: ' . ($user_id ?? 0) . ' - Name: ' . ($user_name ?: 'vide') . ' - Email: ' . ($user_email ?: 'vide'));
}

// Récupérer le nom du partenaire
$partner_name = '';
$post_id      = function_exists('admin_lab_get_post_id_from_rafflepress') ? admin_lab_get_post_id_from_rafflepress($rafflepress_id) : null;
if ($post_id) {
    $partner_id = get_post_meta($post_id, '_giveaway_partner_id', true);
    if ($partner_id) {
        $partner     = get_userdata($partner_id);
        $partner_name = $partner ? $partner->display_name : '';
    }
}

// Récupérer le nom du site
$admin_id   = get_option('admin_lab_account_id');
$admin_user = $admin_id ? get_userdata($admin_id) : null;
$website_name = $admin_user ? $admin_user->display_name : 'Me5rine LAB';

// Récupérer les couleurs Elementor si disponibles
$colors = [];
if (function_exists('admin_lab_get_elementor_kit_colors')) {
    $colors = admin_lab_get_elementor_kit_colors();
}

$primary_color = !empty($colors['primary']) ? $colors['primary'] : '#02395A';
$secondary_color = !empty($colors['secondary']) ? $colors['secondary'] : '#0485C8';
$text_color = !empty($colors['338f618']) ? $colors['338f618'] : '#FFFFFF';
$bg_color = !empty($colors['3d5ef52']) ? $colors['3d5ef52'] : '#F9FAFB';

// Récupérer les traductions
$translations = [
    'prizeMessage' => [
        'single'   => __('You can participate in the giveaway for a chance to win a(n) %s.', 'me5rine-lab'),
        'multiple' => __('You can participate in the giveaway for a chance to win one of the following prizes: %s.', 'me5rine-lab'),
        'none'     => __('You can participate in the giveaway below.', 'me5rine-lab'),
        'login'    => __('Please log in to participate:', 'me5rine-lab'),
        'loginBtn' => __('Login', 'me5rine-lab'),
        'registerBtn' => __('Register', 'me5rine-lab')
    ],
    'greeting'        => __('Hello, %s! You are logged in.', 'me5rine-lab'),
    'separator'       => __('More chances to win with Me5rine LAB', 'me5rine-lab'),
    'discordJoinLabel' => __('Join %s on Discord', 'me5rine-lab'),
    'discordJoinText'  => __('To get credit for this entry, join us on Discord.', 'me5rine-lab'),
    'discordJoinBtn'   => __('Join Discord', 'me5rine-lab'),
    'blueskyJoinLabel' => __('Join %s on Bluesky', 'me5rine-lab'),
    'blueskyJoinText'  => __('To get credit for this entry, join us on Bluesky.', 'me5rine-lab'),
    'blueskyJoinBtn'   => __('Join Bluesky', 'me5rine-lab'),
    'threadsJoinLabel' => __('Join %s on Threads', 'me5rine-lab'),
    'threadsJoinText'  => __('To get credit for this entry, join us on Threads.', 'me5rine-lab'),
    'threadsJoinBtn'   => __('Join Threads', 'me5rine-lab'),
];

// Utiliser le template RafflePress original mais avec nos modifications
// On charge leur template avec output buffering pour pouvoir modifier les données
ob_start();

// Charger le template RafflePress original
if (defined('RAFFLEPRESS_PRO_PLUGIN_PATH')) {
    require_once RAFFLEPRESS_PRO_PLUGIN_PATH . 'resources/views/rafflepress-giveaway.php';
} else {
    wp_die(__('RafflePress plugin not found', 'me5rine-lab'));
}

$output = ob_get_clean();

// Charger le fichier CSS personnalisé
$css_url = plugins_url('../../assets/css/giveaway-rafflepress-custom.css', __FILE__);
$css_link = '<link rel="stylesheet" id="me5rine-lab-giveaway-custom-css" href="' . esc_url($css_url) . '?v=' . ME5RINE_LAB_VERSION . '" type="text/css" media="all" />';

// Injecter les variables CSS pour les couleurs dynamiques
$css_variables = '
<style id="me5rine-lab-custom-variables">
:root {
    --me5rine-lab-bg-color: ' . esc_attr($bg_color) . ';
    --me5rine-lab-primary-color: ' . esc_attr($primary_color) . ';
    --me5rine-lab-secondary-color: ' . esc_attr($secondary_color) . ';
    --me5rine-lab-text-color: ' . esc_attr($text_color) . ';
}
</style>
';

$output = str_replace('</head>', $css_link . $css_variables . '</head>', $output);

// Injecter un script IMMÉDIAT pour masquer le bloc natif dès qu'il apparaît
$hide_native_script = '
<script>
(function() {
    "use strict";
    // Masquer immédiatement le bloc de connexion natif dès qu\'il apparaît
    function hideNativeLoginBlock() {
        var loginBlock = document.querySelector("#rafflepress-giveaway-login");
        if (loginBlock && !loginBlock.classList.contains("admin-lab-customized")) {
            loginBlock.style.opacity = "0";
            loginBlock.style.visibility = "hidden";
            loginBlock.style.maxHeight = "0";
            loginBlock.style.overflow = "hidden";
            loginBlock.style.padding = "0";
            loginBlock.style.margin = "0";
            
            // Masquer aussi tous les formulaires natifs
            var forms = loginBlock.querySelectorAll("form, .rafflepress-login-form, input[type=\'email\'], button[type=\'submit\']");
            forms.forEach(function(form) {
                form.style.display = "none";
                form.style.opacity = "0";
                form.style.visibility = "hidden";
                form.style.height = "0";
                form.style.overflow = "hidden";
            });
        }
    }
    
    // Masquer immédiatement si le DOM est déjà chargé
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", hideNativeLoginBlock);
    } else {
        hideNativeLoginBlock();
    }
    
    // Observer les mutations pour masquer le bloc dès qu\'il apparaît
    if (typeof MutationObserver !== "undefined") {
        var hideObserver = new MutationObserver(function(mutations) {
            hideNativeLoginBlock();
        });
        hideObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Masquer aussi périodiquement pour être sûr
    setInterval(hideNativeLoginBlock, 100);
})();
</script>
';
$output = str_replace('<head>', '<head>' . $hide_native_script, $output);

// Retirer le script iframe-resizer de RafflePress pour éviter les rebonds au scroll
// On utilise une hauteur fixe définie dans le shortcode au lieu d'un resizer dynamique
$output = preg_replace('/<script[^>]*iframeResizer\.contentWindow[^>]*>.*?<\/script>/is', '', $output);
$output = preg_replace('/<script[^>]*data-cfasync[^>]*iframeResizer[^>]*>.*?<\/script>/is', '', $output);

// Injecter un script pour nettoyer les données RafflePress AVANT le rendu si l'utilisateur n'est pas connecté
// ET masquer immédiatement le formulaire natif
if (!$is_logged_in || $user_id === 0) {
    $cleanup_script = '
<script>
(function() {
    "use strict";
    // Nettoyer immédiatement les données RafflePress du localStorage/sessionStorage
    // ET supprimer le cookie rafflepress_hash_{giveaway_id}
    // AVANT que RafflePress ne les lise
    try {
        var giveawayId = ' . intval($rafflepress_id) . ';
        
        // IMPORTANT: Supprimer le cookie RafflePress qui identifie l\'utilisateur
        // RafflePress utilise rafflepress_hash_{giveaway_id} pour identifier l\'utilisateur meme s\'il n\'est pas connecte
        var cookieName = "rafflepress_hash_" + giveawayId;
        // Supprimer le cookie en le définissant avec une expiration dans le passé
        // Essayer plusieurs chemins possibles
        var paths = ["/", window.location.pathname];
        var domains = [window.location.hostname, "." + window.location.hostname];
        
        paths.forEach(function(path) {
            domains.forEach(function(domain) {
                // Essayer avec et sans secure
                document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + ";";
                document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + "; secure;";
            });
        });
        
        // Supprimer aussi toutes les variantes possibles du cookie
        if (document.cookie) {
            var cookies = document.cookie.split(";");
            cookies.forEach(function(cookie) {
                var cookieParts = cookie.split("=");
                var name = cookieParts[0].trim();
                if (name.indexOf("rafflepress_hash_") === 0) {
                    paths.forEach(function(path) {
                        domains.forEach(function(domain) {
                            document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + ";";
                            document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + "; secure;";
                        });
                    });
                }
            });
        }
        var allStorageKeys = Object.keys(localStorage);
        allStorageKeys.forEach(function(key) {
            if (key.indexOf("rafflepress") !== -1) {
                if (key.indexOf(giveawayId) !== -1) {
                    localStorage.removeItem(key);
                } else if (key === "rafflepress_data") {
                    try {
                        var parsed = JSON.parse(localStorage.getItem(key));
                        if (parsed && parsed.giveaway_id === giveawayId) {
                            parsed.entries = {};
                            parsed.completed_actions = {};
                            parsed.user = {};
                            parsed.user_email = "";
                            parsed.user_name = "";
                            localStorage.setItem(key, JSON.stringify(parsed));
                        }
                    } catch(e) {}
                }
            }
        });
        var sessionKeys = Object.keys(sessionStorage);
        sessionKeys.forEach(function(key) {
            if (key.indexOf("rafflepress") !== -1 && key.indexOf(giveawayId) !== -1) {
                sessionStorage.removeItem(key);
            }
        });
    } catch(e) {
        console.warn("[Me5rine LAB] Erreur lors du nettoyage préventif:", e);
    }
    
    // Masquer immédiatement le formulaire natif de RafflePress
    function hideNativeForm() {
        var loginBlock = document.querySelector("#rafflepress-giveaway-login");
        if (loginBlock) {
            var forms = loginBlock.querySelectorAll("form, .rafflepress-login-form, input[type=\'email\']");
            forms.forEach(function(form) {
                form.style.display = "none";
                form.style.opacity = "0";
                form.style.visibility = "hidden";
                form.style.height = "0";
                form.style.overflow = "hidden";
            });
        }
    }
    
    // Masquer immédiatement si le DOM est déjà chargé
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", hideNativeForm);
    } else {
        hideNativeForm();
    }
    
    // Observer les mutations pour masquer le formulaire dès qu\'il apparaît
    if (typeof MutationObserver !== "undefined") {
        var observer = new MutationObserver(function(mutations) {
            hideNativeForm();
        });
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
</script>
';
    $output = str_replace('<head>', '<head>' . $cleanup_script, $output);
    
    // Modifier rafflepress_data APRÈS qu'il soit défini par RafflePress
    // On utilise un setTimeout pour s'assurer que RafflePress a fini de le définir
    $modify_data_script = '
<script>
(function() {
    "use strict";
    // Attendre que rafflepress_data soit défini, puis le modifier
    function modifyRafflepressData() {
        if (typeof rafflepress_data !== "undefined" && rafflepress_data.giveaway && rafflepress_data.giveaway.id === ' . intval($rafflepress_id) . ') {
            // Forcer l\'absence d\'utilisateur
            rafflepress_data.user = {};
            rafflepress_data.user_email = "";
            rafflepress_data.user_name = "";
            rafflepress_data.entries = {};
            rafflepress_data.completed_actions = {};
        } else {
            // Réessayer après un court délai
            setTimeout(modifyRafflepressData, 100);
        }
    }
    // Démarrer la modification après le chargement du DOM
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", modifyRafflepressData);
    } else {
        setTimeout(modifyRafflepressData, 100);
    }
})();
</script>
';
    $output = str_replace('</body>', $modify_data_script . '</body>', $output);
}

// Injecter le script de personnalisation avant </body>
$custom_script = '
<script>
(function() {
    "use strict";
    
    // Forcer les valeurs vides si l\'utilisateur n\'est pas connecté
    var isUserLoggedIn = ' . ($is_logged_in ? 'true' : 'false') . ';
    var userName = ' . json_encode($user_name) . ';
    var userEmail = ' . json_encode($user_email) . ';
    
    // Double vérification côté client : s\'assurer que les valeurs sont cohérentes
    // IMPORTANT: Ne jamais utiliser les données depuis les attributs data-* de l\'iframe parent
    // Utiliser uniquement les données fournies par le serveur PHP
    if (!isUserLoggedIn || !userName || !userEmail || userName === "" || userEmail === "" || userName === null || userEmail === null) {
        isUserLoggedIn = false;
        userName = "";
        userEmail = "";
    }
    
    // Debug en production (à retirer si tout fonctionne)
    if (typeof console !== "undefined" && console.log) {
        console.log("[Me5rine LAB] État de connexion:", {
            isLoggedIn: isUserLoggedIn,
            hasUserName: !!userName,
            hasUserEmail: !!userEmail,
            userName: userName,
            userEmail: userEmail
        });
    }
    
    var me5rineLabData = {
        userName: userName,
        userEmail: userEmail,
        isLoggedIn: isUserLoggedIn,
        giveawayId: ' . json_encode($rafflepress_id) . ',
        partnerName: ' . json_encode($partner_name) . ',
        websiteName: ' . json_encode($website_name) . ',
        translations: ' . json_encode($translations) . ',
        colors: ' . json_encode($colors) . ',
        loginUrl: ' . json_encode(wp_login_url($parent_url)) . ',
        registerUrl: ' . json_encode(add_query_arg('redirect_to', urlencode($parent_url), wp_registration_url())) . '
    };

    // Fonction pour nettoyer les données RafflePress du localStorage/sessionStorage
    // Cette fonction copie le comportement du logout de RafflePress
    function cleanRafflePressStorage() {
        try {
            var giveawayId = me5rineLabData.giveawayId;
            
            // IMPORTANT: Supprimer le cookie RafflePress qui identifie l\'utilisateur
            // RafflePress utilise rafflepress_hash_{giveaway_id} pour identifier l\'utilisateur meme s\'il n\'est pas connecte
            var cookieName = "rafflepress_hash_" + giveawayId;
            // Supprimer le cookie en le définissant avec une expiration dans le passé
            // Essayer plusieurs chemins possibles
            var paths = ["/", window.location.pathname];
            var domains = [window.location.hostname, "." + window.location.hostname];
            
            paths.forEach(function(path) {
                domains.forEach(function(domain) {
                    // Essayer avec et sans secure
                    document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + ";";
                    document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + "; secure;";
                });
            });
            
            // Supprimer aussi toutes les variantes possibles du cookie
            if (document.cookie) {
                var cookies = document.cookie.split(";");
                cookies.forEach(function(cookie) {
                    var cookieParts = cookie.split("=");
                    var name = cookieParts[0].trim();
                    if (name.indexOf("rafflepress_hash_") === 0) {
                        paths.forEach(function(path) {
                            domains.forEach(function(domain) {
                                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + ";";
                                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + "; domain=" + domain + "; secure;";
                            });
                        });
                    }
                });
            }
            
            var allStorageKeys = Object.keys(localStorage);
            
            // Supprimer toutes les clés liées à ce giveaway spécifique
            allStorageKeys.forEach(function(key) {
                if (key.indexOf("rafflepress") !== -1) {
                    // Supprimer les clés spécifiques au giveaway
                    if (key.indexOf(giveawayId) !== -1) {
                        localStorage.removeItem(key);
                    }
                    // Nettoyer aussi rafflepress_data global
                    else if (key === "rafflepress_data") {
                        try {
                            var parsed = JSON.parse(localStorage.getItem(key));
                            if (parsed) {
                                // Si c\'est le même giveaway, nettoyer complètement
                                if (parsed.giveaway_id === giveawayId || parsed.giveaway && parsed.giveaway.id === giveawayId) {
                                    // Réinitialiser toutes les données utilisateur et entrées
                                    parsed.entries = {};
                                    parsed.completed_actions = {};
                                    parsed.user = {};
                                    parsed.user_email = "";
                                    parsed.user_name = "";
                                    parsed.user_id = null;
                                    // Supprimer aussi les données d\'entrées spécifiques
                                    if (parsed.entry_data) {
                                        parsed.entry_data = {};
                                    }
                                    localStorage.setItem(key, JSON.stringify(parsed));
                                } else {
                                    // Même si ce n\'est pas le même giveaway, nettoyer les données utilisateur
                                    // pour éviter que RafflePress détecte l\'utilisateur
                                    if (parsed.user) {
                                        parsed.user = {};
                                    }
                                    if (parsed.user_email) {
                                        parsed.user_email = "";
                                    }
                                    if (parsed.user_name) {
                                        parsed.user_name = "";
                                    }
                                    localStorage.setItem(key, JSON.stringify(parsed));
                                }
                            }
                        } catch(e) {
                            // Si erreur de parsing, supprimer complètement
                            localStorage.removeItem(key);
                        }
                    }
                }
            });
            
            // Nettoyer sessionStorage aussi
            var sessionKeys = Object.keys(sessionStorage);
            sessionKeys.forEach(function(key) {
                if (key.indexOf("rafflepress") !== -1 && key.indexOf(giveawayId) !== -1) {
                    sessionStorage.removeItem(key);
                }
            });
            
            // Nettoyer aussi rafflepress_data dans window si défini
            if (typeof window.rafflepress_data !== "undefined" && window.rafflepress_data) {
                if (window.rafflepress_data.giveaway && window.rafflepress_data.giveaway.id === giveawayId) {
                    window.rafflepress_data.entries = {};
                    window.rafflepress_data.completed_actions = {};
                    window.rafflepress_data.user = {};
                    window.rafflepress_data.user_email = "";
                    window.rafflepress_data.user_name = "";
                    window.rafflepress_data.user_id = null;
                } else {
                    // Même si ce n\'est pas le même giveaway, nettoyer les données utilisateur
                    if (window.rafflepress_data.user) {
                        window.rafflepress_data.user = {};
                    }
                    if (window.rafflepress_data.user_email) {
                        window.rafflepress_data.user_email = "";
                    }
                    if (window.rafflepress_data.user_name) {
                        window.rafflepress_data.user_name = "";
                    }
                }
            }
            
            // Forcer Vue.js à mettre à jour en déclenchant un événement si disponible
            if (typeof window.dispatchEvent !== "undefined") {
                try {
                    window.dispatchEvent(new Event("storage"));
                } catch(e) {}
            }
        } catch(e) {
            console.warn("[Me5rine LAB] Erreur lors du nettoyage du storage:", e);
        }
    }

    // Nettoyer immédiatement si l\'utilisateur n\'est pas connecté
    // Et aussi après un court délai pour s\'assurer que RafflePress a fini de charger
    if (!me5rineLabData.isLoggedIn) {
        cleanRafflePressStorage();
        // Réessayer après que RafflePress ait chargé ses données
        setTimeout(function() {
            cleanRafflePressStorage();
        }, 500);
        setTimeout(function() {
            cleanRafflePressStorage();
        }, 1500);
        setTimeout(function() {
            cleanRafflePressStorage();
        }, 3000);
    }
    
    // Vérifier périodiquement que l\'utilisateur est toujours connecté
    // Si les données changent, nettoyer le storage
    setInterval(function() {
        // Vérifier si l\'état de connexion a changé
        var currentIsLoggedIn = me5rineLabData.isLoggedIn === true && 
                                typeof me5rineLabData.userEmail === \'string\' && 
                                me5rineLabData.userEmail.trim() !== \'\' && 
                                typeof me5rineLabData.userName === \'string\' && 
                                me5rineLabData.userName.trim() !== \'\';
        
        if (!currentIsLoggedIn) {
            cleanRafflePressStorage();
        }
    }, 2000);
    
    // Récupérer les prix depuis rafflepress_data
    if (typeof rafflepress_data !== "undefined" && rafflepress_data.settings && rafflepress_data.settings.prizes) {
        me5rineLabData.prizes = rafflepress_data.settings.prizes.map(function(p) { return p.name; }).filter(Boolean);
    }
    
    let customizationsApplied = false;
    let retryCount = 0;
    const maxRetries = 100;
    
    function applyCustomizations() {
        const app = document.querySelector("#rafflepress-frontent-vue-app");
        const loginBlock = document.querySelector("#rafflepress-giveaway-login");
        
        console.log("[Me5rine LAB] applyCustomizations appelée - App:", !!app, "LoginBlock:", !!loginBlock);
        
        // Si le bloc de connexion n\'existe pas encore, réessayer
        if (!loginBlock) {
            retryCount++;
            console.log("[Me5rine LAB] Bloc de connexion non trouvé, tentative", retryCount);
            if (retryCount < maxRetries) {
                setTimeout(applyCustomizations, 100);
            } else {
                console.warn("[Me5rine LAB] Le bloc de connexion n\'a pas été trouvé après", maxRetries, "tentatives");
            }
            return;
        }
        
        // Si l\'app n\'existe pas encore mais que le bloc de connexion est là, on peut quand même personnaliser
        // L\'app peut être chargée plus tard, mais le bloc de connexion est déjà disponible
        if (!app) {
            console.log("[Me5rine LAB] L\'application Vue.js n\'est pas encore chargée, mais le bloc de connexion est disponible, personnalisation...");
            // On continue quand même car le bloc de connexion est disponible
        }
        
        console.log("[Me5rine LAB] Bloc de connexion trouvé, contenu actuel:", loginBlock.innerHTML.substring(0, 200));
        
        // Vérifier si le bloc contient déjà notre contenu personnalisé
        var hasCustomContent = loginBlock.querySelector(".admin-lab-login-block, .admin-lab-welcome-block");
        var isAlreadyCustomized = loginBlock.classList.contains("admin-lab-customized");
        
        console.log("[Me5rine LAB] hasCustomContent:", !!hasCustomContent, "isAlreadyCustomized:", isAlreadyCustomized, "customizationsApplied:", customizationsApplied);
        
        // Si déjà personnalisé et que le contenu est toujours là, ne rien faire
        if (isAlreadyCustomized && hasCustomContent && customizationsApplied) {
            console.log("[Me5rine LAB] Bloc déjà personnalisé, pas de modification nécessaire");
            return;
        }
        
        console.log("[Me5rine LAB] Application des personnalisations...");
        
        // Toujours réappliquer les personnalisations pour s\'assurer qu\'elles sont actives
        if (!customizationsApplied || retryCount < 20 || !hasCustomContent) {
            const data = me5rineLabData;
            let prizeMessage = "";
            
            if (data.prizes.length === 1) {
                prizeMessage = data.translations.prizeMessage.single.replace("%s", data.prizes[0]);
            } else if (data.prizes.length > 1) {
                prizeMessage = data.translations.prizeMessage.multiple.replace("%s", data.prizes.join(", "));
            } else {
                prizeMessage = data.translations.prizeMessage.none;
            }
            
            // Vérifier l\'état de connexion depuis les données serveur
            const isActuallyLoggedIn = data.isLoggedIn === true && typeof data.userEmail === \'string\' && data.userEmail.trim() !== \'\' && typeof data.userName === \'string\' && data.userName.trim() !== \'\';

            console.log("[Me5rine LAB] Remplacement du contenu du bloc de connexion, isActuallyLoggedIn:", isActuallyLoggedIn);
            
            // Remplacer complètement le contenu du bloc comme dans l\'ancien script
            // Cela empêche Vue.js de réinitialiser le contenu
            if (isActuallyLoggedIn) {
                var welcomeHTML = \'<div class="admin-lab-welcome-block"><p>\' + data.translations.greeting.replace("%s", "<strong>" + data.userName + "</strong>") + \'</p><p>\' + prizeMessage + \'</p></div>\';
                console.log("[Me5rine LAB] HTML de bienvenue:", welcomeHTML);
                loginBlock.innerHTML = welcomeHTML;
            } else {
                // Forcer le nettoyage si on détecte que l\'utilisateur n\'est pas connecté
                // Nettoyer immédiatement et aussi après un court délai
                cleanRafflePressStorage();
                setTimeout(function() {
                    cleanRafflePressStorage();
                }, 200);
                var primaryColor = data.colors.primary || "#02395A";
                var secondaryColor = data.colors.secondary || "#0485C8";
                var textColor = data.colors["338f618"] || "#FFFFFF";
                var bgColor = data.colors["3d5ef52"] || "#F9FAFB";
                
                loginBlock.innerHTML = \'<div class="admin-lab-login-block" style="background-color: \' + bgColor + \'; border-radius: 12px; padding: 5px; text-align: center; max-width: 400px; margin: 5px auto; font-family: \\\'Segoe UI\\\', sans-serif;"><p style="font-size: 16px; font-weight: 500; margin-bottom: 10px; color: #374151;">\' + data.translations.prizeMessage.login + \'</p><a href="\' + data.loginUrl + \'" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: \' + primaryColor + \'; color: \' + textColor + \' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">\' + data.translations.prizeMessage.loginBtn + \'</a><a href="\' + data.registerUrl + \'" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: \' + primaryColor + \'; color: \' + textColor + \' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">\' + data.translations.prizeMessage.registerBtn + \'</a></div>\';
            }
            
            // Marquer le bloc comme personnalisé pour éviter les réinitialisations
            loginBlock.classList.add("admin-lab-customized");
            
            // Forcer le rendu visible avec inline style
            loginBlock.style.display = "block";
            loginBlock.style.opacity = "1";
            loginBlock.style.visibility = "visible";
            loginBlock.style.maxHeight = "none";
            loginBlock.style.height = "auto";
            loginBlock.style.overflow = "visible";
            
            console.log("[Me5rine LAB] Personnalisation appliquée avec succès, nouveau contenu:", loginBlock.innerHTML.substring(0, 200));
            
            // Empêcher Vue.js de réinitialiser ce bloc en interceptant les mutations
            if (typeof MutationObserver !== "undefined") {
                var blockObserver = new MutationObserver(function(mutations) {
                    // Si Vue.js essaie de modifier le contenu, le restaurer
                    var hasCustomContent = loginBlock.querySelector(".admin-lab-login-block, .admin-lab-welcome-block");
                    if (!hasCustomContent && loginBlock.innerHTML.indexOf("admin-lab") === -1) {
                        console.warn("[Me5rine LAB] Vue.js a réinitialisé le contenu, restauration...");
                        // Vue.js a réinitialisé le contenu, le restaurer
                        setTimeout(function() {
                            applyCustomizations();
                        }, 50);
                    }
                });
                blockObserver.observe(loginBlock, {
                    childList: true,
                    subtree: true
                });
            }
            
            customizationsApplied = true;
            console.log("[Me5rine LAB] Personnalisation terminée avec succès");
            
            // Continuer à vérifier périodiquement pour s\'assurer que le formulaire natif ne réapparaît pas
            if (retryCount < 30) {
                retryCount++;
                setTimeout(applyCustomizations, 200);
            }
        } else {
            // Vérifier périodiquement que le formulaire natif ne réapparaît pas
            var nativeForms = loginBlock.querySelectorAll("form, .rafflepress-login-form, input[type=\'email\']");
            if (nativeForms.length > 0) {
                nativeForms.forEach(function(form) {
                    form.style.display = "none";
                    form.style.opacity = "0";
                    form.style.visibility = "hidden";
                });
            }
        }
        
        // Remplacer le séparateur d\'action automatique
        const autoEntryWrapper = document.querySelector(".rafflepress-action-separator .btn-action-automatic-entry");
        if (autoEntryWrapper) {
            const parentBlock = autoEntryWrapper.closest(".rafflepress-action-separator");
            if (parentBlock && !parentBlock.classList.contains("admin-lab-replaced")) {
                parentBlock.classList.add("admin-lab-replaced");
                const separator = document.createElement("div");
                separator.innerHTML = \'<hr style="margin: 20px 0;"><p style="text-align:center; font-weight:bold; font-size: 16px; color: #2d2d2d;">\' + me5rineLabData.translations.separator + \'</p>\';
                parentBlock.replaceWith(separator);
            }
        }
        
        // Personnaliser les actions Discord, Bluesky, Threads
        ["discord", "bluesky", "threads"].forEach(function(type) {
            const wrappers = document.querySelectorAll(".rafflepress-action-" + type + ":not(.admin-lab-customized), .rafflepress-action-" + type + "_2:not(.admin-lab-customized)");
            wrappers.forEach(function(wrapper) {
                wrapper.classList.add("admin-lab-customized");
                
                const button = wrapper.querySelector(".btn-action-visit-a-page");
                const label = wrapper.querySelector(".rafflepress-action-text");
                const icon = wrapper.querySelector(".rafflepress-entry-option-icon");
                
                if (button) button.classList.add("btn-" + type);
                if (icon) icon.classList.add("icon-" + type);
                
                let entryId = wrapper.getAttribute("data-entry-id") || "";
                if (!entryId) {
                    const entryEl = wrapper.querySelector("[data-entry-id]");
                    entryId = entryEl ? entryEl.getAttribute("data-entry-id") : "";
                }
                if (!entryId) {
                    if (wrapper.classList.contains("rafflepress-action-" + type + "_2")) {
                        entryId = type + "_2";
                    } else if (wrapper.classList.contains("rafflepress-action-" + type)) {
                        entryId = type;
                    }
                }
                
                let displayName = me5rineLabData.partnerName || "";
                if (entryId === type + "_2") {
                    displayName = me5rineLabData.websiteName || "Me5rine LAB";
                }
                
                if (label && me5rineLabData.translations[type + "JoinLabel"]) {
                    label.textContent = me5rineLabData.translations[type + "JoinLabel"].replace("%s", displayName);
                }
                
                // Appliquer les styles inline UNIQUEMENT sur les icônes, pas sur les boutons
                if (icon) {
                    if (type === "discord") {
                        icon.style.setProperty("background-color", "#36393e", "important");
                        icon.style.setProperty("border-color", "#36393e", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (type === "bluesky") {
                        icon.style.setProperty("background-color", "#1185fe", "important");
                        icon.style.setProperty("border-color", "#1185fe", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (type === "threads") {
                        icon.style.setProperty("background-color", "#000000", "important");
                        icon.style.setProperty("border-color", "#000000", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    }
                }
                
                // S\'assurer que les boutons ne reçoivent PAS ces styles
                if (button) {
                    button.style.removeProperty("background-color");
                    button.style.removeProperty("border-color");
                    button.style.removeProperty("color");
                }
                
                if (button && !button.hasAttribute("data-me5rine-listener")) {
                    button.setAttribute("data-me5rine-listener", "true");
                    button.addEventListener("click", function() {
                        setTimeout(function() {
                            const actionArea = wrapper.querySelector(".rafflepress-action");
                            if (actionArea) {
                                const intro = actionArea.querySelector("p");
                                if (intro && me5rineLabData.translations[type + "JoinText"]) {
                                    intro.textContent = me5rineLabData.translations[type + "JoinText"];
                                }
                                
                                const linkBtn = actionArea.querySelector("a.btn-visit-a-page");
                                if (linkBtn) {
                                    const iconEl = linkBtn.querySelector("i.fas.fa-external-link-alt");
                                    if (iconEl) {
                                        iconEl.className = "fa-brands fa-" + type;
                                    }
                                    
                                    const textNode = Array.from(linkBtn.childNodes).find(function(n) {
                                        return n.nodeType === Node.TEXT_NODE;
                                    });
                                    if (textNode && me5rineLabData.translations[type + "JoinBtn"]) {
                                        textNode.nodeValue = " " + me5rineLabData.translations[type + "JoinBtn"];
                                    }
                                    
                                    linkBtn.classList.add("btn-" + type);
                                    // NE PAS appliquer les styles sur les boutons, seulement sur les icones
                                    // Les styles sont deja appliques via CSS sur les icones uniquement
                                }
                                
                                // Appliquer les styles uniquement sur l\'icone dans l\'action area
                                const actionIcon = actionArea.querySelector(".rafflepress-entry-option-icon.icon-" + type);
                                if (actionIcon) {
                                    if (type === "discord") {
                                        actionIcon.style.setProperty("background-color", "#36393e", "important");
                                        actionIcon.style.setProperty("border-color", "#36393e", "important");
                                        actionIcon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    } else if (type === "bluesky") {
                                        actionIcon.style.setProperty("background-color", "#1185fe", "important");
                                        actionIcon.style.setProperty("border-color", "#1185fe", "important");
                                        actionIcon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    } else if (type === "threads") {
                                        actionIcon.style.setProperty("background-color", "#000000", "important");
                                        actionIcon.style.setProperty("border-color", "#000000", "important");
                                        actionIcon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    }
                                }
                            }
                        }, 0);
                    });
                }
            });
        });
        
        // Remplacer les logos/images pour les actions visit-a-page custom (pas Discord, Bluesky, Threads)
        function replaceCustomActionLogos() {
            try {
                // Récupérer les données RafflePress depuis window.rafflepress_data ou les attributs data-*
                let rafflepressData = null;
                if (typeof window.rafflepress_data !== "undefined") {
                    rafflepressData = window.rafflepress_data;
                } else if (typeof window.rafflepress !== "undefined" && window.rafflepress.data) {
                    rafflepressData = window.rafflepress.data;
                }
                
                if (!rafflepressData || !rafflepressData.entry_options) {
                    return;
                }
                
                // Trouver toutes les actions visit-a-page qui ne sont pas Discord, Bluesky ou Threads
                const customVisitPageWrappers = document.querySelectorAll(".rafflepress-action-visit-a-page:not(.admin-lab-logo-replaced)");
                
                customVisitPageWrappers.forEach(function(wrapper) {
                    // Vérifier que ce n'est pas une action Discord, Bluesky ou Threads
                    const isDiscord = wrapper.classList.contains("rafflepress-action-discord") || wrapper.querySelector(".icon-discord");
                    const isBluesky = wrapper.classList.contains("rafflepress-action-bluesky") || wrapper.querySelector(".icon-bluesky");
                    const isThreads = wrapper.classList.contains("rafflepress-action-threads") || wrapper.querySelector(".icon-threads");
                    
                    if (isDiscord || isBluesky || isThreads) {
                        return; // Ignorer Discord, Bluesky, Threads (déjà gérés)
                    }
                    
                    wrapper.classList.add("admin-lab-logo-replaced");
                    
                    // Récupérer l'ID de l'entry option depuis le wrapper
                    let entryId = wrapper.getAttribute("data-entry-id") || "";
                    if (!entryId) {
                        const entryEl = wrapper.querySelector("[data-entry-id]");
                        entryId = entryEl ? entryEl.getAttribute("data-entry-id") : "";
                    }
                    
                    // Trouver l'entry option correspondante dans les données RafflePress
                    let entryOption = null;
                    for (let i = 0; i < rafflepressData.entry_options.length; i++) {
                        const option = rafflepressData.entry_options[i];
                        if (option.type === "visit-a-page" && option.id === entryId) {
                            entryOption = option;
                            break;
                        }
                        // Essayer aussi de matcher par l'URL si l'ID ne correspond pas
                        if (option.type === "visit-a-page" && option.url) {
                            const wrapperUrl = wrapper.querySelector("a[href]");
                            if (wrapperUrl && wrapperUrl.href && option.url === wrapperUrl.href) {
                                entryOption = option;
                                break;
                            }
                        }
                    }
                    
                    // Si on a trouvé l'entry option avec une image, remplacer le logo
                    if (entryOption && entryOption.image) {
                        const icon = wrapper.querySelector(".rafflepress-entry-option-icon");
                        if (icon) {
                            // Remplacer l'image de fond ou l'élément img
                            const img = icon.querySelector("img");
                            if (img) {
                                img.src = entryOption.image;
                                img.setAttribute("src", entryOption.image);
                            } else {
                                // Utiliser background-image si pas d'élément img
                                icon.style.backgroundImage = "url(" + entryOption.image + ")";
                                icon.style.backgroundSize = "contain";
                                icon.style.backgroundRepeat = "no-repeat";
                                icon.style.backgroundPosition = "center";
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn("[Me5rine LAB] Erreur lors du remplacement des logos custom:", e);
            }
        }
        
        // Appeler la fonction de remplacement des logos custom
        setTimeout(replaceCustomActionLogos, 200);
        setTimeout(replaceCustomActionLogos, 500);
        setTimeout(replaceCustomActionLogos, 1000);
        setTimeout(replaceCustomActionLogos, 2000);
        
        // Observer les mutations pour remplacer les logos quand de nouvelles actions sont ajoutées
        if (typeof MutationObserver !== "undefined") {
            const logoObserver = new MutationObserver(function(mutations) {
                replaceCustomActionLogos();
            });
            
            const vueApp = document.querySelector("#rafflepress-frontent-vue-app");
            if (vueApp) {
                logoObserver.observe(vueApp, {
                    childList: true,
                    subtree: true
                });
            }
        }
        
        // Ajouter Font Awesome pour les icônes
        if (!document.querySelector("link[href*=\'font-awesome\']")) {
            const faLink = document.createElement("link");
            faLink.rel = "stylesheet";
            faLink.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css";
            faLink.media = "all";
            document.head.appendChild(faLink);
        }
        
            // Forcer l\'application des styles pour Discord/Bluesky/Threads
            // UNIQUEMENT sur les icones, pas sur les boutons
            setTimeout(function() {
                // Cibler uniquement les icones avec rafflepress-entry-option-icon
                const icons = document.querySelectorAll(".rafflepress-entry-option-icon.icon-visit-a-page.icon-discord, .rafflepress-entry-option-icon.icon-discord, .rafflepress-entry-option-icon.icon-visit-a-page.icon-bluesky, .rafflepress-entry-option-icon.icon-bluesky, .rafflepress-entry-option-icon.icon-visit-a-page.icon-threads, .rafflepress-entry-option-icon.icon-threads");
                icons.forEach(function(icon) {
                    if (icon.classList.contains("icon-discord")) {
                        icon.style.setProperty("background-color", "#36393e", "important");
                        icon.style.setProperty("border-color", "#36393e", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (icon.classList.contains("icon-bluesky")) {
                        icon.style.setProperty("background-color", "#1185fe", "important");
                        icon.style.setProperty("border-color", "#1185fe", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (icon.classList.contains("icon-threads")) {
                        icon.style.setProperty("background-color", "#000000", "important");
                        icon.style.setProperty("border-color", "#000000", "important");
                        icon.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    }
                });
                
                // S\'assurer que les boutons ne reçoivent PAS ces styles
                const buttons = document.querySelectorAll(".btn.btn-threads, .btn.btn-discord, .btn.btn-bluesky, .btn-action.btn-threads, .btn-action.btn-discord, .btn-action.btn-bluesky");
                buttons.forEach(function(btn) {
                    // Réinitialiser les styles si ils ont été appliqués par erreur
                    btn.style.removeProperty("background-color");
                    btn.style.removeProperty("border-color");
                    btn.style.removeProperty("color");
                });
            }, 100);
    }
    
    // Log pour debug - Vérifier que me5rineLabData est défini
    if (typeof me5rineLabData === "undefined") {
        console.error("[Me5rine LAB] ERREUR: me5rineLabData n\'est pas défini!");
        return;
    }
    
    console.log("[Me5rine LAB] Script de personnalisation chargé");
    console.log("[Me5rine LAB] Données:", me5rineLabData);
    
    // Démarrer la personnalisation dès que possible
    // Essayer plusieurs fois car Vue.js peut mettre du temps à charger
    console.log("[Me5rine LAB] Première tentative de personnalisation...");
    applyCustomizations();
    
    // Réessayer après le chargement complet
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            console.log("[Me5rine LAB] DOMContentLoaded, nouvelles tentatives...");
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 50ms"); applyCustomizations(); }, 50);
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 200ms"); applyCustomizations(); }, 200);
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 500ms"); applyCustomizations(); }, 500);
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 1000ms"); applyCustomizations(); }, 1000);
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 2000ms"); applyCustomizations(); }, 2000);
            setTimeout(function() { console.log("[Me5rine LAB] Tentative à 3000ms"); applyCustomizations(); }, 3000);
        });
    } else {
        console.log("[Me5rine LAB] DOM déjà chargé, nouvelles tentatives...");
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 50ms"); applyCustomizations(); }, 50);
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 200ms"); applyCustomizations(); }, 200);
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 500ms"); applyCustomizations(); }, 500);
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 1000ms"); applyCustomizations(); }, 1000);
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 2000ms"); applyCustomizations(); }, 2000);
        setTimeout(function() { console.log("[Me5rine LAB] Tentative à 3000ms"); applyCustomizations(); }, 3000);
    }
    
    // Observer les mutations du DOM pour détecter quand RafflePress ajoute le bloc de connexion
    if (typeof MutationObserver !== "undefined") {
        var domObserver = new MutationObserver(function(mutations) {
            var loginBlock = document.querySelector("#rafflepress-giveaway-login");
            if (loginBlock && !loginBlock.classList.contains("admin-lab-customized")) {
                // Le bloc vient d\'être ajouté, appliquer les personnalisations
                setTimeout(applyCustomizations, 50);
            }
        });
        
        // Observer le body et l\'app Vue.js
        var vueApp = document.querySelector("#rafflepress-frontent-vue-app");
        if (vueApp) {
            domObserver.observe(vueApp, {
                childList: true,
                subtree: true
            });
        }
        domObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Vérifier périodiquement que le bloc personnalisé est toujours affiché
    setInterval(function() {
        var loginBlock = document.querySelector("#rafflepress-giveaway-login");
        if (loginBlock && !loginBlock.classList.contains("admin-lab-customized")) {
            // Le bloc n\'est pas personnalisé, réappliquer
            applyCustomizations();
        } else if (loginBlock && loginBlock.classList.contains("admin-lab-customized")) {
            // Vérifier que le formulaire natif n\'est pas visible
            var nativeForms = loginBlock.querySelectorAll("form, .rafflepress-login-form, input[type=\'email\']");
            nativeForms.forEach(function(form) {
                if (form.offsetParent !== null || form.style.display !== "none") {
                    form.style.display = "none";
                    form.style.opacity = "0";
                    form.style.visibility = "hidden";
                }
            });
        }
    }, 1000);
    
    // Fallback : rendre le bloc visible après un délai si la personnalisation échoue
    setTimeout(function() {
        const loginBlock = document.querySelector("#rafflepress-giveaway-login");
        if (loginBlock && !loginBlock.classList.contains("admin-lab-customized")) {
            // Si le bloc n\'a pas été personnalisé après 3 secondes, forcer la personnalisation
            applyCustomizations();
        }
    }, 3000);
    
    let observerTimeout;
    const mutationObserver = new MutationObserver(function(mutations) {
        clearTimeout(observerTimeout);
        observerTimeout = setTimeout(function() {
            const loginBlock = document.querySelector("#rafflepress-giveaway-login");
            if (loginBlock && !customizationsApplied) {
                applyCustomizations();
            }
        }, 200);
    });
    
    const vueAppElement = document.querySelector("#rafflepress-frontent-vue-app");
    if (vueAppElement) {
        mutationObserver.observe(vueAppElement, {
            childList: true,
            subtree: true
        });
    }
})();
</script>
';

// Script pour nettoyer le fragment #_=_ après redirection
// Ce script doit s'exécuter dans la fenêtre parente, pas dans l'iframe
$clean_hash_script = '
<script>
(function() {
    "use strict";
    // Nettoyer le fragment #_=_ dans la fenêtre parente si on est dans une iframe
    try {
        if (window.parent && window.parent !== window) {
            // On est dans une iframe, nettoyer le hash dans le parent
            if (window.parent.location.hash === "#_=_") {
                if (window.parent.history && window.parent.history.replaceState) {
                    window.parent.history.replaceState(null, null, window.parent.location.pathname + window.parent.location.search);
                }
            }
            
            // Écouter les changements de hash dans le parent
            window.parent.addEventListener("hashchange", function() {
                if (window.parent.location.hash === "#_=_") {
                    if (window.parent.history && window.parent.history.replaceState) {
                        window.parent.history.replaceState(null, null, window.parent.location.pathname + window.parent.location.search);
                    }
                }
            }, false);
        } else {
            // On est dans la fenêtre principale, nettoyer directement
            if (window.location.hash === "#_=_") {
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname + window.location.search);
                } else {
                    window.location.hash = "";
                }
            }
            
            // Écouter les changements de hash
            window.addEventListener("hashchange", function() {
                if (window.location.hash === "#_=_") {
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname + window.location.search);
                    } else {
                        window.location.hash = "";
                    }
                }
            }, false);
        }
    } catch(e) {
        // Erreur de cross-origin, ignorer
        console.warn("[Me5rine LAB] Impossible de nettoyer le hash dans le parent:", e);
    }
})();
</script>
';

$output = str_replace('</body>', $clean_hash_script . $custom_script . '</body>', $output);

// Ajouter un script pour calculer et communiquer la hauteur du contenu au parent
$height_calculator_script = '
<script>
(function() {
    "use strict";
    
    let lastHeight = 0;
    let resizeTimeout;
    
    // Fonction pour calculer la hauteur réelle du contenu HTML
    function calculateContentHeight() {
        const html = document.documentElement;
        const body = document.body;
        
        // Trouver le dernier élément visible dans le body pour déterminer la hauteur réelle
        let maxBottom = 0;
        
        // Parcourir tous les éléments enfants directs du body
        const bodyChildren = Array.from(body.children);
        bodyChildren.forEach(function(el) {
            // Ignorer les éléments avec display: none ou visibility: hidden
            const style = window.getComputedStyle(el);
            if (style.display === "none" || style.visibility === "hidden" || style.opacity === "0") {
                return;
            }
            
            const rect = el.getBoundingClientRect();
            // Utiliser bottom qui donne la position absolue depuis le haut du viewport
            // On doit ajouter scrollTop pour avoir la position absolue dans le document
            const scrollTop = window.pageYOffset || html.scrollTop || body.scrollTop || 0;
            const absoluteBottom = rect.bottom + scrollTop;
            
            if (absoluteBottom > maxBottom) {
                maxBottom = absoluteBottom;
            }
        });
        
        // Si on n\'a pas trouvé d\'éléments, utiliser scrollHeight comme fallback
        if (maxBottom === 0) {
            maxBottom = Math.max(html.scrollHeight, body.scrollHeight);
        }
        
        // Vérifier aussi la hauteur de l\'élément principal de l\'app Vue
        const appElement = document.querySelector("#rafflepress-frontent-vue-app");
        if (appElement) {
            const appRect = appElement.getBoundingClientRect();
            const scrollTop = window.pageYOffset || html.scrollTop || body.scrollTop || 0;
            const appBottom = appRect.bottom + scrollTop;
            
            // Utiliser la position bottom de l\'app si elle est plus grande
            if (appBottom > maxBottom) {
                maxBottom = appBottom;
            }
        }
        
        // Ajouter 5px pour éviter que les bordures des éléments en bas soient coupées
        return Math.ceil(maxBottom + 5);
    }
    
    // Fonction pour envoyer la hauteur au parent (avec debounce optionnel)
    function sendHeightToParent(immediate) {
        if (!immediate) {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                sendHeightToParent(true);
            }, 50); // Délai réduit à 50ms
            return;
        }
        
        const height = calculateContentHeight();
        
        // Toujours envoyer la hauteur, même si elle n\'a pas changé (pour forcer la mise à jour)
        // Cela permet de réduire la hauteur quand un accordéon plus petit s\'ouvre
        lastHeight = height;
        
        // Envoyer la hauteur au parent via postMessage
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: "me5rine-lab-iframe-height",
                height: height,
                iframeId: "rafflepress-' . $rafflepress_id . '"
            }, "*");
        }
    }
    
    // Envoyer la hauteur initiale après le chargement
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() { sendHeightToParent(true); }, 100);
        });
    } else {
        setTimeout(function() { sendHeightToParent(true); }, 100);
    }
    
    // Envoyer la hauteur après que Vue.js ait rendu le contenu
    setTimeout(function() { sendHeightToParent(true); }, 1000);
    setTimeout(function() { sendHeightToParent(true); }, 2000);
    setTimeout(function() { sendHeightToParent(true); }, 3000);
    
    // Observer les changements dans le DOM pour recalculer la hauteur
    const mutationObserver = new MutationObserver(function(mutations) {
        // Vérifier si les mutations concernent des éléments qui affectent la hauteur
        let shouldRecalculate = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === "attributes") {
                const attrName = mutation.attributeName;
                if (attrName === "style" || attrName === "class" || attrName === "height") {
                    shouldRecalculate = true;
                }
            } else if (mutation.type === "childList") {
                shouldRecalculate = true;
            }
        });
        
        if (shouldRecalculate) {
            // Recalcul immédiat + plusieurs recalculs pour capturer les animations
            sendHeightToParent(true);
            setTimeout(function() { sendHeightToParent(true); }, 50);
            setTimeout(function() { sendHeightToParent(true); }, 150);
            setTimeout(function() { sendHeightToParent(true); }, 300);
        }
    });
    
    mutationObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["style", "class", "height", "max-height", "min-height", "display"]
    });
    
    // Utiliser ResizeObserver pour détecter les changements de taille des éléments
    if (typeof ResizeObserver !== "undefined") {
        const resizeObserver = new ResizeObserver(function(entries) {
            // Recalcul immédiat lors des changements de taille
            sendHeightToParent(true);
            // Recalcul supplémentaire après un court délai pour les animations
            setTimeout(function() { sendHeightToParent(true); }, 100);
        });
        
        // Observer le body et l\'élément principal de l\'app Vue
        resizeObserver.observe(document.body);
        const appElement = document.querySelector("#rafflepress-frontent-vue-app");
        if (appElement) {
            resizeObserver.observe(appElement);
        }
        
        // Observer aussi tous les éléments collapse/accordion
        const collapseElements = document.querySelectorAll(".collapse, .accordion, [data-toggle=\'collapse\']");
        collapseElements.forEach(function(el) {
            resizeObserver.observe(el);
        });
    }
    
    // Écouter les événements de redimensionnement de la fenêtre
    window.addEventListener("resize", function() {
        sendHeightToParent(true);
    });
    
    // Écouter les clics sur les accordéons et autres éléments interactifs
    document.addEventListener("click", function(e) {
        // Détecter les clics sur les éléments qui pourraient changer la hauteur
        const target = e.target;
        const isAccordionRelated = target && (
            target.classList.contains("collapse") ||
            target.closest(".collapse") ||
            target.classList.contains("accordion") ||
            target.closest(".accordion") ||
            target.getAttribute("data-toggle") === "collapse" ||
            target.closest("[data-toggle=\'collapse\']") ||
            target.closest("[data-bs-toggle=\'collapse\']")
        );
        
        if (isAccordionRelated) {
            // Recalcul immédiat + plusieurs recalculs pour capturer l\'animation
            sendHeightToParent(true);
            setTimeout(function() { sendHeightToParent(true); }, 50);
            setTimeout(function() { sendHeightToParent(true); }, 150);
            setTimeout(function() { sendHeightToParent(true); }, 300);
            setTimeout(function() { sendHeightToParent(true); }, 500);
        }
    }, true);
    
    // Observer les changements de classes (pour détecter les accordéons qui s\'ouvrent/ferment)
    const classObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "attributes" && mutation.attributeName === "class") {
                const target = mutation.target;
                // Vérifier si c\'est un élément d\'accordéon
                const isAccordionElement = target.classList.contains("collapse") || 
                    target.classList.contains("show") ||
                    target.classList.contains("collapsing") ||
                    target.closest(".accordion") ||
                    target.closest(".collapse");
                
                if (isAccordionElement) {
                    // Recalcul immédiat + plusieurs recalculs
                    sendHeightToParent(true);
                    setTimeout(function() { sendHeightToParent(true); }, 50);
                    setTimeout(function() { sendHeightToParent(true); }, 150);
                    setTimeout(function() { sendHeightToParent(true); }, 300);
                    setTimeout(function() { sendHeightToParent(true); }, 500);
                }
            }
        });
    });
    
    classObserver.observe(document.body, {
        subtree: true,
        attributes: true,
        attributeFilter: ["class"]
    });
    
    // Observer aussi les changements de style (pour détecter les changements de display, height, etc.)
    const styleObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "attributes" && mutation.attributeName === "style") {
                const target = mutation.target;
                if (target.closest(".collapse") || target.closest(".accordion")) {
                    sendHeightToParent(true);
                    setTimeout(function() { sendHeightToParent(true); }, 100);
                    setTimeout(function() { sendHeightToParent(true); }, 300);
                }
            }
        });
    });
    
    styleObserver.observe(document.body, {
        subtree: true,
        attributes: true,
        attributeFilter: ["style"]
    });
    
    // Recalculer plus fréquemment (toutes les 500ms) pour s\'assurer que la hauteur est à jour
    setInterval(function() {
        const currentHeight = calculateContentHeight();
        if (currentHeight !== lastHeight) {
            sendHeightToParent(true);
        }
    }, 500);
})();
</script>
';

$output = str_replace('</body>', $height_calculator_script . '</body>', $output);

echo $output;
