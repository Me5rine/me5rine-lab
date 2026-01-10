<?php
// File: modules/giveaways/functions/giveaways-custom-render-route.php

if (!defined('ABSPATH')) exit;

// Ajouter la variable de requête personnalisée
add_filter('query_vars', 'me5rine_lab_add_custom_query_vars');
function me5rine_lab_add_custom_query_vars($vars) {
    $vars[] = 'me5rine_lab_giveaway_render';
    $vars[] = 'me5rine_lab_giveaway_id';
    return $vars;
}

// Intercepter les requêtes pour le rendu personnalisé
// On intercepte AVANT RafflePress (priorité 5 vs 10 par défaut)
add_action('template_redirect', 'me5rine_lab_custom_giveaway_render', 5);
function me5rine_lab_custom_giveaway_render() {
    $giveaway_id = get_query_var('me5rine_lab_giveaway_id');
    
    // Vérifier aussi dans $_GET pour compatibilité
    if (empty($giveaway_id) && !empty($_GET['me5rine_lab_giveaway_id'])) {
        $giveaway_id = absint($_GET['me5rine_lab_giveaway_id']);
    }
    
    // Vérifier si c'est notre route personnalisée
    if (empty($giveaway_id)) {
        return;
    }
    
    // Nettoyer le buffer si nécessaire
    $c = ob_get_contents();
    if ($c) {
        @ob_end_clean();
    }
    
    // Headers pour éviter le cache
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Charger notre template personnalisé complet
    require_once __DIR__ . '/../templates/giveaways-custom-rafflepress-giveaway.php';
    exit();
}

// Fonction pour générer le script de personnalisation
function me5rine_lab_get_customization_script() {
    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    $user_name    = $is_logged_in ? $current_user->display_name : '';
    $user_email   = $is_logged_in ? $current_user->user_email : '';
    
    // Récupérer l'ID du giveaway
    $giveaway_id = get_query_var('me5rine_lab_giveaway_id');
    if (empty($giveaway_id) && !empty($_GET['me5rine_lab_giveaway_id'])) {
        $giveaway_id = absint($_GET['me5rine_lab_giveaway_id']);
    }
    
    // Récupérer le nom du partenaire
    $partner_name = '';
    $post_id      = function_exists('admin_lab_get_post_id_from_rafflepress') ? admin_lab_get_post_id_from_rafflepress($giveaway_id) : null;
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
    
    // Récupérer les prix
    global $wpdb;
    $prizes = [];
    if ($giveaway_id) {
        $settings_json = $wpdb->get_var($wpdb->prepare(
            "SELECT settings FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d",
            $giveaway_id
        ));
        if ($settings_json) {
            $settings = json_decode($settings_json, true);
            if (!empty($settings['prizes']) && is_array($settings['prizes'])) {
                foreach ($settings['prizes'] as $prize) {
                    if (!empty($prize['name'])) {
                        $prizes[] = $prize['name'];
                    }
                }
            }
        }
    }
    
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
    
    // Récupérer les couleurs Elementor si disponibles
    $colors = [];
    if (function_exists('admin_lab_get_elementor_kit_colors')) {
        $colors = admin_lab_get_elementor_kit_colors();
    }
    
    // Le script de personnalisation (même que dans le template personnalisé)
    ob_start();
    ?>
    <script>
    (function() {
        'use strict';
        
        var me5rineLabData = {
            userName: <?php echo json_encode($user_name); ?>,
            userEmail: <?php echo json_encode($user_email); ?>,
            partnerName: <?php echo json_encode($partner_name); ?>,
            websiteName: <?php echo json_encode($website_name); ?>,
            prizes: <?php echo json_encode($prizes); ?>,
            translations: <?php echo json_encode($translations); ?>,
            colors: <?php echo json_encode($colors); ?>,
            loginUrl: <?php echo json_encode(wp_login_url(get_permalink())); ?>,
            registerUrl: <?php echo json_encode(wp_registration_url()); ?>
        };
        
        if (typeof me5rineLabData === 'undefined') {
            console.warn('[Me5rine LAB] Données de personnalisation non disponibles');
            return;
        }
        
        let customizationsApplied = false;
        let retryCount = 0;
        const maxRetries = 50;
        
        function applyCustomizations() {
            if (customizationsApplied && retryCount > 10) {
                return;
            }
            
            const app = document.querySelector('#rafflepress-frontent-vue-app');
            const loginBlock = document.querySelector('#rafflepress-giveaway-login');
            
            if (!app || !loginBlock) {
                retryCount++;
                if (retryCount < maxRetries) {
                    setTimeout(applyCustomizations, 100);
                }
                return;
            }
            
            if (!customizationsApplied) {
                const data = me5rineLabData;
                let prizeMessage = '';
                
                if (data.prizes.length === 1) {
                    prizeMessage = data.translations.prizeMessage.single.replace('%s', data.prizes[0]);
                } else if (data.prizes.length > 1) {
                    prizeMessage = data.translations.prizeMessage.multiple.replace('%s', data.prizes.join(', '));
                } else {
                    prizeMessage = data.translations.prizeMessage.none;
                }
                
                if (data.userEmail && data.userName) {
                    loginBlock.innerHTML = '<div class="admin-lab-welcome-block"><p>' + data.translations.greeting.replace('%s', '<strong>' + data.userName + '</strong>') + '</p><p>' + prizeMessage + '</p></div>';
                } else {
                    // Utiliser des styles inline pour garantir l'application
                    var primaryColor = data.colors.primary || '#02395A';
                    var secondaryColor = data.colors.secondary || '#0485C8';
                    var textColor = data.colors['338f618'] || '#FFFFFF';
                    var bgColor = data.colors['3d5ef52'] || '#F9FAFB';
                    
                    loginBlock.innerHTML = '<div class="admin-lab-login-block" style="background-color: ' + bgColor + '; border-radius: 12px; padding: 5px; text-align: center; max-width: 400px; margin: 5px auto; font-family: \'Segoe UI\', sans-serif;"><p style="font-size: 16px; font-weight: 500; margin-bottom: 10px; color: #374151;">' + data.translations.prizeMessage.login + '</p><a href="' + data.loginUrl + '" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: ' + primaryColor + '; color: ' + textColor + ' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">' + data.translations.prizeMessage.loginBtn + '</a><a href="' + data.registerUrl + '" target="_parent" style="display: inline-block; margin: 8px 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 5px; background-color: ' + primaryColor + '; color: ' + textColor + ' !important; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.1); cursor: pointer;">' + data.translations.prizeMessage.registerBtn + '</a></div>';
                }
                
                customizationsApplied = true;
            }
            
            const autoEntryWrapper = document.querySelector('.rafflepress-action-separator .btn-action-automatic-entry');
            if (autoEntryWrapper) {
                const parentBlock = autoEntryWrapper.closest('.rafflepress-action-separator');
                if (parentBlock && !parentBlock.classList.contains('admin-lab-replaced')) {
                    parentBlock.classList.add('admin-lab-replaced');
                    const separator = document.createElement('div');
                    separator.innerHTML = '<hr style="margin: 20px 0;"><p style="text-align:center; font-weight:bold; font-size: 16px; color: #2d2d2d;">' + me5rineLabData.translations.separator + '</p>';
                    parentBlock.replaceWith(separator);
                }
            }
            
            ['discord', 'bluesky', 'threads'].forEach(function(type) {
                const wrappers = document.querySelectorAll('.rafflepress-action-' + type + ':not(.admin-lab-customized), .rafflepress-action-' + type + '_2:not(.admin-lab-customized)');
                wrappers.forEach(function(wrapper) {
                    wrapper.classList.add('admin-lab-customized');
                    
                    const button = wrapper.querySelector('.btn-action-visit-a-page');
                    const label = wrapper.querySelector('.rafflepress-action-text');
                    const icon = wrapper.querySelector('.rafflepress-entry-option-icon');
                    
                    if (button) button.classList.add('btn-' + type);
                    if (icon) icon.classList.add('icon-' + type);
                    
                    let entryId = wrapper.getAttribute('data-entry-id') || '';
                    if (!entryId) {
                        const entryEl = wrapper.querySelector('[data-entry-id]');
                        entryId = entryEl ? entryEl.getAttribute('data-entry-id') : '';
                    }
                    if (!entryId) {
                        if (wrapper.classList.contains('rafflepress-action-' + type + '_2')) {
                            entryId = type + '_2';
                        } else if (wrapper.classList.contains('rafflepress-action-' + type)) {
                            entryId = type;
                        }
                    }
                    
                    let displayName = me5rineLabData.partnerName || '';
                    if (entryId === type + '_2') {
                        displayName = me5rineLabData.websiteName || 'Me5rine LAB';
                    }
                    
                    if (label && me5rineLabData.translations[type + 'JoinLabel']) {
                        label.textContent = me5rineLabData.translations[type + 'JoinLabel'].replace('%s', displayName);
                    }
                    
                    // Appliquer les styles inline immédiatement
                    if (button) {
                        if (type === 'discord') {
                            button.style.backgroundColor = '#36393e';
                            button.style.borderColor = '#36393e';
                            button.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                        } else if (type === 'bluesky') {
                            button.style.backgroundColor = '#1185fe';
                            button.style.borderColor = '#1185fe';
                            button.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                        } else if (type === 'threads') {
                            button.style.backgroundColor = '#000000';
                            button.style.borderColor = '#000000';
                            button.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                        }
                    }
                    
                    if (button && !button.hasAttribute('data-me5rine-listener')) {
                        button.setAttribute('data-me5rine-listener', 'true');
                        button.addEventListener('click', function() {
                            setTimeout(function() {
                                const actionArea = wrapper.querySelector('.rafflepress-action');
                                if (actionArea) {
                                    const intro = actionArea.querySelector('p');
                                    if (intro && me5rineLabData.translations[type + 'JoinText']) {
                                        intro.textContent = me5rineLabData.translations[type + 'JoinText'];
                                    }
                                    
                                    const linkBtn = actionArea.querySelector('a.btn-visit-a-page');
                                    if (linkBtn) {
                                        const iconEl = linkBtn.querySelector('i.fas.fa-external-link-alt');
                                        if (iconEl) {
                                            iconEl.className = 'fa-brands fa-' + type;
                                        }
                                        
                                        const textNode = Array.from(linkBtn.childNodes).find(function(n) {
                                            return n.nodeType === Node.TEXT_NODE;
                                        });
                                        if (textNode && me5rineLabData.translations[type + 'JoinBtn']) {
                                            textNode.nodeValue = ' ' + me5rineLabData.translations[type + 'JoinBtn'];
                                        }
                                        
                                        linkBtn.classList.add('btn-' + type);
                                        // Réappliquer les styles après le clic
                                        if (type === 'discord') {
                                            linkBtn.style.backgroundColor = '#36393e';
                                            linkBtn.style.borderColor = '#36393e';
                                            linkBtn.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                                        } else if (type === 'bluesky') {
                                            linkBtn.style.backgroundColor = '#1185fe';
                                            linkBtn.style.borderColor = '#1185fe';
                                            linkBtn.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                                        } else if (type === 'threads') {
                                            linkBtn.style.backgroundColor = '#000000';
                                            linkBtn.style.borderColor = '#000000';
                                            linkBtn.style.color = me5rineLabData.colors['338f618'] || '#FFFFFF';
                                        }
                                    }
                                }
                            }, 0);
                        });
                    }
                });
            });
            
            // Ajouter Font Awesome pour les icônes
            if (!document.querySelector('link[href*="font-awesome"]')) {
                const faLink = document.createElement('link');
                faLink.rel = 'stylesheet';
                faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css';
                faLink.media = 'all';
                document.head.appendChild(faLink);
            }
            
            // Forcer l'application des styles personnalisés pour Discord/Bluesky/Threads
            // Appliquer les styles inline pour garantir qu'ils s'appliquent
            setTimeout(function() {
                const actionButtons = document.querySelectorAll('.btn-discord, .btn-bluesky, .btn-threads, .icon-discord, .icon-bluesky, .icon-threads, .rafflepress-action-discord .btn-primary, .rafflepress-action-bluesky .btn-primary, .rafflepress-action-threads .btn-primary');
                actionButtons.forEach(function(btn) {
                    if (btn.classList.contains('btn-discord') || btn.classList.contains('icon-discord') || btn.closest('.rafflepress-action-discord')) {
                        btn.style.setProperty('background-color', '#36393e', 'important');
                        btn.style.setProperty('border-color', '#36393e', 'important');
                        btn.style.setProperty('color', me5rineLabData.colors['338f618'] || '#FFFFFF', 'important');
                    } else if (btn.classList.contains('btn-bluesky') || btn.classList.contains('icon-bluesky') || btn.closest('.rafflepress-action-bluesky')) {
                        btn.style.setProperty('background-color', '#1185fe', 'important');
                        btn.style.setProperty('border-color', '#1185fe', 'important');
                        btn.style.setProperty('color', me5rineLabData.colors['338f618'] || '#FFFFFF', 'important');
                    } else if (btn.classList.contains('btn-threads') || btn.classList.contains('icon-threads') || btn.closest('.rafflepress-action-threads')) {
                        btn.style.setProperty('background-color', '#000000', 'important');
                        btn.style.setProperty('border-color', '#000000', 'important');
                        btn.style.setProperty('color', me5rineLabData.colors['338f618'] || '#FFFFFF', 'important');
                    }
                });
            }, 100);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(applyCustomizations, 500);
            });
        } else {
            setTimeout(applyCustomizations, 500);
        }
        
        let observerTimeout;
        const observer = new MutationObserver(function(mutations) {
            clearTimeout(observerTimeout);
            observerTimeout = setTimeout(function() {
                const loginBlock = document.querySelector('#rafflepress-giveaway-login');
                if (loginBlock && !customizationsApplied) {
                    applyCustomizations();
                }
            }, 200);
        });
        
        const app = document.querySelector('#rafflepress-frontent-vue-app');
        if (app) {
            observer.observe(app, {
                childList: true,
                subtree: true
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// Fonction pour générer les styles personnalisés
function me5rine_lab_get_customization_styles() {
    $colors = [];
    if (function_exists('admin_lab_get_elementor_kit_colors')) {
        $colors = admin_lab_get_elementor_kit_colors();
    }
    
    $primary_color = !empty($colors['primary']) ? $colors['primary'] : '#02395A';
    $secondary_color = !empty($colors['secondary']) ? $colors['secondary'] : '#0485C8';
    $text_color = !empty($colors['338f618']) ? $colors['338f618'] : '#FFFFFF';
    $bg_color = !empty($colors['3d5ef52']) ? $colors['3d5ef52'] : '#F9FAFB';
    
    ob_start();
    ?>
    <style id="me5rine-lab-custom-styles">
    /* Styles personnalisés Me5rine LAB - Priorité élevée */
    #rafflepress-giveaway-login .admin-lab-login-block {
        background-color: <?php echo esc_attr($bg_color); ?> !important;
        border-radius: 12px !important;
        padding: 5px !important;
        text-align: center !important;
        max-width: 400px !important;
        margin: 5px auto !important;
        font-family: 'Segoe UI', sans-serif !important;
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
        background-color: <?php echo esc_attr($primary_color); ?> !important;
        color: <?php echo esc_attr($text_color); ?> !important;
        border: none !important;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1) !important;
        cursor: pointer !important;
    }
    #rafflepress-giveaway-login .admin-lab-login-block a:hover {
        background-color: <?php echo esc_attr($secondary_color); ?> !important;
        color: <?php echo esc_attr($text_color); ?> !important;
    }
    #rafflepress-giveaway-login .admin-lab-welcome-block {
        padding: 15px !important;
        text-align: center !important;
    }
    /* Styles pour Discord, Bluesky, Threads */
    .rafflepress-giveaway .icon-discord,
    .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-discord,
    .rafflepress-giveaway .rafflepress-action-discord .btn-primary,
    .rafflepress-giveaway .rafflepress-action-discord_2 .btn-primary {
        background-color: #36393e !important;
        border-color: #36393e !important;
        color: <?php echo esc_attr($text_color); ?> !important;
    }
    .rafflepress-giveaway .icon-bluesky,
    .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-bluesky,
    .rafflepress-giveaway .rafflepress-action-bluesky .btn-primary {
        background-color: #1185fe !important;
        border-color: #1185fe !important;
        color: <?php echo esc_attr($text_color); ?> !important;
    }
    .rafflepress-giveaway .icon-threads,
    .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-threads,
    .rafflepress-giveaway .rafflepress-action-threads .btn-primary {
        background-color: #000000 !important;
        border-color: #000000 !important;
        color: <?php echo esc_attr($text_color); ?> !important;
    }
    .rafflepress-giveaway .rafflepress-entry-option-icon {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding-top: 2px !important;
    }
    /* Styles pour les icônes Font Awesome */
    .rafflepress-giveaway .icon-discord::before {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
        content: "\f392" !important;
        font-style: normal !important;
    }
    .rafflepress-giveaway .icon-bluesky::before {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
        content: "\e671" !important;
        font-style: normal !important;
    }
    .rafflepress-giveaway .icon-threads::before {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
        content: "\e618" !important;
        font-style: normal !important;
    }
    </style>
    <?php
    return ob_get_clean();
}

// Fonction pour générer les styles dynamiques en JS
function me5rine_lab_get_dynamic_styles() {
    $colors = [];
    if (function_exists('admin_lab_get_elementor_kit_colors')) {
        $colors = admin_lab_get_elementor_kit_colors();
    }
    
    $primary_color = !empty($colors['primary']) ? $colors['primary'] : '#02395A';
    $secondary_color = !empty($colors['secondary']) ? $colors['secondary'] : '#0485C8';
    $text_color = !empty($colors['338f618']) ? $colors['338f618'] : '#FFFFFF';
    
    return "
        .rafflepress-giveaway .icon-discord,
        .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-discord,
        .rafflepress-giveaway .rafflepress-action-discord .btn-primary,
        .rafflepress-giveaway .rafflepress-action-discord_2 .btn-primary {
            background-color: #36393e !important;
            border-color: #36393e !important;
            color: {$text_color} !important;
        }
        .rafflepress-giveaway .icon-bluesky,
        .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-bluesky,
        .rafflepress-giveaway .rafflepress-action-bluesky .btn-primary {
            background-color: #1185fe !important;
            border-color: #1185fe !important;
            color: {$text_color} !important;
        }
        .rafflepress-giveaway .icon-threads,
        .rafflepress-giveaway .btn.btn-primary.btn-visit-a-page.btn-block.btn-threads,
        .rafflepress-giveaway .rafflepress-action-threads .btn-primary {
            background-color: #000000 !important;
            border-color: #000000 !important;
            color: {$text_color} !important;
        }
    ";
}

