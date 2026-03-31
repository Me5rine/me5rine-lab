<?php

namespace ContentEgg\application\components\ai;

defined('\ABSPATH') || exit;

/**
 * SystemPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class SystemPrompt
{
    const MODEL = 'gpt-4o-mini';

    protected $client;
    protected $lang;

    public function __construct($api_key, $lang = '')
    {
        if ($lang)
        {
            $this->lang = $lang;
        }

        $this->client = new OpenAiClient($api_key, self::MODEL);
    }

    protected function query($prompt, array $placeholders = [], $system = '', array $ai_options = [])
    {
        if (!isset($placeholders['lang']) && $this->lang && $this->lang !== 'English')
        {
            $placeholders['lang'] = $this->lang;
            $system .= "\nRespond in %lang%!";
        }

        $system = trim($system);

        $prompt = $this->buildPrompt($prompt, $placeholders);
        $system = $this->buildPrompt($system, $placeholders);

        try
        {
            $content = $this->client->query($prompt, $system, $ai_options);
        }
        catch (\Exception $e)
        {
            $error = sprintf('AI Error: %s', $e->getMessage());
            throw new \Exception(esc_html($error));
        }

        return $content;
    }

    protected function queryList($prompt, array $placeholders = [], $system = '', array $ai_options = [], $list_name = 'list', $item_name = 'item')
    {
        $system .= ' Produce JSON format: {"' . $list_name . '": ["' . $item_name . '1", "' . $item_name . '2",...]}. If no results are found, return {"' . $list_name . '": []}';
        $system = trim($system);

        $ai_options['response_format'] = array(
            'type' => 'json_object',
        );

        $responseContent = $this->query($prompt, $placeholders, $system, $ai_options);

        $decodedResponse = json_decode($responseContent, true);

        if (json_last_error() !== JSON_ERROR_NONE)
            return [];

        if (!isset($decodedResponse[$list_name]) || !is_array($decodedResponse[$list_name]))
            return [];

        return $decodedResponse[$list_name];
    }

    protected function queryString($prompt, array $placeholders = [], $system = '', array $ai_options = [], $string_name = 'result')
    {
        $system .= ' Produce JSON format: {"' . $string_name . '": "..."}. If no result is found, return: {"' . $string_name . '": ""}.';
        $system = trim($system);

        $ai_options['response_format'] = array(
            'type' => 'json_object',
        );

        $responseContent = $this->query($prompt, $placeholders, $system, $ai_options);

        $decodedResponse = json_decode($responseContent, true);

        if (json_last_error() !== JSON_ERROR_NONE)
            return [];

        if (!isset($decodedResponse[$string_name]) || !is_scalar($decodedResponse[$string_name]))
            return [];

        return $decodedResponse[$string_name];
    }

    public function getLastUsage()
    {
        return $this->client->getLastUsage();
    }

    public function getLastUsageStat()
    {
        $usage = $this->client->getLastUsage();

        if (!$usage)
        {
            return [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'ai_cost' => 0,
            ];
        }

        return [
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'ai_cost' => $this->client->getLastUsagePrice() ?? 0,
        ];
    }

    public function buildPrompt($prompt, array $placeholders = array())
    {
        $placeholders = $this->preparePlaceholders($placeholders, $prompt);
        return PromptHelper::build($prompt, $placeholders);
    }

    public function preparePlaceholders(array $params, $prompt = '')
    {
        if (!isset($params['lang']))
            $params['lang'] = $this->lang;

        return $params;
    }
}
