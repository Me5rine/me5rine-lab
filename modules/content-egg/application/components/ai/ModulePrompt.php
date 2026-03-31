<?php

namespace ContentEgg\application\components\ai;

use ContentEgg\application\helpers\TextHelper;

defined('\ABSPATH') || exit;

/**
 * ModulePrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ModulePrompt extends SystemPrompt
{
    const MAX_FIELD_LENGTH = 250; //chars

    const FIELD_DESCRIPTIONS = [
        'id'            => 'The unique identifier',
        'title'         => 'Product name',
        'description'   => 'Product description',
        'affiliate link' => 'Affiliate URL. If Affiliate URL is not available, use the direct product URL',
        'image link'    => 'Product image URL',
        'price'         => 'Product price. It can include a currency symbol/code or be a numeric value',
        'sale price'    => 'Sale price if applicable. It can include a currency symbol/code or be a numeric value',
        'currency'      => '3‑letter currency code (e.g., USD, EUR)',
        'is in stock'   => 'Boolean stock status. Supported values: 1, true, on, yes, 0, false, off, no.',
        'availability'  => 'Text‑based stock status. Supported values: in stock, out of stock',
        'direct link'   => 'Direct (non‑affiliate) URL to the original product page',
        'additional image link' => 'Additional product image (or images) beyond the main image',
        'brand'         => 'Product brand or manufacturer',
        'gtin'          => 'A Global Trade Item Number — EAN, GTIN‑13 or GTIN‑14',
        'category'      => 'Product category or full category path',
        'shipping cost' => 'Shipping cost',
    ];

    private function suggestFieldsMappingFlat(array $product, array $standardFields): array
    {
        if ($product === [])
        {
            return [];
        }

        // 1️⃣  Build the system+user prompt ------------------------------------
        $prompt  = "You are a data‑integration assistant. Your task is to match *each* platform‑standard\n";
        $prompt .= "product field to the most likely raw key found in the provided <product> JSON.\n\n";
        $prompt .= "Standard field descriptions (to aid your reasoning):\n";
        foreach ($standardFields as $field)
        {
            $desc = self::FIELD_DESCRIPTIONS[strtolower($field)] ?? '';
            $prompt .= "• {$field}: {$desc}\n";
        }
        $prompt .= "\nInstructions:\n";
        $prompt .= "- For **every** standard field, output the name of the raw key that best matches it.\n";
        $prompt .= "- If the product does *not* contain a suitable raw key for a given standard field, output the literal string \"unknown\" for that field.\n";

        // Truncate very long string values so the token count stays reasonable.
        foreach ($product as $key => $value)
        {
            if (is_string($value) && mb_strlen($value) > self::MAX_FIELD_LENGTH)
            {
                $product[$key] = TextHelper::truncate($value, self::MAX_FIELD_LENGTH);
            }
        }

        $prompt .= "\n<product>\n" . json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n</product>\n";

        // 2️⃣  Build an explicit JSON‑Schema for the assistant's answer --------
        $schemaProperties = [];
        foreach ($standardFields as $field)
        {
            $fieldLower       = strtolower($field);
            $extraDescription = self::FIELD_DESCRIPTIONS[$fieldLower] ?? '';
            $schemaProperties[$field] = [
                'type'        => 'string',
                'description' => trim($extraDescription . ' Raw key that maps to \"' . $field . '\", or \"unknown\".'),
            ];
        }

        $schema = [
            'name'   => 'product_data_mapping',
            'schema' => [
                'type'                 => 'object',
                'properties'           => $schemaProperties,
                'required'             => $standardFields,  // every standard field must be present
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];

        $aiOptions = [
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => $schema,
            ],
        ];

        // 3️⃣  Query the LLM ----------------------------------------------------
        $responseJson = $this->query($prompt, [], '', $aiOptions);

        // 4️⃣  Decode & validate ----------------------------------------------
        $decoded = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new \RuntimeException(
                'Invalid JSON from AI: ' . esc_html(wp_strip_all_tags(json_last_error_msg()))
            );
        }

        return $decoded;
    }

    /**
     * Suggest an XPath mapping from raw product-feed XML to platform-standard fields.
     *
     * @param string $productXml      A single <item>…</item> node as raw XML.
     * @param array  $standardFields  List of platform-standard field names.
     *
     * @return array<string,string>   e.g. [ 'price' => 'g:price', 'currency' => 'price/@currency', … ]
     *
     * @throws \RuntimeException      If the LLM returns invalid JSON.
     */
    public function suggestFieldsMappingXml(string $productXml, array $standardFields): array
    {
        // ──────────────── 0️⃣  Guard against empty input ────────────────
        if (trim($productXml) === '')
        {
            return [];
        }

        // ──────────────── 1️⃣  Prettify + truncate XML ────────────────
        $prettyXml = $productXml;          // fallback if DOM parsing fails

        try
        {
            $dom                     = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput       = true;
            $dom->loadXML(
                $productXml,
                LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            // truncate very long text nodes so the prompt stays small
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//*[text()]') as $textNode)
            {
                if (mb_strlen($textNode->nodeValue) > self::MAX_FIELD_LENGTH)
                {
                    $textNode->nodeValue = TextHelper::truncate(
                        $textNode->nodeValue,
                        self::MAX_FIELD_LENGTH
                    );
                }
            }

            // keep only the product node (documentElement)
            $prettyXml = $dom->saveXML($dom->documentElement);
        }
        catch (\Throwable $e)
        {
            // silently fall back to raw XML – better than breaking
        }

        // ──────────────── 2️⃣  Build the prompt ────────────────
        $prompt = <<<PROMPT
You are a data-integration assistant.

Your task: map each **platform-standard product field** to **one XPath expression**
that selects the most relevant element or attribute **inside the single <item> node**
shown below.

──────────────────────  XPath rules  ──────────────────────
1. Context = the <item> node ⇒ **use relative paths only**
     • Element   : g:title          (NOT /item/g:title, NOT .//g:title)
     • Attribute : price/@currency  (NOT /item/price/@currency)

2. Prefer direct-child selectors; use `.//` **only** when the target node is nested
   deeper and no direct child exists.

3. Return the element/attribute itself – do **NOT** append `/text()`.

4. Use namespace prefixes exactly as they appear; they are pre-registered.

5. Output the literal **"unknown"** (lowercase) ** if the node is
   absent**.

────────────────  Platform-standard fields  ───────────────
PROMPT;

        foreach ($standardFields as $field)
        {
            $desc = self::FIELD_DESCRIPTIONS[strtolower($field)] ?? '';
            $prompt .= "• {$field}: {$desc}\n";
        }

        $prompt .= <<<EXAMPLE

──────────────── Example (for reference) ────────────────
XML context (single <item>):
  <g:id>123</g:id>
  <price currency="USD">10.00</price>

Expected mapping fragment:
  {
    "id"      : "g:id",
    "price"   : "price",
    "currency": "price/@currency"
  }

---------------  Actual product XML  ---------------
{$prettyXml}
----------------------------------------------------
EXAMPLE;

        // ──────────────── 3️⃣  JSON schema to enforce ────────────────
        $schemaProps = [];
        foreach ($standardFields as $field)
        {
            $extra               = self::FIELD_DESCRIPTIONS[strtolower($field)] ?? '';
            $schemaProps[$field] = [
                'type'        => 'string',
                'description' => trim("{$extra} — XPath (relative) or \"unknown\"."),
            ];
        }

        $schema = [
            'name'   => 'product_xpath_mapping',
            'schema' => [
                'type'                 => 'object',
                'properties'           => $schemaProps,
                'required'             => $standardFields,
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];

        $aiOptions = [
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => $schema,
            ],
        ];

        // ──────────────── 4️⃣  Call the LLM ────────────────
        $responseJson = $this->query($prompt, [], '', $aiOptions);

        // ──────────────── 5️⃣  Decode & validate ────────────────
        $decoded = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new \RuntimeException(
                'Invalid JSON from AI: ' . esc_html(wp_strip_all_tags(json_last_error_msg()))
            );
        }

        return $decoded;
    }

    public function suggestFieldsMappingCsv(array $product, array $standardFields): array
    {
        return $this->suggestFieldsMappingFlat($product, $standardFields);
    }

    public function suggestFieldsMappingJson(array $product, array $standardFields): array
    {
        return $this->suggestFieldsMappingFlat($product, $standardFields);
    }
}
