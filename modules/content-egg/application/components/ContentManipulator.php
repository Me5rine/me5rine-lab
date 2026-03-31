<?php

namespace ContentEgg\application\components;

use ContentEgg\application\components\ai\ContentHelper;
use ContentEgg\application\helpers\TextHelper;



defined('\ABSPATH') || exit;

/**
 * ContentManipulator abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ContentManipulator
{
    protected \Wp_Post $post;
    protected string $lang;
    protected array $blocks = [];
    protected array $paragraphChunks = [];
    protected array $sectionChunks = [];
    protected array $injectedPositions = [];
    protected bool $isGutenberg = false;
    protected $excerptChunkIndex = null;

    public function __construct(?\Wp_Post $post = null, ?string $lang = null)
    {
        if (!function_exists('parse_blocks') || !function_exists('serialize_block'))
        {
            throw new \RuntimeException('WordPress 5.9 or higher is required for block parsing.');
        }
        if ($post)
        {
            $this->setPost($post);
        }
        if ($lang)
        {
            $this->lang = $lang;
        }
    }

    public function setPost(\WP_Post $post): void
    {
        $this->post = $post;
        $this->blocks = [];
        $this->paragraphChunks = [];
        $this->sectionChunks = [];
        $this->injectedPositions = [];
        $this->isGutenberg = false;
        $this->excerptChunkIndex = null;

        $this->splitIntoBlocks();
    }

    /**
     * Inject snippets and save the result back to WordPress.
     *
     * @param  array    $config  Injection specification.
     * @return int|\WP_Error     0 = no change, post-ID on success, or WP_Error.
     */
    public function injectAndSave(array $config, \WP_Post $post)
    {
        $this->setPost($post);

        $modified = $this->inject($this->post->post_content, $config);

        if ($modified === $post->post_content)
        {
            return 0;
        }

        $update = [
            'ID'           => $this->post->ID,
            'post_content' => $modified,
        ];

        return wp_update_post(wp_slash($update), true);
    }

    /**
     * Return the content with snippets injected according to $config.
     *
     * @param  string $content  Raw post_content (blocks or classic HTML).
     * @param  array  $config   Same structure used in injectBlocks().
     * @return string           Modified content (ready to save).
     */
    public function inject(string $content, array $config): string
    {
        // Detect if we can use the cheap path
        $simple = true;
        foreach ($config as $item)
        {
            $pos = strtolower($item['position'] ?? '');
            if ($pos !== 'before_content' && $pos !== 'after_content')
            {
                $simple = false;
                break;
            }
        }

        // 2.  FAST path  (only before/after content)
        if ($simple)
        {
            $this->isGutenberg = strpos($content, '<!-- wp:') !== false;

            $before = $after = [];

            foreach ($config as $item)
            {
                $code = $this->prepareSnippet($item['code'] ?? '');

                if (!$code)
                {
                    continue;
                }

                (strtolower($item['position']) === 'before_content')
                    ? $before[] = $code
                    : $after[]  = $code;
            }

            $out = '';
            if ($before)
            {
                $out .= implode("\n", $before) . "\n";
                $this->injectedPositions[-1] = -1;
            }
            $out .= $content;
            if ($after)
            {
                $out .= "\n" . implode("\n", $after);
                $this->injectedPositions[-2] = -2;
            }

            return $this->normalizePostWhitespace($out);
        }

        // 3. FULL path  (middle, after_excerpt, after_paragraph_X …)
        $this->buildParagraphChunks();
        $out = $this->injectBlocks($config);

        return $this->normalizePostWhitespace($out);
    }

    protected function splitIntoBlocks(): array
    {
        return $this->blocks = parse_blocks($this->post->post_content);
    }

    /**
     * Builds $this->paragraphChunks so that:
     *   • each chunk ends with a real paragraph (<p>…</p>)
     *   • core/more is glued to the previous chunk
     *   • classic-editor (<p>…</p>) content is split correctly
     */
    protected function buildParagraphChunks(): void
    {
        $this->paragraphChunks   = [];
        $this->excerptChunkIndex = null;
        $this->isGutenberg       = false;

        $buffer         = '';
        $excerptPending = false;

        foreach ($this->blocks as $block)
        {
            if ($block['blockName'] !== null)
            {
                $this->isGutenberg = true;
            }

            /* ── 0. Gutenberg “more” (core/more) ────────────────────── */
            if ($block['blockName'] === 'core/more')
            {
                $buffer .= serialize_block($block);

                if ($this->paragraphChunks)
                {
                    $this->paragraphChunks[array_key_last($this->paragraphChunks)] .= $buffer;
                    $this->excerptChunkIndex = array_key_last($this->paragraphChunks);
                    $buffer         = '';
                    $excerptPending = false;
                }
                else
                {
                    $excerptPending = true;                    // more before first <p>
                }
                continue;
            }

            /* ── 1. Gutenberg paragraph ─────────────────────────────── */
            if ($block['blockName'] === 'core/paragraph')
            {
                $buffer .= serialize_block($block);
                $this->paragraphChunks[] = $buffer;
                $buffer = '';

                if ($excerptPending)
                {
                    $this->excerptChunkIndex = array_key_last($this->paragraphChunks);
                    $excerptPending          = false;
                }
                continue;
            }

            /* ── 2. Classic raw HTML (no block wrapper) ─────────────── */
            if ($block['blockName'] === null)
            {
                if (stripos($block['innerHTML'], '<p') === false)
                {
                    $html = wpautop($block['innerHTML'], false);
                }
                else
                {
                    $html = $block['innerHTML'];
                }

                $buffer = $this->flushParagraphsFromHtml($html, $buffer, $excerptPending);
                $excerptPending = false;
                continue;
            }

            /* ── 3. Any other block (core/html, custom, etc.) ───────── */
            $buffer .= serialize_block($block);

            if (
                ! empty($block['innerContent']) &&
                stripos(implode('', (array) $block['innerContent']), '<p') !== false
            )
            {

                $this->paragraphChunks[] = $buffer;
                $buffer = '';

                if ($excerptPending)
                {
                    $this->excerptChunkIndex = array_key_last($this->paragraphChunks);
                    $excerptPending          = false;
                }
            }
        }

        /* ── final flush ────────────────────────────────────────────── */
        if ($buffer !== '')
        {
            $this->paragraphChunks[] = $buffer;
            if ($excerptPending)
            {
                $this->excerptChunkIndex = array_key_last($this->paragraphChunks);
            }
        }
    }

    /**
     * Split a raw-HTML chunk (classic editor) into paragraph-ending chunks.
     *
     * @param string $html            The raw HTML from the current block.
     * @param string $buffer          Accumulated HTML *before* this block.
     * @param bool   &$excerptPending Whether the excerpt ends on the next <p>.
     * @return string                 Residual buffer (no complete <p> yet).
     */
    protected function flushParagraphsFromHtml(string $html, string $buffer, bool &$excerptPending): string
    {
        /* Add the raw HTML exactly once – don’t prepend earlier */
        $parts = preg_split(
            '/(<p\b[^>]*>.*?<\/p>)/is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        foreach ($parts as $part)
        {
            $buffer .= $part;

            if (preg_match('/^<p\b/i', $part))
            {
                $this->paragraphChunks[] = $buffer;
                $buffer = '';

                if ($excerptPending)
                {
                    $this->excerptChunkIndex = array_key_last($this->paragraphChunks);
                    $excerptPending          = false;
                }
            }
        }

        return $buffer;           // may contain trailing non-<p> HTML
    }

    public function insertBlockAt(int $position, string $blockContent): void
    {
        array_splice($this->blocks, $position, 0, $blockContent);
    }

    public function appendBlock(string $blockContent): void
    {
        $this->blocks[] = $blockContent;
    }

    public function prependBlock(string $blockContent): void
    {
        array_unshift($this->blocks, $blockContent);
    }

    /**
     * Insert each “code” snippet at its declared “position” and
     * return the complete, ready-to-save post_content.
     *
     * Allowed positions:
     *   - before_content, after_content, middle, after_excerpt
     *   - after_paragraph_N  (1-based)
     *
     * @param  array $config  see example below
     * @return string
     */
    public function injectBlocks(array $config): string
    {
        $this->injectedPositions = [];

        if (empty($config))
        {
            return implode('', $this->paragraphChunks);
        }

        $total = count($this->paragraphChunks);
        $map   = [];                // chunk-index  =>  array of codes (keeps order)

        // translate human positions → chunk indices
        foreach ($config as $item)
        {
            $pos  = strtolower($item['position'] ?? '');
            $code = $this->prepareSnippet($item['code'] ?? '');

            if (!$pos || !$code)
            {
                continue;
            }

            switch ($pos)
            {
                case 'before_content':
                    $map[-1][] = $code;
                    $this->injectedPositions[-1] = -1;
                    break;

                case 'after_content':
                    $map[$total][] = $code;
                    $this->injectedPositions[$total] = $total;
                    break;

                case 'middle':
                    $idx = max(0, (int) floor(($total - 1) / 2)); // after middle chunk
                    $map[$idx][] = $code;
                    $this->injectedPositions[$idx] = $idx;
                    break;

                case 'after_excerpt':
                    $idx = $this->excerptChunkIndex !== null ? $this->excerptChunkIndex : 0;
                    $map[$idx][] = $code;
                    $this->injectedPositions[$idx] = $idx;
                    break;

                default:
                    if (preg_match('/after_paragraph_(\d+)/', $pos, $m))
                    {
                        $n   = (int) $m[1];
                        $idx = min(max($n, 1) - 1, $total - 1);   // clamp
                        $map[$idx][] = $code;
                        $this->injectedPositions[$idx] = $idx;
                    }
                    break;
            }
        }

        // rebuild post_content with injections
        $out = '';

        // codes that go before everything
        if (isset($map[-1]))
        {
            $out .= implode("\n", $map[-1]) . "\n";
        }

        foreach ($this->paragraphChunks as $i => $chunk)
        {
            $out .= $chunk;

            if (isset($map[$i]))
            {
                $out .= "\n" . implode("\n", $map[$i]) . "\n";
            }
        }

        // codes that go after everything
        if (isset($map[$total]))
        {
            $out .= "\n" . implode("\n", $map[$total]) . "\n";
        }

        return $out;
    }

    /**
     * Wrap a raw shortcode in a Shortcode block if needed.
     */
    protected function prepareSnippet(string $code): string
    {
        $trim = trim($code);

        // Already a Gutenberg block comment → leave as-is
        if (preg_match('/<!--\s*wp:/i', $trim))
        {
            return $trim;
        }

        // Simple shortcode detector
        if (preg_match('/^\[.+\]$/s', $trim))
        {
            if ($this->isGutenberg)
            {
                return "<!-- wp:shortcode -->\n{$trim}\n<!-- /wp:shortcode -->";
            }
            // classic: keep plain but make sure it sits on its own line
            return "\n{$trim}\n";
        }

        /* Raw HTML or anything else */
        return $code;
    }

    /**
     * Return a list of position names where snippets were injected.
     *
     * -1                 → 'before_content'
     * 0 or more integers → 'after_paragraph_N'
     * -2                 → 'after_content'
     */
    public function getInsertedPositions(): array
    {
        ksort($this->injectedPositions);

        $positions = [];

        foreach ($this->injectedPositions as $position)
        {
            if ($position === -1)
            {
                $positions[] = 'before content';
            }
            elseif ($position === -2)
            {
                $positions[] = 'after content';
            }
            elseif (is_int($position) && $position >= 0)
            {
                $positions[] = 'after paragraph ' . $position;
            }
        }

        return $positions;
    }

    /**
     * Tidy up cosmetic whitespace _without_ breaking:
     */
    protected function normalizePostWhitespace(string $content): string
    {
        /* 0 — normalise all line endings to "\n" */
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        /* 1 — trim trailing spaces/tabs on every line (safe everywhere) */
        $content = preg_replace('/[ \t]+$/m', '', $content);

        /* 2 — block-aware handling */
        if ($this->isGutenberg)
        {
            /* a) remove indentation on otherwise-blank lines */
            $content = preg_replace('/^[ \t]+$/m', '', $content);

            /* b) collapse 3-or-more blank lines to exactly two */
            $content = preg_replace("/\n{3,}/", "\n\n", $content);

            /* c) full trim, re-add single trailing LF (core style) */
            $content = trim($content) . "\n";
        }
        else
        {
            /**
             * Classic editor:
             *  – keep double-LFs because wpautop() needs them
             *  – but we can reduce 4+ LFs to 2 to avoid giant gaps
             */
            $content = preg_replace("/\n{4,}/", "\n\n", $content);

            // rtrim _only_ spaces/tabs/newlines at the very end
            $content = rtrim($content) . "\n";
        }

        return $content;
    }

    public function getPostSections(\WP_Post $post): array
    {
        $this->setPost($post);
        return $this->getSections();
    }

    /**
     * Return an array with logical “sections” of the post.
     *
     * A section starts at every <h2> / <h3> heading that is followed by at
     * least $minWords words OR whenever the current section exceeds $maxWords.
     *
     * Each array element has:
     *   [
     *     'heading'       => (string) Normalised heading text – empty for intro,
     *     'html'          => (string) Raw HTML of the section (ready to save),
     *     'word_count'    => (int)    Approx. word count,
     *     'chunk_indices' => (int[])  Paragraph‑chunk indices that form the section
     *   ]
     *
     * @param  int $minWords   Minimal words required to “open” a new section.
     * @param  int $maxWords   Force‑split when current section grows beyond this.
     * @return array
     */
    public function getSections(int $minWords = 120, int $maxWords = 300): array
    {
        if (empty($this->sectionChunks))
        {
            $this->splitIntoSections($minWords, $maxWords);
        }

        return $this->sectionChunks;
    }

    protected function splitIntoSections(int $minWords, int $maxWords): void
    {
        if (!$this->paragraphChunks)
        {
            $this->buildParagraphChunks();
        }

        $this->sectionChunks = [];

        // Tracks the last seen <h2> text
        $currentH2 = '';

        // Initialize the “current” section container
        $current = [
            'heading'       => '',
            'h2_heading'    => '',
            'html'          => '',
            'text'          => '',
            'word_count'    => 0,
            'chunk_indices' => [],
        ];

        foreach ($this->paragraphChunks as $idx => $html)
        {
            // ── Detect <h2> specifically ────────────────────────────────
            if (preg_match('/<h2[^>]*>(.*?)<\/h2>/is', $html, $m))
            {
                $currentH2 = wp_strip_all_tags($m[1]);
            }

            // ── Detect h2/h3 headings for section splitting ────────────
            if (preg_match('/<h([23])[^>]*>(.*?)<\/h\1>/is', $html, $m))
            {
                $headingText = wp_strip_all_tags($m[2]);

                // Start NEW section if we've already got enough words
                if ($current['word_count'] >= $minWords)
                {
                    // before pushing, ensure we stamp on the last-seen <h2>
                    $current['h2_heading'] = $currentH2;
                    $this->sectionChunks[] = $current;

                    // reset current with new heading
                    $current = [
                        'heading'       => $headingText,
                        'h2_heading'    => $currentH2,
                        'html'          => '',
                        'text'          => '',
                        'word_count'    => 0,
                        'chunk_indices' => [],
                    ];
                }
                else
                {
                    // too short to split: carry over or set intro heading
                    if (!$current['heading'])
                    {
                        $current['heading'] = $headingText;
                    }
                    // always keep the current <h2> context
                    $current['h2_heading'] = $currentH2;
                }
            }

            // ── Add this chunk into our section ────────────────────────
            $current['html']           .= "\n" . $html;
            $current['text']           .= "\n" . ContentHelper::prepareBlockPostContent($html);
            $current['chunk_indices'][] = $idx;
            $current['word_count']     = TextHelper::countWords($current['text'], $this->lang);

            // stamp the h2 into the live section
            $current['h2_heading']     = $currentH2;

            // ── Force-split if we exceed maxWords and next chunk isn't a heading ──
            if ($current['word_count'] >= $maxWords)
            {
                $this->sectionChunks[] = $current;
                // start fresh, but keep H2 context
                $current = [
                    'heading'       => '',
                    'h2_heading'    => $currentH2,
                    'html'          => '',
                    'text'          => '',
                    'word_count'    => 0,
                    'chunk_indices' => [],
                ];
            }
        }

        // push any remaining content
        if ($current['word_count'] > 0)
        {
            $current['h2_heading'] = $currentH2;
            $this->sectionChunks[] = $current;
        }

        // calculate total words
        $totalWords = 0;
        foreach ($this->sectionChunks as $section)
        {
            $totalWords += $section['word_count'];
        }
    }

    /**
     * Decide which logical sections will receive product‑blocks
     * (“ad‑slots”) and, if needed, merge tiny neighbour sections so the
     * final number of slots equals $blockCount.
     *
     * Rules
     * -------
     * • If total sections ≤ $blockCount  →  return them unchanged.
     * • If number of unique <h2> headings ≥ $blockCount
     *   → pick the first section under each distinct <h2> until we
     *   reach $blockCount.
     * • Otherwise merge adjacent sections that are
     *   – under $minWords AND
     *   – share the same <h2> context.
     *   Never merge across a different <h2>.
     *   Stop when we have exactly $blockCount sections.
     *
     * Each returned array element is the same structure produced by
     * getSections() (with keys heading, h2_heading, html, …).
     *
     * @param int $blockCount Desired number of product blocks (1‑6).
     * @param int $minWords Merge threshold.
     * @param int $maxWords
     * @return array Sections ready to receive blocks.
     */
    public function getAdSlotSections(int $blockCount, int $minWords = 120, int $maxWords = 400): array
    {
        $sections = $this->getSections($minWords, $maxWords);

        if (count($sections) <= $blockCount)
        {
            return $sections;                             // nothing to do
        }

        /* -----------------------------------------------------------
        * 1. Unique‑H2 strategy
        * --------------------------------------------------------- */
        $byH2 = [];
        foreach ($sections as $sec)
        {
            $h2 = $sec['h2_heading'] ?: '__intro__';
            if (!isset($byH2[$h2]))
            {
                $byH2[$h2] = $sec;                        // grab first under h2
            }
        }

        if (count($byH2) >= $blockCount)
        {
            // pick first $blockCount unique‑H2 sections in original order
            $chosen = [];
            foreach ($sections as $sec)
            {
                $h2 = $sec['h2_heading'] ?: '__intro__';
                if (isset($byH2[$h2]))
                {
                    $chosen[] = $sec;
                    unset($byH2[$h2]);
                    if (count($chosen) === $blockCount)
                    {
                        break;
                    }
                }
            }
            return $chosen;
        }

        /* -----------------------------------------------------------
        * 2. Merge tiny subsections until we hit target count
        * --------------------------------------------------------- */
        while (count($sections) > $blockCount)
        {

            $merged = false;                      // flag if we merged in this pass

            // Pass 1 – merge first tiny section we find (same H2)
            foreach ($sections as $i => $sec)
            {

                if ($sec['word_count'] >= $minWords)
                {
                    continue;
                }

                // prefer merging forward with next sibling under same <h2>
                if (
                    $i + 1 < count($sections) &&
                    $sections[$i]['h2_heading'] === $sections[$i + 1]['h2_heading']
                )
                {

                    $sections[$i] = $this->mergeSectionPair($sections[$i], $sections[$i + 1]);
                    array_splice($sections, $i + 1, 1);
                    $merged = true;
                    break;
                }

                // otherwise merge backwards (prev sibling) if same <h2>
                if (
                    $i > 0 &&
                    $sections[$i]['h2_heading'] === $sections[$i - 1]['h2_heading']
                )
                {

                    $sections[$i - 1] = $this->mergeSectionPair($sections[$i - 1], $sections[$i]);
                    array_splice($sections, $i, 1);
                    $merged = true;
                    break;
                }
            }

            // Pass 2 – if no tiny section found, merge the two
            // shortest adjacent sections with same <h2>.
            if (!$merged)
            {
                $shortestIdx = null;
                $shortestSum = PHP_INT_MAX;

                for ($i = 0; $i < count($sections) - 1; $i++)
                {
                    if ($sections[$i]['h2_heading'] !== $sections[$i + 1]['h2_heading'])
                    {
                        continue;                 // different topics → skip
                    }
                    $sum = $sections[$i]['word_count'] + $sections[$i + 1]['word_count'];
                    if ($sum < $shortestSum)
                    {
                        $shortestSum = $sum;
                        $shortestIdx = $i;
                    }
                }

                // fallback: if still null (all different H2), merge last two
                if ($shortestIdx === null)
                {
                    $shortestIdx = count($sections) - 2;
                }

                $sections[$shortestIdx] = $this->mergeSectionPair(
                    $sections[$shortestIdx],
                    $sections[$shortestIdx + 1]
                );
                array_splice($sections, $shortestIdx + 1, 1);
            }
        }

        return $sections;
    }

    private function mergeSectionPair(array $a, array $b): array
    {
        return [
            'heading'       => $a['heading'] ?: $b['heading'],
            'h2_heading'    => $a['h2_heading'] ?: $b['h2_heading'],
            'html'          => rtrim($a['html']) . "\n\n" . ltrim($b['html']),
            'text'          => rtrim($a['text']) . "\n\n" . ltrim($b['text']),
            'word_count'    => $a['word_count'] + $b['word_count'],
            'chunk_indices' => array_merge($a['chunk_indices'], $b['chunk_indices']),
        ];
    }

    /**
     * Inject product blocks into the full set of paragraph‐chunks.
     *
     * @param array $adSections   Array of sections from getAdSlotSections(), in insertion order.
     * @param array $blockHtmls   Array of HTML strings (shortcodes or block markup),
     *                            one per $adSections entry.
     * @return array              New paragraph‐chunk list with blocks injected.
     */
    public function injectAdSlots(array $adSections, array $blockHtmls): array
    {
        // Build map: chunk index → list of blocks to insert after that index
        $insertAfter = [];

        foreach ($adSections as $i => $slot)
        {
            $indices = $slot['chunk_indices'] ?? [];
            if (empty($indices))
            {
                continue;
            }

            if ($i === 0)
            {
                // first section and <!--more--> → inject at the end
                if (strpos($slot['html'], '<!--more-->') !== false)
                {
                    $chunkToInjectAfter = $indices[count($indices) - 1];
                }
                else
                {
                    // first section → inject after the middle chunk
                    $middlePos = (int) floor(count($indices) / 2);
                    $chunkToInjectAfter = $indices[$middlePos];
                }
            }
            else
            {
                // subsequent sections → randomly after the 1st or 2nd chunk
                $maxPick = count($indices) > 1 ? 1 : 0;
                $pick    = random_int(0, $maxPick);
                $chunkToInjectAfter = $indices[$pick];
            }

            $htmlBlock = $blockHtmls[$i] ?? '';
            if ($htmlBlock !== '')
            {
                $insertAfter[$chunkToInjectAfter][] = $htmlBlock;
            }
        }

        // Walk original chunks, splicing in blocks at the chosen points
        $newChunks = [];
        foreach ($this->paragraphChunks as $idx => $chunkHtml)
        {
            $newChunks[] = $chunkHtml;
            if (!empty($insertAfter[$idx]))
            {
                foreach ($insertAfter[$idx] as $blockHtml)
                {
                    $newChunks[] = "\n\n" . $blockHtml . "\n\n";
                }
            }
        }

        return $newChunks;
    }

    /**
     * Rebuild the post content from an ordered list of chunks.
     *
     * @param  array|null $chunks  Array of HTML snippets in display‑order.
     * @param  bool       $trim    Trim excessive line‑breaks between chunks.
     * @return string              Full post HTML ready for wp_update_post().
     */
    public function assembleContent(?array $chunks = null): string
    {
        if ($chunks === null)
        {
            $chunks = $this->paragraphChunks ?: [];
        }

        $html = implode("\n\n", $chunks);

        $html = $this->normalizePostWhitespace($html);

        return (string) apply_filters('cegg_prefill_rebuilt_post_html', $html, $chunks);
    }

    /**
     * Inject a hero block into post content.
     *
     * @param string $content
     * @param string $block
     *
     * @return string         The filtered content.
     */
    public function addHeroBlock($content, $block)
    {
        if (trim($block) === '')
        {
            return $content;
        }

        // 1. Gutenberg “More” block
        if (strpos($content, '<!-- /wp:more') !== false)
        {
            return preg_replace(
                '/(\<\!\-\-\s*\/wp:more\s*\-\-\>)/i',
                '$1' . "\n\n" . $block,
                $content,
                1
            );
        }

        // 2. Classic editor “more” tag
        if (strpos($content, '<!--more') !== false)
        {
            return preg_replace(
                '/(\<\!\-\-\s*more\s*\-\-\>)/i',
                '$1' . "\n\n" . $block,
                $content,
                1
            );
        }

        // 3. Fallback – prepend the hero block.
        return $block . "\n\n" . $content;
    }
}
