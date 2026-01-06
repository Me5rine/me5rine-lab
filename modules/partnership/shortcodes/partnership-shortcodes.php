<?php
// File: modules/partnership/partnership-shortcodes.php

if (!defined('ABSPATH')) exit;

// Shortcode dashboard des partenaires
function admin_lab_render_partner_dashboard_shortcode() {
    ob_start();
    include __DIR__ . '/../templates/partnership-dashboard.php';
    return ob_get_clean();
}
add_shortcode('partner_dashboard', 'admin_lab_render_partner_dashboard_shortcode');

// Shortcode menu partenaires
function admin_lab_render_partner_menu_shortcode($atts = []) {
    if (!is_user_logged_in()) {
        return '';
    }

    $user_id = get_current_user_id();
    if (!admin_lab_user_is_partner($user_id) && !admin_lab_user_is_subscriber($user_id)) {
        return '';
    }

    // Enqueue FontAwesome pour les icônes (toujours, WordPress évite les doublons)
    wp_enqueue_style(
        'me5rine-lab-font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css',
        [],
        '6.7.1'
    );
    
    // Note: Le JavaScript du menu est géré par le thème pour une intégration unifiée avec Ultimate Member

    // Récupérer la structure du menu
    $menu_structure = admin_lab_get_partner_menu_structure($user_id);
    if (empty($menu_structure)) {
        return '';
    }

    ob_start();
    ?>
    <div class="me5rine-lab-menu-wrapper">
        <button class="me5rine-lab-menu-toggle" aria-expanded="false" aria-controls="me5rine-lab-menu">
            <p class="me5rine-lab-menu-toggle-text"><?php esc_html_e('Menu', 'me5rine-lab'); ?></p>
        </button>
        <nav id="me5rine-lab-menu" class="me5rine-lab-menu-vertical">
            <?php foreach ($menu_structure as $module_key => $module_data): 
                $has_submenu = !empty($module_data['items']);
                $is_open = admin_lab_is_menu_module_open($module_data);
                $is_active = admin_lab_is_menu_item_active($module_data['url'] ?? '');
                
                if ($has_submenu):
                    $parent_class = $is_open ? 'has-sub open' : 'has-sub';
                    // Pour les items avec sous-menu : active si le parent est actif OU si un enfant est actif (donc si $is_open est true)
                    $link_class = $is_open ? 'active' : '';
                ?>
                <div class="<?php echo esc_attr($parent_class); ?>">
                    <a href="<?php echo esc_url($module_data['url']); ?>" class="<?php echo esc_attr($link_class); ?>">
                        <?php if (!empty($module_data['icon'])): ?>
                            <span class="me5rine-lab-menu-icon"><i class="fa <?php echo esc_attr($module_data['icon']); ?>"></i></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($module_data['label']); ?></span>
                    </a>
                    <div class="submenu">
                        <?php foreach ($module_data['items'] as $item): 
                            $item_is_active = admin_lab_is_menu_item_active($item['url'] ?? '');
                            $item_class = $item_is_active ? 'active' : '';
                        ?>
                        <a href="<?php echo esc_url($item['url']); ?>" class="<?php echo esc_attr($item_class); ?>">
                            <?php echo esc_html($item['label']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: 
                    $link_class = $is_active ? 'active' : '';
                ?>
                <a href="<?php echo esc_url($module_data['url']); ?>" class="<?php echo esc_attr($link_class); ?>">
                    <?php if (!empty($module_data['icon'])): ?>
                        <span class="me5rine-lab-menu-icon"><i class="fa <?php echo esc_attr($module_data['icon']); ?>"></i></span>
                    <?php endif; ?>
                    <span><?php echo esc_html($module_data['label']); ?></span>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('partner_menu', 'admin_lab_render_partner_menu_shortcode');