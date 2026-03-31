<?php
/**
 * Shortcode [me5rine_affiliate_links] – affichage depuis les tables
 */

if (!defined('ABSPATH')) {
    exit;
}

function admin_lab_affiliate_links_register_shortcode() {
    add_shortcode('me5rine_affiliate_links', 'admin_lab_affiliate_links_shortcode_output');
}

function admin_lab_affiliate_links_shortcode_output($atts) {
    $atts = shortcode_atts([
        'category'    => '',
        'category_id' => '',
        'template'    => 'list',
        'show_price'  => '1',
        'show_image'  => '0',
        'limit'       => 50,
    ], $atts, 'me5rine_affiliate_links');

    $category = $atts['category_id'] ? $atts['category_id'] : $atts['category'];
    if (empty($category)) {
        return '';
    }

    $limit = (int) $atts['limit'];
    $items = admin_lab_affiliate_links_get_products_by_category($category, $limit > 0 ? $limit : 0);
    if (empty($items)) {
        return '';
    }

    wp_enqueue_style('admin-lab-affiliate-links');

    $show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
    $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
    $template = in_array($atts['template'], ['list', 'grid'], true) ? $atts['template'] : 'list';

    ob_start();
    $tpl = ME5RINE_LAB_AFFILIATE_LINKS_PATH . 'templates/affiliate-links-' . $template . '.php';
    if (file_exists($tpl)) {
        include $tpl;
    }
    return ob_get_clean();
}
