<?php

namespace ContentEgg\application\components\stopwatch;

defined('\ABSPATH') || exit;

/**
 * StopwatchEvent abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class StopwatchEvent
{
    private $name;
    private $start_time;
    private $stop_time;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getStopTime()
    {
        return $this->start_time;
    }

    public function start()
    {
        $this->start_time = microtime(true);
    }

    public function stop()
    {
        if (!$this->start_time)
            new \Exception(sprintf('Event "%s" is not started yet', $this->name));

        $this->stop_time = microtime(true);
    }

    public function elapsed()
    {
        if (!$this->start_time)
            new \Exception(sprintf('Event "%s" is not started yet', $this->name));

        return microtime(true) - $this->start_time;
    }

    public function duration()
    {
        if (!$this->start_time)
            new \Exception(sprintf('Event "%s" is not started yet', $this->name));

        if (!$this->stop_time)
            new \Exception(sprintf('Event "%s" is not stopped yet', $this->name));

        return $this->stop_time - $this->start_time;
    }
}
