<?php
/**
 * Helpers Affiliate Links – lecture/écriture tables (catégories, produits)
 */

if (!defined('ABSPATH')) {
    exit;
}

function admin_lab_affiliate_links_get_categories($order_by = 'sort_order') {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_categories');
    $order = $order_by === 'name' ? 'name ASC' : 'sort_order ASC, name ASC';
    return $wpdb->get_results("SELECT * FROM {$table} ORDER BY {$order}", ARRAY_A);
}

function admin_lab_affiliate_links_get_category($id_or_slug) {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_categories');
    if (is_numeric($id_or_slug)) {
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $id_or_slug), ARRAY_A);
    }
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE slug = %s", sanitize_title($id_or_slug)), ARRAY_A);
}

function admin_lab_affiliate_links_get_products_by_category($category_id_or_slug, $limit = 0) {
    global $wpdb;
    $cat = admin_lab_affiliate_links_get_category($category_id_or_slug);
    if (!$cat) {
        return [];
    }
    $table = admin_lab_getTable('affiliate_products');
    $sql = "SELECT * FROM {$table} WHERE category_id = %d ORDER BY sort_order ASC, title ASC";
    if ($limit > 0) {
        $sql .= $wpdb->prepare(" LIMIT %d", $limit);
    }
    return $wpdb->get_results($wpdb->prepare($sql, (int) $cat['id']), ARRAY_A);
}

function admin_lab_affiliate_links_get_product($id) {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_products');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $id), ARRAY_A);
}

function admin_lab_affiliate_links_save_category($data) {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_categories');
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $slug = isset($data['slug']) ? sanitize_title($data['slug']) : sanitize_title($name);
    $description = isset($data['description']) ? wp_kses_post($data['description']) : '';
    $sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
    if (empty($name)) {
        return new WP_Error('missing_name', __('Name is required.', 'me5rine-lab'));
    }
    if (empty($slug)) {
        $slug = sanitize_title($name);
    }
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s AND id != %d", $slug, $id));
    if ($exists) {
        return new WP_Error('duplicate_slug', __('This slug is already in use.', 'me5rine-lab'));
    }
    if ($id) {
        $wpdb->update($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'sort_order' => $sort_order,
        ], ['id' => $id]);
        return $id;
    }
    $wpdb->insert($table, [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'sort_order' => $sort_order,
    ]);
    return $wpdb->insert_id ? (int) $wpdb->insert_id : new WP_Error('insert_failed', __('Could not create category.', 'me5rine-lab'));
}

function admin_lab_affiliate_links_delete_category($id) {
    global $wpdb;
    $cat_table = admin_lab_getTable('affiliate_categories');
    $prod_table = admin_lab_getTable('affiliate_products');
    $id = (int) $id;
    $wpdb->delete($prod_table, ['category_id' => $id]);
    $wpdb->delete($cat_table, ['id' => $id]);
    return true;
}

function admin_lab_affiliate_links_save_product($data) {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_products');
    $id = isset($data['id']) ? (int) $data['id'] : 0;
    $category_id = isset($data['category_id']) ? (int) $data['category_id'] : 0;
    $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
    $url = isset($data['url']) ? esc_url_raw($data['url']) : '';
    $img_url = isset($data['img_url']) ? esc_url_raw($data['img_url']) : '';
    $price = isset($data['price']) ? sanitize_text_field($data['price']) : '';
    $price_old = isset($data['price_old']) ? sanitize_text_field($data['price_old']) : '';
    $description = isset($data['description']) ? wp_kses_post($data['description']) : '';
    $sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
    if (empty($title) || empty($url) || !$category_id) {
        return new WP_Error('missing_fields', __('Title, URL and category are required.', 'me5rine-lab'));
    }
    $row = [
        'category_id' => $category_id,
        'title' => $title,
        'url' => $url,
        'img_url' => $img_url,
        'price' => $price,
        'price_old' => $price_old,
        'description' => $description,
        'sort_order' => $sort_order,
    ];
    if ($id) {
        $wpdb->update($table, $row, ['id' => $id]);
        return $id;
    }
    $wpdb->insert($table, $row);
    return $wpdb->insert_id ? (int) $wpdb->insert_id : new WP_Error('insert_failed', __('Could not create product.', 'me5rine-lab'));
}

function admin_lab_affiliate_links_delete_product($id) {
    global $wpdb;
    $table = admin_lab_getTable('affiliate_products');
    $wpdb->delete($table, ['id' => (int) $id]);
    return true;
}
