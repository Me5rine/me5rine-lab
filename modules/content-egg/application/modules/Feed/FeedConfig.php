<?php

namespace ContentEgg\application\modules\Feed;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\AffiliateFeedParserModuleConfig;
use ContentEgg\application\helpers\CurrencyHelper;
use ContentEgg\application\helpers\TextHelper;

/**
 * FeedConfig class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class FeedConfig extends AffiliateFeedParserModuleConfig
{

    public function options()
    {
        $currencies = CurrencyHelper::getCurrenciesList();

        $has_ai_api_key = (bool) GeneralConfig::getOption('system_ai_key', '', 'contentegg_options');

        $ai_description = __('Automatically maps feed fields using AI suggestions. You can manually adjust the mapping after it’s generated.', 'content-egg');

        if (empty($has_ai_api_key))
        {
            $ai_description .= ' <span class="description warning" style="color: #ff5400;">' . sprintf(
                __('Warning: OpenAI API key is not set. AI features will not work until it is configured. You can set it under %s.', 'content-egg'),
                'Settings &gt; AI &gt; OpenAI API Key'
            ) . '</span>';
        }

        $zip_notice = '';

        if (is_admin() && !class_exists('\ZipArchive'))
        {
            $zip_notice = ' ' . sprintf(
                '<p class="description">%s</p>',
                sprintf(
                    '⚠ ' . __('ZIP support is not enabled on your server. For better performance and support for large feed files, please enable the %s PHP extension.', 'content-egg'),
                    '<a href="https://www.php.net/manual/en/zip.installation.php" target="_blank" rel="noopener noreferrer">ZipArchive</a>'
                )
            );
        }

        $options = array(
            'feed_name' => array(
                'title' => __('Feed name', 'content-egg') . ' <span class="cegg_required">*</span>',
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                    '\sanitize_text_field',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'required'),
                        'message' => sprintf(__('The field "%s" can not be empty.', 'content-egg'), __('Feed name', 'content-egg')),
                    ),
                    array(
                        'call' => array($this, 'saveModuleName'),
                        'type' => 'filter',
                    ),
                ),
            ),
            'feed_url' => [
                'title'       => sprintf(
                    '%s <span class="cegg_required">*</span>',
                    __('Feed URL', 'content-egg')
                ),
                'description' => __('Enter the URL to your product feed file (CSV, XML, or JSON). CSV is recommended if available.', 'content-egg'),
                'callback'    => [$this, 'render_input'],
                'default'     => '',
                'validator'   => [
                    'trim',
                    [
                        'call'    => [\ContentEgg\application\helpers\FormValidator::class, 'required'],
                        'when'    => 'is_active',
                        'message' => __('Please provide the feed URL.', 'content-egg'),
                    ],
                    [
                        'call'    => [$this, 'validateFeedUrl'],
                        'when'    => 'is_active',
                        'message' => __('Please enter a valid feed URL. Supported schemes: http://, https://, ftp://, ftps://.', 'content-egg'),
                    ],

                ],
            ],
            'feed_format' => array(
                'title' => __('Feed format', 'content-egg') . '**',
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'csv' => __('CSV', 'content-egg'),
                    'xml' => 'XML',
                    'json' => 'JSON',
                ),
                'default' => 'csv',
            ),
            'archive_format' => array(
                'title'            => __('Archive format', 'content-egg') . '**',
                'description'      => $zip_notice,
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'none' => __('None', 'content-egg'),
                    'zip'  => 'ZIP',
                    'gz'   => __('GZIP (.gz)', 'content-egg'),
                ),
                'default' => 'none',
            ),
            'encoding' => array(
                'title' => __('Feed encoding', 'content-egg') .  '**',
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'UTF-8' => 'UTF-8',
                    'ISO-8859-1' => 'ISO-8859-1',
                ),
                'default' => 'UTF-8',
            ),
            'currency' => array(
                'title' => __('Default currency', 'content-egg') .  '**',
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array_combine($currencies, $currencies),
                'default' => 'USD',
            ),
            'domain' => array(
                'title' => __('Default merchant domain', 'content-egg') . ' <span class="cegg_required">*</span>',
                'description' => __('Enter the default domain name of the merchant (e.g., example.com).', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'required'),
                        'when' => 'is_active',
                        'message' => sprintf(__('The field "%s" can not be empty.', 'content-egg'), 'Default merchant domain'),
                    ),
                    array(
                        'call' => array($this, 'sanitizeDomain'),
                        'type' => 'filter',
                    ),
                ),
            ),
            'auto_mapping' => [
                'title'            => __('AI Auto-Mapping', 'content-egg') . '**',
                'description'      => $ai_description,
                'callback'         => [$this, 'render_dropdown'],
                'dropdown_options' => [
                    'enabled'  => __('Enabled', 'content-egg'),
                    'disabled' => __('Disabled', 'content-egg'),
                ],
                'is_pro' => false,
                'default' => 'enabled',
            ],
            'mapping' => array(
                'title' => __('Field mapping', 'content-egg') . '**',
                'description' => __('Map your feed columns to the appropriate Content Egg product fields, or enable AI Auto-Mapping to do it automatically.', 'content-egg'),
                'help_url' => 'https://ce-docs.keywordrush.com/modules/feed-modules/field-mapping',
                'callback' => array($this, 'render_mapping_block'),
                'validator' => array(
                    array(
                        'call' => array($this, 'mappingSanitize'),
                        'type' => 'filter',
                    ),
                    array(
                        'call' => array($this, 'mappingValidate'),
                        'when' => 'is_active',
                        'message' => __('Please fill out all required mapping fields.', 'content-egg'),
                    ),
                ),
            ),
            'sync_interval' => [
                'title'            => __('Feed sync interval', 'content-egg') . ' **',
                'description'      => __('Sets how frequently the product feed is synced with the local database.', 'content-egg'),
                'callback'         => [$this, 'render_dropdown'],
                'dropdown_options' => [
                    '3600.'    => __('Every 1 hour',             'content-egg'),
                    '10800.'   => __('Every 3 hours',            'content-egg '),
                    '21600.'   => __('Every 6 hours',            'content-egg'),
                    '43200.'   => __('Every 12 hours',           'content-egg ') . ' ' . __('(default)', 'content-egg'),
                    '86400.'   => __('Every 1 day',              'content-egg'),
                    '259200.'  => __('Every 3 days',             'content-egg '),
                    '604800.'  => __('Every 1 week',             'content-egg'),
                ],
                'default' => '43200.',
            ],
            'tracking_params' => array(
                'title'       => __('SubID tracking parameters', 'content-egg') . '**',
                'description' => sprintf(
                    __('Append one or more query parameters for affiliate SubID tracking (e.g., clickref, subId1). Use name=value pairs separated by "&". Dynamic placeholders are supported (e.g., {{post_id}}, {{item_unique_id}}). Examples: <code>clickref={{post_id}}</code> (Awin), <code>subId1=mysite1&subId2={{post_id}}</code> (Impact). <a href="%s" target="_blank">See placeholder guide</a>.', 'content-egg'),
                    'https://ce-docs.keywordrush.com/features/subid-tracking'
                ),
                'callback'    => array($this, 'render_input'),
                'default'     => '',
                'validator'   => array('trim'),
            ),

            'deeplink' => array(
                'title' => __('Deeplink', 'content-egg'),
                'description' => __('Enable this option only if your feed does not include affiliate links.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                ),
                'section' => 'default',
            ),
            'search_type' => array(
                'title' => __('Search type', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'full' => 'Full text search (relevance)',
                    'strict' => 'Full text search (strict mode)',
                    'exact' => 'Exact phrase search',
                ),
                'default' => 'full',
            ),
            'in_stock' => [
                'title'       => __('In-Stock Products', 'content-egg'),
                'description' =>  __('Only Import In-Stock Products', 'content-egg') .
                    '<p class="description">' . __('Make sure the "Availability" or "In Stock" fields are correctly mapped in your feed settings.', 'content-egg')  . '</p>',
                'callback'    => [$this, 'render_checkbox'],
                'default'     => true,
                'section'     => 'default',
            ],
            'csv_delimiter' => [
                'title'            => __('CSV Delimiter', 'content-egg'),
                'description'      => __('Overrides automatic detection for CSV feeds. Leave as "Auto-detect" unless you know the exact delimiter used by your file.', 'content-egg'),
                'callback'         => [$this, 'render_dropdown'],
                'dropdown_options' => [
                    'auto' => __('Auto-detect (recommended)', 'content-egg'),
                    "\t"   => __('Tab (\\t)', 'content-egg'),
                    ';'    => __('Semicolon (;)', 'content-egg'),
                    ','    => __('Comma (,)', 'content-egg'),
                    '|'    => __('Pipe (|)', 'content-egg'),
                ],
                'default'          => 'auto',
            ],
            'csv_enclosure' => [
                'title'            => __('CSV Enclosure', 'content-egg'),
                'description'      => __('Overrides automatic detection of the text qualifier. Choose this if your CSV uses a specific quote character around fields.', 'content-egg'),
                'callback'         => [$this, 'render_dropdown'],
                'dropdown_options' => [
                    'auto' => __('Auto-detect (recommended)', 'content-egg'),
                    '"'    => __('Double quote (")', 'content-egg'),
                    "'"    => __("Single quote (')", 'content-egg'),
                    'none' => __('None (unquoted fields)', 'content-egg'),
                ],
                'default'          => 'auto',
            ],

            'xml_processor' => array(
                'title'       => __('XML Processor', 'content-egg'),
                'description' => __(
                    'Choose which XML parser to use. XmlStringStreamer is the default and works for most feeds. '
                        . 'If you experience “Premature end of data” or similar XML errors, try switching to XmlReader.',
                    'content-egg'
                ),
                'callback'    => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'XmlStringStreamer' => __('XmlStringStreamer (default, faster)', 'content-egg'),
                    'XmlReader'         => __('XmlReader (safer for nested XML)', 'content-egg'),
                ),
                'default'     => 'XmlStringStreamer',
            ),

        );
        $options = array_merge(parent::options(), $options);

        return self::moveRequiredUp($options);
    }

    public function render_mapping_row($args)
    {
        $field_name = $args['_field_name'];
        $value = isset($args['value'][$field_name]) ? $args['value'][$field_name] : '';

        $display_name = $field_name;

        if ($field_name == 'product node')
        {
            $display_name .= ' ' . __('(required for XML/JSON feed only)', 'content-egg');
        }
        elseif ($this->isMappingFieldRequared($field_name))
        {
            $display_name .= ' ' . __('(required)', 'content-egg');
        }
        else
        {
            $display_name .= ' ' . __('(optional)', 'content-egg');
        }

        echo '<input value="' . \esc_attr($display_name) . '" class="regular-text ltr" type="text" readonly />';
        echo ' &#x203A; ';
        echo '<input name="' . \esc_attr($args['option_name']) . '['
            . \esc_attr($args['name']) . '][' . \esc_attr($field_name) . ']" value="'
            . \esc_attr($value) . '" class="regular-text ltr" placeholder="' . \esc_attr(__('In your feed', 'content-egg')) . '"  type="text"/>';
    }

    public function render_mapping_block($args)
    {
        if (!$args['value'])
            $args['value'] = array();

        foreach (array_keys($this->mappingFields()) as $str)
        {
            echo '<div style="padding-bottom: 5px;">';
            $args['_field_name'] = $str;
            $this->render_mapping_row($args);
            echo '</div>';
        }

        if ($args['description'])
        {
            echo '<p class="description">';
            echo esc_html($args['description']);
            $this->render_help_icon($args);
            echo '</p>';
        }
    }

    public function mappingFields()
    {
        $fields = array(
            'product node' => false,
            'id' => true,
            'title' => true,
            'description' => true,
            'affiliate link' => true,
            'image ​​link' => true,
            'price' => true,
            'sale price' => false,
            'currency' => false,
            'availability' => false,
            'is in stock' => false,
            'direct link' => false,
            'additional image link' => false,
            'brand' => false,
            'category' => false,
            'short description' => false,
            'subtitle' => false,
            'isbn' => false,
            'gtin' => false,
            'shipping cost' => false,
            'attributes' => false,
        );

        return $fields;
    }

    public function isMappingFieldRequared($field)
    {
        $fields = $this->mappingFields();
        if (isset($fields[$field]) && $fields[$field])
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function mappingSanitize($values)
    {
        foreach ($values as $k => $value)
        {
            $values[$k] = trim(\sanitize_text_field($value));
        }

        return $values;
    }

    public function mappingValidate($values)
    {
        if ($this->get_submitted_value('auto_mapping') == 'enabled')
        {
            return true;
        }

        return $this->isAllRequiredFieldsFilled($values);
    }

    public function isAllRequiredFieldsFilled(array $mapping): bool
    {
        foreach ($this->mappingFields() as $field => $isRequired)
        {
            if ($isRequired)
            {
                if (!isset($mapping[$field]) || $mapping[$field] === '')
                {
                    return false;
                }
            }
        }

        return true;
    }

    public  function missingRequired(array $values): array
    {
        $missing = [];

        foreach ($this->mappingFields() as $field => $isRequired)
        {
            if ($isRequired && empty($values[$field]))
            {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function sanitizeDomain($value)
    {
        $value = trim(\sanitize_text_field($value));
        if ($host = TextHelper::getHostName($value))
        {
            $value = $host;
        }

        $value = strtolower($value);
        $value = str_replace('www.', '', $value);
        $value = trim($value, "/");

        return $value;
    }

    /**
     * Validate feed URL for http(s) and ftp(s).
     */
    public function validateFeedUrl($value)
    {
        if (!is_string($value) || $value === '')
        {
            return false;
        }

        // Basic URL validation (supports ftp, ftps too)
        if (filter_var($value, FILTER_VALIDATE_URL) === false)
        {
            return false;
        }

        $parts = parse_url($value);
        if ($parts === false)
        {
            return false;
        }

        $scheme  = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        $host    = isset($parts['host']) ? (string) $parts['host'] : '';
        $allowed = ['http', 'https', 'ftp', 'ftps'];

        if ($scheme === '' || $host === '' || !in_array($scheme, $allowed, true))
        {
            return false;
        }

        // For FTP/FTPS require a file path (i.e., path exists and does not end with '/')
        if ($scheme === 'ftp' || $scheme === 'ftps')
        {
            $path = isset($parts['path']) ? (string) $parts['path'] : '';
            if ($path === '' || substr($path, -1) === '/')
            {
                // e.g., reject ftp://host/ (directory only)
                return false;
            }
        }

        if (isset($parts['port']))
        {
            $port = (int) $parts['port'];
            if ($port < 1 || $port > 65535)
            {
                return false;
            }
        }

        return true;
    }
}
