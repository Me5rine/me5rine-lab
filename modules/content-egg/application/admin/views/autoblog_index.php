<?php defined('\ABSPATH') || exit; ?>
<div id="cegg_waiting_products" style="display:none; text-align: center;">
    <h2><?php esc_html_e('Working... Please wait...', 'content-egg'); ?></h2>
    <p>
        <img src="<?php echo esc_url_raw(\ContentEgg\PLUGIN_RES); ?>/img/egg_waiting.gif" />
    </p>
</div>
<script type="text/javascript">
    "use strict";
    var $j = jQuery.noConflict();
    $j(document).ready(function() {
        $j(document).on('click', '.run_avtoblogging', function() {
            $j.blockUI({
                message: $j('#cegg_waiting_products')
            });
        });
    });
</script>

<?php
$message = '';
if ($table->current_action() == 'delete' && !empty($_GET['id']))
{
    if (is_array($_GET['id']))
        $count = count($_GET['id']);
    else
        $count = 1;
    $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Deleted tasks for autoblogging: ', 'content-egg') . ' %d', $count) . '</p></div>';
}
if ($table->current_action() == 'run')
    $message = '<div class="updated below-h2" id="message"><p>' . __('Autoblogging finished tasks', 'content-egg') . '</p></div>';
?>

<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>

    <div class="wrap">
        <div class="cegg5-container">
            <h2 class="h4 mb-2 mt-4" style="height: 30px;">
                <?php esc_html_e('Autoblogging', 'content-egg'); ?>
                <a class="add-new-h2" href="<?php echo esc_url_raw(get_admin_url(get_current_blog_id(), 'admin.php?page=content-egg-autoblog-edit')); ?>"><?php esc_html_e('Add autoblogging', 'content-egg'); ?></a>
            </h2>

            <div class="alert alert-warning mt-3" role="alert">
                <?php
                printf(
                    esc_html__('While the Autoblogging feature is now deprecated, it will remain available in the plugin for as long as needed. We recommend using the new %1$sAuto Import%2$s feature.', 'content-egg'),
                    '<a href="' . esc_url(admin_url('admin.php?page=content-egg-product-import')) . '">',
                    '</a>'
                );
                ?>
            </div>

            <?php echo \wp_kses_post($message); ?>

            <div id="poststuff">
                <p>
                </p>
            </div>

            <form id="eggs-table" method="GET">
                <input type="hidden" name="page" value="content-egg-autoblog" />
                <?php $table->display() ?>
            </form>
        </div>

        <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>