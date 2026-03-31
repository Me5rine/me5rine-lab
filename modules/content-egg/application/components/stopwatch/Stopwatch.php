<?php

namespace ContentEgg\application\components\stopwatch;

defined('\ABSPATH') || exit;

/**
 * Stopwatch abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class Stopwatch
{
    private $events = array();

    public function getEvent($name)
    {
        if (!isset($this->events[$name]))
            new \Exception(sprintf('Event "%s" does not exist', $name));

        return $this->events[$name];
    }

    public function start($name = 'default')
    {
        if (!isset($this->events[$name]))
            $event = $this->events[$name] = new StopwatchEvent($name);

        $event->start();
        return $event;
    }

    public function stop($name = 'default')
    {
        $event = $this->getEvent($name);
        $event->stop();
        return $event;
    }

    public function reset()
    {
        $this->events = array();
    }

    public function elapsed($name = 'default')
    {
        $event = $this->getEvent($name);
        return $event->elapsed();
    }

    public function duration($name = 'default')
    {
        $event = $this->getEvent($name);
        return $event->duration();
    }
}
