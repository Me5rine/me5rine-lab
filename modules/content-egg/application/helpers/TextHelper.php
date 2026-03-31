<?php

namespace ContentEgg\application\helpers;

defined('\ABSPATH') || exit;

/**
 * TextHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class TextHelper
{

    public static function truncate($string, $length = 80, $etc = '...', $charset = 'UTF-8', $break_words = false, $middle = false)
    {
        if ($length == 0)
        {
            return '';
        }

        if (mb_strlen($string, 'UTF-8') > $length)
        {
            $length -= min($length, mb_strlen($etc, 'UTF-8'));
            if (!$break_words && !$middle)
            {
                $string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1, $charset));
            }
            if (!$middle)
            {
                return mb_substr($string, 0, $length, $charset) . $etc;
            }
            else
            {
                return mb_substr($string, 0, $length / 2, $charset) . $etc . mb_substr($string, -$length / 2, $charset);
            }
        }
        else
        {
            return $string;
        }
    }

    /**
     * Truncates text.
     * Modified version of cakephp truncate.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as Ending and appended to the trimmed string (`ending` is deprecated)
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of html attributes and options.
     *
     * @return string Trimmed string.
     * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
     */
    public static function truncateHtml($text, $length = 100, $options = array())
    {
        $defaults = array(
            'ellipsis' => '...',
            'exact' => false,
            'html' => true
        );
        if (isset($options['ending']))
        {
            $defaults['ellipsis'] = $options['ending'];
        }
        elseif (!empty($options['html']))
        {
            $defaults['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $defaults;
        extract($options);

        if (!function_exists('mb_strlen'))
        {
            class_exists('Multibyte');
        }

        if ($html)
        {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text), 'UTF-8') <= $length)
            {
                return $text;
            }
            $totalLength = mb_strlen(strip_tags($ellipsis), 'UTF-8');
            $openTags = array();
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag)
            {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2]))
                {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0]))
                    {
                        array_unshift($openTags, $tag[2]);
                    }
                    elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag))
                    {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false)
                        {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]), 'UTF-8');
                if ($contentLength + $totalLength > $length)
                {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE))
                    {
                        foreach ($entities[0] as $entity)
                        {
                            if ($entity[1] + 1 - $entitiesLength <= $left)
                            {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0], 'UTF-8');
                            }
                            else
                            {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength, 'UTF-8');
                    break;
                }
                else
                {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length)
                {
                    break;
                }
            }
        }
        else
        {
            if (mb_strlen($text, 'UTF-8') <= $length)
            {
                return $text;
            }
            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis, 'UTF-8'), 'UTF-8');
        }
        if (!$exact)
        {
            $spacepos = mb_strrpos($truncate, ' ', 0, 'UTF-8');
            if ($html)
            {
                $truncateCheck = mb_substr($truncate, 0, $spacepos, 'UTF-8');
                $lastOpenTag = mb_strrpos($truncateCheck, '<', 0, 'UTF-8');
                $lastCloseTag = mb_strrpos($truncateCheck, '>', 0, 'UTF-8');
                if ($lastOpenTag > $lastCloseTag)
                {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
                    $lastTag = array_pop($lastTagMatches[0]);
                    $spacepos = mb_strrpos($truncate, $lastTag, 0, 'UTF-8') + mb_strlen($lastTag, 'UTF-8');
                }
                $bits = mb_substr($truncate, $spacepos, null, 'UTF-8');
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags))
                {
                    if (!empty($openTags))
                    {
                        foreach ($droppedTags as $closingTag)
                        {
                            if (!in_array($closingTag[1], $openTags))
                            {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                    else
                    {
                        foreach ($droppedTags as $closingTag)
                        {
                            $openTags[] = $closingTag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos, 'UTF-8');
        }
        $truncate .= $ellipsis;

        if ($html)
        {
            foreach ($openTags as $tag)
            {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    public static function clear($str)
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $str);
    }

    public static function clearId($str)
    {
        return preg_replace('/[^a-zA-Z0-9_\-~\.@\|]/', '', $str);
    }

    public static function clear_utf8($str)
    {
        //$str = str_replace('/', ' ', $str);
        $str = preg_replace("/[^\pL\s\d\-\.\+_\,\/]+/ui", '', $str);
        $str = preg_replace("/\s+/ui", ' ', $str);

        return $str;
    }

    private static function get_random($matches)
    {
        $rand = array_rand($split = explode("|", $matches[1]));

        return $split[$rand];
    }

    public static function spin($str)
    {
        //$new_str = preg_replace_callback('/\{([^{}]*)\}/uim', array('TextHelper', 'get_random'), $str);
        $new_str = preg_replace_callback(
            '/\{([^{}]*)\}/uim',
            function ($matches)
            {
                $rand = array_rand($split = explode("|", $matches[1]));

                return $split[$rand];
            },
            $str
        );
        if ($new_str !== $str)
        {
            $str = TextHelper::spin($new_str);
        }

        return $str;
    }

    /* bool/array unserialize_xml ( string $input [ , callback $callback ] )
     * Unserializes an XML string, returning a multi-dimensional associative array, optionally runs a callback on all non-array data
     * Returns false on all failure
     * Notes:
     * Root XML tags are stripped
     * Due to its recursive nature, unserialize_xml() will also support SimpleXMLElement objects and arrays as input
     * Uses simplexml_load_string() for XML parsing, see SimpleXML documentation for more info
     */

    static public function unserialize_xml($input, $callback = null, $recurse = false)
    {
        //Отключение ошибок libxml
        libxml_use_internal_errors(false);

        // Get input, loading an xml string with simplexml if its the top level of recursion
        $data = ((!$recurse) && is_string($input)) ? @simplexml_load_string($input, '\SimpleXMLElement', LIBXML_NOCDATA) : $input;

        // Convert SimpleXMLElements to array
        if ($data instanceof \SimpleXMLElement)
        {
            $data = (array) $data;
        }

        // Recurse into arrays
        if (is_array($data))
        {
            foreach ($data as &$item)
            {
                $item = self::unserialize_xml($item, $callback, true);
            }
        }

        // Run callback and return
        return (!is_array($data) && is_callable($callback)) ? call_user_func($callback, $data) : $data;
    }

    static public function br2nl($str)
    {
        return str_ireplace(array("<br />", "<br>", "<br/>"), "\r\n", (string) $str);
    }

    static public function nl2br($str)
    {
        return str_replace(array("\r\n", "\r", "\n"), "<br />", (string) $str);
    }

    static public function removeExtraBreaks($str)
    {
        return trim(preg_replace("/(\r\n)+/i", "\r\n", (string) $str));
    }

    static public function removeExtraBr($str)
    {
        return preg_replace('#<br[^>]*>(\s*<br[^>]*>)+#', '<br />', (string) $str);
    }

    /**
     * 10.55 -> 1055 & 1055 -> 10.55
     */
    static public function pricePenniesDenomination($p, $to_int = true)
    {
        if ($to_int)
        {
            $p = (int) number_format($p, 2, '', '');
        }
        else
        {
            if (strlen($p) == 2) // Under $1
            {
                $p = '00' . $p;
            }
            $p = number_format(preg_replace("/(\d+)(\d{2}$)/msi", '${1}.${2}', $p), 2, '.', '');
        }

        return $p;
    }

    static public function currencyTyping($c)
    {
        return TemplateHelper::currencyTyping($c);
    }

    public static function safeHtml($html, $type)
    {
        $allowed = array(
            'p' => array(),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'b' => array(),
            'i' => array(),
            'strong' => array(),
            'em' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'br' => array(),
            'hr' => array(),
            'em' => array(),
            'mark' => array(),
        );
        switch ($type)
        {
            case 'allow_all':
                return $html;
            case 'safe_html':
                return \wp_kses_post($html);
            case 'allowed_tags':
                return \wp_kses($html, $allowed);
            default:
                return strip_tags($html);
        }
    }

    public static function commaList($str, $input_delimer = ',', $return_delimer = ',')
    {
        $parts = explode($input_delimer, $str);
        $parts = array_map('trim', $parts);

        return join($return_delimer, $parts);
    }

    public static function prepareKeywords(array $keywords)
    {
        $result = array();
        foreach ($keywords as $keyword)
        {
            if (filter_var($keyword, FILTER_VALIDATE_URL))
                $keyword = \esc_url_raw($keyword);
            else
                $keyword = \sanitize_text_field($keyword);

            $keyword = trim($keyword);

            if (!$keyword)
                continue;

            $result[] = $keyword;
        }

        return $result;
    }

    public static function parsePriceAmount(string $money): float
    {
        // 1. Keep only digits, dots, commas and minus sign.
        $clean = preg_replace('/[^\d.,-]+/u', '', $money);

        if ($clean === null || $clean === '')
        {
            return 0.0;
        }

        // 2. Detect and strip leading minus.
        $negative = false;
        if ($clean[0] === '-')
        {
            $negative = true;
            $clean = substr($clean, 1);
        }

        if ($clean === '')
        {
            return 0.0;
        }

        $lastDot   = strrpos($clean, '.');
        $lastComma = strrpos($clean, ',');

        // 3. Decide which (if any) mark is the decimal separator.
        $decimalSep = null;

        if ($lastDot !== false && $lastComma !== false)
        {
            // Both present: whichever comes last is almost always the decimal separator.
            $decimalSep = ($lastDot > $lastComma) ? '.' : ',';
        }
        else
        {
            // Only one type of separator is present: need heuristics.
            $sep = null;
            $pos = null;

            if ($lastDot !== false)
            {
                $sep = '.';
                $pos = $lastDot;
            }
            elseif ($lastComma !== false)
            {
                $sep = ',';
                $pos = $lastComma;
            }

            if ($sep !== null && $pos !== null)
            {
                $digitsAfter = strlen($clean) - $pos - 1;

                /**
                 * Heuristic:
                 *  - 0 digits after: separator is thousands (e.g. "1." → 1).
                 *  - 1–2 digits after: treat as decimal (e.g. "10,5", "10.50").
                 *  - ≥3 digits after: treat as thousands (e.g. "10,000", "1.234").
                 */
                if ($digitsAfter > 0 && $digitsAfter <= 2)
                {
                    $decimalSep = $sep;
                }
            }
        }

        // 4. Strip thousands separators & normalize decimal to dot.
        if ($decimalSep === '.')
        {
            // Dot is decimal, commas are thousands.
            $number = str_replace(',', '', $clean);
        }
        elseif ($decimalSep === ',')
        {
            // Comma is decimal, dots are thousands.
            $number = str_replace('.', '', $clean);
            $number = str_replace(',', '.', $number);
        }
        else
        {
            // No decimal separator: remove all separators, treat as integer.
            $number = str_replace([',', '.'], '', $clean);
        }

        // 5. Cast and round to 2 decimal places.
        $value = (float) $number;
        if ($negative)
        {
            $value = -$value;
        }

        return round($value, 2);
    }

    protected static function decideSingleSeparator(string $str, string $sep, int $pos): ?string
    {
        // Multiple occurrences → thousands
        if (substr_count($str, $sep) > 1)
        {
            return null;
        }

        $digitsAfter  = strlen($str) - $pos - 1;
        $digitsBefore = $pos;

        // Looks like thousands if exactly 3 digits after and ≤3 digits before
        if ($digitsAfter === 3 && $digitsBefore <= 3)
        {
            return null;
        }

        return $sep;
    }

    public static function parseCurrencyCode($money)
    {
        $currencies = array(
            '$' => 'USD',
            '£' => 'GBP',
            'EUR' => 'EUR',
            '₹' => 'INR',
            'Rs.' => 'INR',
            '€' => 'EUR',
            '₫' => 'VND',
            'грн' => 'UAH'
        );

        foreach ($currencies as $symbol => $code)
        {
            if (strstr($money, $symbol))
            {
                return $code;
            }
        }

        return null;
    }

    public static function randomPassword($len = 8)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++)
        {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    public static function getHostName($url)
    {
        $url = (string)$url;
        $url = trim($url);
        if (!parse_url($url, PHP_URL_HOST))
            return '';
        return TextHelper::getDomainWithoutSubdomain(strtolower(str_ireplace('www.', '', parse_url($url, PHP_URL_HOST))));
    }

    public static function isValidDomainName($domain)
    {
        $domain = (string)$domain;
        return preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $domain);
    }

    public static function findOriginalDomain($url)
    {
        if (!$query = parse_url($url, PHP_URL_QUERY))
        {
            return '';
        }
        parse_str($query, $params);
        foreach ($params as $param)
        {
            if (filter_var($param, FILTER_VALIDATE_URL))
            {
                return TextHelper::getHostName($param);
            }
        }
    }

    public static function parseOriginalUrl($url, $go_param)
    {
        $url = html_entity_decode($url);
        if (!$query = parse_url($url, PHP_URL_QUERY))
        {
            return '';
        }
        parse_str($query, $arr);
        if (isset($arr[$go_param]))
        {
            return $arr[$go_param];
        }
        else
        {
            return '';
        }
    }

    public static function parseDomain($url, $go_param)
    {
        if (!$url = TextHelper::parseOriginalUrl($url, $go_param))
        {
            return '';
        }

        return TextHelper::getHostName($url);
    }

    public static function getDomainWithoutSubdomain($domain)
    {
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,7})$/i', $domain, $regs))
        {
            return $regs['domain'];
        }

        return $domain;
    }

    public static function getRandomFromCommaList($str)
    {
        $str = TextHelper::commaList($str);
        $arr = explode(",", $str);

        return $arr[array_rand($arr)];
    }

    public static function getArrayFromCommaList($str)
    {
        return explode(",", TextHelper::commaList($str));
    }

    /**
     * Verify a correct EAN13 structure
     */
    public static function isEan($barcode)
    {
        $barcode = self::fixEan($barcode);

        if (!preg_match("/^[0-9]{13}$/", $barcode))
        {
            return false;
        }

        $digits = (string) $barcode;
        $even_sum = $digits[1] + $digits[3] + $digits[5] +
            $digits[7] + $digits[9] + $digits[11];
        $even_sum_three = $even_sum * 3;
        $odd_sum = $digits[0] + $digits[2] + $digits[4] +
            $digits[6] + $digits[8] + $digits[10];
        $total_sum = $even_sum_three + $odd_sum;
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = $next_ten - $total_sum;
        if ($check_digit == $digits[12])
        {
            return true;
        }

        return false;
    }

    public static function fixEan($barcode, $n = 13)
    {
        if (strlen($barcode == $n))
        {
            return $barcode;
        }

        $barcode = ltrim($barcode, '0');
        $barcode = str_pad($barcode, $n, '0', STR_PAD_LEFT);

        return $barcode;
    }

    /**
     * Is valid amazon ASIN
     */
    public static function isAsin($str)
    {
        if (preg_match('/^(B[\dA-Z]{9}|\d{9}(X|\d))$/', $str))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function splitAsins($str)
    {
        $asins = explode(',', TextHelper::commaList($str, ' ', ','));
        $res = array();
        foreach ($asins as $asin)
        {
            if (self::isAsin($asin))
            {
                $res[] = $asin;
            }
        }

        return $res;
    }

    public static function ratingPrepare($rating, $min_rating = 1, $max_rating = 5)
    {
        $rating = (float) $rating;
        $rating = abs(round($rating));
        if ($rating < $min_rating || $rating > $max_rating)
            return null;
        else
            return $rating;
    }

    public static function sluggable($string)
    {
        $cyrylicFrom = array(
            'Є',
            'І',
            'Ѓ',
            'і',
            'є',
            'ѓ',
            'А',
            'Б',
            'В',
            'Г',
            'Д',
            'Е',
            'Ё',
            'Ж',
            'З',
            'И',
            'Й',
            'К',
            'Л',
            'М',
            'Н',
            'О',
            'П',
            'Р',
            'С',
            'Т',
            'У',
            'Ф',
            'Х',
            'Ц',
            'Ч',
            'Ш',
            'Щ',
            'Ъ',
            'Ы',
            'Ь',
            'Э',
            'Ю',
            'Я',
            'а',
            'б',
            'в',
            'г',
            'д',
            'е',
            'ё',
            'ж',
            'з',
            'и',
            'й',
            'к',
            'л',
            'м',
            'н',
            'о',
            'п',
            'р',
            'с',
            'т',
            'у',
            'ф',
            'х',
            'ц',
            'ч',
            'ш',
            'щ',
            'ъ',
            'ы',
            'ь',
            'э',
            'ю',
            'я'
        );
        $cyrylicTo = array(
            'YE',
            'I',
            'G',
            'i',
            'ye',
            'g',
            'A',
            'B',
            'V',
            'G',
            'D',
            'E',
            'YO',
            'ZH',
            'Z',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'F',
            'X',
            'C',
            'CH',
            'SH',
            'SHH',
            '',
            'Y',
            '',
            'E',
            'YU',
            'YA',
            'a',
            'b',
            'v',
            'g',
            'd',
            'e',
            'yo',
            'zh',
            'z',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            't',
            'u',
            'f',
            'x',
            'c',
            'ch',
            'sh',
            'shh',
            '',
            'y',
            '',
            'e',
            'yu',
            'ya'
        );
        $from = array(
            "Á",
            "À",
            "Â",
            "Ä",
            "Ă",
            "Ā",
            "Ã",
            "Å",
            "Ą",
            "Æ",
            "Ć",
            "Ċ",
            "Ĉ",
            "Č",
            "Ç",
            "Ď",
            "Đ",
            "Ð",
            "É",
            "È",
            "Ė",
            "Ê",
            "Ë",
            "Ě",
            "Ē",
            "Ę",
            "Ə",
            "Ġ",
            "Ĝ",
            "Ğ",
            "Ģ",
            "á",
            "à",
            "â",
            "ä",
            "ă",
            "ā",
            "ã",
            "å",
            "ą",
            "æ",
            "ć",
            "ċ",
            "ĉ",
            "č",
            "ç",
            "ď",
            "đ",
            "ð",
            "é",
            "è",
            "ė",
            "ê",
            "ë",
            "ě",
            "ē",
            "ę",
            "ə",
            "ġ",
            "ĝ",
            "ğ",
            "ģ",
            "Ĥ",
            "Ħ",
            "I",
            "Í",
            "Ì",
            "İ",
            "Î",
            "Ï",
            "Ī",
            "Į",
            "Ĳ",
            "Ĵ",
            "Ķ",
            "Ļ",
            "Ł",
            "Ń",
            "Ň",
            "Ñ",
            "Ņ",
            "Ó",
            "Ò",
            "Ô",
            "Ö",
            "Õ",
            "Ő",
            "Ø",
            "Ơ",
            "Œ",
            "ĥ",
            "ħ",
            "ı",
            "í",
            "ì",
            "i",
            "î",
            "ï",
            "ī",
            "į",
            "ĳ",
            "ĵ",
            "ķ",
            "ļ",
            "ł",
            "ń",
            "ň",
            "ñ",
            "ņ",
            "ó",
            "ò",
            "ô",
            "ö",
            "õ",
            "ő",
            "ø",
            "ơ",
            "œ",
            "Ŕ",
            "Ř",
            "Ś",
            "Ŝ",
            "Š",
            "Ş",
            "Ť",
            "Ţ",
            "Þ",
            "Ú",
            "Ù",
            "Û",
            "Ü",
            "Ŭ",
            "Ū",
            "Ů",
            "Ų",
            "Ű",
            "Ư",
            "Ŵ",
            "Ý",
            "Ŷ",
            "Ÿ",
            "Ź",
            "Ż",
            "Ž",
            "ŕ",
            "ř",
            "ś",
            "ŝ",
            "š",
            "ş",
            "ß",
            "ť",
            "ţ",
            "þ",
            "ú",
            "ù",
            "û",
            "ü",
            "ŭ",
            "ū",
            "ů",
            "ų",
            "ű",
            "ư",
            "ŵ",
            "ý",
            "ŷ",
            "ÿ",
            "ź",
            "ż",
            "ž"
        );
        $to = array(
            "A",
            "A",
            "A",
            "A",
            "A",
            "A",
            "A",
            "A",
            "A",
            "AE",
            "C",
            "C",
            "C",
            "C",
            "C",
            "D",
            "D",
            "D",
            "E",
            "E",
            "E",
            "E",
            "E",
            "E",
            "E",
            "E",
            "G",
            "G",
            "G",
            "G",
            "G",
            "a",
            "a",
            "a",
            "a",
            "a",
            "a",
            "a",
            "a",
            "a",
            "ae",
            "c",
            "c",
            "c",
            "c",
            "c",
            "d",
            "d",
            "d",
            "e",
            "e",
            "e",
            "e",
            "e",
            "e",
            "e",
            "e",
            "g",
            "g",
            "g",
            "g",
            "g",
            "H",
            "H",
            "I",
            "I",
            "I",
            "I",
            "I",
            "I",
            "I",
            "I",
            "IJ",
            "J",
            "K",
            "L",
            "L",
            "N",
            "N",
            "N",
            "N",
            "O",
            "O",
            "O",
            "O",
            "O",
            "O",
            "O",
            "O",
            "CE",
            "h",
            "h",
            "i",
            "i",
            "i",
            "i",
            "i",
            "i",
            "i",
            "i",
            "ij",
            "j",
            "k",
            "l",
            "l",
            "n",
            "n",
            "n",
            "n",
            "o",
            "o",
            "o",
            "o",
            "o",
            "o",
            "o",
            "o",
            "o",
            "R",
            "R",
            "S",
            "S",
            "S",
            "S",
            "T",
            "T",
            "T",
            "U",
            "U",
            "U",
            "U",
            "U",
            "U",
            "U",
            "U",
            "U",
            "U",
            "W",
            "Y",
            "Y",
            "Y",
            "Z",
            "Z",
            "Z",
            "r",
            "r",
            "s",
            "s",
            "s",
            "s",
            "B",
            "t",
            "t",
            "b",
            "u",
            "u",
            "u",
            "u",
            "u",
            "u",
            "u",
            "u",
            "u",
            "u",
            "w",
            "y",
            "y",
            "y",
            "z",
            "z",
            "z"
        );
        $from = array_merge($from, $cyrylicFrom);
        $to = array_merge($to, $cyrylicTo);
        $string = str_replace($from, $to, $string);

        if (function_exists('iconv'))
        {
            $string = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $string);
        }
        $string = preg_replace("/[^A-Za-z0-9'_\-\.]/", '-', $string);
        $string = preg_replace('/\-+/', '-', $string);
        $string = preg_replace('/^-+/', '', $string);
        $string = preg_replace('/-+$/', '', $string);

        return $string;
    }

    public static function addUrlParam($url, $param_name, $param_value, $replace = true)
    {
        $url_parts = parse_url($url);
        if (isset($url_parts['query']))
        {
            $query = $url_parts['query'];
        }
        else
        {
            $query = '';
        }
        parse_str($query, $query_array);
        if (!isset($query_array[$param_name]) && !$replace)
        {
            return $url;
        }
        if (isset($query_array[$param_name]) && $query_array[$param_name] == $param_value)
        {
            return $url;
        }

        $query_array[$param_name] = $param_value;

        return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . http_build_query($query_array);
    }

    public static function removeUrlParam($url, $param_name)
    {
        $url_parts = parse_url($url);
        if (isset($url_parts['query']))
        {
            $query = $url_parts['query'];
        }
        else
        {
            $query = '';
        }
        parse_str($query, $query_array);
        if (!isset($query_array[$param_name]))
        {
            return $url;
        }

        unset($query_array[$param_name]);

        return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . http_build_query($query_array);
    }

    public static function getParamFromPath($path, $param)
    {
        $parts = explode('/', $path);
        $position = array_search($param, $parts);

        if ($position !== false && array_key_exists($position + 1, $parts))
        {
            return $parts[$position + 1];
        }
        else
        {
            return false;
        }
    }

    public static function getUrlWithoutDomain($url)
    {
        if (!$parts = parse_url($url))
        {
            return false;
        }
        $res = '';
        if (isset($parts['path']))
        {
            $res .= $parts['path'];
        }
        if (isset($parts['query']))
        {
            $res .= '?' . $parts['query'];
        }

        return $res;
    }

    public static function urlHost($url)
    {
        return strtolower(preg_replace('/^www\./', '', parse_url(strtolower(trim($url)), PHP_URL_HOST)));
    }

    public static function sanitizeHtml($string)
    {
        $allowed_html = array(
            'abbr' => array(),
            'br' => array(),
            'blockquote' => array(),
            'cite' => array(),
            'code' => array(),
            'del' => array(),
            'em' => array(),
            'strong' => array(),
            'pre' => array(),
            'i' => array(),
            'p' => array(),
            'b' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'strike' => array(),
            's' => array(),
            'table' => array(),
            'tr' => array(),
            'td' => array(),
            'th' => array(),
            'span' => array('class' => array()),
            'mark' => array(),
            'a' => array('href' => array(), 'rel' => array()),
            'dl' => array(),
            'dt' => array(),
            'dd' => array(),
            'div' => array(),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
            'div' => array('class' => array(), 'style' => array()),
        );

        return \wp_kses($string, $allowed_html);
    }

    public static function isHtmlTagDetected($string)
    {
        $string = (string) $string;
        if ($string != strip_tags($string))
            return true;
        else
            return false;
    }

    public static function findOriginalUrl($url)
    {
        if (!$query = parse_url($url, PHP_URL_QUERY))
            return '';

        parse_str($query, $params);

        if (isset($params['url']) && $query2 = parse_url($params['url'], PHP_URL_QUERY))
        {
            parse_str($query2, $params2);
            if (isset($params2['origin_link']))
                return $params2['origin_link'];
        }

        foreach ($params as $param)
        {
            if (filter_var($param, FILTER_VALIDATE_URL))
                return $param;
        }

        return false;
    }

    public static function trimExcerpt($text)
    {
        $raw_excerpt = $text;
        $text = strip_shortcodes($text);
        $text = excerpt_remove_blocks($text);
        $text = apply_filters('the_content', $text);
        $text = str_replace(']]>', ']]&gt;', $text);
        $excerpt_length = (int) _x('55', 'excerpt_length');
        $excerpt_length = (int) apply_filters('excerpt_length', $excerpt_length);
        $excerpt_more = apply_filters('excerpt_more', ' ' . '[&hellip;]');
        $text = wp_trim_words($text, $excerpt_length, $excerpt_more);
        return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
    }

    static public function isUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function countWords($text, $lang = '')
    {
        if (!$text)
            return 0;

        if (is_array($text))
        {
            $to_string = array();
            array_walk_recursive($text, function ($v) use (&$to_string)
            {
                $to_string[] = $v;
            });
            $to_string = implode(' ', $to_string);

            $text = $to_string;
        }

        $symbol_to_word_ratio = array(
            'Chinese' => 0.70,
            'Korean' => 0.5,
            'Japanese' => 0.5,
            'Thai' => 0.20,
        );

        if ($lang && isset($symbol_to_word_ratio[$lang]))
        {
            $count_characters = self::countCharacters($text);
            return round($count_characters * $symbol_to_word_ratio[$lang]);
        }

        $words = preg_split("/[\n\r\t ]+/", $text, 0, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    public static function countCharacters($text)
    {
        if (!$text)
            return 0;

        if (is_array($text))
        {
            $to_string = array();
            array_walk_recursive($text, function ($v) use (&$to_string)
            {
                $to_string[] = $v;
            });
            $to_string = implode(' ', $to_string);

            $text = $to_string;
        }

        $text = preg_replace("/[\n\r\t ]+/", '', $text);
        return mb_strlen($text, 'UTF-8');
    }
}
