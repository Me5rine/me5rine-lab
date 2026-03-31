<?php
defined('\ABSPATH') || exit;

/*
  Name: Add all to cart button
 */

use ContentEgg\application\helpers\TemplateHelper;

if (!$params['btn_text'])
	$params['btn_text'] = __('ADD ALL TO CART', 'content-egg-tpl');

$url = '';
$item = reset($items);
$locales = TemplateHelper::findAmazonLocales($items);

?>

<?php foreach ($locales as $locale): ?>
	<div class="container px-0 mb-3 mt-1" <?php $this->colorMode(); ?>>
		<?php
		$url = TemplateHelper::generateAddAllToCartUrl($items, $locale);
		$item['url'] = $url;
		?>
		<?php TemplateHelper::button($item, $params); ?>

	</div>
<?php endforeach; ?>