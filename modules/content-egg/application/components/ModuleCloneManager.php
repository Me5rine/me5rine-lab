<?php

namespace ContentEgg\application\components;

use ContentEgg\application\helpers\TextHelper;;

defined('\ABSPATH') || exit;

/**
 * ModuleCloneManager class file
 *
 * @author
 * @link
 * @copyright
 */
class ModuleCloneManager
{
    const MODULES_OPTION_NAME = 'content_egg_clones';
    const MAX_CLONES_PER_PARENT = 20;

    public static function getClonedModules()
    {
        $cloned_modules = get_option(self::MODULES_OPTION_NAME, array());
        return (is_array($cloned_modules)) ? $cloned_modules : array();
    }

    private static function saveClonedModules(array $clones)
    {
        update_option(self::MODULES_OPTION_NAME, $clones, true);
    }

    public static function createClone($parent_id, $parent_name = '', $duplicate_parent_options = true)
    {
        $clones = self::getClonedModules();

        $max_suffix = self::findMaxSuffixForParent($clones, $parent_id);
        $next_suffix = $max_suffix + 1;

        if ($next_suffix > self::MAX_CLONES_PER_PARENT)
        {
            return false;
        }

        $clone_id = TextHelper::clearId($parent_id . '__clone' . $next_suffix);

        if ($parent_name)
        {
            $clone_name = $parent_name . ' Clone ' . $next_suffix;
        }
        else
        {
            $clone_name = ucfirst($parent_id) . ' Clone ' . $next_suffix;
        }

        if (isset($clones[$clone_id]))
        {
            return false;
        }

        if ($duplicate_parent_options)
        {
            $parent_options = get_option('content-egg_' . $parent_id, array());
            if ($parent_options)
            {
                $parent_options['feed_name'] = $parent_name . ' ' . $next_suffix;
                update_option('content-egg_' . $clone_id, $parent_options, true);
            }
        }

        $clones[$clone_id] = array(
            'clone_id'   => $clone_id,
            'clone_name' => sanitize_text_field($clone_name),
            'parent_id'  => $parent_id,
        );

        self::saveClonedModules($clones);

        return $clone_id;
    }

    private static function findMaxSuffixForParent(array $clones, $parent_id)
    {
        $max_suffix = 0;
        foreach ($clones as $clone)
        {
            if (isset($clone['parent_id']) && $clone['parent_id'] === $parent_id)
            {
                $parts = explode('__', $clone['clone_id']);
                $num = end($parts);
                $num = str_replace('clone', '', $num);
                $num = intval($num);

                if ($num > $max_suffix)
                {
                    $max_suffix = $num;
                }
            }
        }
        return $max_suffix;
    }

    public static function removeClone($clone_id)
    {
        $clones = self::getClonedModules();

        if (isset($clones[$clone_id]))
        {
            unset($clones[$clone_id]);
            self::saveClonedModules($clones);
            return true;
        }

        return false;
    }

    public static function renameClone($clone_id, $new_name)
    {
        $clones = self::getClonedModules();

        if (isset($clones[$clone_id]))
        {
            $clones[$clone_id]['clone_name'] = sanitize_text_field($new_name);
            self::saveClonedModules($clones);
            return true;
        }

        return false;
    }

    public static function isCloningAllowed($module_id)
    {
        $modules = ModuleManager::getInstance()->getAffiliateParsers(false, true);

        if (!isset($modules[$module_id]))
        {
            return false;
        }

        $module = $modules[$module_id];

        if ($module->isFeedModule())
        {
            return false;
        }
        if ($module->isAeParser())
        {
            return false;
        }
        if ($module->isClone())
        {
            return false;
        }

        $clones = self::getClonedModules();
        $parentCloneCount = 0;

        foreach ($clones as $clone)
        {
            if (!empty($clone['parent_id']) && $clone['parent_id'] === $module_id)
            {
                $parentCloneCount++;
            }
            if ($parentCloneCount >= self::MAX_CLONES_PER_PARENT)
            {
                return false;
            }
        }

        return true;
    }

    public static function deleteClone($module_id)
    {
        $clones = self::getClonedModules();

        if (!isset($clones[$module_id]))
        {
            return false;
        }

        unset($clones[$module_id]);
        self::saveClonedModules($clones);

        return true;
    }
}
