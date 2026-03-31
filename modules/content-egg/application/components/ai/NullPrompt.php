<?php

namespace ContentEgg\application\components\ai;

use ContentEgg\application\helpers\TextHelper;;

defined('\ABSPATH') || exit;

/**
 * NullPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class NullPrompt
{
    public function __call(string $method, array $args)
    {
        throw new \Exception('AI features are disabled. Please set your OpenAI API key in CE Settings > AI.');
    }
}
