<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

/**
 * AbstractTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

abstract class AbstractTab
{
    protected $slug;
    protected $title;

    public function __construct($slug, $title)
    {
        $this->slug  = $slug;
        $this->title = $title;
    }

    abstract public function render();

    final public function getSlug()
    {
        return $this->slug;
    }

    final public function getTitle()
    {
        return $this->title;
    }

    public function enqueueAssets()
    {
    }
}
