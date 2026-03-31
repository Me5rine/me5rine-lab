<?php
/**
 * Récupération produits par mot-clé via les parsers Content Egg, enregistrement dans nos tables.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retourne les modules CE actifs qui sont des parsers (product/content).
 *
 * @return array [ 'module_id' => 'Module Name', ... ]
 */
function admin_lab_affiliate_links_get_ce_parser_modules() {
    if (!class_exists('\ContentEgg\application\components\ModuleManager', false)) {
        return [];
    }
    $list = [];
    $modules = \ContentEgg\application\components\ModuleManager::getInstance()->getModules(true);
    foreach ($modules as $module) {
        if (!$module->isParser()) {
            continue;
        }
        $list[$module->getId()] = $module->getName();
    }
    return $list;
}

/**
 * Lance une recherche par mot-clé avec un module CE et enregistre les résultats dans notre table pour une catégorie.
 *
 * @param int    $category_id ID de la catégorie affiliée
 * @param string $module_id   ID du module CE (Offer, Amazon, etc.)
 * @param string $keyword     Mot-clé de recherche
 * @return array { 'success' => bool, 'count' => int, 'error' => string }
 */
function admin_lab_affiliate_links_fetch_by_keyword($category_id, $module_id, $keyword) {
    $category_id = (int) $category_id;
    $keyword = \ContentEgg\application\components\ContentManager::sanitizeKeyword($keyword);
    if (!$category_id || !$keyword) {
        return ['success' => false, 'count' => 0, 'error' => __('Category and keyword are required.', 'me5rine-lab')];
    }
    if (!class_exists('\ContentEgg\application\components\ModuleManager', false)) {
        return ['success' => false, 'count' => 0, 'error' => __('Content Egg engine is not available.', 'me5rine-lab')];
    }
    $cat = admin_lab_affiliate_links_get_category($category_id);
    if (!$cat) {
        return ['success' => false, 'count' => 0, 'error' => __('Category not found.', 'me5rine-lab')];
    }
    $mm = \ContentEgg\application\components\ModuleManager::getInstance();
    if (!$mm->isModuleActive($module_id)) {
        return ['success' => false, 'count' => 0, 'error' => __('Module is not active.', 'me5rine-lab')];
    }
    try {
        $module = $mm->factory($module_id);
    } catch (\Exception $e) {
        return ['success' => false, 'count' => 0, 'error' => $e->getMessage()];
    }
    if (!$module->isParser()) {
        return ['success' => false, 'count' => 0, 'error' => __('Module is not a parser.', 'me5rine-lab')];
    }
    try {
        $data = $module->doMultipleRequests($keyword, [], true);
    } catch (\Exception $e) {
        return ['success' => false, 'count' => 0, 'error' => $e->getMessage()];
    }
    if (empty($data)) {
        return ['success' => true, 'count' => 0, 'error' => ''];
    }
    $data = array_map([\ContentEgg\application\components\ContentManager::class, 'object2Array'], $data);
    $saved = 0;
    foreach ($data as $item) {
        $row = admin_lab_affiliate_links_ce_item_to_product($item, $category_id);
        if (!$row) {
            continue;
        }
        $result = admin_lab_affiliate_links_save_product($row);
        if (!is_wp_error($result)) {
            $saved++;
        }
    }
    return ['success' => true, 'count' => $saved, 'error' => ''];
}

/**
 * Convertit un item CE (array) en ligne pour notre table.
 *
 * @param array $item        Données CE (title, url, img, price, priceOld, description, etc.)
 * @param int   $category_id ID catégorie
 * @return array|null Données pour save_product ou null si pas d'URL
 */
function admin_lab_affiliate_links_ce_item_to_product($item, $category_id) {
    $url = isset($item['url']) ? esc_url_raw($item['url']) : '';
    if (empty($url)) {
        return null;
    }
    $title = isset($item['title']) ? sanitize_text_field($item['title']) : '';
    if (empty($title)) {
        $title = wp_trim_words($url, 5);
    }
    $img = isset($item['img']) ? esc_url_raw($item['img']) : '';
    $price = isset($item['price']) ? sanitize_text_field((string) $item['price']) : '';
    $price_old = isset($item['priceOld']) ? sanitize_text_field((string) $item['priceOld']) : '';
    $description = isset($item['description']) ? wp_kses_post($item['description']) : '';
    return [
        'id' => 0,
        'category_id' => (int) $category_id,
        'title' => $title,
        'url' => $url,
        'img_url' => $img,
        'price' => $price,
        'price_old' => $price_old,
        'description' => $description,
        'sort_order' => 0,
    ];
}
