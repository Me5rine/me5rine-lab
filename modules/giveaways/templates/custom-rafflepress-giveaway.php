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

// Récupérer les données utilisateur pour les personnalisations
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$user_name    = $is_logged_in ? $current_user->display_name : '';
$user_email   = $is_logged_in ? $current_user->user_email : '';

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

// Injecter nos styles personnalisés dans le head
$custom_styles = '
<style id="me5rine-lab-custom-styles">
/* Styles personnalisés Me5rine LAB */
#rafflepress-giveaway-login .admin-lab-login-block {
    background-color: ' . esc_attr($bg_color) . ' !important;
    border-radius: 12px !important;
    padding: 5px !important;
    text-align: center !important;
    max-width: 400px !important;
    margin: 5px auto !important;
    font-family: \'Segoe UI\', sans-serif !important;
}
#rafflepress-giveaway-login .admin-lab-login-block p {
    font-size: 16px !important;
    font-weight: 500 !important;
    margin-bottom: 10px !important;
    color: #374151 !important;
}
#rafflepress-giveaway-login .admin-lab-login-block a {
    display: inline-block !important;
    margin: 8px 10px !important;
    padding: 10px 20px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    border-radius: 5px !important;
    background-color: ' . esc_attr($primary_color) . ' !important;
    color: ' . esc_attr($text_color) . ' !important;
    border: none !important;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1) !important;
    cursor: pointer !important;
}
#rafflepress-giveaway-login .admin-lab-login-block a:hover {
    background-color: ' . esc_attr($secondary_color) . ' !important;
    color: ' . esc_attr($text_color) . ' !important;
}
#rafflepress-giveaway-login .admin-lab-welcome-block {
    padding: 15px !important;
    text-align: center !important;
}
.rafflepress-giveaway .icon-discord,
.rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-discord,
.rafflepress-giveaway .rafflepress-action-discord .btn-primary,
.rafflepress-giveaway .rafflepress-action-discord_2 .btn-primary {
    background-color: #36393e !important;
    border-color: #36393e !important;
    color: ' . esc_attr($text_color) . ' !important;
}
.rafflepress-giveaway .icon-bluesky,
.rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-bluesky,
.rafflepress-giveaway .rafflepress-action-bluesky .btn-primary {
    background-color: #1185fe !important;
    border-color: #1185fe !important;
    color: ' . esc_attr($text_color) . ' !important;
}
.rafflepress-giveaway .icon-threads,
.rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-threads,
.rafflepress-giveaway .rafflepress-action-threads .btn-primary {
    background-color: #000000 !important;
    border-color: #000000 !important;
    color: ' . esc_attr($text_color) . ' !important;
}
.rafflepress-giveaway .rafflepress-entry-option-icon {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding-top: 2px !important;
}
.rafflepress-giveaway .icon-discord::before {
    font-family: "Font Awesome 6 Brands" !important;
    font-weight: 400 !important;
    content: "\\f392" !important;
    font-style: normal !important;
}
.rafflepress-giveaway .icon-bluesky::before {
    font-family: "Font Awesome 6 Brands" !important;
    font-weight: 400 !important;
    content: "\\e671" !important;
    font-style: normal !important;
}
.rafflepress-giveaway .icon-threads::before {
    font-family: "Font Awesome 6 Brands" !important;
    font-weight: 400 !important;
    content: "\\e618" !important;
    font-style: normal !important;
}
</style>
';

$output = str_replace('</head>', $custom_styles . '</head>', $output);

// Retirer le script iframe-resizer de RafflePress pour éviter les rebonds au scroll
// On utilise une hauteur fixe définie dans le shortcode au lieu d'un resizer dynamique
$output = preg_replace('/<script[^>]*iframeResizer\.contentWindow[^>]*>.*?<\/script>/is', '', $output);
$output = preg_replace('/<script[^>]*data-cfasync[^>]*iframeResizer[^>]*>.*?<\/script>/is', '', $output);

// Injecter le script de personnalisation avant </body>
$custom_script = '
<script>
(function() {
    "use strict";
    
    var me5rineLabData = {
        userName: ' . json_encode($user_name) . ',
        userEmail: ' . json_encode($user_email) . ',
        partnerName: ' . json_encode($partner_name) . ',
        websiteName: ' . json_encode($website_name) . ',
        translations: ' . json_encode($translations) . ',
        colors: ' . json_encode($colors) . ',
        loginUrl: ' . json_encode(wp_login_url(get_permalink())) . ',
        registerUrl: ' . json_encode(wp_registration_url()) . '
    };
    
    // Récupérer les prix depuis rafflepress_data
    if (typeof rafflepress_data !== "undefined" && rafflepress_data.settings && rafflepress_data.settings.prizes) {
        me5rineLabData.prizes = rafflepress_data.settings.prizes.map(function(p) { return p.name; }).filter(Boolean);
    }
    
    let customizationsApplied = false;
    let retryCount = 0;
    const maxRetries = 50;
    
    function applyCustomizations() {
        if (customizationsApplied && retryCount > 10) {
            return;
        }
        
        const app = document.querySelector("#rafflepress-frontent-vue-app");
        const loginBlock = document.querySelector("#rafflepress-giveaway-login");
        
        if (!app || !loginBlock) {
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(applyCustomizations, 100);
            }
            return;
        }
        
        if (!customizationsApplied) {
            const data = me5rineLabData;
            let prizeMessage = "";
            
            if (data.prizes.length === 1) {
                prizeMessage = data.translations.prizeMessage.single.replace("%s", data.prizes[0]);
            } else if (data.prizes.length > 1) {
                prizeMessage = data.translations.prizeMessage.multiple.replace("%s", data.prizes.join(", "));
            } else {
                prizeMessage = data.translations.prizeMessage.none;
            }
            
            if (data.userEmail && data.userName) {
                loginBlock.innerHTML = \'<div class="admin-lab-welcome-block"><p>\' + data.translations.greeting.replace("%s", "<strong>" + data.userName + "</strong>") + \'</p><p>\' + prizeMessage + \'</p></div>\';
            } else {
                var primaryColor = data.colors.primary || "#02395A";
                var secondaryColor = data.colors.secondary || "#0485C8";
                var textColor = data.colors["338f618"] || "#FFFFFF";
                var bgColor = data.colors["3d5ef52"] || "#F9FAFB";
                
                loginBlock.innerHTML = \'<div class="admin-lab-login-block" style="background-color: \' + bgColor + \'; border-radius: 12px; padding: 5px; text-align: center; max-width: 400px; margin: 5px auto; font-family: \\\'Segoe UI\\\', sans-serif;"><p style="font-size: 16px; font-weight: 500; margin-bottom: 10px; color: #374151;">\' + data.translations.prizeMessage.login + \'</p><a href="\' + data.loginUrl + \'" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: \' + primaryColor + \'; color: \' + textColor + \' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">\' + data.translations.prizeMessage.loginBtn + \'</a><a href="\' + data.registerUrl + \'" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: \' + primaryColor + \'; color: \' + textColor + \' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">\' + data.translations.prizeMessage.registerBtn + \'</a></div>\';
            }
            
            customizationsApplied = true;
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
                
                // Appliquer les styles inline immédiatement
                if (button) {
                    if (type === "discord") {
                        button.style.setProperty("background-color", "#36393e", "important");
                        button.style.setProperty("border-color", "#36393e", "important");
                        button.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (type === "bluesky") {
                        button.style.setProperty("background-color", "#1185fe", "important");
                        button.style.setProperty("border-color", "#1185fe", "important");
                        button.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    } else if (type === "threads") {
                        button.style.setProperty("background-color", "#000000", "important");
                        button.style.setProperty("border-color", "#000000", "important");
                        button.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                    }
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
                                    // Réappliquer les styles après le clic
                                    if (type === "discord") {
                                        linkBtn.style.setProperty("background-color", "#36393e", "important");
                                        linkBtn.style.setProperty("border-color", "#36393e", "important");
                                        linkBtn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    } else if (type === "bluesky") {
                                        linkBtn.style.setProperty("background-color", "#1185fe", "important");
                                        linkBtn.style.setProperty("border-color", "#1185fe", "important");
                                        linkBtn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    } else if (type === "threads") {
                                        linkBtn.style.setProperty("background-color", "#000000", "important");
                                        linkBtn.style.setProperty("border-color", "#000000", "important");
                                        linkBtn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                                    }
                                }
                            }
                        }, 0);
                    });
                }
            });
        });
        
        // Ajouter Font Awesome pour les icônes
        if (!document.querySelector("link[href*=\'font-awesome\']")) {
            const faLink = document.createElement("link");
            faLink.rel = "stylesheet";
            faLink.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css";
            faLink.media = "all";
            document.head.appendChild(faLink);
        }
        
        // Forcer l\'application des styles pour Discord/Bluesky/Threads
        setTimeout(function() {
            const actionButtons = document.querySelectorAll(".btn-discord, .btn-bluesky, .btn-threads, .icon-discord, .icon-bluesky, .icon-threads, .rafflepress-action-discord .btn-primary, .rafflepress-action-bluesky .btn-primary, .rafflepress-action-threads .btn-primary");
            actionButtons.forEach(function(btn) {
                if (btn.classList.contains("btn-discord") || btn.classList.contains("icon-discord") || btn.closest(".rafflepress-action-discord")) {
                    btn.style.setProperty("background-color", "#36393e", "important");
                    btn.style.setProperty("border-color", "#36393e", "important");
                    btn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                } else if (btn.classList.contains("btn-bluesky") || btn.classList.contains("icon-bluesky") || btn.closest(".rafflepress-action-bluesky")) {
                    btn.style.setProperty("background-color", "#1185fe", "important");
                    btn.style.setProperty("border-color", "#1185fe", "important");
                    btn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                } else if (btn.classList.contains("btn-threads") || btn.classList.contains("icon-threads") || btn.closest(".rafflepress-action-threads")) {
                    btn.style.setProperty("background-color", "#000000", "important");
                    btn.style.setProperty("border-color", "#000000", "important");
                    btn.style.setProperty("color", me5rineLabData.colors["338f618"] || "#FFFFFF", "important");
                }
            });
        }, 100);
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(applyCustomizations, 500);
        });
    } else {
        setTimeout(applyCustomizations, 500);
    }
    
    let observerTimeout;
    const observer = new MutationObserver(function(mutations) {
        clearTimeout(observerTimeout);
        observerTimeout = setTimeout(function() {
            const loginBlock = document.querySelector("#rafflepress-giveaway-login");
            if (loginBlock && !customizationsApplied) {
                applyCustomizations();
            }
        }, 200);
    });
    
    const app = document.querySelector("#rafflepress-frontent-vue-app");
    if (app) {
        observer.observe(app, {
            childList: true,
            subtree: true
        });
    }
})();
</script>
';

$output = str_replace('</body>', $custom_script . '</body>', $output);

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
