<?php

namespace ContentEgg\application\components\ai;

use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\helpers\TextHelper;

defined('\ABSPATH') || exit;

/**
 * PrefillPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PrefillPrompt extends SystemPrompt
{
    const MAX_PRODUCT_DESC_CHARS = 3000;

    public function suggestProductKeywordsForPost($post_title, $post_content, $max_keywords = 2, array $module_ids = array())
    {
        $shop_name = self::getShopName($module_ids);

        $prompt = "You are an e-commerce SEO assistant.\n\n";
        $prompt .= "Task\n";
        $prompt .= "1. Read the blog post below (title + main content).\n";
        $prompt .= "2. Identify the core product-related concepts.\n";
        $prompt .= "3. Suggest up to %max% short keyword phrases (2-4 words each) that a shopper would type";

        if ($shop_name)
        {
            $prompt .= " on the %shop% site";
        }
        else
        {
            $prompt .= " on popular sites like Amazon";
        }

        $prompt .= " to find best-selling items related to the post.";

        if ($shop_name)
        {
            $prompt .= " Products must be available on %shop%.";
        }

        $prompt .= "\n";
        $prompt .= "4. Focus on concrete product types, not generic terms.\n\n";

        $prompt .= "Example Output (for a hypothetical post about \"How to Growing Your First Garden\"):\n";
        $prompt .= "1. organic garden soil\n";
        $prompt .= "2. soil test kit\n";
        $prompt .= "3. garden tiller tool\n";

        $prompt .= "\n";
        $prompt .= "Blog post\n";
        $prompt .= "Title: '%post_title%'\n";
        $prompt .= "Content:\n";
        $prompt .= "%post_content%\n";

        $post_length = apply_filters('cegg_prefill_ai_max_post_chars', 6000);
        $prepared_content = self::preparePostContent($post_content, $post_length);
        $prepared_title = TextHelper::truncate($post_title, 200);

        $placeholders = [
            'post_title'   => $prepared_title,
            'post_content' => $prepared_content,
            'max' => $max_keywords,
            'shop' => $shop_name,
        ];

        $keywords = $this->queryList($prompt, $placeholders, '', [], 'keywords', 'keyword');

        shuffle($keywords);
        $keywords = array_slice($keywords, 0, $max_keywords);

        return $keywords;
    }

    public function suggestProductKeywordsForSection($post_title, $section_heading, $section_content, $max_keywords = 2, array $module_ids = array())
    {
        $shop_name = self::getShopName($module_ids);

        $prompt = "You are an e-commerce SEO assistant.\n\n";
        $prompt .= "Task\n";
        $prompt .= "1. Read the post section below (heading + main content).\n";
        $prompt .= "2. Identify the core product-related concepts.\n";
        $prompt .= "3. Suggest up to %max% short keyword phrases (2-4 words each) that a shopper would type";

        if ($shop_name)
        {
            $prompt .= " on the %shop% site";
        }
        else
        {
            $prompt .= " on popular sites like Amazon";
        }

        $prompt .= " to find best-selling items related to the post content.";

        if ($shop_name)
        {
            $prompt .= " Products must be available on %shop%.";
        }

        $prompt .= "\n";
        $prompt .= "4. Focus on specificity and accuracy. If the topic suggests certain product categories or common shopping terms (e.g., “rain jacket,” “camping blanket,” “volumizing shampoo”), include those in the keywords.\n";

        $prompt .= "Example Output (for a hypothetical post about \"Beginner Gardening Strategies\" and a section on \"Container Gardens for Small Spaces\"):\n";
        $prompt .= "- Self-watering planter\n";
        $prompt .= "- Organic potting mix\n";
        $prompt .= "- Indoor grow light set\n";

        $prompt .= "\nGiven:\n";
        $num = 1;
        $prompt .= "{$num}. The blog post title: \"%post_title%\".\n";
        $num++;
        if ($section_heading)
        {
            $prompt .= "{$num}. The section heading: \"%section_heading%\".\n";
            $num++;
        }
        $prompt .= "{$num}. The section content:\n%section_content%";

        $section_length = apply_filters('cegg_prefill_ai_max_section_chars', 3500);
        $prepared_content = self::preparePostContent($section_content, $section_length);
        $prepared_title = TextHelper::truncate($post_title, 200);
        $prepared_heading = TextHelper::truncate($section_heading, 200);

        $placeholders = [
            'post_title'   => $prepared_title,
            'section_heading'   => $prepared_heading,
            'section_content' => $prepared_content,
            'max' => $max_keywords,
            'shop' => $shop_name,
        ];

        $keywords = $this->queryList($prompt, $placeholders, '', [], 'keywords', 'keyword');

        shuffle($keywords);
        $keywords = array_slice($keywords, 0, $max_keywords);

        return $keywords;
    }

    public function getIrrelevantProductIDsForArticle(array $products, $post_title, $post_content)
    {
        $originalIds = array_column($products, 'unique_id');

        $productData = array_map(function ($p)
        {
            return [
                'unique_id' => $p['unique_id'],
                'title'     => $p['title'],
            ];
        }, $products);

        $prompt = "You are an expert content reviewer. You will be provided with:\n";
        $prompt .= "- The article title\n";
        $prompt .= "- The article content\n";
        $prompt .= "- A list of products, each with a unique_id and title\n\n";

        $prompt .= "Your task:\n";
        $prompt .= "- Identify products that are irrelevant to the article.\n";
        $prompt .= "- A product is relevant if it would be a suitable recommendation for someone interested in this article’s topic. Be lenient in borderline cases.\n";
        $prompt .= "- Return ONLY the unique_id values of irrelevant products as an array.\n\n";

        $prompt .= "Article:\n";
        $prompt .= "Title: \"%post_title%\"\n\n";
        $prompt .= "Content:\n";
        $prompt .= "%post_content%\n\n";

        $prompt .= "Products:\n";
        $prompt .= wp_json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $post_length = apply_filters('cegg_prefill_ai_max_post_chars', 6000);
        $prepared_content = self::preparePostContent($post_content, $post_length);
        $prepared_title = TextHelper::truncate($post_title, 200);

        $placeholders = [
            'post_title'   => $prepared_title,
            'post_content' => $prepared_content,
        ];

        $responseIds = $this->queryList($prompt, $placeholders, '', [], 'irrelevant_ids', 'unique_id');

        // Return only IDs that were in the original list
        return array_values(array_intersect($originalIds, $responseIds));
    }

    public function isRelevantProductForSection($post_title, $section_heading, $product_title)
    {
        $prompt = "You are an expert content reviewer. Given the information below:\n";

        $num = 1;
        $prompt .= "{$num}. Article Title: \"%post_title%\".\n";
        $num++;
        if ($section_heading)
        {
            $prompt .= "{$num}. Section Heading: \"%section_heading%\".\n";
            $num++;
        }
        $prompt .= "{$num}. Product: \"%product_title%\"";

        $prompt .= "\n\nYour task:\n";
        $prompt .= "- Determine if the product is clearly relevant to the content of the article section.\n";
        $prompt .= "- Relevancy means that the product would be a suitable recommendation for someone interested in that section's topic. Be slightly lenient in your judgment.\n";
        $prompt .= "- Answer ONLY with “Yes” or “No”. No extra text.";

        $placeholders = [
            'post_title' => $post_title,
            'section_heading' => $section_heading,
            'product_title' => $product_title,
            'lang' => 'English',
        ];

        $answer = $this->queryString($prompt, $placeholders, '', [], 'answer');

        $answer = trim(strtolower($answer), '“”"\'');
        if ($answer == 'yes')
            return true;
        else
            return false;
    }

    /**
     * Clean and truncate post content for prompt usage.
     */
    public static function preparePostContent($rawContent, $maxChars = 6000): string
    {
        return ContentHelper::prepareBlockPostContent($rawContent, $maxChars);
    }

    public static function getShopName(array $module_ids = []): string
    {
        $map = [
            'amazon'    => 'Amazon',
            'bolcom'    => 'Bol.com',
            'aliexpress' => 'AliExpress',
            'ebay'      => 'eBay',
            'bestbuy'      => 'Bestbuy.com',
            'booking'      => 'Booking.com',
            'envato'      => 'Envato',
            'flipkart'      => 'Flipkart.com',
            'shopee'      => 'Shopee',
            'walmart'      => 'Walmart',
        ];

        foreach ($module_ids as $module_id)
        {
            $id_lower = strtolower($module_id);
            foreach ($map as $key => $label)
            {
                if (strpos($id_lower, $key) !== false)
                {
                    return $label;
                }
            }
        }

        return '';
    }

    private function prepareProductData(ContentProduct $product, array $fields = []): array
    {
        if (empty($fields))
        {
            $fields = [
                'unique_id',
                'title',
                'description',
            ];
        }

        $data = [];
        foreach ($fields as $field)
        {
            switch ($field)
            {
                case 'unique_id':
                    $data['unique_id'] = $product->unique_id;
                    break;

                case 'title':
                    $data['title'] = (string) TextHelper::truncate($product->title, 250);
                    break;

                case 'description':
                    $maxChars = apply_filters('cegg_prefill_ai_max_desc_chars', self::MAX_PRODUCT_DESC_CHARS);
                    $plainText = ContentHelper::htmlToText($product->description);
                    $data['description'] = TextHelper::truncate($plainText, $maxChars);
                    break;

                default:
                    $data[$field] = '';
                    break;
            }
        }

        return $data;
    }

    public function processProductDataForShortcode(array $products): array
    {
        if (empty($products))
        {
            return [];
        }

        // 1) Build lookup by ORIGINAL unique_id
        $productByOriginalId = [];
        foreach ($products as $product)
        {
            if (!($product instanceof ContentProduct))
            {
                throw new \InvalidArgumentException(
                    'Invalid product type. Expected instance of ContentProduct.'
                );
            }
            $productByOriginalId[$product->unique_id] = $product;
        }

        // 2) Prepare input for AI, mapping prompt-ID -> original-ID
        $inputProducts = [];
        $promptToOriginalMap = [];  // prompt_id => original_unique_id
        $counter = 1;
        foreach ($productByOriginalId as $originalId => $product)
        {
            $data = $this->prepareProductData(
                $product,
                ['unique_id', 'title', 'description']
            );
            // use a simple 1,2,3... ID for the AI prompt
            $data['unique_id'] = $counter;
            $inputProducts[]     = $data;

            // remember how to get back to the real ID
            $promptToOriginalMap[$counter] = $originalId;
            $counter++;
        }

        // 3) Send to AI
        $prompt    = $this->buildProductPrompt($inputProducts);
        $schema = [
            "name" => "product_list",
            "schema" => [
                "type" => "object",
                "properties" => [
                    "products" => [
                        "type" => "array",
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "short_title" => ["type" => "string"],
                                "subtitle"    => ["type" => "string"],
                                "badge"       => ["type" => "string"],
                                "description" => ["type" => "string"],
                                "unique_id"   => ["type" => "integer"],
                            ],
                            "required"             => ["short_title", "subtitle", "badge", "description", "unique_id"],
                            "additionalProperties" => false,
                        ],
                    ],
                ],
                "required"             => ["products"],
                "additionalProperties" => false,
            ],
            "strict" => true,
        ];
        $aiOptions = [
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => $schema,
            ],
        ];
        $response = $this->query($prompt, [], '', $aiOptions);

        // 4) Decode & validate
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new \RuntimeException(
                'Invalid JSON from AI: ' . esc_html(wp_strip_all_tags(json_last_error_msg()))
            );
        }
        if (
            !isset($decoded['products']) ||
            count($decoded['products']) !== count($inputProducts)
        )
        {
            throw new \RuntimeException(
                'Unexpected product count in AI response.'
            );
        }

        // 5) Map AI output back to ORIGINAL products
        $colors  = ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'light', 'dark'];
        $results = [];

        foreach ($decoded['products'] as $item)
        {
            $promptId = $item['unique_id'];
            if (!isset($promptToOriginalMap[$promptId]))
            {
                throw new \Exception(
                    'AI: Smart Groups error: ' . esc_html(wp_strip_all_tags($e->getMessage()))
                );
            }

            $originalId = $promptToOriginalMap[$promptId];
            $product    = $productByOriginalId[$originalId];

            // assign AI-generated fields
            $product->title       = sanitize_text_field($item['short_title']);
            $product->subtitle    = sanitize_text_field($item['subtitle']);
            $product->badge       = sanitize_text_field($item['badge']);
            $product->badge_color = $colors[array_rand($colors)];
            $product->description = sanitize_text_field($item['description']);

            $results[$originalId] = $product;
        }

        return $results;
    }

    private function buildProductPrompt(array $products): string
    {
        $instructions = <<<EOD
You are given an array of products. For each product, output a JSON object with exactly five fields, preserving the original order and unique IDs. Ensure all generated text (titles, subtitles, badges, descriptions) is in %lang%:
1. short_title: A concise, informative title of 6–9 words.
2. subtitle: A brief highlight of the product’s unique feature or accolade (max 10 words).
3. badge: A short (2–3 words), impactful label reflecting popularity, quality, or value. Choose from “Hot Pick”, “Top Rated”, “Trending Now”, “Must-Have” — or create your own fitting custom badge.
4. description: 1–2 sentences explaining the product’s purpose and benefits clearly.
5. unique_id: Exact copy of the product’s ID from the source array.

EOD;

        return $instructions . "<products>\n" . json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n</products>";
    }
}
