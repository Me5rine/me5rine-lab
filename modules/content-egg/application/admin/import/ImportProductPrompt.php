<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\helpers\TextHelper;;

defined('\ABSPATH') || exit;

/**
 * ImportProductPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImportProductPrompt extends ImportPrompt
{

    public function craftProductData(array $product, array $gen_fields = []): array
    {
        /* -----------------------------------------------------------------
        * 0. Decide which fields we need
        * ----------------------------------------------------------------- */
        $allFields = [
            'short_title',
            'short_description',
            'subtitle',
            'rating',
            'badge',
            'badge_icon',
            'attributes',
            'keyword',
        ];

        // If nothing requested, generate everything.
        $fields = $gen_fields ?: $allFields;

        /* -----------------------------------------------------------------
        * 1. Build the dynamic prompt
        * ----------------------------------------------------------------- */
        $fieldPrompts = [
            'short_title'       => 'short_title: A concise and informative product title (6–9 words).',
            'short_description' => 'short_description: A clear and engaging summary (1–2 sentences) that explains the product’s purpose and key benefits.',
            'subtitle'          => 'subtitle: Up to 10 words highlighting a unique feature or award.',
            'rating'            => 'rating: A score between 1.0 and 10.0 reflecting the product’s overall quality (e.g. 8.4, 9.3).',
            'badge'             => 'badge: Short value label such as “Best Value”, “Editor\'s Choice”, or “Must-Have” (in %lang%).',
            'badge_icon'        => "badge_icon: Choose the most relevant icon from the following options: 'award', 'bag-check', 'balloon-heart-fill', 'bell', 'bookmark-heart', ...].",
            'attributes'        => 'attributes: key/value pairs of product specs (e.g. Weight – 1.2 kg, Material – Aluminum).',
            'keyword'           => 'keyword: For digital games, use exactly the game’s official name (no extra words). For all other products, write a short shopping/search phrase (2–5 words) a buyer would type to find this product or close alternatives (e.g., brand + model, or category + key spec). No punctuation.',
        ];

        $promptLines = [];
        $n = 1;
        foreach ($fields as $f)
        {
            $promptLines[] = $n++ . '. ' . $fieldPrompts[$f];
        }

        $prompt = <<<EOD
You are provided with product data. Based on this and on your internal knowledge of the product, generate:

{$this->indentLines($promptLines)}

Guidelines:
• All output must be written in %lang%
• Format all texts in plain text (no Markdown)
EOD;

        // Attach the product payload for context
        $product_data = ImportPrompt::prepareProductData($product, [
            'title',
            'description',
            'price',
            'specifications',
            'userRating',
            'userReviewsCount',
        ]);
        $prompt .= "\n\n<product>\n" .
            json_encode($product_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
            "\n</product>";

        /* -----------------------------------------------------------------
 * 2. Build a minimal JSON-schema for just those fields
 * ----------------------------------------------------------------- */
        $iconWhitelist = [
            'award',
            'bag-check',
            'balloon-heart-fill',
            'bell',
            'bookmark-heart',
            'bookmark-star',
            'box2-heart',
            'boxes',
            'chat-heart-fill',
            'check-all',
            'check-circle',
            'check-lg',
            'check-square-fill',
            'circle-fill',
            'coin',
            'controller',
            'earbuds',
            'emoji-frown',
            'emoji-sunglasses',
            'fire',
            'gem',
            'hand-thumbs-down',
            'hand-thumbs-up',
            'headphones',
            'heart',
            'house-check',
            'lightning-charge-fill',
            'music-note-beamed',
            'patch-check',
            'patch-exclamation',
            'rocket-takeoff',
            'star-fill',
            'stars',
            'trophy',
        ];

        $propertyMap = [
            'short_title'       => ['type' => 'string'],
            'short_description' => ['type' => 'string'],
            'subtitle'          => ['type' => 'string'],
            'rating'            => ['type' => 'number'],
            'badge'             => ['type' => 'string'],
            'badge_icon'        => ['type' => 'string', 'enum' => $iconWhitelist],
            'attributes'        => [
                'type'                 => 'object',
                'additionalProperties' => ['type' => 'string'],
            ],
            'keyword'           => ['type' => 'string'],
        ];

        $properties = $required = [];
        foreach ($fields as $f)
        {
            $properties[$f] = $propertyMap[$f];
            $required[]     = $f;
        }

        $jsonSchema = [
            'name'   => 'product',
            'schema' => [
                'type'                 => 'object',
                'properties'           => $properties,
                'required'             => $required,
                'additionalProperties' => false,
            ],
        ];

        /* -----------------------------------------------------------------
        * 3. Call the LLM
        * ----------------------------------------------------------------- */
        $response = $this->query(
            $prompt,
            [],
            '',
            [
                'response_format' => [
                    'type'        => 'json_schema',
                    'json_schema' => $jsonSchema,
                ],
            ],
        );

        /* -----------------------------------------------------------------
 * 4. Decode & validate JSON
 * ----------------------------------------------------------------- */
        $item = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($item))
        {
            throw new \RuntimeException(
                sprintf(
                    'Invalid JSON from AI: %s',
                    esc_html(json_last_error_msg())
                )
            );
        }

        /* -----------------------------------------------------------------
        * 5. Map only the generated fields back to the product
        * ----------------------------------------------------------------- */
        $colors = ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'light', 'dark'];

        foreach ($fields as $f)
        {
            switch ($f)
            {
                case 'short_title':
                    $product['title'] = sanitize_text_field($item['short_title']);
                    break;

                case 'short_description':
                    $product['description'] = sanitize_textarea_field($item['short_description']);
                    break;

                case 'subtitle':
                    $product['subtitle'] = sanitize_text_field($item['subtitle']);
                    break;

                case 'rating':
                    $product['ratingDecimal'] = max(1.0, min(10.0, (float) $item['rating']));
                    $product['rating']        = '';
                    break;

                case 'badge':
                    $product['badge'] = sanitize_text_field($item['badge']);
                    $product['badge_color'] = $colors[array_rand($colors)];
                    break;

                case 'badge_icon':
                    $product['badge'] = sanitize_text_field($item['badge_icon']) . ':' . $product['badge'];
                    break;

                case 'attributes':
                    $product['features'] = [];
                    foreach ((array) ($item['attributes'] ?? []) as $key => $val)
                    {
                        $product['features'][] = [
                            'name'  => sanitize_text_field($key),
                            'value' => sanitize_text_field($val),
                        ];
                    }
                    break;

                case 'keyword':
                    $product['keyword'] = sanitize_text_field($item['keyword']);
                    break;
            }
        }

        return $product;
    }

    /**
     * Helper: indent each prompt line by two spaces for readability.
     *
     * @param string[] $lines
     */
    private function indentLines(array $lines): string
    {
        return implode("\n", array_map(static fn($l) => '  ' . $l, $lines));
    }
}
