<?php

namespace ContentEgg\application\components\ai;

defined('\ABSPATH') || exit;

use  ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\vendor\parsedown\Parsedown;



/**
 * ContentHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ContentHelper
{
    public static function listToArray($text, $max_length = 300)
    {
        if (!strstr($text, "\n") && substr_count($text, ',') >= 2)
            $lines = explode(",", $text);
        else
            $lines = preg_split("~\n~", $text, -1, PREG_SPLIT_NO_EMPTY);

        $lines = self::tryFixList($lines);
        if (!self::isList($lines))
            return array();

        $res = array();

        foreach ($lines as $i => $line)
        {
            if (preg_match('~:$~', $line))
                continue;

            if ($i == count($lines) - 1 && count($lines) > 3)
            {
                if (!preg_match('~^\d~', $line) && preg_match('~^\d~', $lines[$i - 1]))
                    continue;
            }

            $line = trim($line);
            $line = preg_replace('~^\d+\.\s~', '', $line);
            $line = preg_replace('~^\d+\)\s~', '', $line);
            $line = trim($line, " \t\r\n\"'.-");
            $line = strip_tags($line);
            $line = \sanitize_text_field($line);

            if (!$line || mb_strlen($line, 'UTF-8') > $max_length)
                continue;

            $res[] = $line;
        }

        return $res;
    }

    public static function isList(array $lines)
    {
        $list_items = array();
        foreach ($lines as $line)
        {
            $line = trim($line, " \t\r\n\"'");

            if (!preg_match('~^\d+\.\s~', $line) && !preg_match('~^-~', $line) && !preg_match('~^\d+\)\s~', $line))
                continue;

            $list_items[] = $line;
        }

        if ($list_items)
            return true;
        else
            return false;
    }

    public static function tryFixList(array $lines)
    {
        if (self::isList($lines))
            return $lines;

        if (count($lines) < 3)
            return $lines;

        foreach ($lines as $i => $line)
        {
            $line = trim($line, " \t\r\n\"' ");

            if ($i == 0 && mb_strlen($line, 'UTF-8') > 90)
                continue;

            if (mb_strlen($line, 'UTF-8') > 180)
                continue;

            $lines[$i] = '- ' . $line;
        }

        return $lines;
    }

    public static function prepareTitle($text)
    {
        if (strstr($text, "\n"))
        {
            $list = ContentHelper::listToArray($text);
            $text = reset($list);
        }

        $text = \sanitize_text_field($text);
        $text = trim($text, " \".");

        return $text;
    }

    public static function prepareProductTitle($text)
    {
        return self::prepareTitle($text);
    }

    public static function preparePostTitle($text)
    {
        return TextHelper::truncate(self::prepareTitle($text), 200);
    }

    public static function prepareMarkdown($text)
    {
        $text = self::removeMarkdownCodeBlock($text);

        $parsedown = new Parsedown();

        $html = $parsedown->text($text);

        $html = self::prepareHtml($html);
        return $html;
    }

    public static function prepareHtml($html)
    {
        $html = preg_replace('~<a.*?>(.*?)</a>~ui', '$1', $html);
        $html = preg_replace('/<img[^>]+\>/ui', '', $html);
        $html = preg_replace("~\n+~u", '', $html);

        $html = TextHelper::sanitizeHtml($html);

        return $html;
    }

    public static function removeMarkdownCodeBlock($text)
    {
        $text = preg_replace('/^```[a-zA-Z]*/', '', $text);
        $text = trim($text, '`');
        $text = trim($text);

        return $text;
    }

    public static function prepareArticle(string $html, string $title = ''): string
    {
        // 1) Remove markdown fences
        $html = str_replace(['```html', '```'], '', $html);

        // 2) If there's a <body>, extract only its inner HTML
        if (stripos($html, '<body') !== false)
        {
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            // Prepend XML tag to force UTF-8
            $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            if ($body = $doc->getElementsByTagName('body')->item(0))
            {
                $inner = '';
                foreach ($body->childNodes as $child)
                {
                    $inner .= $doc->saveHTML($child);
                }
                $html = $inner;
            }
        }

        // 3) Move <h1> down if present
        if (stripos($html, '<h1>') !== false)
        {
            $html = self::headerDown($html);
        }

        // 4) Remove the specific title heading (h2 or h3) if provided
        if ($title !== '')
        {
            $escaped = preg_quote($title, '#');
            $html = preg_replace(
                "#<h[23]>\s*{$escaped}\s*</h[23]>#iu",
                '',
                $html
            );
        }

        // 5) Strip out <title>, <style> and <script> blocks
        $html = preg_replace('#<title>.*?</title>#isu',       '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#isu',   '', $html);
        $html = preg_replace('#<script[^>]*>.*?</script>#isu', '', $html);

        // 6) Final trim & sanitize
        $html = trim($html);
        return TextHelper::sanitizeHtml($html);
    }

    public static function headerDown($html)
    {
        for ($i = 1; $i <= 5; $i++)
        {
            $r = $i + 1;
            $html = str_replace('<h' . $i . '>', '<hhhhhh' . $r . '>', $html);
            $html = str_replace('</h' . $i . '>', '</hhhhhh' . $r . '>', $html);
        }

        $html = str_replace('hhhhhh', 'h', $html);
        return $html;
    }

    public static function htmlToText($html)
    {
        $html = (string) $html;

        $text = preg_replace(
            array(
                '~</?((div)|(h[1-9])|(ins)|(br)|(p)|(pre))~iu',
                '~</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))~iu',
                '~</?((table)|(th)|(td)|(caption))~iu',
            ),
            array(
                "\n\$0",
                "\n\$0",
                "\n\$0",
            ),
            $html
        );

        $text = \wp_strip_all_tags($text);
        $text = preg_replace("~\r+~u", "\n", $text);
        $text = preg_replace("~\n+~u", "\n", $text);
        $text = trim($text);

        return $text;
    }

    public static function fixAiResponse($text)
    {
        $text = preg_replace('/^As an AI language model.+?However,/ims', '', $text);
        $text = preg_replace('/^As an AI language model.+?but generally,/ims', '', $text);
        $text = preg_replace('/^As an AI language model.+?It is recommended/ims', 'It is recommended', $text);
        $text = trim($text, " ,");
        return $text;
    }

    public static function isAiGenerated($text)
    {
        $footprints = array(
            ' AI language',
            ' AI model',
        );

        foreach ($footprints as $footprint)
        {
            if (mb_stripos($text, $footprint) !== false)
                return true;
        }

        return false;
    }

    public static function prepareJsonResponse($text)
    {
        $text = str_replace('```json', '', $text);
        $text = trim($text, '`');
        $text = trim($text);

        if ($json = json_decode($text, true))
            return $json;
        else
            return array();
    }

    public static function countWords($text, $lang = '')
    {
        return TextHelper::countWords($text, $lang);
    }

    /**
     * Clean and truncate post content for prompt usage.
     *
     * @param string $rawContent Raw post_content coming from WP_Query.
     * @param int    $maxChars   Optional hard limit (multibyte‑safe). 0 ⇒ no limit.
     */
    public static function prepareBlockPostContent(string $rawContent, int $maxChars = 0): string
    {
        $allowedBlocks = apply_filters(
            'cegg_prefill_allowed_text_blocks',
            [
                'core/paragraph',
                'core/heading',
                'core/list',        // wrapper – recursion handles children
                'core/list-item',   // individual <li>
                'core/quote',
            ]
        );

        // 1. Parse blocks & collect text recursively.
        $text = self::collectAllowedBlocksText(parse_blocks($rawContent), $allowedBlocks);

        if ($text === '')
        {
            $text = $rawContent; // Ultimate fallback – we tried, but nothing matched.
        }

        // 2. Replace angle‑collision ("><") to keep words separated once tags are stripped.
        $text = str_replace('><', '> <', $text);

        // 3. Strip shortcodes & tags – now that we handled block‑level extraction.
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text);

        // 4. Normalise newlines.
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 5. Collapse horizontal whitespace (tabs/spaces) but keep newlines.
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // 6. Collapse >2 consecutive newlines into exactly two (paragraph spacing).
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        $text = trim($text);

        // 7. Truncate if necessary.
        if ($maxChars && mb_strlen($text) > $maxChars)
        {
            $text = TextHelper::truncate($text, $maxChars);
        }

        return $text;
    }

    /**
     * Recursively walk parsed Gutenberg blocks and collect text from allowed ones.
     * Also performs a heuristic fallback for custom blocks by scanning innerContent
     * for <p> / <hN> tags or any raw HTML chunk.
     *
     * @param array $blocks        Output of parse_blocks().
     * @param array $allowedBlocks Block names we explicitly support.
     * @param bool  $inOrderedList Whether we are inside an ordered list.
     */
    private static function collectAllowedBlocksText(array $blocks, array $allowedBlocks, bool $inOrderedList = false): string
    {
        $out = '';
        static $liCounterStack = []; // track numbering per nested ordered list

        foreach ($blocks as $block)
        {
            $name        = $block['blockName']   ?? '';
            $innerHTML   = $block['innerHTML']   ?? '';
            $innerBlocks = $block['innerBlocks'] ?? [];
            $innerContent = $block['innerContent'] ?? [];

            // Detect list context for <li> numbering / bullets
            $isListWrapper  = $name === 'core/list';
            $orderedContext = $inOrderedList;
            if ($isListWrapper)
            {
                $orderedContext = !empty($block['attrs']['ordered']);
                if ($orderedContext)
                {
                    array_push($liCounterStack, 0); // start new numbering context
                }
            }

            // Recurse into children first so wrapper tags are skipped
            if ($innerBlocks)
            {
                $out .= self::collectAllowedBlocksText($innerBlocks, $allowedBlocks, $orderedContext);
            }

            // ----- PRIMARY EXTRACTION FOR ALLOWED BLOCKS -----
            if ($name && in_array($name, $allowedBlocks, true))
            {
                if ($name === 'core/list')
                {
                    // Already handled via recursion – nothing else to do.
                }
                elseif ($name === 'core/list-item')
                {
                    $clean = self::cleanInnerHTML($innerHTML);
                    if ($clean !== '')
                    {
                        if ($orderedContext)
                        {
                            $idx = ++$liCounterStack[array_key_last($liCounterStack)];
                            $clean = $idx . '. ' . $clean;
                        }
                        else
                        {
                            $clean = '• ' . $clean;
                        }
                        $out .= $clean . "\n\n";
                    }
                }
                else
                { // paragraph, heading, quote, etc.
                    $clean = self::cleanInnerHTML($innerHTML);
                    if ($clean !== '')
                    {
                        $out .= $clean . "\n\n";
                    }
                }
            }
            // ----- HEURISTIC FALLBACK FOR UNKNOWN / CUSTOM BLOCKS -----
            elseif ($innerContent && is_array($innerContent))
            {
                foreach ($innerContent as $chunk)
                {
                    if (!is_string($chunk))
                    {
                        continue;
                    }

                    if (preg_match('/<(p|h[1-6])[^>]*>/i', $chunk))
                    {
                        $clean = self::cleanInnerHTML($chunk);
                    }
                    else
                    {
                        // Broad HTML‑to‑text conversion for arbitrary markup.
                        $clean = method_exists('ContentHelper', 'htmlToText')
                            ? ContentHelper::htmlToText($chunk)
                            : trim(strip_tags($chunk));
                    }

                    if ($clean !== '')
                    {
                        $out .= $clean . "\n\n";
                    }
                }
            }

            // Exit ordered list context when wrapper ends.
            if ($isListWrapper && $orderedContext)
            {
                array_pop($liCounterStack);
            }
        }

        return $out;
    }

    /**
     * Remove leading numbers/bullets and strip tags from an HTML fragment.
     */
    private static function cleanInnerHTML(string $html): string
    {
        $text = preg_replace('/^\s*\d+\.\s*/u', '', strip_tags($html));
        return trim($text);
    }
}
