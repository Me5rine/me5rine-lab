<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\components\ai\Prompt;

defined('\ABSPATH') || exit;

/**
 * ImportPostPromptFree class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImportPostPromptFree extends Prompt
{

    public static function titleMethods(): array
    {
        return [
            [
                'key'    => 'generate_review_title',
                'label'  => __('Generate a Review Title', 'content-egg'),
                'method' => 'generateReviewTitle',
            ],
            [
                'key'    => 'generate_buyers_guide_title',
                'label'  => __("Generate a Buyer's Guide Title", 'content-egg'),
                'method' => 'generateBuyersGuideTitle',
            ],
            [
                'key'    => 'generate_how_to_use_title',
                'label'  => __('Generate a How-to-Use Title', 'content-egg'),
                'method' => 'generateHowToUseTitle',
            ],
            [
                'key'    => 'generate_question_title',
                'label'  => __('Generate a Question-Article Title', 'content-egg'),
                'method' => 'generateQuestionTitle',
            ],
            [
                'key'    => 'shorten',
                'label'  => __('Shorten Product Title', 'content-egg'),
                'method' => 'shortenProductTitle',
            ],
            [
                'key'    => 'rephrase',
                'label'  => __('Rephrase Product Title', 'content-egg'),
                'method' => 'rephraseProductTitle',
            ],
            [
                'key'    => 'translate',
                'label'  => __('Translate Product Title', 'content-egg'),
                'method' => 'translateProductTitle',
            ],
            [
                'key'    => 'prompt1',
                'label'  => __('Custom Prompt #1', 'content-egg'),
                'method' => 'customPrompt1Title',
            ],
            [
                'key'    => 'prompt2',
                'label'  => __('Custom Prompt #2', 'content-egg'),
                'method' => 'customPrompt2Title',
            ],
            [
                'key'    => 'prompt3',
                'label'  => __('Custom Prompt #3', 'content-egg'),
                'method' => 'customPrompt3Title',
            ],
        ];
    }

    public static function descriptionMethods(): array
    {
        return [
            [
                'key'    => 'write_review',
                'label'  => __('Write a Review', 'content-egg'),
                'method' => 'writeReviewProductDescription',
            ],
            [
                'key'    => 'write_buyers_guide',
                'label'  => __("Write a Buyer's Guide", 'content-egg'),
                'method' => 'writeBuyersGuideProductDescription',
            ],
            [
                'key'    => 'write_how_to_use',
                'label'  => __('Write How-to-Use Instructions', 'content-egg'),
                'method' => 'writeHowToUseProductDescription',
            ],
            [
                'key'    => 'write_article',
                'label'  => __('Write an Article', 'content-egg'),
                'method' => 'writeArticleProductDescription',
            ],
            [
                'key'    => 'rewrite',
                'label'  => __('Rewrite', 'content-egg'),
                'method' => 'rewriteProductDescription',
            ],
            [
                'key'    => 'paraphrase',
                'label'  => __('Paraphrase', 'content-egg'),
                'method' => 'paraphraseProductDescription',
            ],
            [
                'key'    => 'translate',
                'label'  => __('Translate', 'content-egg'),
                'method' => 'translateProductDescription',
            ],
            [
                'key'    => 'summarize',
                'label'  => __('Summarize', 'content-egg'),
                'method' => 'summarizeProductDescription',
            ],
            [
                'key'    => 'bullet_points',
                'label'  => __('Bullet Points', 'content-egg'),
                'method' => 'bulletPointsProductDescription',
            ],
            [
                'key'    => 'bullet_points_compact',
                'label'  => __('Bullet Points (Concise)', 'content-egg'),
                'method' => 'bulletPointsCompactProductDescription',
            ],
            [
                'key'    => 'write_paragraphs',
                'label'  => __('Write a Few Paragraphs', 'content-egg'),
                'method' => 'writeParagraphsProductDescription',
            ],
            [
                'key'    => 'craft_description',
                'label'  => __('Craft a Product Description', 'content-egg'),
                'method' => 'craftProductDescription',
            ],
            [
                'key'    => 'turn_into_advertising',
                'label'  => __('Turn into Advertising', 'content-egg'),
                'method' => 'turnIntoAdvertisingProductDescription',
            ],
            [
                'key'    => 'cta_text',
                'label'  => __('Generate CTA Text', 'content-egg'),
                'method' => 'ctaTextProductDescription',
            ],
            [
                'key'    => 'prompt1',
                'label'  => __('Custom Prompt #1', 'content-egg'),
                'method' => 'customPrompt1Description',
            ],
            [
                'key'    => 'prompt2',
                'label'  => __('Custom Prompt #2', 'content-egg'),
                'method' => 'customPrompt2Description',
            ],
            [
                'key'    => 'prompt3',
                'label'  => __('Custom Prompt #3', 'content-egg'),
                'method' => 'customPrompt3Description',
            ],
        ];
    }

    public static function shortDescriptionMethods(): array
    {
        return [
            [
                'key'    => 'generate_short_description',
                'label'  => __('Generate Short Description', 'content-egg'),
                'method' => 'generateProductShortDescription',
            ],
            [
                'key'    => 'summarize',
                'label'  => __('Summarize', 'content-egg'),
                'method' => 'summarizeProductDescription',
            ],
            [
                'key'    => 'bullet_points',
                'label'  => __('Bullet Points', 'content-egg'),
                'method' => 'bulletPointsProductDescription',
            ],
            [
                'key'    => 'bullet_points_compact',
                'label'  => __('Bullet Points (Concise)', 'content-egg'),
                'method' => 'bulletPointsCompactProductDescription',
            ],
            [
                'key'    => 'write_paragraphs',
                'label'  => __('Write a Few Paragraphs', 'content-egg'),
                'method' => 'writeParagraphsProductDescription',
            ],
            [
                'key'    => 'turn_into_advertising',
                'label'  => __('Turn into Advertising', 'content-egg'),
                'method' => 'turnIntoAdvertisingProductDescription',
            ],
            [
                'key'    => 'cta_text',
                'label'  => __('Generate CTA Text', 'content-egg'),
                'method' => 'ctaTextProductDescription',
            ],
            [
                'key'    => 'prompt1',
                'label'  => __('Custom Prompt #1', 'content-egg'),
                'method' => 'customPrompt1Description',
            ],
            [
                'key'    => 'prompt2',
                'label'  => __('Custom Prompt #2', 'content-egg'),
                'method' => 'customPrompt2Description',
            ],
            [
                'key'    => 'prompt3',
                'label'  => __('Custom Prompt #3', 'content-egg'),
                'method' => 'customPrompt3Description',
            ],
        ];
    }

    public static function getTitleMethodOptions(): array
    {
        $options = [];
        foreach (self::titleMethods() as $method)
        {
            $options[$method['key']] = $method['label'];
        }
        return $options;
    }

    public static function getDescriptionMethodOptions(): array
    {
        $options = [];
        foreach (self::descriptionMethods() as $method)
        {
            $options[$method['key']] = $method['label'];
        }
        return $options;
    }

    public static function getShortDescriptionMethodOptions(): array
    {
        $options = [];
        foreach (self::shortDescriptionMethods() as $method)
        {
            $options[$method['key']] = $method['label'];
        }
        return $options;
    }
}
