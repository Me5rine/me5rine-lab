<?php

namespace ContentEgg\application\components\ai;

defined('\ABSPATH') || exit;

/**
 * PromptHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PromptHelper
{
    public static function build($prompt, array $params)
    {
        $replace = array();

        foreach ($params as $name => $value)
        {
            $replace['%' . $name . '%'] = $value;
        }

        return str_ireplace(array_keys($replace), array_values($replace), $prompt);
    }
}
