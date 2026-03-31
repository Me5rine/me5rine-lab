<?php defined('\ABSPATH') || exit; ?>
<div id="cegg_waiting_products" style="display:none; text-align: center;">
    <h2><?php esc_html_e('Scanning... Please wait...', 'content-egg'); ?></h2>
    <p>
        <img src="<?php echo esc_url_raw(\ContentEgg\PLUGIN_RES); ?>/img/egg_waiting.gif" ; />
    </p>
</div>
<script type="text/javascript">
    "use strict";
    var $j = jQuery.noConflict();
    $j(document).ready(function() {
        $j(document).on('click', '#btn_scan_products', function() {
            $j.blockUI({
                message: $j('#cegg_waiting_products')
            });
        });
    });
</script>

<?php
$message = '';
?>

<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>

    <div class="wrap">

        <div class="cegg5-container">
            <h1 class="wp-heading-inline h3">
                <?php esc_html_e('Products', 'content-egg'); ?>
            </h1>
            <a
                id="btn_scan_products"
                href="<?php echo esc_url(admin_url('admin.php?page=content-egg-product&action=scan')); ?>"
                class="page-title-action">
                <?php esc_html_e('Scan Products', 'content-egg'); ?>
            </a>

            <p class="description">
                <?php
                /* translators: %s: date/time of last scan */
                printf(
                    esc_html__('Last scanned: %s', 'content-egg'),
                    esc_html($last_scaned_str)
                );
                ?>
            </p>

            <p>
                <?php esc_html_e(
                    'This page displays all Content Egg products you’ve added across your posts.',
                    'content-egg'
                ); ?>
            </p>
        </div>
        <?php echo wp_kses_post($message); ?>

        <form id="cegg-products-table" method="GET">
            <input type="hidden" name="page" value="content-egg-product" />
            <?php if (isset($_REQUEST['stock_status'])) : ?>
                <input type="hidden" name="stock_status" value="<?php echo \esc_attr(\sanitize_text_field(wp_unslash($_REQUEST['stock_status']))); ?>" />
            <?php endif; ?>
            <?php $table->views(); ?>
            <?php $table->search_box(__('Search products', 'content-egg'), 'key'); ?>
            <?php $table->display(); ?>
        </form>
    </div>

    <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>

<style>
    table.wp-list-table .column-clicks_30d {
        text-align: left !important;
        width: 8ch;
    }

    table.wp-list-table .column-clicks_total {
        text-align: left !important;
        width: 8ch;
    }
</style>