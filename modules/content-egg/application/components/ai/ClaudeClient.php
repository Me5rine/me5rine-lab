<?php

namespace ContentEgg\application\components\ai;;

defined('\ABSPATH') || exit;

/**
 * ClaudeClient class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ClaudeClient extends AiClient
{
	const API_VERSION = '2023-06-01';
	const MAX_TOKENS = 4096;

	public function getChatUrl()
	{
		return 'https://api.anthropic.com/v1/messages';
	}

	public function getHeaders()
	{
		return array(
			'Content-Type: application/json',
			'x-api-key:' . $this->api_key,
			'anthropic-version: ' . self::API_VERSION,
		);
	}

	public function getPayload($prompt, $system = '', $params = array())
	{
		$payload = $messages = array();

		if ($system)
			$payload['system'] = $system;

		$message = array(
			'role' => 'user',
			'content' => $prompt,
		);

		$messages[] = $message;
		$payload['messages'] = $messages;
		$payload['max_tokens'] = self::MAX_TOKENS;
		$payload = array_merge($params, $payload);

		return $payload;
	}

	public function getContent($response)
	{
		if (!$data = json_decode($response, true))
			throw new \Exception('Invalid JSON formatting.');

		if (!isset($data['content'][0]['text']))
			throw new \Exception('No content in the claude response.');

		$content = $data['content'][0]['text'];

		if (isset($data['usage']))
			$this->last_usage = $data['usage'];
		else
			$this->last_usage = array();

		return $content;
	}
}
