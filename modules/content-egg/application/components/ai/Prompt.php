<?php

namespace ContentEgg\application\components\ai;



defined('\ABSPATH') || exit;

/**
 * Prompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class Prompt
{
    protected $lang;
    protected $temperature;
    protected $client;

    public function __construct($api_key, $model, $models = array())
    {
        $this->client = AiClient::createClient($api_key, $model, $models);
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function setTemperature($temperature)
    {
        $this->temperature = (float) $temperature;
    }

    protected function query($prompt, array $params = array(), array $ai_params = array())
    {
        $params = $this->prepareParams($params, $prompt);
        $prompt = PromptHelper::build($prompt, $params);
        if ($this->lang)
            $system = sprintf('Respond in %s!', $this->lang);

        if ($this->temperature && !isset($ai_params['temperature']))
        {
            $ai_params['temperature'] = (float) $this->temperature;
        }

        // GPT-5 models do not support temperature settings
        if (isset($ai_params['temperature']) && strpos($this->client->getModel(), 'gpt-5') !== false)
        {
            unset($ai_params['temperature']);
        }

        $content = $this->client->query($prompt, $system, $ai_params);

        return $content;
    }

    protected function prepareParams(array $params, $prompt = '')
    {
        return $params;
    }
}
