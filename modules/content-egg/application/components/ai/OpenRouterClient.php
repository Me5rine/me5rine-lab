<?php

namespace ContentEgg\application\components\ai;


defined('\ABSPATH') || exit;

/**
 * OpenRouterClient class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class OpenRouterClient extends OpenAiClient
{
	public function getChatUrl()
	{
		return 'https://openrouter.ai/api/v1/chat/completions';
	}
}
