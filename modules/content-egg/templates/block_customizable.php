<?php

/*
 * Name: Customizable (use with "show" parameter)
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

?>

<?php foreach ($data as $module_id => $items) : ?>
    <?php foreach ($items as $item) : ?>
        <?php

        switch ($params['show'])
        {
            case 'title':
                echo esc_html(TemplateHelper::truncate($item['title']));
                break;
            case 'img':
                TemplateHelper::displayImage($item, 190, 170, array('class' => 'object-fit-scale img-thumbnail'));
                break;
            case 'price':
                TemplateHelper::price($item, $params);
                break;
            case 'priceold':
                TemplateHelper::oldPrice($item, $params);
                break;
            case 'currencycode':
                TemplateHelper::currencyCode($item, $params);
                break;
            case 'button':
                echo '<div class="cegg5-container">';
                TemplateHelper::button($item, $params);
                echo '</div>';
                break;
            case 'stock_status':
                TemplateHelper::stockStatus($item, $params);
                break;
            case 'description':
                TemplateHelper::description($item);
                break;
            case 'url':
                echo esc_url_raw($item['url']);
                break;
            case 'last_update':
                echo esc_html(TemplateHelper::getLastUpdateFormatted($item['module_id']));
                break;
            case 'img+url':
                TemplateHelper::openATag($item);
                TemplateHelper::displayImage($item, 190, 170, array('class' => 'object-fit-scale img-thumbnail'));
                TemplateHelper::closeATag();
                break;
            case 'title+url':
                TemplateHelper::openATag($item);
                echo esc_html(TemplateHelper::truncate($item['title']));
                TemplateHelper::closeATag();
                break;
            case 'merchant':
                TemplateHelper::merchant($item);
                break;
            default:
                break;
        }
        ?>

    <?php endforeach; ?>
<?php endforeach; ?>
