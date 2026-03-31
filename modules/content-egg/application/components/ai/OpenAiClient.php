<?php

namespace ContentEgg\application\components\ai;;

defined('\ABSPATH') || exit;

/**
 * OpenAiClient class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class OpenAiClient extends AiClient
{
	//@link: https://openai.com/api/pricing/
	const PRICE_INPUT_5_nano = 0.05;
	const PRICE_OUTPUT_5_nano = 0.40;
	const PRICE_INPUT_4o_mini = 0.150;
	const PRICE_OUTPUT_4o_mini = 0.600;
	const PRICE_INPUT_4o = 2.50;
	const PRICE_OUTPUT_4o = 10.00;

	public function getChatUrl()
	{
		return 'https://api.openai.com/v1/chat/completions';
	}

	public function getHeaders()
	{
		return array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->api_key,
		);
	}

	public function getAiModelPrices()
	{
		return [
			'gpt-5-nano' => [
				'input'  => apply_filters('cegg_price_input_4o_nano', self::PRICE_INPUT_5_nano),
				'output' => apply_filters('cegg_price_output_4o_nano', self::PRICE_OUTPUT_5_nano),
			],
			'gpt-4o-mini' => [
				'input'  => apply_filters('cegg_price_input_4o_mini', self::PRICE_INPUT_4o_mini),
				'output' => apply_filters('cegg_price_output_4o_mini', self::PRICE_OUTPUT_4o_mini),
			],
			'gpt-4o' => [
				'input'  => apply_filters('cegg_price_input_4o', self::PRICE_INPUT_4o),
				'output' => apply_filters('cegg_price_output_4o', self::PRICE_OUTPUT_4o),
			],
		];
	}

	public function getPayload($prompt, $system = '', $params = array())
	{
		$messages = array();

		if ($system)
		{
			$message = array(
				'role' => 'system',
				'content' => $system,
			);

			$messages[] = $message;
		}

		$message = array(
			'role' => 'user',
			'content' => $prompt,
		);

		$messages[] = $message;

		$payload = array(
			'messages' => $messages,
		);

		$payload = array_merge($params, $payload);

		return $payload;
	}

	public function getContent($response)
	{
		if (!$data = json_decode($response, true))
			throw new \Exception('Invalid JSON formatting.');

		if (isset($data['error']['message']))
		{
			$errorMessage = 'AI API error: ' . $data['error']['message'];
			if (isset($data['error']['code']))
				$errorMessage .= ' | Error code: ' . $data['error']['code'];

			if (isset($data['error']['metadata']['raw']))
				$errorMessage .= ' | Raw metadata: ' . $data['error']['metadata']['raw'];

			throw new \Exception(esc_html($errorMessage));
		}

		if (!isset($data['choices'][0]['message']['content']))
			throw new \Exception('No content message in the AI response.');

		$content = $data['choices'][0]['message']['content'];

		if (isset($data['usage']))
			$this->last_usage = $data['usage'];
		else
			$this->last_usage = array();

		return $content;
	}

	public function getLastUsagePrice()
	{
		if (!$this->last_usage)
			return 0;

		$price = $this->last_usage['prompt_tokens'] / 1000000 * $this->getLastUsedModelPriceInput();
		$price += $this->last_usage['completion_tokens'] / 1000000 * $this->getLastUsedModelPriceOutput();

		return $price;
	}

	public function getLastUsedModelPriceInput()
	{
		$prices = $this->getAiModelPrices();
		return $prices[$this->last_used_model]['input'];
	}

	public function getLastUsedModelPriceOutput()
	{
		$prices = self::getAiModelPrices();
		return $prices[$this->last_used_model]['output'];
	}
}
