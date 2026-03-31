<?php
/**
 * Admin Affiliate Links – catégories et produits (tables)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'admin_lab_affiliate_links_admin_menu', 20);

function admin_lab_affiliate_links_admin_menu() {
    add_submenu_page(
        'me5rine-lab',
        __('Affiliate Links', 'me5rine-lab'),
        __('Affiliate Links', 'me5rine-lab'),
        'manage_options',
        'admin-lab-affiliate-links',
        'admin_lab_affiliate_links_page_list'
    );
    add_submenu_page(
        '',
        __('Edit category', 'me5rine-lab'),
        __('Edit category', 'me5rine-lab'),
        'manage_options',
        'admin-lab-affiliate-links-edit-category',
        'admin_lab_affiliate_links_page_edit_category'
    );
    add_submenu_page(
        '',
        __('Edit product', 'me5rine-lab'),
        __('Edit product', 'me5rine-lab'),
        'manage_options',
        'admin-lab-affiliate-links-edit-product',
        'admin_lab_affiliate_links_page_edit_product'
    );
}

function admin_lab_affiliate_links_page_list() {
    $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
    if ($action === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'affiliate-links-delete-cat-' . (int) $_GET['id'])) {
            admin_lab_affiliate_links_delete_category((int) $_GET['id']);
            wp_redirect(admin_url('admin.php?page=admin-lab-affiliate-links&deleted=1'));
            exit;
        }
    }

    $categories = admin_lab_affiliate_links_get_categories();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Affiliate Links', 'me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-affiliate-links-edit-category')); ?>" class="page-title-action"><?php esc_html_e('Add category', 'me5rine-lab'); ?></a>
        <hr class="wp-header-end">

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Category deleted.', 'me5rine-lab'); ?></p></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Name', 'me5rine-lab'); ?></th>
                    <th scope="col"><?php esc_html_e('Slug', 'me5rine-lab'); ?></th>
                    <th scope="col"><?php esc_html_e('Products', 'me5rine-lab'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'me5rine-lab'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No categories yet.', 'me5rine-lab'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-affiliate-links-edit-category')); ?>"><?php esc_html_e('Add one', 'me5rine-lab'); ?></a>.</td></tr>
                <?php else :
                    global $wpdb;
                    $prod_table = admin_lab_getTable('affiliate_products');
                    foreach ($categories as $cat) :
                        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prod_table} WHERE category_id = %d", (int) $cat['id']));
                        $edit_url = admin_url('admin.php?page=admin-lab-affiliate-links-edit-category&id=' . (int) $cat['id']);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=admin-lab-affiliate-links&action=delete&id=' . (int) $cat['id']), 'affiliate-links-delete-cat-' . (int) $cat['id']);
                ?>
                    <tr>
                        <td><strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($cat['name']); ?></a></strong></td>
                        <td><code><?php echo esc_html($cat['slug']); ?></code></td>
                        <td><?php echo (int) $count; ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'me5rine-lab'); ?></a>
                            | <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('Delete this category and its products?', 'me5rine-lab')); ?>');"><?php esc_html_e('Delete', 'me5rine-lab'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <p class="description" style="margin-top:1em;"><?php esc_html_e('Shortcode:', 'me5rine-lab'); ?> <code>[me5rine_affiliate_links category="slug" template="list|grid" show_price="1" show_image="0" limit="50"]</code></p>
        <?php if (class_exists('\ContentEgg\application\Plugin', false)) : ?>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=content-egg')); ?>"><?php esc_html_e('Content Egg settings (modules, API keys)', 'me5rine-lab'); ?></a></p>
        <?php endif; ?>
    </div>
    <?php
}

function admin_lab_affiliate_links_page_edit_category() {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $category = $id ? admin_lab_affiliate_links_get_category($id) : null;

    // Fetch products by keyword (Content Egg)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['affiliate_links_fetch']) && $id) {
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'affiliate-links-fetch-products')) {
            $module_id = isset($_POST['fetch_module']) ? sanitize_text_field(wp_unslash($_POST['fetch_module'])) : '';
            $keyword = isset($_POST['fetch_keyword']) ? sanitize_text_field(wp_unslash($_POST['fetch_keyword'])) : '';
            if (function_exists('admin_lab_affiliate_links_fetch_by_keyword')) {
                $result = admin_lab_affiliate_links_fetch_by_keyword($id, $module_id, $keyword);
                $msg = $result['success']
                    ? sprintf(__('Products fetched: %d added.', 'me5rine-lab'), $result['count'])
                    : ($result['error'] ?: __('Fetch failed.', 'me5rine-lab'));
                $url = add_query_arg($result['success'] ? 'fetched' : 'fetch_error', $result['success'] ? $result['count'] : urlencode($result['error']), admin_url('admin.php?page=admin-lab-affiliate-links-edit-category&id=' . $id));
                wp_safe_redirect($url);
                exit;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'affiliate-links-save-category')) {
            $data = [
                'id' => $id,
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'slug' => isset($_POST['slug']) ? $_POST['slug'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            ];
            $result = admin_lab_affiliate_links_save_category($data);
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                $id = $result;
                $category = admin_lab_affiliate_links_get_category($id);
                echo '<div class="notice notice-success"><p>' . esc_html__('Category saved.', 'me5rine-lab') . '</p></div>';
            }
        }
    }

    if ($id && !$category) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Category not found.', 'me5rine-lab') . '</p></div>';
        return;
    }

    $name = $category ? $category['name'] : '';
    $slug = $category ? $category['slug'] : '';
    $description = $category ? $category['description'] : '';
    $sort_order = $category ? (int) $category['sort_order'] : 0;
    $products = $id ? admin_lab_affiliate_links_get_products_by_category($id, 0) : [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && isset($_POST['product_id'])) {
        $pid = (int) $_POST['product_id'];
        if (wp_verify_nonce($_POST['_wpnonce'], 'affiliate-links-delete-product-' . $pid)) {
            admin_lab_affiliate_links_delete_product($pid);
            wp_redirect(admin_url('admin.php?page=admin-lab-affiliate-links-edit-category&id=' . $id . '&deleted=1'));
            exit;
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $id ? esc_html__('Edit category', 'me5rine-lab') : esc_html__('Add category', 'me5rine-lab'); ?></h1>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-affiliate-links')); ?>">&larr; <?php esc_html_e('Back to categories', 'me5rine-lab'); ?></a></p>
        <?php if (isset($_GET['fetched'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('Products fetched: %s added to this category.', 'me5rine-lab'), (string) $_GET['fetched'])); ?></p></div>
        <?php endif;
        if (isset($_GET['fetch_error'])) : ?>
            <div class="notice notice-warning is-dismissible"><p><?php echo esc_html(sprintf(__('Fetch: %s', 'me5rine-lab'), urldecode((string) $_GET['fetch_error']))); ?></p></div>
        <?php endif; ?>
        <form method="post" style="max-width:600px;">
            <?php wp_nonce_field('affiliate-links-save-category'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php esc_html_e('Name', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" id="name" name="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="slug"><?php esc_html_e('Slug', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="description"><?php esc_html_e('Description', 'me5rine-lab'); ?></label></th>
                    <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="sort_order"><?php esc_html_e('Order', 'me5rine-lab'); ?></label></th>
                    <td><input type="number" id="sort_order" name="sort_order" value="<?php echo (int) $sort_order; ?>" class="small-text" /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="<?php esc_attr_e('Save category', 'me5rine-lab'); ?>" /></p>
        </form>

        <?php if ($id) :
            $ce_modules = function_exists('admin_lab_affiliate_links_get_ce_parser_modules') ? admin_lab_affiliate_links_get_ce_parser_modules() : [];
            if (!empty($ce_modules)) : ?>
            <h2><?php esc_html_e('Fetch products by keyword (Content Egg)', 'me5rine-lab'); ?></h2>
            <p class="description"><?php esc_html_e('Search with a module (e.g. Offer, Amazon) and add results to this category. Configure modules and API keys in Content Egg settings.', 'me5rine-lab'); ?></p>
            <form method="post" style="margin-bottom:1.5em;">
                <?php wp_nonce_field('affiliate-links-fetch-products'); ?>
                <input type="hidden" name="affiliate_links_fetch" value="1" />
                <select name="fetch_module" required>
                    <option value=""><?php esc_html_e('Select module', 'me5rine-lab'); ?></option>
                    <?php foreach ($ce_modules as $mid => $mname) : ?>
                        <option value="<?php echo esc_attr($mid); ?>"><?php echo esc_html($mname); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="fetch_keyword" placeholder="<?php esc_attr_e('Keyword or product URL', 'me5rine-lab'); ?>" class="regular-text" required />
                <button type="submit" class="button button-secondary"><?php esc_html_e('Fetch and add to category', 'me5rine-lab'); ?></button>
            </form>
            <?php endif; ?>
            <h2><?php esc_html_e('Products in this category', 'me5rine-lab'); ?></h2>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-affiliate-links-edit-product&category_id=' . $id)); ?>" class="button"><?php esc_html_e('Add product manually', 'me5rine-lab'); ?></a></p>
            <?php if (empty($products)) : ?>
                <p><?php esc_html_e('No products yet.', 'me5rine-lab'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('URL', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Price', 'me5rine-lab'); ?></th>
                            <th><?php esc_html_e('Actions', 'me5rine-lab'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($p['title']); ?></strong></td>
                                <td><a href="<?php echo esc_url($p['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html(wp_trim_words($p['url'], 8)); ?></a></td>
                                <td><?php echo esc_html($p['price'] ?: '—'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-affiliate-links-edit-product&id=' . (int) $p['id'])); ?>"><?php esc_html_e('Edit', 'me5rine-lab'); ?></a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Delete this product?', 'me5rine-lab')); ?>');">
                                        <?php wp_nonce_field('affiliate-links-delete-product-' . (int) $p['id']); ?>
                                        <input type="hidden" name="delete_product" value="1" />
                                        <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>" />
                                        <button type="submit" class="button-link submitdelete"><?php esc_html_e('Delete', 'me5rine-lab'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function admin_lab_affiliate_links_page_edit_product() {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
    $product = $id ? admin_lab_affiliate_links_get_product($id) : null;
    if ($product) {
        $category_id = (int) $product['category_id'];
    }
    if (!$category_id && !$product) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Missing category.', 'me5rine-lab') . '</p></div>';
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (wp_verify_nonce($_POST['_wpnonce'], 'affiliate-links-save-product')) {
            $data = [
                'id' => $id,
                'category_id' => isset($_POST['category_id']) ? (int) $_POST['category_id'] : $category_id,
                'title' => isset($_POST['title']) ? $_POST['title'] : '',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'img_url' => isset($_POST['img_url']) ? $_POST['img_url'] : '',
                'price' => isset($_POST['price']) ? $_POST['price'] : '',
                'price_old' => isset($_POST['price_old']) ? $_POST['price_old'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            ];
            $result = admin_lab_affiliate_links_save_product($data);
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                wp_redirect(admin_url('admin.php?page=admin-lab-affiliate-links-edit-category&id=' . (int) $data['category_id'] . '&saved=1'));
                exit;
            }
        }
    }

    $categories = admin_lab_affiliate_links_get_categories();
    $cat = admin_lab_affiliate_links_get_category($category_id);
    $back_url = $category_id ? admin_url('admin.php?page=admin-lab-affiliate-links-edit-category&id=' . $category_id) : admin_url('admin.php?page=admin-lab-affiliate-links');
    $title = isset($_POST['title']) ? $_POST['title'] : ($product ? $product['title'] : '');
    $url = isset($_POST['url']) ? $_POST['url'] : ($product ? $product['url'] : '');
    $img_url = isset($_POST['img_url']) ? $_POST['img_url'] : ($product ? $product['img_url'] : '');
    $price = isset($_POST['price']) ? $_POST['price'] : ($product ? $product['price'] : '');
    $price_old = isset($_POST['price_old']) ? $_POST['price_old'] : ($product ? $product['price_old'] : '');
    $description = isset($_POST['description']) ? $_POST['description'] : ($product ? $product['description'] : '');
    $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : ($product ? (int) $product['sort_order'] : 0);
    ?>
    <div class="wrap">
        <h1><?php echo $id ? esc_html__('Edit product', 'me5rine-lab') : esc_html__('Add product', 'me5rine-lab'); ?></h1>
        <p><a href="<?php echo esc_url($back_url); ?>">&larr; <?php echo $cat ? esc_html(sprintf(__('Back to %s', 'me5rine-lab'), $cat['name'])) : esc_html__('Back to categories', 'me5rine-lab'); ?></a></p>

        <form method="post" style="max-width:600px;">
            <?php wp_nonce_field('affiliate-links-save-product'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="category_id"><?php esc_html_e('Category', 'me5rine-lab'); ?></label></th>
                    <td>
                        <select id="category_id" name="category_id" class="regular-text" required>
                            <?php foreach ($categories as $c) : ?>
                                <option value="<?php echo (int) $c['id']; ?>" <?php selected($category_id, (int) $c['id']); ?>><?php echo esc_html($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="title"><?php esc_html_e('Title', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="url"><?php esc_html_e('Affiliate URL', 'me5rine-lab'); ?></label></th>
                    <td><input type="url" id="url" name="url" value="<?php echo esc_attr($url); ?>" class="large-text" required /></td>
                </tr>
                <tr>
                    <th><label for="img_url"><?php esc_html_e('Image URL', 'me5rine-lab'); ?></label></th>
                    <td><input type="url" id="img_url" name="img_url" value="<?php echo esc_attr($img_url); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label for="price"><?php esc_html_e('Price', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="price_old"><?php esc_html_e('Old price', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" id="price_old" name="price_old" value="<?php echo esc_attr($price_old); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="description"><?php esc_html_e('Description', 'me5rine-lab'); ?></label></th>
                    <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="sort_order"><?php esc_html_e('Order', 'me5rine-lab'); ?></label></th>
                    <td><input type="number" id="sort_order" name="sort_order" value="<?php echo (int) $sort_order; ?>" class="small-text" /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="<?php esc_attr_e('Save product', 'me5rine-lab'); ?>" /></p>
        </form>
    </div>
    <?php
}
