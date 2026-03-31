<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\Config;
use ContentEgg\application\Plugin;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\components\ai\AiClient;
use ContentEgg\application\models\PriceAlertModel;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\components\TemplateManager;
use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\LocalRedirector;

/**
 * GeneralSettings class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class GeneralConfig extends Config
{

    private static $affiliate_modules;

    public function page_slug()
    {
        return Plugin::slug() . '';
    }

    public function option_name()
    {
        return 'contentegg_options';
    }

    public function add_admin_menu()
    {
        \add_submenu_page(Plugin::slug, __('Settings', 'content-egg') . ' &lsaquo; Content Egg', __('Settings', 'content-egg'), 'manage_options', $this->page_slug, array($this, 'settings_page'));
    }

    private static function frontendTexts()
    {
        return array(
            'EXPERT SCORE' => __('EXPERT SCORE', 'content-egg-tpl'),
            'Amazon price updated:' => __('Amazon price updated:', 'content-egg-tpl'),
            'in stock' => __('in stock', 'content-egg-tpl'),
            'out of stock' => __('out of stock', 'content-egg-tpl'),
            'Show Code' => __('Show Code', 'content-egg-tpl'),
            'Coupons' => __('Coupons', 'content-egg-tpl'),
            'Last updated on %s' => __('Last updated on %s', 'content-egg-tpl'),
            'as of %s' => __('as of %s', 'content-egg-tpl'),
            '%d new from %s' => __('%d new from %s', 'content-egg-tpl'),
            '%d used from %s' => __('%d used from %s', 'content-egg-tpl'),
            'Free shipping' => __('Free shipping', 'content-egg-tpl'),
            'OFF' => __('OFF', 'content-egg-tpl'),
            'Plus %s Cash Back' => __('Plus %s Cash Back', 'content-egg-tpl'),
            'Price' => __('Price', 'content-egg-tpl'),
            'Features' => __('Features', 'content-egg-tpl'),
            'Specifications' => __('Specifications', 'content-egg-tpl'),
            'Statistics' => __('Statistics', 'content-egg-tpl'),
            'Current Price' => __('Current Price', 'content-egg-tpl'),
            'Highest Price' => __('Highest Price', 'content-egg-tpl'),
            'Lowest Price' => __('Lowest Price', 'content-egg-tpl'),
            'Since %s' => __('Since %s', 'content-egg-tpl'),
            'Last price changes' => __('Last price changes', 'content-egg-tpl'),
            'Start date: %s' => __('Start date: %s', 'content-egg-tpl'),
            'End date: %s' => __('End date: %s', 'content-egg-tpl'),
            'Set Alert for' => __('Set Alert for', 'content-egg-tpl'),
            'Price History' => __('Price History', 'content-egg-tpl'),
            'Create Your Free Price Drop Alert!' => __('Create Your Free Price Drop Alert!', 'content-egg-tpl'),
            'Wait For A Price Drop' => __('Wait For A Price Drop', 'content-egg-tpl'),
            'Your Email' => __('Your Email', 'content-egg-tpl'),
            'Desired Price' => __('Desired Price', 'content-egg-tpl'),
            'SET ALERT' => __('SET ALERT', 'content-egg-tpl'),
            'You will receive a notification when the price drops.' => __('You will receive a notification when the price drops.', 'content-egg-tpl'),
            'I agree to the %s.' => __('I agree to the %s.', 'content-egg-tpl'),
            'Privacy Policy' => __('Privacy Policy', 'content-egg-tpl'),
            'Sorry. No products found.' => __('Sorry. No products found.', 'content-egg-tpl'),
            'Search Results for "%s"' => __('Search Results for "%s"', 'content-egg-tpl'),
            'Price per unit: %s' => __('Price per unit: %s', 'content-egg-tpl'),
            'today' => __('today', 'content-egg-tpl'),
            '%d day ago' => __('%d day ago', 'content-egg-tpl'),
            '%d days ago' => __('%d days ago', 'content-egg-tpl'),
            'Shop %d Offers' => __('Shop %d Offers', 'content-egg-tpl'),
            'from' => __('from', 'content-egg-tpl'),
            'Free delivery' => __('Free delivery', 'content-egg-tpl'),
            'Incl. %s delivery' => __('Incl. %s delivery', 'content-egg-tpl'),
            '%s incl. delivery' => __('%s incl. delivery', 'content-egg-tpl'),
            '%s at %s' => __('%s at %s', 'content-egg-tpl'),
            'View Price at %s' => __('View Price at %s', 'content-egg-tpl'),
            'View on %s' => __('View on %s', 'content-egg-tpl'),
            'Show %d More' => __('Show %d More', 'content-egg-tpl'),
            '+ Delivery *' => __('+ Delivery *', 'content-egg-tpl'),
            '* Delivery cost shown at checkout.' => __('* Delivery cost shown at checkout.', 'content-egg-tpl'),
            'Last Amazon price update was: %s' => __('Last Amazon price update was: %s', 'content-egg-tpl'),
        );
    }

    public static function langs()
    {
        return array(
            'ar' => 'Arabic (ar)',
            'bg' => 'Bulgarian (bg)',
            'ca' => 'Catalan (ca)',
            'zh_CN' => 'Chinese (zh_CN)',
            'zh_TW' => 'Chinese (zh_TW)',
            'hr' => 'Croatian (hr)',
            'cs' => 'Czech (cs)',
            'da' => 'Danish (da)',
            'nl' => 'Dutch (nl)',
            'en' => 'English (en)',
            'et' => 'Estonian (et)',
            'tl' => 'Filipino (tl)',
            'fi' => 'Finnish (fi)',
            'fr' => 'French (fr)',
            'de' => 'German (de)',
            'el' => 'Greek (el)',
            'iw' => 'Hebrew (iw)',
            'hi' => 'Hindi (hi)',
            'hu' => 'Hungarian (hu)',
            'is' => 'Icelandic (is)',
            'id' => 'Indonesian (id)',
            'it' => 'Italian (it)',
            'ja' => 'Japanese (ja)',
            'ko' => 'Korean (ko)',
            'lv' => 'Latvian (lv)',
            'lt' => 'Lithuanian (lt)',
            'ms' => 'Malay (ms)',
            'no' => 'Norwegian (no)',
            'fa' => 'Persian (fa)',
            'pl' => 'Polish (pl)',
            'pt' => 'Portuguese (pt)',
            'br' => 'Portuguese (br)',
            'ro' => 'Romanian (ro)',
            'ru' => 'Russian (ru)',
            'sr' => 'Serbian (sr)',
            'sk' => 'Slovak (sk)',
            'sl' => 'Slovenian (sl)',
            'es' => 'Spanish (es)',
            'sv' => 'Swedish (sv)',
            'th' => 'Thai (th)',
            'tr' => 'Turkish (tr)',
            'uk' => 'Ukrainian (uk)',
            'ur' => 'Urdu (ur)',
            'vi' => 'Vietnamese (vi)',
        );
    }

    protected function options()
    {
        $options = array_merge(
            $this->getGeneralOptions(),
            $this->getFrontendOptions(),
            $this->getAiOptions(),
            $this->getWooCommerceOptions(),
            $this->getPriceAlertOptions(),
            $this->getFrontendSearchOptions(),
            $this->getFrontendOptions(),
            $this->getShopsOptions(),
            $this->getDeprecatedOptions(),
        );

        $options = \apply_filters('cegg_general_config', $options);

        return $options;
    }

    public function getPostTypeList()
    {
        $post_types = get_post_types(array('public' => true), 'names');
        $post_types = array_diff($post_types, array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache'));

        foreach ($post_types as $k => $post_type)
        {
            $post_types[$k] = get_post_type_object($post_type)->labels->name;
        }

        return $post_types;
    }

    private function getGeneralOptions()
    {
        $post_types = self::getPostTypeList();

        $clear_cache_url  = wp_nonce_url(
            get_admin_url(get_current_blog_id(), 'admin.php?page=content-egg-tools&action=clear-logo-cache'),
            'cegg_clear-logo-cache'
        );

        return array(
            'lang' => array(
                'title' => __('Website Language', 'content-egg'),
                'description' => __('Set the language for the frontend display.', 'content-egg'),
                'dropdown_options' => self::langs(),
                'callback' => array($this, 'render_dropdown'),
                'default' => self::getDefaultLang(),
                'section' => __('General settings', 'content-egg'),
            ),
            'external_featured_images' => array(
                'title' => __('External Featured Images', 'content-egg'),
                'description' => __('Enable or disable the use of featured images sourced from external URLs.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'disabled' => __('Disabled - Use internal image', 'content-egg'),
                    'enabled_internal_priority' => __('Enabled - Internal image takes priority', 'content-egg'),
                    'enabled_external_priority' => __('Enabled - External image takes priority', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('General settings', 'content-egg'),
            ),
            'image_proxy' => array(
                'title' => __('Image Proxy', 'content-egg'),
                'description' => sprintf(__('Enable a local proxy for external Amazon images. This may increase server load, so enable only if <a target="_blank" href="%s">necessary</a>.', 'content-egg'), 'https://ce-docs.keywordrush.com/faq/is-content-egg-gdpr-compliant#product-images-and-embedded-content'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'disabled' => __('Disabled', 'content-egg'),
                    'enabled' => __('Enabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('General settings', 'content-egg'),
            ),
            'post_types' => array(
                'title' => 'Post Types',
                'description' => __('Select the post types that you want to integrate with the Content Egg plugin.', 'content-egg'),
                'checkbox_options' => $post_types,
                'callback' => array($this, 'render_checkbox_list'),
                'default' => array('post', 'page', 'product'),
                'section' => __('General settings', 'content-egg'),
            ),
            'cashback_integration' => array(
                'title' => __('Cashback Tracker Integration', 'content-egg'),
                'description' => sprintf(__('Enable integration with the %s plugin to automatically convert affiliate links into trackable cashback links where applicable.', 'content-egg'), '<a target="_blanl" href="https://www.keywordrush.com/cashbacktracker">Cashback Tracker</a>'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled' => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'enabled',
                'section' => __('General settings', 'content-egg'),
            ),

            'outofstock_product' => array(
                'title' => __('Out of Stock Products', 'content-egg'),
                'description' => __('Choose how to manage products that are out of stock.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Do nothing', 'content-egg'),
                    'hide_price' => __('Hide price', 'content-egg'),
                    'hide_product' => __('Hide product', 'content-egg'),
                ),
                'default' => '',
                'section' => __('General settings', 'content-egg'),
            ),

            'clicks_track_redirect' => array(
                'title'       => __('Track Clicks With Redirect', 'content-egg'),
                'description' => __('Counts clicks that go through local redirect links (“link cloaking”). You can enable local redirects per module in its settings.', 'content-egg'),
                'callback'    => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'enabled',
                'section'     => __('General settings', 'content-egg'),
            ),

            'clicks_track_direct' => array(
                'title'       => __('Track Clicks Without Redirect', 'content-egg'),
                'description' => __('Count clicks on direct affiliate links (no local redirect). Recommended for programs that prohibit cloaking (e.g., Amazon). Uses a lightweight client-side beacon; no personal data is stored.', 'content-egg')
                    . '<p class="description">' . esc_html__('Note:', 'content-egg') . ' '
                    . esc_html__('After enabling, the plugin will index existing product links in the background. On larger sites this can take several minutes.', 'content-egg')
                    . '</p>',
                'callback'    => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'validator'   => [
                    ['call' => [$this, 'processClicksTrackDirect'], 'type' => 'filter'],
                ],
                'section'     => __('General settings', 'content-egg'),
            ),
            'clicks_retention_days' => array(
                'title'       => __('Click Stats Retention (days)', 'content-egg'),
                'description' => __('Keep daily click aggregates for this many days. Set 0 to keep forever (no automatic cleanup).', 'content-egg'),
                'callback'    => array($this, 'render_input'),
                'default'     => 180,
                'validator'   => array(
                    'trim',
                ),
                'section'     => __('General settings', 'content-egg'),
            ),
            'send_ga_click_event' => array(
                'title'       => __('GA4 Link Tracking', 'content-egg'),
                'description' =>
                __('Automatically track affiliate link clicks in Google Analytics 4 as custom events for better performance monitoring.', 'content-egg')
                    . '<br>' .
                    __('Note: GA4 must be installed on your site for tracking to function.', 'content-egg')
                    . '<br>' .
                    sprintf(
                        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                        esc_url('https://ce-docs.keywordrush.com/features/clicks-statistics#id-4.-tracking-clicks-in-google-analytics-ga4'),
                        esc_html__('Learn how to track clicks in GA4', 'content-egg')
                    ),
                'callback'          => array($this, 'render_dropdown'),
                'dropdown_options'  => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('General settings', 'content-egg'),
            ),

            'filter_bots' => array(
                'title' => __('Bot Filtering', 'content-egg'),
                'description' => __('Prevent bots from triggering parsers.', 'content-egg') .
                    '<p class="description">' . __('When enabled, price and keyword updates will only occur when the page is opened by a non-bot user. If a known bot user agent is detected, parsers will remain inactive.', 'content-egg') . '</p>',
                'callback' => array($this, 'render_checkbox'),
                'default' => true,
                'section' => __('General settings', 'content-egg'),
            ),

            'logo_source' => [
                'title'       => esc_html__('Logo Source', 'content-egg'),
                'description' => sprintf(
                    __('Choose the service for retrieving logos. Some providers may require attribution—please consult their documentation. %s', 'content-egg'),
                    '<a href="' . esc_url($clear_cache_url) . '" title="' . esc_attr__('Clear all cached logos', 'content-egg') . '">'
                        . esc_html__('Click here to purge cached logos', 'content-egg')
                        . '</a>'
                ),
                'callback'         => [$this, 'render_dropdown'],
                'dropdown_options' => [
                    'clearbit'   => esc_html__('Clearbit', 'content-egg'),
                    'brandfetch' => esc_html__('Brandfetch', 'content-egg'),
                    'logodev'    => esc_html__('Logo.dev', 'content-egg'),
                ],
                'default'          => 'clearbit',
                'section'          => esc_html__('General settings', 'content-egg'),
            ],
            'brandfetch_client_id' => array(
                'title'       => __('Brandfetch Client ID', 'content-egg'),
                'description' => sprintf(
                    __('Enter your Client ID from <a href="%s" target="_blank" rel="noopener noreferrer">Brandfetch Developer Portal</a>.', 'content-egg'),
                    'https://developers.brandfetch.com/dashboard/logo-link'
                ),
                'callback'    => array($this, 'render_input'),
                'default'     => '',
                'section'     => __('General settings', 'content-egg'),
            ),
            'logodev_key' => array(
                'title'       => __('Logo.dev Token', 'content-egg'),
                'description' => sprintf(
                    __('Enter your API Token from <a href="%s" target="_blank" rel="noopener noreferrer">Logo.de Developer Portal</a>.', 'content-egg'),
                    'https://www.logo.dev/dashboard'
                ),
                'callback'    => array($this, 'render_input'),
                'default'     => '',
                'section'     => __('General settings', 'content-egg'),
            ),
            'logo_hotlinking' => array(
                'title'            => __('Enable Logo Hotlinking', 'content-egg'),
                'description'      => __('Choose whether to hotlink logos directly from the provider’s servers or download and save them locally on your site.', 'content-egg'),
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Hotlink (serve from provider)', 'content-egg'),
                    'disabled' => __('Save locally', 'content-egg'),
                ),
                'default'          => 'disabled',
                'section'          => __('General settings', 'content-egg'),
            ),

        );
    }

    private function getAiOptions()
    {
        return array(
            'system_ai_key' => array(
                'title' => __('OpenAI API Key', 'content-egg') . ' <span style="color:red;">*</span>',
                'description' => sprintf(
                    __('Paste your <a target="_blank" href="%1$s">OpenAI API key</a>.', 'content-egg'),
                    esc_url('https://platform.openai.com/api-keys')
                ) .
                    '<br>' . __('The key enables system AI-powered features, including the Product Prefill Tool and Product Import Tools.', 'content-egg') .
                    '<br>' . sprintf(__('The plugin uses the <code>%s</code> model for the best balance of performance and cost.', 'content-egg'), '<code>gpt-4o-mini</code>') .
                    '<br>' . __('Be sure your OpenAI account has sufficient credit.', 'content-egg'),

                'callback' => array($this, 'render_password'),
                'default' => '',
                'validator' => array('trim'),
                'section' => __('AI', 'content-egg'),
            ),

            'separator' => array(
                'description' => '<hr>',
                'callback' => array($this, 'render_text'),
                'section' => __('AI', 'content-egg'),
            ),
            'ai_language' => array(
                'title' => __('Language', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'description' => __('Select the primary language for your website. The AI will generate all content in this language.', 'content-egg'),
                'dropdown_options' => self::getAiLanguagesList(),
                'default' => self::getDefaultAiLang(),
                'section' => __('AI', 'content-egg'),

            ),
            'ai_model' => array(
                'title' => __('AI Model', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getAiModelList(),
                'default' => 'gpt-4o-mini',
                'description' => __('Please be cautious with your model settings, as some AI models may be significantly more expensive than others.', 'content-egg') . '<br><br>' .
                    __('Note: Our default prompts are optimized for OpenAI GPT and Claude models. Results may be unpredictable when using other models via OpenRouter.', 'content-egg') . '<br><br>' .
                    __('For pricing details, please visit the <a href="https://platform.openai.com/docs/pricing" target="_blank">Pricing</a> page.', 'content-egg'),
                'section' => __('AI', 'content-egg'),
            ),

            'ai_key' => array(
                'title' => 'AI API key' . ' <span style="color:red;">*</span>',
                'description' => sprintf(
                    __('Add your <a target="_blank" href="%1$s">OpenAI</a>, <a target="_blank" href="%2$s">OpenRouter</a>, or <a target="_blank" href="%3$s">Claude</a> API key according to the AI model you have selected.', 'content-egg'),
                    esc_url('https://platform.openai.com/api-keys'),
                    esc_url('https://openrouter.ai/settings/keys'),
                    esc_url('https://console.anthropic.com/settings/keys')
                ) . '<br>' . __('Ensure you have sufficient funds in your balance!', 'content-egg'),
                'callback' => array($this, 'render_password'),
                'default' => '',
                'validator' => array(
                    'trim',
                ),
                'section' => __('AI', 'content-egg'),
            ),

            'openrouter_models' => array(
                'title' => 'OpenRouter Models',
                'description' => sprintf(__('Specify the <a target="_blank" href="%1$s">models</a> to be used exclusively with OpenRouter. Enter a comma-separated list of model identifiers (e.g., "deepseek/deepseek-r1:free, openai/gpt-4o-mini"). The system will prioritize models in the given order, attempting the first model first and using the subsequent models as fallbacks.', 'content-egg'), esc_url('https://ce-docs.keywordrush.com/ai/openrouter-api#how-to-set-openrouter-models')),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                    array(
                        'call' => array($this, 'openRouterModelsFilter'),
                        'type' => 'filter',
                    ),
                ),
                'section' => __('AI', 'content-egg'),
            ),

            'ai_temperature' => array(
                'title' => __('Creativity level', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getAiCreativitiesList(),
                'default' => '0.75',
                'section' => __('AI', 'content-egg'),

            ),
            'separator2' => array(
                'description' => '<hr>',
                'callback' => array($this, 'render_text'),
                'section' => __('AI', 'content-egg'),
            ),
            'prompt1' => array(
                'title' => sprintf(__('Custom prompt #%d', 'content-egg'), 1),
                'description' => __('For custom prompts, you can use placeholders such as %title%, %description%, %description_html%, %short_description%, %lang%, %features%, %reviews%, %title_new%.', 'content-egg')
                    . ' ' . sprintf(__('<a target="_blank" href="%s">More info...</a>', 'content-egg'), 'https://ce-docs.keywordrush.com/ai/custom-prompts'),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
                'section' => __('AI', 'content-egg'),

            ),
            'prompt2' => array(
                'title' => sprintf(__('Custom prompt #%d', 'content-egg'), 2),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
                'section' => __('AI', 'content-egg'),

            ),
            'prompt3' => array(
                'title' => sprintf(__('Custom prompt #%d', 'content-egg'), 3),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
                'section' => __('AI', 'content-egg'),

            ),
            'prompt4' => array(
                'title' => sprintf(__('Custom prompt #%d', 'content-egg'), 4),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
                'section' => __('AI', 'content-egg'),
            ),
        );
    }

    private function getFrontendOptions()
    {
        $variation_options = array();
        foreach (TemplateManager::getColorVariants() as $i => $variant)
        {
            $variation_option = array(
                'title' => sprintf(__('%s Color', 'content-egg'), ucfirst($variant)),
                'callback' => array($this, 'render_color_picker'),
                'default' => '',
                'validator' => array(
                    'trim',
                ),
                'section' => __('Frontend', 'content-egg'),
            );

            if ($i == 0)
                $variation_option['description'] = __('Choose custom colors if needed, or leave the fields empty to use the default colors for buttons, badges, and other elements.', 'content-egg');

            $variation_options[$variant . '_color'] = $variation_option;
        }

        $options = array(

            'color_mode' => array(
                'title' => __('Color Mode', 'content-egg'),
                'description' => __('Choose between Light or Dark theme settings.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'light' => __('Light mode', 'content-egg'),
                    'dark' => __('Dark mode', 'content-egg'),
                ),
                'default' => 'light',
                'section' => __('Frontend', 'content-egg'),
            ),
            'btn_variant' => array(
                'title' => __('Button Variant', 'content-egg'),
                'description' => __('Select the default style for buttons.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getButtonVariantList(),
                'default' => 'outline-primary',
                'section' => __('Frontend', 'content-egg'),
            ),

            'btn_text_buy_now' => array(
                'title'       => __('Product Button Text (Affiliate)', 'content-egg'),
                'description' => sprintf(
                    __('Text for the external “Buy Now” button. Leave empty to use the default: %1$s. Supports tokens: %2$s', 'content-egg'),
                    '<code>Buy Now</code>',
                    '<code>%MERCHANT%</code>, <code>%DOMAIN%</code>, <code>%PRICE%</code>, <code>%STOCK_STATUS%</code>'
                ),
                'callback'    => array($this, 'render_input'),
                'default'     => '', // empty = use built-in default “Buy Now”
                'validator'   => array('strip_tags'),
                'section'     => __('Frontend', 'content-egg'),
            ),

            'btn_text_bridge' => array(
                'title'       => __('Bridge Button Text (On-Site)', 'content-egg'),
                'description' => sprintf(
                    __('Text for the on-site Bridge button. Leave empty to use the default: %1$s. Supports tokens: %2$s', 'content-egg'),
                    '<code>See Details</code>',
                    '<code>%MERCHANT%</code>, <code>%DOMAIN%</code>, <code>%PRICE%</code>, <code>%STOCK_STATUS%</code>, <code>%SITE_NAME%</code>'
                ),
                'callback'    => array($this, 'render_input'),
                'default'     => '', // empty = use built-in default “See Details”
                'validator'   => array('strip_tags'),
                'section'     => __('Frontend', 'content-egg'),

            ),

            'btn_text_coupon' => array(
                'title' => __('Coupon Button Text', 'content-egg'),
                'description' => sprintf(__('Customize the text for the coupon button, replacing "%s" with your preferred wording.', 'content-egg'), __('Shop Sale', 'content-egg-tpl')),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'strip_tags',
                ),
                'section' => __('Frontend', 'content-egg'),
            ),

        );

        $options = array_merge($options, $variation_options, array(
            'post_disclaimer_text' => array(
                'title' => __('Post Disclaimer Text', 'content-egg'),
                'description' => __('Enter the disclaimer text that will be displayed at the top or bottom of each post containing Content Egg products. Basic HTML tags are supported.', 'content-egg'),
                'placeholder' => TemplateHelper::getPostDisclimerText(true),
                'callback' => array($this, 'render_textarea'),
                'rows' => 3,
                'default' => '',
                'section' => __('Frontend', 'content-egg'),
            ),
            'post_disclaimer_position' => array(
                'title' => __('Post Disclaimer Position', 'content-egg'),
                'description' => __('Choose where to display the affiliate disclaimer in your posts.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'top' => __('Display at the top of each post', 'content-egg'),
                    'bottom' => __('Display at the bottom of each post', 'content-egg'),
                    'disabled' => __('Do not display the post disclaimer', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('Frontend', 'content-egg'),
            ),
            'block_disclaimer_text' => array(
                'title' => __('Product Block Disclaimer Text', 'content-egg'),
                'description' => __('Enter the disclaimer text that will be displayed after each product block. Basic HTML tags are supported.', 'content-egg'),
                'placeholder' => TemplateHelper::getBlockDisclimerText(true),
                'callback' => array($this, 'render_textarea'),
                'rows' => 3,
                'default' => '',
                'section' => __('Frontend', 'content-egg'),
            ),

            'product_block_disclaimer' => array(
                'title' => __('Product Block Disclaimer', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled' => __('Display disclaimer after each product block', 'content-egg'),
                    'disabled' => __('Do not display the product block disclaimer', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('Frontend', 'content-egg'),
            ),
            'disclaimer_text' => array(
                'title' => __('Amazon Price Disclaimer Text', 'content-egg'),
                'placeholder' => TemplateHelper::getAmazonPriceDisclimerText(true),
                'callback' => array($this, 'render_textarea'),
                'rows' => 3,
                'default' => '',
                'validator' => array(
                    'strip_tags',
                ),
                'section' => __('Frontend', 'content-egg'),
            ),
            'amazon_price_update_display' => array(
                'title' => __('Amazon Price Update Display', 'content-egg'),
                'description' => __('Display the last Amazon price update as required by Amazon\'s operating agreement. Disabling this feature is not recommended.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled' => __('Display the last Amazon price update for each block', 'content-egg'),
                    'disabled' => __('Do not display the Amazon price update information', 'content-egg'),
                ),
                'default' => 'enabled',
                'section' => __('Frontend', 'content-egg'),
            ),
            'rel_attribute' => array(
                'title' => 'Rel Attribute',
                'description' => sprintf(__('<a target="_blank" href="%s">Qualify</a> your affiliate links to Google.', 'content-egg'), 'https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links'),
                'checkbox_options' => array(
                    'nofollow' => 'nofollow',
                    'sponsored' => 'sponsored',
                    'external' => 'external',
                    'noopener' => 'noopener',
                    'noreferrer' => 'noreferrer',
                    'ugc' => 'ugc',
                ),
                'callback' => array($this, 'render_checkbox_list'),
                'default' => array('nofollow', 'noopener'),
                'section' => __('Frontend', 'content-egg'),
            ),
            'redirect_prefix' => array(
                'title' => __('Redirect Prefix', 'content-egg'),
                'description' => __('Set the path segment used for local redirect URLs. Leave empty to use the default "go".', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    array(
                        'call' => array($this, 'processRedirectPrefix'),
                        'type' => 'filter',
                    ),
                    'trim',
                    'allow_empty',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'alpha_numeric'),
                        'message' => sprintf(__('The field "%s" can contain only Latin letters and digits.', 'content-egg'), __('Redirect prefix', 'content-egg')),
                    ),
                ),
                'section' => __('Frontend', 'content-egg'),
            ),
            'redirect_pass_parameters' => array(
                'title' => __('Pass-through Query Parameters', 'content-egg'),
                'description' => __('Enable or disable the forwarding of query parameters to redirect links.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled' => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('Frontend', 'content-egg'),
            ),
            'redirect_status_code' => array(
                'title'       => __('Redirect Status Code', 'content-egg'),
                'description' => __('Choose the HTTP status code for local redirects.', 'content-egg'),
                'callback'    => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '301' => __('301 — Moved Permanently', 'content-egg'),
                    '302' => __('302 — Found (Temporary)', 'content-egg'),
                    '307' => __('307 — Temporary Redirect', 'content-egg'),
                ),
                'default' => '301',
                'section' => __('Frontend', 'content-egg'),
            ),
            'link_destination' => array(
                'title' => __('Link destination preference', 'content-egg'),
                'description' => __('Choose where product links should send visitors when a Bridge Page exists. You can override this behavior using the link_target shortcode parameter.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'affiliate' => __('Prefer Merchant (affiliate)', 'content-egg'),
                    'bridge' => __('Prefer Bridge Page (on your site)', 'content-egg'),
                ),
                'default' => 'bridge',
                'section' => __('Frontend', 'content-egg'),
            ),
            'logos' => array(
                'title' => __('Merchant Logos', 'content-egg'),
                'description' => __('Specify the URLs for your custom logos.', 'content-egg'),
                'callback' => array($this, 'render_logo_fields_block'),
                'validator' => array(
                    array(
                        'call' => array($this, 'formatLogoFields'),
                        'type' => 'filter',
                    ),
                ),
                'default' => array(),
                'section' => __('Frontend', 'content-egg'),
            ),

            'add_schema_markup' => array(
                'title' => __('Add Schema Markup', 'content-egg'),
                'description' => __('Enable Product/AggregateOffer schema markup for posts. Activate this option only if your posts are used for price comparisons or feature single products.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled' => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('Frontend', 'content-egg'),
            ),

            'frontend_texts' => array(
                'title' => __('Frontend Texts', 'content-egg'),
                'description' => '',
                'callback' => array($this, 'render_translation_block'),
                'validator' => array(
                    array(
                        'call' => array($this, 'frontendTextsSanitize'),
                        'type' => 'filter',
                    ),
                ),
                'section' => __('Frontend', 'content-egg'),
            ),
        ));

        return $options;
    }

    private function getWooCommerceOptions()
    {
        return array(
            'woocommerce_modules' => array(
                'title' => __('Automatic Synchronization Modules', 'content-egg'),
                'description' => __('Select the modules to be automatically synchronized with WooCommerce.', 'content-egg'),
                'checkbox_options' => self::getAffiliteModulesList(),
                'callback' => array($this, 'render_checkbox_list'),
                'default' => array(),
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_product_sync' => array(
                'title' => __('Automatic Synchronization Criteria', 'content-egg'),
                'description' => __('Choose the method for selecting products to automatically synchronize with WooCommerce.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'min_price' => __('Minimum Price', 'content-egg'),
                    'max_price' => __('Maximum Price', 'content-egg'),
                    'random' => __('Random', 'content-egg'),
                    'manually' => __('Manually Only', 'content-egg'),
                ),
                'default' => 'min_price',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_attributes_sync' => array(
                'title' => __('Import Product Attributes', 'content-egg'),
                'description' => __('Automatically import attributes for synchronized products.', 'content-egg'),
                'callback' => array($this, 'render_checkbox'),
                'default' => false,
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_attributes_filter' => array(
                'title' => __('Global Attributes Filter', 'content-egg'),
                'description' => sprintf(__('Configure how WooCommerce attributes are created during synchronization. For more details, please refer to our <a target="_blank" href="%s">documentation</a>.', 'content-egg'), 'https://ce-docs.keywordrush.com/woocommerce/attributes-synchronization'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Default filter', 'content-egg'),
                    'whitelist' => __('Whitelist Attribute Names', 'content-egg'),
                    'blacklist' => __('Blacklist Attribute Names', 'content-egg'),
                ),
                'default' => 'whitelist',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_attributes_list' => array(
                'title' => __('Attributes List', 'content-egg'),
                'description' => __('Specify a comma-separated list of WooCommerce global (filterable) attributes for inclusion in the whitelist or blacklist.', 'content-egg'),
                'callback' => array($this, 'render_textarea'),
                'default' => '',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_echo_update_date' => array(
                'title' => __('Update Date Display', 'content-egg'),
                'description' => __('Choose to show the price update date for WooCommerce products.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'content-egg'),
                    'amazon' => __('Amazon Only', 'content-egg'),
                    'all' => __('All Modules', 'content-egg'),
                ),
                'default' => 'amazon',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_echo_price_per_unit' => array(
                'title' => __('Price Per Unit', 'content-egg'),
                'description' => __('Display the price per unit.', 'content-egg') .
                    '<p class="description">' .
                    __('This option is only applicable to Amazon and eBay modules.', 'content-egg') . '<br>' .
                    '</p>',
                'callback' => array($this, 'render_checkbox'),
                'default' => false,
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_btn_text' => array(
                'title' => __('Buy Button Text', 'content-egg'),
                'description' => __('Customize the button text for external WooCommerce products.', 'content-egg') . ' ' . __('You can use tags like %MERCHANT%, %DOMAIN%, %PRICE%, and %STOCK_STATUS% for dynamic content.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'strip_tags',
                ),
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'aggregate_offer' => array(
                'title' => __('AggregateOffer Markup', 'content-egg'),
                'description' => __('Add AggregateOffer to the product\'s structured data. This is useful for price comparison sites.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'disabled' => __('Disabled', 'content-egg'),
                    'enabled' => __('Enabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_sync_description' => array(
                'title' => __('Description Synchronization', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'content-egg'),
                    'full' => __('Sync Full Description', 'content-egg'),
                    'short' => __('Sync Short Description', 'content-egg'),
                ),
                'default' => '',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'woocommerce_sync_gallery' => array(
                'title' => __('Gallery Images', 'content-egg'),
                'description' => __('Automatically sync product gallery images when available. Note: Gallery image support may vary by module.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'content-egg'),
                    'external' => __('Use external URLs for additional images', 'content-egg'),
                    'local' => __('Download images to the WP Media Library', 'content-egg'),
                ),
                'default' => 'external',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'sync_woo_brand' => array(
                'title' => __('Brand Taxonomy', 'content-egg'),
                'description' => __('Sync manufacturer or store domain with the WooCommerce Brand taxonomy.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'brand' => __('Manufacturer', 'content-egg'),
                    'store' => __('Store domain', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'sync_brand' => array(
                'title' => __('ReHub Brand Mapping', 'content-egg'),
                'description' => __('Sync manufacturer or store domain with the Brand taxonomy in the ReHub theme.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'brand' => __('Manufacturer', 'content-egg'),
                    'store' => __('Store domain', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'outofstock_woo' => array(
                'title' => __('Out of Stock Products', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Do Nothing', 'content-egg'),
                    'hide_price' => __('Hide WooCommerce Price', 'content-egg'),
                    'hide_product' => __('Set Catalog Visibility to Hidden', 'content-egg'),
                    'move_to_trash' => __('Move WooCommerce Product to Trash', 'content-egg'),
                ),
                'default' => '',
                'section' => __('WooCommerce', 'content-egg'),
            ),
            'sync_gtin' => array(
                'title'            => __('Sync GTIN (WooCommerce native)', 'content-egg'),
                'description'      => __('Synchronize the Content Egg GTIN/EAN to WooCommerce’s native product identifier field.', 'content-egg'),
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),

            'sync_ean' => array(
                'title'            => __('Sync EAN (EAN for WooCommerce plugin)', 'content-egg'),
                'description'      => __('Copy the EAN from Content Egg to the EAN for WooCommerce field (meta: _alg_ean). Requires the EAN for WooCommerce plugin to be active.', 'content-egg'),
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),

            'sync_isbn' => array(
                'title'            => __('Sync ISBN (EAN for WooCommerce plugin)', 'content-egg'),
                'description'      => __('Copy the ISBN from Content Egg to the EAN for WooCommerce field (meta: _alg_isbn). Requires the EAN for WooCommerce plugin to be active.', 'content-egg'),
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ),
                'default' => 'disabled',
                'section' => __('WooCommerce', 'content-egg'),
            ),

            'woocommerce_shortcode_single' => array(
                'title' => __('Add Shortcode to Single Product Pages', 'content-egg'),
                'description' => __(
                    'Insert any Content Egg shortcode into the product summary, such as a price comparison block.',
                    'content-egg'
                ) . '<br>' . sprintf(
                    __('For example: %s', 'content-egg'),
                    '[content-egg-block template=offers_logo_btn hide=title]'
                ),
                'callback' => array($this, 'render_textarea'),
                'default' => '',
                'section' => __('WooCommerce', 'content-egg'),
                'validator' => array(
                    array(
                        'call' => array($this, 'formatHtmlField'),
                        'type' => 'filter',
                    ),
                ),
            ),
            'woocommerce_shortcode_archive' => array(
                'title' => __('Add Shortcode to Archive Pages', 'content-egg'),
                'description' => __(
                    'Insert any Content Egg shortcode into the shop and archive pages, such as a price comparison block.',
                    'content-egg'
                ) . '<br>' . sprintf(
                    __('For example: %s', 'content-egg'),
                    '[content-egg-block template=price_comparison limit=3]'
                ),
                'callback' => array($this, 'render_textarea'),
                'default' => '',
                'section' => __('WooCommerce', 'content-egg'),
                'validator' => array(
                    array(
                        'call' => array($this, 'formatHtmlField'),
                        'type' => 'filter',
                    ),
                ),
            ),
        );
    }

    private function getPriceAlertOptions()
    {
        $total_price_alerts = PriceAlertModel::model()->count('status = ' . PriceAlertModel::STATUS_ACTIVE);
        $sent_price_alerts = PriceAlertModel::model()->count('status = ' . PriceAlertModel::STATUS_DELETED
            . ' AND TIMESTAMPDIFF( DAY, complet_date, "' . \current_time('mysql') . '") <= ' . PriceAlertModel::CLEAN_DELETED_DAYS);

        $export_url = wp_nonce_url(
            get_admin_url(get_current_blog_id(), "admin.php?page=content-egg-tools&action=subscribers-export"),
            "cegg_subscribers-export"
        );

        return array(
            'price_history_days' => array(
                'title' => __('Price History', 'content-egg'),
                'description' => __('Specify the duration for retaining price history. Set to 0 to disable price history tracking.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => 180,
                'validator' => array(
                    'trim',
                    'absint',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'less_than_equal_to'),
                        'arg' => 1875,
                        'message' => sprintf(__('The field "%s" can\'t be more than %d.', 'content-egg'), __('Price history', 'content-egg'), 365),
                    ),
                ),
                'section' => __('Price alerts', 'content-egg'),
            ),
            'price_drops_days' => array(
                'title' => __('Price Drop Tracking Window', 'content-egg'),
                'description' => __('Set how far back to look when detecting price drops for the Price Movers widget. Products must have been updated within this timeframe.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '1.' => __('The last 1 day', 'content-egg'),
                    '2.' => sprintf(__('The last %d days', 'content-egg'), 2),
                    '3.' => sprintf(__('The last %d days', 'content-egg'), 3),
                    '4.' => sprintf(__('The last %d days', 'content-egg'), 4),
                    '5.' => sprintf(__('The last %d days', 'content-egg'), 5),
                    '6.' => sprintf(__('The last %d days', 'content-egg'), 6),
                    '7.' => sprintf(__('The last %d days', 'content-egg'), 7),
                    '21.' => sprintf(__('The last %d days', 'content-egg'), 21),
                    '30.' => sprintf(__('The last %d days', 'content-egg'), 30),
                    '90.' => sprintf(__('The last %d days', 'content-egg'), 90),
                    '180.' => sprintf(__('The last %d days', 'content-egg'), 180),
                    '360.' => sprintf(__('The last %d days', 'content-egg'), 360),
                ),
                'default' => '90.',
                'section' => __('Price alerts', 'content-egg'),
            ),
            'price_alert_enabled' => array(
                'title' => __('Enable Price Alerts', 'content-egg'),
                'description' => __('Allow visitors to subscribe to email notifications when product prices drop.', 'content-egg') .
                    '<p class="description">' .
                    sprintf(__('Currently active subscriptions: <strong>%d</strong>.', 'content-egg'), $total_price_alerts) . '<br>' .
                    sprintf(__('Notifications sent in the last %d days: <strong>%d</strong>.', 'content-egg'), PriceAlertModel::CLEAN_DELETED_DAYS, $sent_price_alerts) . '<br>' .
                    sprintf(__('Export data: [<a href="%s">All</a> | <a href="%s">Active only</a>]', 'content-egg'), $export_url, $export_url . '&active_only=true') .
                    '</p>' .
                    '<p class="description">' .
                    __('Note: The "Price history" option must be enabled for alerts to work.', 'content-egg') . '<br>' .
                    __('Privacy recommendation: Go to Settings → Privacy and select a Privacy Policy page.', 'content-egg') .
                    '</p>',
                'callback' => array($this, 'render_checkbox'),
                'default'  => true,
                'section'  => __('Price alerts', 'content-egg'),
            ),

            'price_alert_mode' => array(
                'title' => __('Alert Trigger Scope', 'content-egg'),
                'description' => __('Choose how price drop alerts should be triggered: per individual product or for any product within a post.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'product' => __('Per product - alert for each product separately', 'content-egg'),
                    'post'    => __('Per post - one alert for any product in the post', 'content-egg'),
                ),
                'default' => 'product',
                'section' => __('Price alerts', 'content-egg'),
            ),

            'from_name' => array(
                'title' => __('Sender Name', 'content-egg'),
                'description' => __('This name will be shown as the sender in the “From” field of all email alerts sent by the Content Egg plugin.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                    'allow_empty',
                ),
                'section' => __('Price alerts', 'content-egg'),
            ),
            'from_email' => array(
                'title' => __('Sender Email Address', 'content-egg'),
                'description' => __('This email address will appear in the “From” field of all email alerts.', 'content-egg') . ' ' .
                    __('To reduce the risk of emails being marked as spam, use an address that matches your website domain.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                    'allow_empty',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'valid_email'),
                        'message' => __('The email address is not valid.', 'content-egg'),
                    ),
                ),
                'section' => __('Price alerts', 'content-egg'),
            ),

            'email_template_activation' => array(
                'title' => __('Activation Email Content Template', 'content-egg'),
                'description' => sprintf(
                    __('You can use the following tags in your email template: %s.', 'content-egg'),
                    '%POST_ID%, %POST_URL%, %POST_TITLE%, %PRODUCT_TITLE%, %VALIDATE_URL%, %UNSUBSCRIBE_URL%'
                ) .
                    '<br>' .
                    sprintf(__('%s is a required tag.', 'content-egg'), '%VALIDATE_URL%') . ' ' .
                    sprintf(__('Example usage: %s', 'content-egg'), \esc_html('<a href="%VALIDATE_URL%">%VALIDATE_URL%</a>')),
                'callback' => array($this, 'render_textarea'),
                'default'  => '',
                'section'  => __('Price alerts', 'content-egg'),
                'validator' => array(
                    '\wp_kses_post',
                    'trim',
                ),
            ),

            'email_template_alert' => array(
                'title' => __('Price Alert Email Content Template', 'content-egg'),
                'description' => sprintf(
                    __('You can use the following tags in your email template: %s.', 'content-egg'),
                    '%POST_ID%, %POST_URL%, %POST_TITLE%, %PRODUCT_TITLE%, %START_PRICE%, %DESIRED_PRICE%, %CURRENT_PRICE%, %SAVED_AMOUNT%, %SAVED_PERCENTAGE%, %UPDATE_DATE%, %UNSUBSCRIBE_URL%'
                ) .
                    '<br>' .
                    __('Use these tags to personalize alert emails when a price drops below the user’s desired amount.', 'content-egg'),
                'callback' => array($this, 'render_textarea'),
                'default'  => '',
                'section'  => __('Price alerts', 'content-egg'),
                'validator' => array(
                    '\wp_kses_post',
                    'trim',
                ),
            ),

            'email_signature' => array(
                'title' => __('Email Signature', 'content-egg'),
                'description' => __('Add a custom signature that will appear at the bottom of all price alert emails. You may include text, links, or basic HTML.', 'content-egg'),
                'callback' => array($this, 'render_textarea'),
                'default'  => '',
                'section'  => __('Price alerts', 'content-egg'),
                'validator' => array(
                    '\wp_kses_post',
                    'trim',
                ),
            ),

        );
    }

    private function getFrontendSearchOptions()
    {
        return array(
            'search_modules' => array(
                'title' => __('Search modules', 'content-egg'),
                'description' => __('Select the modules to include in the frontend search.', 'content-egg') . ' ' .
                    __('We recommend choosing no more than 1-2 modules for optimal performance.', 'content-egg') . '<br>' .
                    __('Please note that AE modules may slow down the search functionality and are not recommended for this purpose.', 'content-egg') . '<br>' .
                    __('Don\'t forget to add the search widget or use the shortcode [content-egg-search-form].', 'content-egg'),
                'checkbox_options' => self::getAffiliteModulesList(),
                'callback' => array($this, 'render_checkbox_list'),
                'default' => array(),
                'section' => __('Frontend search', 'content-egg'),
            ),
            'search_page_tpl' => array(
                'title' => __('Search Page Template', 'content-egg'),
                'description' => __('Define the template for the search page content.', 'content-egg') . ' ' .
                    sprintf(__('You can include shortcodes such as: %s.', 'content-egg'), '[content-egg-block template=offers_list]'),
                'callback' => array($this, 'render_textarea'),
                'default' => '[content-egg-block template=offers_list]',
                'section' => __('Frontend search', 'content-egg'),
            ),
        );
    }

    private function getShopsOptions()
    {
        return array(

            'merchants' => array(
                'title' => __('Shops', 'content-egg'),
                'callback' => array($this, 'render_merchants_block'),
                'validator' => array(
                    array(
                        'call' => array($this, 'formatMerchantFields'),
                        'type' => 'filter',
                    ),
                ),
                'default' => array(),
                'section' => __('Shops', 'content-egg'),
            ),
            'popup_type' => array(
                'title' => __('Popup type', 'content-egg') . ' (' . __('Deprecated', 'content-egg') . ')',
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'popover' => __('Popover', 'content-egg'),
                    'modal' => __('Modal', 'content-egg'),
                ),
                'default' => 'popover',
                'section' => __('Shops', 'content-egg'),
            ),
        );
    }

    private function getDeprecatedOptions()
    {
        return array(
            'button_color' => array(
                'title' => __('Button Color', 'content-egg'),
                'description' => __('Please use the "Button Variant" and "Colors" options instead.', 'content-egg'),
                'callback' => array($this, 'render_color_picker'),
                'default' => '#d9534f',
                'validator' => array(
                    'trim',
                ),
                'section' => __('Deprecated', 'content-egg'),
            ),
            'price_color' => array(
                'title' => __('Price Color', 'content-egg'),
                'description' => __('Please use the "Colors" options instead.', 'content-egg'),
                'callback' => array($this, 'render_color_picker'),
                'default' => '#dc3545',
                'validator' => array(
                    'trim',
                ),
                'section' => __('Deprecated', 'content-egg'),
            ),
            'show_stock_status' => array(
                'title' => __('Stock Status', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'show_status' => __('Show stock status', 'content-egg'),
                    'hide_status' => __('Hide stock status', 'content-egg'),
                    'show_outofstock' => __('Show OutOfStock status only', 'content-egg'),
                    'show_instock' => __('Show InStock status only', 'content-egg'),
                ),
                'default' => 'show_status',
                'section' => __('Deprecated', 'content-egg'),
            ),
        );
    }

    public static function getAiLanguagesList()
    {
        return array_combine(array_values(self::getAiLanguages()), array_values(self::getAiLanguages()));
    }

    public static function getBtnVariants()
    {
        return array('primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link', 'outline-primary', 'outline-secondary', 'outline-success', 'outline-danger', 'outline-warning', 'outline-info', 'outline-light', 'outline-dark');
    }

    public static function getAiLanguages()
    {
        $list = array(
            'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'en' => 'English',
            'tl' => 'Filipino',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'el' => 'Greek',
            'iw' => 'Hebrew',
            'hi' => 'Hindi',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'ms' => 'Malay',
            'no' => 'Norwegian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'pt_BR' => 'Portuguese (Brazil)',
            'pt_PT' => 'Portuguese (Portugal)',
            'ro' => 'Romanian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
        );

        ksort($list);
        return $list;
    }

    public static function getAiCreativitiesList()
    {
        return array(
            '0.0' => __('Min (more factual, but repetiteve)', 'content-egg'),
            '0.5' => __('Low', 'content-egg'),
            '0.75' => __('Optimal', 'content-egg') . ' ' . __('(recommended)', 'content-egg'),
            '1.0' => __('Optimal+', 'content-egg'),
            '1.2' => __('Hight', 'content-egg'),
            '1.5' => __('Max (less factual, but creative)', 'content-egg'),
        );
    }

    public static function getAiDefaultLang()
    {
        $parts = explode('_', \get_locale());
        $lang = strtolower(reset($parts));
        $languages = self::getAiLanguages();

        if (isset($languages[$lang]))
            return $languages[$lang];
        else
            return 'English';
    }

    public static function getAiModelList()
    {
        $models = AiClient::models();
        $res = array();
        foreach ($models as $key => $model)
        {
            $res[$key] = $model['name'];
        }

        asort($res);
        $res = array_reverse($res, true);

        return $res;
    }

    public static function getDefaultAiLang()
    {
        $parts = explode('_', \get_locale());
        $lang = strtolower(reset($parts));
        $languages = self::getAiLanguages();

        if (isset($languages[$lang]))
            return $languages[$lang];
        else
            return 'English';
    }

    public static function getDefaultLang()
    {
        $locale = \get_locale();
        $lang = explode('_', $locale);
        if (array_key_exists($lang[0], self::langs()))
            return $lang[0];
        else
            return 'en';
    }

    public function settings_page()
    {

        \wp_enqueue_style('cegg-bootstrap5-full', '', Plugin::version());
        \wp_enqueue_script('jquery-ui-tabs');
        \wp_enqueue_style('contentegg-admin-ui-css', \ContentEgg\PLUGIN_RES . '/css/jquery-ui.min.css', false, \ContentEgg\application\Plugin::version);

        PluginAdmin::render('settings', array('page_slug' => $this->page_slug()));
    }

    private static function getAffiliteModulesList()
    {
        if (self::$affiliate_modules === null)
        {
            self::$affiliate_modules = ModuleManager::getInstance()->getAffiliteModulesList(true);
        }
        return self::$affiliate_modules;
    }

    public function render_logo_fields_line($args)
    {
        $i = isset($args['_field']) ? $args['_field'] : 0;
        $name = isset($args['value'][$i]['name']) ? $args['value'][$i]['name'] : '';
        $value = isset($args['value'][$i]['value']) ? $args['value'][$i]['value'] : '';

        echo '<input name="' . \esc_attr($args['option_name']) . '['
            . \esc_attr($args['name']) . '][' . esc_attr($i) . '][name]" value="'
            . \esc_attr($name) . '" class="text" placeholder="' . \esc_attr(__('Domain name', 'content-egg')) . '"  type="text"/>';
        echo '<input name="' . \esc_attr($args['option_name']) . '['
            . \esc_attr($args['name']) . '][' . esc_attr($i) . '][value]" value="'
            . \esc_attr($value) . '" class="regular-text ltr" placeholder="' . \esc_attr(__('Logo URL', 'content-egg')) . '"  type="text"/>';
    }

    public function render_logo_fields_block($args)
    {
        if (is_array($args['value']))
            $total = count($args['value']) + 3;
        else
            $total = 3;

        for ($i = 0; $i < $total; $i++)
        {
            echo '<div style="padding-bottom: 5px;">';
            $args['_field'] = $i;
            $this->render_logo_fields_line($args);
            echo '</div>';
        }
        if ($args['description'])
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    public function formatLogoFields($values)
    {
        $results = array();
        foreach ($values as $k => $value)
        {
            $name = trim(\sanitize_text_field($value['name']));
            if ($host = TextHelper::getHostName($values[$k]['name']))
                $name = $host;

            $value = trim(\sanitize_text_field($value['value']));

            if (!$name || !$value)
                continue;

            if (!filter_var($value, FILTER_VALIDATE_URL))
                continue;

            if (in_array($name, array_column($results, 'name')))
                continue;

            $result = array('name' => $name, 'value' => $value);
            $results[] = $result;
        }

        return $results;
    }

    public function render_translation_row($args)
    {
        $field_name = $args['_field_name'];
        $value = isset($args['value'][$field_name]) ? $args['value'][$field_name] : '';

        echo '<input value="' . \esc_attr($field_name) . '" class="regular-text ltr" type="text" readonly />';
        echo ' &#x203A; ';
        echo '<input name="' . \esc_attr($args['option_name']) . '['
            . \esc_attr($args['name']) . '][' . \esc_attr($field_name) . ']" value="'
            . \esc_attr($value) . '" class="regular-text ltr" placeholder="' . \esc_attr(__('Translated string', 'content-egg')) . '"  type="text"/>';
    }

    public function render_translation_block($args)
    {
        if (!$args['value'])
            $args['value'] = array();

        foreach (array_keys(self::frontendTexts()) as $str)
        {
            echo '<div style="padding-bottom: 5px;">';
            $args['_field_name'] = $str;
            $this->render_translation_row($args);
            echo '</div>';
        }
        if ($args['description'])
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    public function frontendTextsSanitize($values)
    {
        foreach ($values as $k => $value)
        {
            $values[$k] = trim(\sanitize_text_field($value));
        }

        return $values;
    }

    public function render_merchant_line($args)
    {
        $i = isset($args['_field']) ? $args['_field'] : 0;
        $name = isset($args['value'][$i]['name']) ? $args['value'][$i]['name'] : '';
        $value = isset($args['value'][$i]['shop_info']) ? $args['value'][$i]['shop_info'] : '';
        $value2 = isset($args['value'][$i]['shop_coupons']) ? $args['value'][$i]['shop_coupons'] : '';

        echo '<input style="margin-bottom: 5px;" name="' . \esc_attr($args['option_name']) . '['
            . \esc_attr($args['name']) . '][' . esc_attr($i) . '][name]" value="'
            . \esc_attr($name) . '" class="regular-text ltr" placeholder="' . \esc_attr(__('Domain name', 'content-egg')) . '"  type="text"/>';

        $settings = array(
            'textarea_name' => \esc_attr($args['option_name']) . '[' . \esc_attr($args['name']) . '][' . esc_attr($i) . '][shop_info]',
            'textarea_rows' => 7,

        );
        echo '<h4>Shop info:</h4>';

        \wp_editor($value, 'shop_info_area' . $i, $settings);

        $settings = array(
            'textarea_name' => \esc_attr($args['option_name']) . '[' . \esc_attr($args['name']) . '][' . esc_attr($i) . '][shop_coupons]',
            'textarea_rows' => 7,

        );
        echo '<h4>Shop coupons:</h4>';
        \wp_editor($value2, 'shop_coupons_area' . $i, $settings);

        echo '<br><hr>';
    }

    public function render_merchants_block($args)
    {
        if (is_array($args['value']))
            $total = count($args['value']) + 1;
        else
            $total = 1;

        for ($i = 0; $i < $total; $i++)
        {
            echo '<div style="padding-bottom: 20px;">';
            $args['_field'] = $i;
            $this->render_merchant_line($args);
            echo '</div>';
        }
        if ($args['description'])
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    public function formatHtmlField($value)
    {
        return \wp_kses_post($value);
    }

    public function formatMerchantFields($values)
    {
        $results = array();

        foreach ($values as $k => $value)
        {
            $name = strtolower(trim(\sanitize_text_field($value['name'])));
            if ($host = TextHelper::getHostName($values[$k]['name']))
                $name = $host;

            if (!$name)
                continue;

            if (in_array($name, array_column($results, 'name')))
                continue;

            $shop_info = \wp_kses_post($value['shop_info']);
            $shop_coupons = \wp_kses_post($value['shop_coupons']);

            $result = array('name' => $name, 'shop_info' => $shop_info, 'shop_coupons' => $shop_coupons);
            $results[] = $result;
        }

        return $results;
    }

    public static function isShopInfoAvailable()
    {
        $merchants = GeneralConfig::getInstance()->option('merchants');
        if (!$merchants)
            return false;

        foreach ($merchants as $merchant)
        {
            if ($merchant['shop_info'])
                return true;
        }

        return false;
    }

    public function getButtonVariantList()
    {
        $keys = self::getBtnVariants();
        $values = array_map(function ($item)
        {
            return ucwords(str_replace('-', ' ', $item));
        }, $keys);
        return array_combine($keys, $values);
    }

    public function openRouterModelsFilter($value)
    {
        return TextHelper::commaList($value);
    }

    public function importOptions(array $options): array
    {
        $optionName = $this->option_name();

        if (empty($options[$optionName]) || ! is_array($options[$optionName]))
        {
            return [];
        }

        $incoming = $options[$optionName];
        $current  = $this->getOptionValues();

        $filtered = array_intersect_key($incoming, $current);
        $merged   = array_merge($current, $filtered);

        if (update_option($optionName, $merged))
        {
            return [$optionName => $merged];
        }

        return [];
    }

    public function processRedirectPrefix($value): string
    {
        $old = (string) $this->option('redirect_prefix');
        $new = TextHelper::clear((string) $value);

        if ($new === $old)
        {
            return $new;
        }

        $prefix = ($new !== '') ? $new : LocalRedirector::DEFAULT_PREFIX;

        // Flush once to apply the new /{prefix}/{slug} rule
        LocalRedirector::flushRules($prefix);

        return $new;
    }

    public function processClicksTrackDirect($value)
    {
        $old = (string) $this->option('clicks_track_direct');
        $new = (string) $value;

        if ($new === $old)
        {
            return $new;
        }

        if ($new === 'enabled')
        {
            // Backfill all posts for modules with local redirect OFF (direct mode)
            if (!wp_next_scheduled('cegg_link_index_backfill_once', ['direct', null]))
            {
                wp_schedule_single_event(time() + 5, 'cegg_link_index_backfill_once', ['direct', null]);
            }
        }
        else
        {
            // Disabled: cancel any pending direct-mode backfills
            while ($ts = wp_next_scheduled('cegg_link_index_backfill_once', ['direct', null]))
            {
                wp_unschedule_event($ts, 'cegg_link_index_backfill_once', ['direct', null]);
            }
        }

        return $new;
    }
}
