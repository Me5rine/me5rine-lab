<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\import\AbstractTab;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\helpers\WooHelper;
use ContentEgg\application\models\ImportQueueModel;;

defined('ABSPATH') || exit;

/**
 * BulkImportTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class BulkImportTab extends AbstractTab
{
    public const ALLOWED_MONTHS = [0, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    public function __construct()
    {
        parent::__construct('bulk', __('Bulk Import', 'content-egg'));
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

    /* -------------------------------------------------------------------------
	 *  RENDER + HANDLE POST
	 * ---------------------------------------------------------------------- */
    public function render(): void
    {
        $modules = ModuleManager::getInstance()->getAffiliateParsers(true, true);
        if (empty($modules))
        {
            echo '<div class="cegg5-container"><div class="alert alert-warning mt-4">'
                . esc_html__('No modules found. Please activate at least one module.', 'content-egg')
                . '</div></div>';
            return;
        }

        /* ---------- 1.  Handle form submission ---------------------------- */
        if (
            'POST' === $_SERVER['REQUEST_METHOD']
            && isset($_POST['cegg_bulk_import_nonce'])
            && wp_verify_nonce($_POST['cegg_bulk_import_nonce'], 'cegg_bulk_import')
        )
        {
            $this->processBulkImport();
        }

        /* ---------- 2.  Prepare data for the view ------------------------- */
        $preset_options     = PresetRepository::getList();
        $default_preset_id  = (int) PresetRepository::getDefaultId();
        $post_cat_options   = WooHelper::getPostCategoryList();
        $woo_cat_options    = WooHelper::getWooCategoryList();
        $module_meta        = ModuleManager::getInstance()->getAffiliateParsersMeta(true, true, true);

        PluginAdmin::getInstance()->render(
            'bulk_import',
            compact(
                'preset_options',
                'default_preset_id',
                'post_cat_options',
                'woo_cat_options',
                'module_meta'
            )
        );
    }

    /* -------------------------------------------------------------------------
	 *  MAIN LOGIC: enqueue one job per line
	 * ---------------------------------------------------------------------- */
    private function processBulkImport(): void
    {
        if (! current_user_can('manage_options'))
        {
            wp_die(esc_html__('You do not have permission to perform this action.', 'content-egg'));
        }

        /* 1) Validate + sanitise ------------------------------------------------ */

        $preset_id   = absint($_POST['preset_id']         ?? 0);
        $module_id   = TextHelper::clearId($_POST['module_id']   ?? '');
        $months_raw  = (int) ($_POST['schedule_offset']   ?? 0);
        $keywordsRaw = wp_unslash($_POST['keywords']      ?? ''); // textarea

        // Preset must exist
        $preset      = PresetRepository::get($preset_id);
        if (! $preset)
        {
            wp_die(esc_html__('Invalid preset selected.', 'content-egg'));
        }

        // Category: choose post or Woo depending on preset type
        if ($preset['post_type'] === 'post')
        {
            $category_id = absint($_POST['post_cat'] ?? 0);
        }
        else
        { // product
            $category_id = absint($_POST['woo_cat'] ?? 0);
        }

        $months = in_array($months_raw, self::ALLOWED_MONTHS, true) ? $months_raw : 0;

        // Keywords / URLs – split per line, trim, drop empties
        $lines = array_filter(array_map('trim', preg_split('/\R/u', $keywordsRaw)));

        if (empty($lines))
        {
            AdminHelper::redirect(admin_url('admin.php?page=content-egg-product-import&tab=bulk'));
        }

        /* 2)  Enqueue --------------------------------------------------------- */

        $queue  = ImportQueueModel::model();

        foreach ($lines as $keyword)
        {
            $scheduled_at = null;

            if ($months > 0)
            {
                $scheduled_at = self::randomDateWithinMonths($months)->format('Y-m-d H:i:s');
            }

            $queue->enqueue(
                $preset_id,
                $module_id,
                [],
                sanitize_text_field($keyword),
                $category_id,
                $scheduled_at
            );
        }

        ProductImportScheduler::addScheduleEvent();

        /* 3)  Redirect to queue ---------------------------------------------- */
        $redirect_url = admin_url('admin.php?page=content-egg-product-import&tab=queue');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'bulk_import_jobs_added', 'success');

        AdminHelper::redirect($redirect_url);
    }

    /* -------------------------------------------------------------------------
	 *  Helper: get random DateTime within N months from now
	 * ---------------------------------------------------------------------- */
    public static function randomDateWithinMonths(int $months): \DateTime
    {
        $tz = wp_timezone(); // local site tz

        $start = new \DateTime('now', $tz);
        $end   = (clone $start)->modify("+{$months} months");

        $randSeconds = wp_rand(0, $end->getTimestamp() - $start->getTimestamp());

        return (clone $start)->modify("+{$randSeconds} seconds");
    }
}
