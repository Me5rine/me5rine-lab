<?php

namespace ContentEgg\application\components\ai;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\TextHelper;



defined('\ABSPATH') || exit;

/**
 * SmartGroupsPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class SmartGroupsPrompt extends Prompt
{
    protected $products;
    protected $data;

    public function setData(array $data)
    {
        $this->data = $data;
    }

    private function categorize($instruction = '')
    {
        $prompt = "Below is a list of products in JSON format:";
        $prompt .= "\n```json\n{%product_data_json%}\n```\n";
        $prompt .= "\n" . $instruction;
        $prompt .= "\nUse concise names for the product groups. Replace the [ADD GROUP] placeholder with the appropriate group names. Do not change the source JSON structure.";
        $prompt .= " Provide a RFC8259 compliant JSON response. Return only the categorized JSON response without any additional text or explanations.";

        $new_data = ContentHelper::prepareJsonResponse($this->query($prompt));
        $this->applayGroups($new_data);
        return $this->data;
    }

    public function categorizeAuto()
    {
        $instruction = "Categorize each product and add the appropriate product group to each item.";
        return $this->categorize($instruction);
    }

    public function categorizePriceComparison()
    {
        $instruction = "Group identical products offered by different merchants into unique product groups. Consolidate all offers referring to the same product under a single group. When possible, use the product's model name as the group name.";
        return $this->categorize($instruction);
    }

    public function categorizeProductCategory()
    {
        $instruction = "Categorize each product by Google Shopping categories and assign the appropriate product group to each item.";
        return $this->categorize($instruction);
    }

    public function categorizeFeatures()
    {
        $instruction = "Categorize each product by its features and assign the appropriate product group to each item.";
        return $this->categorize($instruction);
    }

    public function categorizeBrand()
    {
        $instruction = "Categorize each product by its brand or manufacturer and use these as group names.";
        return $this->categorize($instruction);
    }

    public function categorizePriceRange()
    {
        $instruction = "Categorize each product in the given JSON list by its price range and use these as group names.";
        return $this->categorize($instruction);
    }

    public function categorizeUsage()
    {
        $instruction = "Categorize each product in the given JSON list by its usage and use these as group names.";
        return $this->categorize($instruction);
    }

    public function categorizeAgeGroup()
    {
        $instruction = "Categorize each product in the given JSON list by its 'age group' (e.g., Kids, Teens, Adults, Seniors) and use these as group names.";
        return $this->categorize($instruction);
    }

    public function categorizeMaterialIngredients()
    {
        $instruction = "Categorize each product in the given JSON list by its material or ingredients (e.g., Wood, Metal, Plastic, Organic, Synthetic and so on) and use these as group names.";
        return $this->categorize($instruction);
    }

    public function categorizeSizeVolume()
    {
        $instruction = "Categorize each product in the given JSON list by its size or volume and use these as group names.";
        return $this->categorize($instruction);
    }

    protected function prepareParams(array $params, $prompt = '')
    {
        if (!$this->data)
            return $params;
        if (!isset($params['product_data_json']))
        {

            $products = array();
            foreach ($this->data as $module_id => $module_data)
            {
                if (!ModuleManager::getInstance()->moduleExists($module_id) || !ModuleManager::getInstance()->isModuleActive($module_id))
                    continue;

                $module = ModuleManager::getInstance()->factory($module_id);
                if (!$module->isAffiliateParser() || !$module->isProductParser())
                    continue;

                foreach ($module_data as $item)
                {
                    $products[] = array(
                        //'unique_id' => $item['unique_id'],
                        'title' => $item['title'],
                        'ean' => $item['ean'],
                        //'sku' => $item['sku'],
                        'price' => $item['price'],
                        'currencyCode' => $item['currencyCode'],
                        'domain' => $item['domain'],
                        //'category' => $item['category'],
                        //'manufacturer' => $item['manufacturer'],
                        'group' => '[ADD GROUP]',
                    );
                }
            }

            $data = array();
            $data['products'] = $products;
            $json = json_encode($data, JSON_PRETTY_PRINT);
            $params['product_data_json'] = $json;
        }

        return $params;
    }

    private function applayGroups(array $new_data)
    {
        if (isset($new_data['products']))
            $new_data = $new_data['products'];

        if (!is_array($new_data) || !isset($new_data[0]['title']))
            throw new \Exception('There was a problem with formatting the data. Please try again.');

        foreach ($this->data as $module_id => $module_data)
        {
            foreach ($module_data as $i => $item)
            {
                foreach ($new_data as $nd)
                {
                    if (isset($nd['title']) && $nd['title'] == $item['title'])
                    {
                        if (isset($nd['group']))
                            $this->data[$module_id][$i]['group'] = TextHelper::truncate(\sanitize_text_field($nd['group']));

                        break;
                    }
                }
            }
        }
    }
}
