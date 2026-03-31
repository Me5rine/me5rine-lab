<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use \ContentEgg\application\Plugin;
use \ContentEgg\application\helpers\TextHelper;
use \ContentEgg\application\admin\AeIntegrationConfig;
use \ContentEgg\application\components\LManager;
use \ContentEgg\application\helpers\ArrayHelper;

/**
 * ModuleManager class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ModuleManager
{

    const DEFAULT_MODULES_DIR = 'application/modules';
    const AE_MODULES_PREFIX = 'AE';
    const FEED_MODULES_PREFIX = 'Feed';
    const MAX_NUM_FEED_MODULES = 50;

    private static $modules = array();
    private static $active_modules = array();
    private static $configs = array();
    private static $instance = null;
    // hidden system modules
    private static $hidden_modules = array('AE', 'Feed');
    private static $custom_modules = array();

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->initModules();
    }

    public function adminInit()
    {
        foreach ($this->getConfigurableModules() as $module)
        {
            $config = self::configFactory($module->getId());
            $config->adminInit();
        }
    }

    /**
     *  Highlight the proper submenu item
     */
    public function highlightAdminMenu($parent_file)
    {
        global $plugin_page;

        if (substr($plugin_page, 0, strlen(Plugin::slug())) !== Plugin::slug())
        {
            return $parent_file;
        }

        if ($parent_file == 'options.php' && $plugin_page == 'content-egg-settings-affiliate')
        {
            $plugin_page = 'content-egg-settings-affiliate';
        }

        return $parent_file;
    }

    private function initModules()
    {
        $modules_ids = $this->scanForDefaultModules();

        if (defined('\CONTENT_EGG_CUSTOM_MODULES') && \CONTENT_EGG_CUSTOM_MODULES)
            $modules_ids = array_merge($modules_ids, $this->scanForCustomModules());

        $feed_modules_ids = $this->getFeedModules();
        $ae_modules_ids = $this->getAffEggModules();
        $cloned_modules_ids = $this->getClonedModules();

        $modules_ids = array_merge($modules_ids, $feed_modules_ids, $ae_modules_ids, $cloned_modules_ids);
        $modules_ids = \apply_filters('content_egg_modules', $modules_ids);
        $d = \get_option(base64_decode('Y2VnZ19zeXNfZGVhZGxpbmU='), 0);

        if ($d && $d < time())
            $modules_ids = array('AffilinetCoupons', 'GoogleImages', 'Viglink', 'Offer', 'Pixabay', 'SkimlinksCoupons', 'RelatedKeywords', 'RssFetcher', 'Youtube', 'Coupon', 'CjLinks', 'Feed__1', 'Feed__2', 'Feed__3');

        // create modules
        foreach ($modules_ids as $module_id)
        {
            // create module
            self::factory($module_id);
        }

        uasort(self::$modules, function ($a, $b)
        {
            return strcmp($a->getName(), $b->getName());
        });

        self::$modules = \apply_filters('content_egg_modules_init', self::$modules);

        // fill active modules
        foreach (self::$modules as $module)
        {
            if ($module->isActive())
            {
                self::$active_modules[$module->getId()] = $module;
            }
        }
    }

    private function scanForDefaultModules()
    {
        $path = \ContentEgg\PLUGIN_PATH . self::DEFAULT_MODULES_DIR . DIRECTORY_SEPARATOR;

        return $this->scanForModules($path);
    }

    private function scanForCustomModules()
    {
        $path = \WP_CONTENT_DIR . DIRECTORY_SEPARATOR . \ContentEgg\CUSTOM_MODULES_DIR . DIRECTORY_SEPARATOR;
        if (!is_dir($path))
        {
            return array();
        }

        self::$custom_modules = $this->scanForModules($path);

        return self::$custom_modules;
    }

    private function scanForModules($path)
    {
        $folder_handle = @opendir($path);
        if ($folder_handle === false)
        {
            return;
        }

        $founded_modules = array();

        while (($m_dir = readdir($folder_handle)) !== false)
        {
            if ($m_dir == '.' || $m_dir == '..')
            {
                continue;
            }
            $module_path = $path . $m_dir;
            if (!is_dir($module_path))
            {
                continue;
            }

            $module_id = $m_dir;
            if (in_array($module_id, self::$hidden_modules))
            {
                continue;
            }

            $founded_modules[] = TextHelper::clear($module_id);
        }
        closedir($folder_handle);

        return $founded_modules;
    }

    public function getAffEggModules()
    {
        if (!AeIntegrationConfig::isAEIntegrationPosible())
        {
            return array();
        }

        $module_ids = AeIntegrationConfig::getInstance()->option('modules');
        if (!$module_ids)
        {
            return array();
        }
        $result = array();
        foreach ($module_ids as $module_id)
        {
            $result[] = self::AE_MODULES_PREFIX . '__' . $module_id;
        }

        return $result;
    }

    public function getFeedModules()
    {
        if (Plugin::isActivated() && LManager::isNulled())
            return array();

        global $wpdb;

        $like = $wpdb->esc_like('content-egg_Feed__') . '%';
        $names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name
         FROM {$wpdb->options}
         WHERE option_name LIKE %s
           AND option_value <> ''",
                $like
            )
        );

        $result = [];
        $prefixMod = self::FEED_MODULES_PREFIX . '__';

        foreach ($names as $name)
        {
            // extract the numeric ID at the end
            if (preg_match('/__(\d+)$/', $name, $m))
            {
                $result[] = $prefixMod . $m[1];
            }
        }

        if (Plugin::isFree())
        {
            $max = 3;
        }
        else
        {
            $max = self::MAX_NUM_FEED_MODULES;
        }

        if (count($result) < $max)
        {
            // Extract the numeric IDs from $result
            $usedIds = array_map(function ($val) use ($prefixMod)
            {
                return (int) str_replace($prefixMod, '', $val);
            }, $result);

            sort($usedIds);

            // Find first gap from 1..$max
            $num = null;
            for ($i = 1; $i <= $max; $i++)
            {
                if (!in_array($i, $usedIds, true))
                {
                    $num = $i;
                    break;
                }
            }

            // If no gap found, take max+1 capped at $max
            if ($num === null)
            {
                $num = min(max($usedIds) + 1, $max);
            }

            $result[] = $prefixMod . $num;
        }

        return $result;
    }

    public function getClonedModules()
    {
        $clones = ModuleCloneManager::getClonedModules();
        $result = array();
        foreach ($clones as $clone)
        {
            $result[] = $clone['clone_id'];
        }

        return $result;
    }

    public static function isCustomModule($module_id)
    {
        if (in_array($module_id, self::$custom_modules))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function factory($module_id)
    {
        if (!isset(self::$modules[$module_id]))
        {
            $path_prefix = Module::getPathId($module_id);
            if (self::isCustomModule($module_id))
                $module_class = "\\ContentEggCustomModule\\" . $path_prefix . "\\" . $path_prefix . 'Module';
            else
                $module_class = "\\ContentEgg\\application\\modules\\" . $path_prefix . "\\" . $path_prefix . 'Module';

            if (! class_exists($module_class, true))
            {
                throw new \Exception('Unable to load module class: "' . esc_html($module_class) . '".');
            }
            try
            {
                $module = new $module_class($module_id);
            }
            catch (\Exception $e)
            {
                return false;
            }

            if (! ($module instanceof \ContentEgg\application\components\Module))
            {
                throw new \Exception('The module "' . esc_html($module_id) . '" must inherit from Module.');
            }
            if (Plugin::isFree() && !$module->isFree())
                return false;

            self::$modules[$module_id] = $module;
        }

        return self::$modules[$module_id];
    }

    public static function parserFactory($module_id)
    {
        $module = self::factory($module_id);
        if (! ($module instanceof \ContentEgg\application\components\ParserModule))
        {
            throw new \Exception('The parser module "' . esc_html($module_id) . '" must inherit from ParserModule.');
        }

        return $module;
    }

    public static function configFactory($module_id)
    {
        if (!isset(self::$configs[$module_id]))
        {
            $path_prefix = Module::getPathId($module_id);

            if (self::isCustomModule($module_id))
            {
                $config_class = "\\ContentEggCustomModule\\" . $path_prefix . "\\" . $path_prefix . 'Config';
            }
            else
            {
                $config_class = "\\ContentEgg\\application\\modules\\" . $path_prefix . "\\" . $path_prefix . 'Config';
            }

            if (class_exists($config_class, true) === false)
            {
                throw new \Exception('Unable to load module config class: "' . esc_html($config_class) . '".');
            }

            $config = $config_class::getInstance($module_id);

            if (self::factory($module_id)->isParser())
            {
                if (!($config instanceof \ContentEgg\application\components\ParserModuleConfig))
                {
                    throw new \Exception('The parser module config "' . esc_html($config_class) . '" must inherit from ParserModuleConfig.');
                }
            }
            else
            {
                if (!($config instanceof \ContentEgg\application\components\ModuleConfig))
                {
                    throw new \Exception('The module config "' . esc_html($config_class) . '" must inherit from ModuleConfig.');
                }
            }

            self::$configs[$module_id] = $config;
        }

        return self::$configs[$module_id];
    }

    public function getModules($only_active = false)
    {
        if ($only_active)
        {
            return self::$active_modules;
        }
        else
        {
            return self::$modules;
        }
    }

    public function getModulesIdList($only_active = false)
    {
        return array_keys($this->getModules($only_active));
    }

    public function getAffiliateParsersList($only_active = true, $no_coupons = true, $sort_by_priority = false)
    {
        $modules = $this->getAffiliateParsers($only_active);
        $list = [];

        foreach ($modules as $module)
        {
            $module_id = $module->getId();

            if ($no_coupons && stripos($module_id, 'coupon') !== false)
            {
                continue;
            }

            $list[$module_id] = [
                'name'     => $module->getName(),
                'priority' => $sort_by_priority ? (int) $module->getConfigInstance()->option('priority') : 0
            ];
        }

        if ($sort_by_priority)
        {
            uasort($list, function ($a, $b)
            {
                return $a['priority'] <=> $b['priority'];
            });
        }

        return array_map(fn($item) => $item['name'], $list);
    }

    public function getParserModules($only_active = false)
    {
        $modules = $this->getModules($only_active);
        $parsers = array();
        foreach ($modules as $module)
        {
            if ($module->isParser())
            {
                $parsers[$module->getId()] = $module;
            }
        }

        return $parsers;
    }

    public function getParsers($only_active = false)
    {
        $modules = $this->getModules($only_active);
        $parsers = array();
        foreach ($modules as $module)
        {
            if (!$module->isParser())
            {
                continue;
            }

            $parsers[$module->getId()] = $module;
        }

        return $parsers;
    }

    public function getAffiliateParsers($only_active = false, $only_product = false)
    {
        $modules = $this->getModules($only_active);
        $parsers = array();
        foreach ($modules as $module)
        {
            if ($only_product && strstr($module->getId(), 'Coupons'))
            {
                continue;
            }

            if (!$module->isAffiliateParser())
            {
                continue;
            }

            $parsers[$module->getId()] = $module;
        }

        return $parsers;
    }

    public function getParserModulesIdList($only_active = false, $by_priority = false)
    {
        $modules = $this->getParserModules($only_active);

        if (!$by_priority)
            return array_keys($modules);

        $module_priorities = array();
        foreach ($modules as $module)
        {
            $module_priorities[$module->getId()] = $module->config('priority');
        }

        $module_priorities = ArrayHelper::asortStable($module_priorities);
        return array_keys($module_priorities);
    }

    public function getParserModulesByTypes($types, $only_active = true)
    {
        if ($types == 'ALL')
        {
            $types = null;
        }

        if ($types && !is_array($types))
        {
            $types = array($types);
        }
        $res = array();
        foreach ($this->getParserModules($only_active) as $module)
        {
            if ($types && !in_array($module->getParserType(), $types))
            {
                continue;
            }
            $res[$module->getId()] = $module;
        }

        return $res;
    }

    public function getParserModuleIdsByTypes($types, $only_active = true)
    {
        return array_keys($this->getParserModulesByTypes($types, $only_active));
    }

    public function getConfigurableModules($active_only = false)
    {
        $result = array();

        foreach ($this->getModules($active_only) as $module)
        {
            if ($module->isConfigurable())
            {
                $result[] = $module;
            }
        }

        return $result;
    }

    public function moduleExists($module_id)
    {
        if (isset(self::$modules[$module_id]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isModuleActive($module_id)
    {
        if (isset(self::$active_modules[$module_id]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getOptionsList()
    {
        $options = array();
        foreach ($this->getConfigurableModules() as $module)
        {
            $config = $module->getConfigInstance();
            $options[$config->option_name()] = $config->getOptionValues();
        }

        return $options;
    }

    public function importOptions(array $options): array
    {
        $existingOptions = $this->getOptionsList();
        $importedOptions = [];

        foreach ($options as $optionName => $incomingValues)
        {
            if (!array_key_exists($optionName, $existingOptions))
            {
                continue;
            }

            $currentValues = $existingOptions[$optionName];

            // Merge only keys that exist in current options
            foreach ($currentValues as $key => $currentValue)
            {
                if (array_key_exists($key, $incomingValues))
                {
                    $currentValues[$key] = $incomingValues[$key];
                }
            }

            update_option($optionName, $currentValues);
            $importedOptions[$optionName] = $currentValues;
        }

        return $importedOptions;
    }

    public function getItemsUpdateModuleIds()
    {
        $result = array();
        foreach ($this->getAffiliateParsers(true) as $module)
        {
            if (!$module->isItemsUpdateAvailable() || !$module->config('ttl_items'))
            {
                continue;
            }

            if ($module->config('update_mode') == 'cron' || $module->config('update_mode') == 'visit_cron')
            {
                $result[] = $module->getId();
            }
        }

        return $result;
    }

    public function getByKeywordUpdateModuleIds()
    {
        $result = array();
        foreach ($this->getParsers(true) as $module)
        {
            if (!$module->config('ttl'))
            {
                continue;
            }

            if ($module->config('update_mode') == 'cron' || $module->config('update_mode') == 'visit_cron')
            {
                $result[] = $module->getId();
            }
        }

        return $result;
    }

    public function getAffiliteModulesList($only_active = true)
    {
        $results = array();
        $modules = ModuleManager::getInstance()->getAffiliateParsers($only_active);
        foreach ($modules as $module_id => $module)
        {
            $results[$module_id] = $module->getName();
        }

        return $results;
    }

    public function destroyModule($module_id)
    {
        $module = ModuleManager::factory($module_id);

        if (!$module)
            return false;

        if (!$module->isClone() && !$module->isFeedModule())
            return false;

        ContentManager::deleteAllDataForModule($module_id);
        ModuleName::getInstance()->deleteName($module_id);
        \delete_option($module->getConfigInstance()->option_name());

        if ($module->isClone())
        {
            ModuleCloneManager::deleteClone($module_id);
        }

        if ($module->isFeedModule())
        {
            $module->setLastImportDate(0);
            $module->setLastImportError('');

            $model = $module->getProductModel();
            if ($model->isTableExists())
            {
                $model->dropTable();
            }
        }

        if (isset(self::$modules[$module_id]))
            unset(self::$modules[$module_id]);

        if (isset(self::$active_modules[$module_id]))
            unset(self::$active_modules[$module_id]);

        if (isset(self::$configs[$module_id]))
            unset(self::$configs[$module_id]);

        return true;
    }

    public function getModuleNamesByIds(array $module_ids)
    {
        $result = array();
        foreach ($module_ids as $id)
        {
            if ($name = $this->getModuleNameById($id))
            {
                $result[$id] = $name;
            }
        }

        return $result;
    }

    public function getModuleNameById($module_id)
    {
        if (isset(self::$modules[$module_id]))
        {
            return self::$modules[$module_id]->getName();
        }

        return '';
    }

    /**
     * Return metadata for all affiliate parsers.
     */
    public function getAffiliateParsersMeta(
        $onlyActive = true,
        $excludeCoupons = true,
        $sortByPriority = false
    )
    {
        $parsers = $this->getAffiliateParsers($onlyActive);
        $metaList = [];

        foreach ($parsers as $parser)
        {
            $id = $parser->getId();

            if (in_array($id, ['Offer']))
            {
                continue;
            }

            // Skip coupon parsers if requested
            if ($excludeCoupons && stripos($id, 'coupon') !== false || stripos($id, 'CjLinks') !== false)
            {
                continue;
            }

            // Price‐filter support
            $priceMap = $parser->getPriceParamMap();
            $hasPriceFilter = !empty($priceMap);

            // Locale‐filter support
            $localeMap = $parser->getLocaleParamMap();
            $hasLocaleFilter = !empty($localeMap);

            $hasUrlSearch = (bool) $parser->isUrlSearchAllowed();

            // Priority from module config
            $priority = (int) $parser->getConfigInstance()->option('priority');

            // Locale
            $config = $parser->getConfigInstance();
            if (method_exists($config, 'getActiveLocalesList'))
            {
                $locales = $config->getActiveLocalesList();
                $default_locale = $config->option('locale');
            }
            else
            {
                $locales = [];
                $default_locale = '';
            }

            $metaList[$id] = [
                'module_id'         => $id,
                'module_name'       => $parser->getName(),
                'is_price_filter'   => $hasPriceFilter,
                'is_locale_filter'  => $hasLocaleFilter,
                'priority'          => $priority,
                'locales'           => $locales,
                'default_locale'    => $default_locale,
                'has_url_search'    => $hasUrlSearch,
            ];
        }

        if ($sortByPriority)
        {
            uasort($metaList, static function (array $a, array $b): int
            {
                return $a['priority'] <=> $b['priority'];
            });
        }

        return $metaList;
    }

    public function getModulePriority($module_id)
    {
        $module = ModuleManager::factory($module_id);

        if (!$module)
            return 0;

        return (int) $module->getConfigInstance()->option('priority');
    }

    public function getActiveFeedModules()
    {
        $feed_modules = array();
        foreach ($this->getAffiliateParsers(true, true) as $module)
        {
            if ($module->isFeedModule())
            {
                $feed_modules[$module->getId()] = $module;
            }
        }

        return $feed_modules;
    }

    public function getParsersWithRedirects($only_active = true)
    {
        $parsers = array();
        foreach ($this->getAffiliateParsers($only_active) as $module)
        {
            if ((bool)$module->config('set_local_redirect'))
            {
                $parsers[$module->getId()] = $module;
            }
        }

        return $parsers;
    }

    public function getContentModules($only_active = true)
    {
        $modules = $this->getConfigurableModules();
        $results = array();
        foreach ($modules as $module)
        {
            if ($only_active && !$module->isActive())
            {
                continue;
            }

            if ($module->isAffiliateParser())
            {
                continue;
            }

            $results[$module->getId()] = $module;
        }

        return $results;
    }
}
