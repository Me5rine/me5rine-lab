<?php

namespace ContentEgg\application\components;

use ContentEgg\application\admin\GeneralConfig;;

defined('\ABSPATH') || exit;

/**
 * Pattern class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class Pattern
{
	private static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null)
			self::$instance = new self;

		return self::$instance;
	}

	private function __construct()
	{
	}

	public static function initAction()
	{
		$post_types = array_values(GeneralConfig::getInstance()->option('post_types'));

		\register_block_pattern_category(
			'cegg/contenteggtpls',
			array(
				'label'       => 'Content Egg',
				'description' => __('Content Egg templates', 'content-egg')
			),
		);

		\register_block_pattern('cegg/grid', array(
			'title'      => __('Grid', 'content-egg'),
			'categories' => array('cegg/contenteggtpls'),
			'source'     => 'plugin',
			'keywords'    => array('grid', 'content egg', 'egg'),
			'postTypes' => $post_types,
			'content'    => self::gridContent(),
		));
	}

	protected static function gridContent()
	{
		return '<!-- wp:greenshift-blocks/repeater {"id":"gsbp-166d4eb","itemSpacing":{"margin":{"values":[],"locked":false},"padding":{"values":{"top":["15px"],"right":["15px"],"bottom":["15px"],"left":["15px"]},"locked":true}},"shadow":{"hoffsetHover":0,"voffsetHover":5,"blurHover":20,"spreadHover":0,"colorHover":"#1f202017","positionHover":"","presetHover":"2"},"border":{"borderRadius":{"values":{"topLeft":["5px"],"topRight":["5px"],"bottomRight":["5px"],"bottomLeft":["5px"]},"locked":true},"style":{"all":["solid"]},"size":{"all":[1]},"color":{"all":["#e8ecee"]},"styleHover":[],"sizeHover":[],"colorHover":[],"custom":[],"customEnabled":[]},"columnGrid":[3,3,2,1],"repeaterType":"ce","extra_filters":{"modules":""},"displayStyle":"grid","flexboxBlock":{"type":"flexbox"}} -->
<!-- wp:greenshift-blocks/element {"id":"gsbp-012e47f","type":"inner","localId":"gsbp-012e47f","localStyles":{"spacing":{"margin":{"values":[],"locked":false},"padding":{"values":[],"locked":false}},"position":{"positionType":["relative","","",""],"positions":{"values":[]}}}} -->
<div data-style-id="gsbp-012e47f"><!-- wp:greenshift-blocks/dynamic-post-image {"id":"gsbp-6c82316","spacing":{"margin":{"values":{"bottom":["5px"]},"locked":false},"padding":{"values":[],"locked":false}},"sourceType":"repeater","image_size":"medium","scale":"scale-down","width":["custom"],"height":["custom"],"widthUnit":["%"],"customWidth":[100],"customHeight":[200],"repeaterField":"img","linkType":"repeater","linkTypeField":"url","linkNewWindow":true,"linkNoFollow":true} /-->

<!-- wp:greenshift-blocks/meta {"id":"gsbp-8c5b104","spacing":{"margin":{"values":{"bottom":["10px"]},"locked":false},"padding":{"values":[],"locked":false}},"typographyValue":{"textShadow":[],"color":"#6b727c99","size":["14px"]},"type":"repeater","typeselect":"repeater","repeaterField":"domain"} /-->

<!-- wp:greenshift-blocks/progressbar {"id":"gsbp-824d41a","spacing":{"margin":{"values":{"bottom":["6px"]},"locked":false},"padding":{"values":[],"locked":false}},"progress":0,"maxvalue":5,"progressheight":[9],"fontsize":[17],"progressline":"#ff8b00","progressbg":"#b2c1d5","dynamicEnable":true,"dynamicField":"","typebar":"star","repeaterField":"rating"} -->
<div class="wp-block-greenshift-blocks-progressbar gs-progressbar gs-progressbar-wrapper gspb_bar-id-gsbp-824d41a"><div class="star-rating"><span style="width:0%"></span></div></div>
<!-- /wp:greenshift-blocks/progressbar -->

<!-- wp:greenshift-blocks/meta {"id":"gsbp-d1bf768","spacing":{"margin":{"values":{"bottom":["15px"]},"locked":false},"padding":{"values":[],"locked":false}},"typographyValue":{"textShadow":[],"size":["16px"],"decoration":"remove"},"type":"repeater","typeselect":"repeater","labelblock":false,"clampEnable":true,"clamp":[3,null,null,null],"repeaterField":"title","linkType":"repeater","linkTypeField":"url","link_enable":true,"linkNewWindow":true,"linkNoFollow":true} /-->

<!-- wp:greenshift-blocks/element {"id":"gsbp-db98bee","type":"inner","localId":"gsbp-db98bee","localStyles":{"spacing":{"margin":{"values":{"bottom":["10px"]},"locked":false},"padding":{"values":[],"locked":false}},"flexbox":{"type":"flexbox","enable":false,"gridcolumns":[2]}}} -->
<div data-style-id="gsbp-db98bee"><!-- wp:greenshift-blocks/dynamic-post-title {"id":"gsbp-56dd503","spacing":{"margin":{"values":{"top":["0px"],"bottom":["0px"]},"unit":["px","px","px","px"],"locked":false},"padding":{"values":[],"unit":["px","px","px","px"],"locked":false}},"typography":{"textShadow":[],"sizeUnit":"rem","size":["22px"],"customweight":"bold"},"sourceType":"repeater","headingTag":"div","link_enable":false,"repeaterField":"price"} /-->

<!-- wp:greenshift-blocks/dynamic-post-title {"id":"gsbp-6876989","spacing":{"margin":{"values":{"top":["0px"],"bottom":["0px"],"left":["5px"]},"unit":["px","px","px","px"],"locked":false},"padding":{"values":[],"unit":["px","px","px","px"],"locked":false}},"typography":{"textShadow":[],"sizeUnit":"rem","size":["18px"],"customweight":"normal","decoration":"line-through"},"sourceType":"repeater","headingTag":"div","link_enable":false,"repeaterField":"priceOld"} /--></div>
<!-- /wp:greenshift-blocks/element --></div>
<!-- /wp:greenshift-blocks/element -->

<!-- wp:greenshift-blocks/buttonbox {"id":"gsbp-03591cc","buttonContent":"Buy Now","background":{"color":"#007bff","hoverColor":"#0069d9"},"spacing":{"margin":{"values":[],"locked":false},"padding":{"values":{"top":["5px"],"right":["5px"],"bottom":["5px"],"left":["5px"]},"locked":true}},"blockWidth":{"customWidth":{"value":["100%"]},"customHeight":{"value":[]},"widthType":"custom"},"typography":{"textShadow":[],"alignment":["center"],"size":["17px"],"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dpalette-color-8, var(\u002d\u002dtheme-palette-color-8, #ffffff))","colorHover":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dpalette-color-8, var(\u002d\u002dtheme-palette-color-8, #ffffff))"},"buttonLink":"https://www.amazon.com/dp/B00F4CEOS8?tag=tmniche-20\u0026linkCode=osi\u0026th=1\u0026psc=1","linkNewWindow":true,"linkNoFollow":true,"dynamicEnable":true,"dynamicField":"","dynamicMetas":{"buttonContent":{"dynamicEnable":false,"repeaterField":"url","dynamicField":""}},"repeaterField":"url"} -->
<div class="gspb_button_wrapper gspb_button-id-gsbp-03591cc" id="gspb_button-id-gsbp-03591cc"><a class="wp-block-greenshift-blocks-buttonbox gspb-buttonbox wp-element-button" href="https://www.amazon.com/dp/B00F4CEOS8?tag=tmniche-20&amp;linkCode=osi&amp;th=1&amp;psc=1" target="_blank" rel="noopener nofollow"><span class="gspb-buttonbox-textwrap"><span class="gspb-buttonbox-text"><span class="gspb-buttonbox-title">Buy Now</span></span></span></a></div>
<!-- /wp:greenshift-blocks/buttonbox -->
<!-- /wp:greenshift-blocks/repeater -->';
	}
}
