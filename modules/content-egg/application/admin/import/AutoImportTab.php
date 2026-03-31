<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\admin\ProductImportController;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\AutoImportRuleModel;
use ContentEgg\application\Plugin;

/**
 * AutoImportTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class AutoImportTab extends AbstractTab
{

    /* ------------------------------------------------------------------
	   Constructor / Assets
	------------------------------------------------------------------ */
    public function __construct()
    {
        parent::__construct('autoimport', __('Auto Import', 'content-egg'));
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style('cegg-bootstrap5-full');

        // Enqueue CodeMirror
        wp_enqueue_code_editor([
            'type' => 'text/html',
        ]);

        wp_enqueue_code_editor(array(
            'codemirror' => array(
                'mode'        => 'null',
                'lineNumbers' => true,
            )
        ));
    }

    /**
     * Render the “All Rules” / “Add New” sub-navigation for Auto Import.
     *
     * @param string $current Slug of the current view (‘list’ or ‘add’).
     */
    private function renderSubnav(string $current): void
    {
        $items = [
            'list' => __('All Rules',   'content-egg'),
            'add'  => __('Add New Rule', 'content-egg'),
        ];

        echo '<ul class="subsubsub">';
        foreach ($items as $slug => $label)
        {
            $url = add_query_arg(
                [
                    'page'   => ProductImportController::SLUG,
                    'tab'    => $this->getSlug(),
                    'action' => $slug,
                ],
                admin_url('admin.php')
            );
            printf(
                '<li><a href="%1$s" class="%2$s">%3$s</a>%4$s</li>',
                esc_url($url),
                $slug === $current ? 'current' : '',
                esc_html($label),
                $slug !== array_key_last($items) ? '<span class="separator"> | </span>' : ''
            );
        }
        echo '</ul><br class="clear">';
    }

    /* ------------------------------------------------------------------
    Main entry
    ------------------------------------------------------------------ */
    public function render(): void
    {
        // ------------------------------------------------------------------
        // Sub-nav: All Rules / Add New
        // ------------------------------------------------------------------
        $rawAction = $_REQUEST['ai_action'] ?? ($_GET['action'] ?? 'list');
        $action    = in_array($rawAction, ['add', 'edit', 'delete', 'toggle', 'run'], true)
            ? $rawAction
            : 'list';
        $current   = in_array($action, ['add', 'edit'], true) ? 'add' : 'list';
        $this->renderSubnav($current);

        // ------------------------------------------------------------------
        // 1) Handle GET "delete"
        // ------------------------------------------------------------------
        if ($action === 'delete' && isset($_GET['rule_id'], $_GET['_wpnonce']))
        {
            check_admin_referer('cegg_autoimport', '_wpnonce');
            $this->handleDeleteRule(absint($_GET['rule_id']));
            return;
        }

        // ------------------------------------------------------------------
        // 2) Handle GET "toggle"
        // ------------------------------------------------------------------
        if ($action === 'toggle' && isset($_GET['rule_id'], $_GET['_wpnonce']))
        {
            check_admin_referer('cegg_autoimport', '_wpnonce');
            $this->handleToggleRule();
            return;
        }

        // ------------------------------------------------------------------
        // 3) Handle GET "run_now"
        // ------------------------------------------------------------------
        if ($action === 'run' && isset($_GET['rule_id'], $_GET['_wpnonce']))
        {
            check_admin_referer('cegg_autoimport', '_wpnonce');
            $this->handleRunRule(absint($_GET['rule_id']));
            return;
        }

        // ------------------------------------------------------------------
        // 4) Handle POST actions
        // ------------------------------------------------------------------
        if (
            'POST' === $_SERVER['REQUEST_METHOD']
            && isset($_POST['cegg_autoimport_nonce'], $_POST['ai_action'])
            && wp_verify_nonce($_POST['cegg_autoimport_nonce'], 'cegg_autoimport')
        )
        {
            $this->routePostAction();
            return;
        }

        // ------------------------------------------------------------------
        // 5) "Add New Rule" screen
        // ------------------------------------------------------------------
        if ($action === 'add')
        {
            $this->renderRuleForm();
            return;
        }

        // ------------------------------------------------------------------
        // 6) "Edit Rule" screen
        // ------------------------------------------------------------------
        if ($action === 'edit' && ! empty($_GET['rule_id']))
        {
            $rule = AutoImportRuleModel::model()->findByID(absint($_GET['rule_id']));
            if ($rule)
            {
                $this->renderRuleForm($rule);
                return;
            }
        }

        // ------------------------------------------------------------------
        // 7) List view
        // ------------------------------------------------------------------
        $ruleModel = AutoImportRuleModel::model();
        $table     = new AutoImportRulesTable($ruleModel);
        $table->prepare_items();

        $is_possibly_stuck = $this->isPossiblyStuck();

        if ($is_possibly_stuck)
        {
            AutoImportScheduler::addScheduleEvent();
        }

        PluginAdmin::render(
            'auto_import',
            [
                'table'               => $table,
                'is_possibly_stuck'   => $is_possibly_stuck,
            ]
        );
    }

    /**
     * Delete an auto-import rule by ID and redirect back to list.
     */
    private function handleDeleteRule(int $rule_id): void
    {
        if ($rule_id <= 0)
        {
            AdminHelper::redirect(admin_url('admin.php?page=content-egg-product-import&tab=autoimport'));
        }

        $model = AutoImportRuleModel::model();
        $model->getDb()->delete(
            $model->tableName(),
            ['id' => $rule_id],
            ['%d']
        );

        $redirect = AdminNotice::add2Url(
            admin_url('admin.php?page=content-egg-product-import&tab=autoimport'),
            'autoimport_rule_deleted',
            'success'
        );
        AdminHelper::redirect($redirect);
    }

    /** ------------------------------------------------------------------
     *  POST-router handles create & update
     * ----------------------------------------------------------------- */
    private function routePostAction(): void
    {
        check_admin_referer('cegg_autoimport', 'cegg_autoimport_nonce');
        if (! current_user_can('manage_options'))
        {
            wp_die(esc_html__('Insufficient permissions.', 'content-egg'));
        }

        $action = sanitize_key($_POST['ai_action'] ?? '');
        switch ($action)
        {
            case 'save':
                $this->handleSaveRule();
                break;
            case 'run_once':
                $this->handleRunOnce();
                break;
        }

        exit;
    }

    /**
     * Toggle a rule between “active” and “paused”.
     * When resuming, schedule its next run 60 seconds from now.
     */
    private function handleToggleRule(): void
    {
        $rule_id = absint($_REQUEST['rule_id'] ?? 0);
        if ($rule_id <= 0)
        {
            return;
        }

        $model = AutoImportRuleModel::model();
        $rule  = $model->findByID($rule_id);
        if (! $rule)
        {
            return;
        }

        $current = $rule['status'] ?? AutoImportRuleModel::STATUS_ACTIVE;
        switch ($current)
        {
            case AutoImportRuleModel::STATUS_ACTIVE:
                $new_status = AutoImportRuleModel::STATUS_PAUSED;
                break;

            case AutoImportRuleModel::STATUS_PAUSED:
                $new_status = AutoImportRuleModel::STATUS_ACTIVE;
                break;

            default:
                // finished/disabled: do nothing
                return;
        }

        // Prepare update payload
        $update = [
            'id'     => $rule_id,
            'status' => $new_status,
        ];

        // If we're resuming, push next_run_at out by 60 seconds
        if ($new_status === AutoImportRuleModel::STATUS_ACTIVE)
        {
            $next_ts = current_time('timestamp') + 60;
            $update['next_run_at'] = date('Y-m-d H:i:s', $next_ts);
        }

        $model->save($update);

        $notice_key = $new_status === AutoImportRuleModel::STATUS_ACTIVE
            ? 'autoimport_rule_resumed'
            : 'autoimport_rule_paused';

        $redirect = admin_url('admin.php?page=content-egg-product-import&tab=autoimport');
        $redirect = AdminNotice::add2Url($redirect, $notice_key, 'success');

        AdminHelper::redirect($redirect);
    }

    /** ------------------------------------------------------------------
     *  Show create / edit form
     * ----------------------------------------------------------------- */
    private function renderRuleForm(?array $rule = null): void
    {
        $rule              = $rule ?: [];                         // new rule
        $preset_options    = PresetRepository::getList();
        $default_preset_id = (int) PresetRepository::getDefaultId();
        $modules           = ModuleManager::getInstance()->getAffiliateParsersMeta(true, true, true);

        $is_edit   = ! empty($rule);

        $rule      = wp_parse_args($rule, [
            'id'                 => 0,
            'name'               => '',
            'status'               => AutoImportRuleModel::STATUS_ACTIVE,
            'preset_id'          => $default_preset_id,
            'module_id'          => '',
            'interval_seconds'   => 604800,
            'sort_newest'        => 0,
            'keywords_json'      => '[]',
            'stop_after_days'    => '',
            'stop_after_imports' => '',
            'stop_if_no_new_results' => 20,
            'max_keyword_no_products' => 10,
        ]);

        $keywords = KeywordCollection::toLines($rule['keywords_json']);
        $keywords = implode("\n", $keywords);

        PluginAdmin::getInstance()->render(
            'auto_import_form',
            compact(
                'is_edit',
                'rule',
                'keywords',
                'preset_options',
                'default_preset_id',
                'modules'
            )
        );
    }

    /**
     * Save (create or update) an auto-import rule.
     */
    private function handleSaveRule(): void
    {
        // Basic fields
        $id           = absint($_POST['rule_id'] ?? 0);
        $name         = sanitize_text_field($_POST['ai_name'] ?? '');

        // Status
        $status_options = AutoImportRuleModel::getStatusOptions();
        $status         = sanitize_key($_POST['status'] ?? '');
        if (! isset($status_options[$status]))
        {
            $status = AutoImportRuleModel::STATUS_ACTIVE;
        }

        // Preset & module
        $preset_id    = absint($_POST['preset_id'] ?? 0);
        $module_id    = TextHelper::clearId($_POST['module_id'] ?? '');

        // Frequency
        $interval_sec = max(5, absint($_POST['interval_seconds'] ?? 0)); // Minimum 5 minutes

        // Sort newest
        $sort_newest  = isset($_POST['sort_newest']) ? 1 : 0;

        // Stop conditions
        $stop_days       = absint($_POST['stop_after_days'] ?? 0) ?: null;
        $stop_imports    = absint($_POST['stop_after_imports'] ?? 0) ?: null;
        $stop_no_results = absint($_POST['stop_if_no_new_results'] ?? 0) ?: null;
        $max_keyword_no_products = absint($_POST['max_keyword_no_products'] ?? 0) ?: null;

        // Determine next_run_at
        if ($id === 0)
        {
            // brand-new rule → schedule first execution 10 sec from now
            $next = current_time('timestamp') + 10;
        }
        else
        {
            // existing rule → keep whatever is already in the DB
            $old = AutoImportRuleModel::model()->findByID($id);
            $next = strtotime($old['next_run_at']);
            // if changed interval or changed status to active
            if ($interval_sec !== (int)$old['interval_seconds'] || ($old['status'] !== $status && $status === AutoImportRuleModel::STATUS_ACTIVE))
            {
                $next = current_time('timestamp') + $interval_sec;
            }
        }
        $next_run_at = date('Y-m-d H:i:s', $next);

        // Keywords
        // parse user’s new lines
        $lines = array_filter(array_map('trim', preg_split('/\R/u', wp_unslash($_POST['keywords'] ?? ''))));
        $lines = array_unique($lines);

        if (!$lines)
        {
            wp_die(esc_html__('At least one keyword is required.', 'content-egg'));
        }

        // load existing objects
        $oldJson   = AutoImportRuleModel::model()->findByID($id)['keywords_json'] ?? '[]';
        $existing  = KeywordCollection::fromJson($oldJson);

        // merge and re-encode
        $newCollection = KeywordCollection::mergeWithLines($lines, $existing);
        $keywords_json = KeywordCollection::toJson($newCollection);

        // Build the row
        $row = [
            'id'                    => $id,
            'name'                  => $name,
            'status'                => $status,
            'preset_id'             => $preset_id,
            'module_id'             => $module_id,
            'interval_seconds'      => $interval_sec,
            'sort_newest'           => $sort_newest,
            'keywords_json'         => $keywords_json,
            'stop_after_days'       => $stop_days,
            'stop_after_imports'    => $stop_imports,
            'stop_if_no_new_results' => $stop_no_results,
            'max_keyword_no_products' => $max_keyword_no_products,
            'next_run_at'           => $next_run_at,
        ];

        AutoImportRuleModel::model()->save($row);

        AutoImportScheduler::addScheduleEvent();

        // Redirect back to list with success notice
        $redirect = admin_url('admin.php?page=content-egg-product-import&tab=autoimport');
        $redirect = AdminNotice::add2Url($redirect, 'autoimport_rule_saved', 'success');
        AdminHelper::redirect($redirect);
    }

    /**
     * Check if any active auto-import rule is overdue (i.e. “stuck”).
     *
     * @return bool True if at least one active rule’s next_run_at + 15min is in the past.
     */
    private function isPossiblyStuck(): bool
    {
        $now      = current_time('timestamp');
        $grace    = 15 * MINUTE_IN_SECONDS; // 900 seconds
        $rules    = AutoImportRuleModel::model()->findAll([
            'where' => ['status = %s', [AutoImportRuleModel::STATUS_ACTIVE]],
        ]);

        foreach ($rules as $rule)
        {
            $nextTs = strtotime($rule['next_run_at']);
            if ($nextTs > 0 && ($now > $nextTs + $grace))
            {
                return true;
            }
        }

        return false;
    }

    private function handleRunOnce()
    {
        @set_time_limit(300);

        if (!Plugin::isDevEnvironment())
        {
            wp_die('This action is only allowed in a development environment.');
        }

        $service = new AutoImportServise();
        $service->processBatch(1);

        AdminHelper::redirect(admin_url('admin.php?page=content-egg-product-import&tab=autoimport'));
        exit;
    }

    private function handleRunRule(int $rule_id): void
    {
        $rule = AutoImportRuleModel::model()->findByID($rule_id);
        $redirectBase = admin_url('admin.php?page=content-egg-product-import&tab=autoimport');

        if (! $rule)
        {
            AdminHelper::redirect($redirectBase);
        }

        try
        {
            $service = new AutoImportServise();
            $service->processRule($rule);

            $redirect = AdminNotice::add2Url(
                $redirectBase,
                'autoimport_rule_processed',
                'success'
            );
        }
        catch (\Throwable $e)
        {
            AdminHelper::redirect($redirectBase);
        }

        AdminHelper::redirect($redirect);
    }
}
