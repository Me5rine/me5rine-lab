<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ModuleCloneManager;
use ContentEgg\application\Plugin;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\TextHelper;

/**
 * ModuleSettingsContoller class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ModuleSettingsContoller
{

    const slug = 'content-egg-modules';

    public function __construct()
    {
        $this->actionHandler();
        \add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu()
    {
        \add_submenu_page(Plugin::slug(), __('Modules', 'content-egg') . ' &lsaquo; Content Egg', __('Modules', 'content-egg'), 'manage_options', self::slug, array($this, 'actionIndex'));
    }

    public function actionIndex()
    {
        \wp_enqueue_style('cegg-bootstrap5-full', '', Plugin::version());
        \wp_enqueue_style('cegg-bootstrap-icons', PluginAdmin::res('/admin/bootstrap/css/bootstrap-icons.min.css'), [], Plugin::version());
        PluginAdmin::getInstance()->render('module_index', array('modules' => ModuleManager::getInstance()->getConfigurableModules()));
    }

    public function actionHandler()
    {
        if (empty($GLOBALS['pagenow']) || $GLOBALS['pagenow'] != 'admin.php')
            return;

        if (empty($_GET['page']) || $_GET['page'] != 'content-egg-modules')
            return;

        if (!empty($_GET['action']) && $_GET['action'] == 'clone')
            $this->actionModuleClone();

        if (!empty($_GET['action']) && $_GET['action'] == 'delete_clone')
            $this->actionDeleteClone();
    }

    private function actionModuleClone()
    {
        if (!\check_admin_referer('ce_clone_module_action'))
            die('You do not have permission to view this page.');

        if (!\current_user_can('manage_options'))
            die('You do not have permission to view this page.');

        if (empty($_GET['module']))
            die('Module ID is required');

        $module_id = TextHelper::clearId($_GET['module']);

        if (!$module = ModuleManager::factory($module_id))
            die('Module not found');

        if (!ModuleCloneManager::isCloningAllowed($module->getId()))
            die('Cloning is not allowed for this module');

        $clone_id = ModuleCloneManager::createClone($module->getId(), $module->getName());
        if (!$clone_id)
            die('Error while creating clone');

        \wp_redirect(\admin_url('admin.php?page=content-egg-modules--' . urlencode($clone_id)));
        exit;
    }

    private function actionDeleteClone()
    {
        if (!\check_admin_referer('ce_remove_module_action'))
            die('You do not have permission to view this page.');

        if (!\current_user_can('manage_options'))
            die('You do not have permission to view this page.');

        if (empty($_GET['module']))
            die('Module ID is required');

        $module_id = TextHelper::clearId($_GET['module']);

        if (!$module = ModuleManager::factory($module_id))
            die('Module not found');

        if (!$module->isClone() && !$module->isFeedParser())
            die('Deleting is allowed only for clones and feed modules');

        ModuleManager::getInstance()->destroyModule($module->getId());

        $redirect_url = \admin_url('admin.php?page=content-egg-modules');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'module_deleted', 'success');

        \wp_redirect($redirect_url);
        exit;
    }
}
