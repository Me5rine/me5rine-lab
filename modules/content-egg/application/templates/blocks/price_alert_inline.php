<?php
defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;

if (!TemplateHelper::isPriceAlertAllowed($item['unique_id'], $item['module_id']))
    return;

$desired_price = '';
$privacy_url = TemplateHelper::getPrivacyUrl();

if (!$params['btn_variant'])
    $params['btn_variant'] = 'warning';
if (!$params['btn_text'])
    $params['btn_text'] = TemplateHelper::__('SET ALERT');

?>
<div class="cegg-price-alert-card cegg-card<?php TemplateHelper::border($params, 'border'); ?>">
    <div class="card-header text-bg-warning">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6" />
        </svg>
        <span class="ms-1"><?php TemplateHelper::esc_html_e('Create Your Free Price Drop Alert!'); ?></span>
    </div>
    <div class="card-body">
        <div class="card-subtitle fs-6 mb-3 text-body-secondary">
            <?php if ($title) : ?>
                <?php echo \esc_html($title); ?>
            <?php else : ?>
                <?php //TemplateHelper::esc_html_e('Wait For A Price Drop');
                ?>
            <?php endif; ?>
        </div>
        <form class="cegg-price-alert-form">

            <div class="row my-2">
                <div class="col-12 col-md-6">
                    <input type="hidden" name="module_id" value="<?php echo \esc_attr($item['module_id']); ?>">
                    <input type="hidden" name="unique_id" value="<?php echo \esc_attr($item['unique_id']); ?>">
                    <input type="hidden" name="post_id" value="<?php echo \esc_attr(get_the_ID()); ?>">
                    <input value="<?php echo \esc_attr(TemplateHelper::getCurrentUserEmail()); ?>" type="email" class="form-control" name="email" id="cegg-email-<?php echo \esc_attr($item['unique_id']); ?>" placeholder="<?php TemplateHelper::esc_html_e('Your Email'); ?>" required>
                </div>
                <div class="col col-md-6">
                    <div class="input-group">

                        <?php $cur_position = TemplateHelper::getCurrencyPos($item['currencyCode']); ?>
                        <?php if ($cur_position == 'left' || $cur_position == 'left_space') : ?>
                            <span class="input-group-text"><?php echo esc_html(TemplateHelper::getCurrencySymbol($item['currencyCode'])); ?></span>
                        <?php endif; ?>
                        <input value="<?php echo esc_attr($desired_price); ?>" type="number" class="form-control" name="price" id="cegg-price-<?php echo \esc_attr($item['unique_id']); ?>" placeholder="<?php TemplateHelper::esc_html_e('Desired Price'); ?>" step="any" required>
                        <?php if ($cur_position == 'right' || $cur_position == 'right_space') : ?>
                            <span class="input-group-text"><?php echo esc_html(TemplateHelper::getCurrencySymbol($item['currencyCode'])); ?></span>
                        <?php endif; ?>

                        <button class="btn btn-warning" type="submit"><?php TemplateHelper::esc_html_e('SET ALERT'); ?></button>

                    </div>

                </div>

            </div>
            <?php if ($privacy_url) : ?>
                <div class="mt-3 mb-1 text-body-secondary">
                    <label class="price-alert-agree-label">
                        <input type="checkbox" name="accepted" value="1" id="cegg_alert_accepted" required />
                        <?php $privacy_link = '<a target="_blank" href="' . \esc_attr($privacy_url) . '">' . TemplateHelper::__('Privacy Policy') . '</a>'; ?>
                        <?php echo wp_kses_post(sprintf(TemplateHelper::__('I agree to the %s.'), $privacy_link)); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div class="spinner-border spinner-border-sm text-warning cegg-price-loading-image" role="status" style="display: none;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="cegg-price-alert-result-succcess text-success" style="display: none;"></div>
            <div class="cegg-price-alert-result-error text-danger" style="display: none;"></div>
        </form>
    </div>
    <div class="card-footer<?php TemplateHelper::border($params, 'border-top'); ?>">
        <small class="text-body-secondary"><?php TemplateHelper::esc_html_e('You will receive a notification when the price drops.'); ?></small>
    </div>

</div>

<?php
if (!function_exists('cegg_price_alert_js')):

    function cegg_price_alert_js()
    {
?>
        <script>
            "use strict";
            document.addEventListener('DOMContentLoaded', function() {

                var forms = document.querySelectorAll('.cegg-price-alert-form');

                var ajaxurl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce = <?php echo json_encode(wp_create_nonce('cegg-price-alert')); ?>;

                forms.forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        var wrap = form;

                        var formData = new FormData(form);
                        formData.append('nonce', nonce);
                        var data = new URLSearchParams(formData).toString();

                        wrap.querySelector('.cegg-price-loading-image').style.display = 'block';
                        wrap.querySelector('.cegg-price-alert-result-error').style.display = 'none';
                        form.querySelectorAll('input, button').forEach(function(el) {
                            el.disabled = true;
                        });

                        var url = ajaxurl + '?action=start_tracking';

                        fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: data,
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(result) {
                                if (result.status == 'success') {
                                    var successElem = wrap.querySelector('.cegg-price-alert-result-succcess');
                                    successElem.style.display = 'block';
                                    successElem.innerHTML = result.message;
                                    wrap.querySelector('.cegg-price-loading-image').style.display = 'none';
                                } else {
                                    form.querySelectorAll('input, button').forEach(function(el) {
                                        el.disabled = false;
                                    });
                                    var errorElem = wrap.querySelector('.cegg-price-alert-result-error');
                                    errorElem.style.display = 'block';
                                    errorElem.innerHTML = result.message;
                                    wrap.querySelector('.cegg-price-loading-image').style.display = 'none';
                                }
                            })
                            .catch(function(error) {
                                form.querySelectorAll('input, button').forEach(function(el) {
                                    el.disabled = false;
                                });
                                wrap.querySelector('.cegg-price-loading-image').style.display = 'none';
                            });
                    });
                });
            });
        </script>
<?php
    }
    cegg_price_alert_js();
endif;

?>