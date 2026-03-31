<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\ProductImportController;
use ContentEgg\application\admin\import\PresetForm;
use ContentEgg\application\admin\import\PresetListTable;
use ContentEgg\application\admin\import\PresetPostType;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\Plugin;

defined('ABSPATH') || exit;

/**
 * PresetsTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PresetsTab extends AbstractTab
{
    public function __construct()
    {
        parent::__construct('presets', __('Presets', 'content-egg'));
    }

    /* ------------------------------------------------------------------
       Main dispatcher
    ------------------------------------------------------------------ */
    public function render(): void
    {
        $action = sanitize_key($_GET['action'] ?? 'list');

        // Sub-nav
        $this->renderSubnav($action);

        // View
        switch ($action)
        {
            case 'add':
                $this->renderAdd();
                break;

            case 'edit':
                $this->renderEdit((int) ($_GET['preset_id'] ?? 0));
                break;

            case 'trash':
                $this->handleDelete((int) ($_GET['preset_id'] ?? 0));
                break;

            case 'list':
            default:
                $this->renderList();
                break;
        }
    }

    /* ------------------------------------------------------------------
       Sub-nav (All / Add)
    ------------------------------------------------------------------ */
    private function renderSubnav(string $current): void
    {
        $items = [
            'list' => __('All Presets', 'content-egg'),
            'add'  => __('Add New',     'content-egg'),
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
       List view
    ------------------------------------------------------------------ */
    private function renderList(): void
    {
        // Screen option (once)
        $screen = get_current_screen();
        if (method_exists($screen, 'add_option'))
        {
            $screen->add_option('per_page', [
                'label'   => __('Presets per page', 'content-egg'),
                'default' => 20,
                'option'  => 'import_presets_per_page',
            ]);
        }

        // Instantiate & prepare
        $table = new PresetListTable();
        $table->prepare_items();

        // Preserve page & tab parameters
        foreach (['page', 'tab'] as $keep)
        {
            if (isset($_GET[$keep]))
            {
                printf(
                    '<input type="hidden" name="%1$s" value="%2$s" />',
                    esc_attr($keep),
                    esc_attr($_GET[$keep])
                );
            }
        }

        // Render the table
        echo '<div id="preset-list">';
        $table->display();
        echo '</div>';

?>
        <style>
            /* Scoped styles for the PresetListTable */
            div[id="preset-list"] .wp-list-table th.column-title,
            div[id="preset-list"] .wp-list-table td.column-title {
                width: 40%;
            }

            div[id="preset-list"] .wp-list-table th.column-post_type,
            div[id="preset-list"] .wp-list-table td.column-post_type {
                width: 12%;
            }

            div[id="preset-list"] .wp-list-table th.column-status,
            div[id="preset-list"] .wp-list-table td.column-status {
                width: 12%;
            }

            div[id="preset-list"] .wp-list-table th.column-ai,
            div[id="preset-list"] .wp-list-table td.column-ai {
                width: 8%;
                text-align: center;
            }

            div[id="preset-list"] .wp-list-table th.column-isdefault,
            div[id="preset-list"] .wp-list-table td.column-isdefault {
                width: 10%;
                text-align: center;
            }

            div[id="preset-list"] .wp-list-table th.column-date,
            div[id="preset-list"] .wp-list-table td.column-date {
                width: 15%;
            }

            div[id="preset-list"] .wp-list-table td {
                vertical-align: middle;
                padding-top: 8px;
                padding-bottom: 8px;
            }

            div[id="preset-list"] .cegg-icon-yes {
                color: #28a745;
                font-weight: bold;
                font-size: 1.2em;
            }

            div[id="preset-list"] .cegg-icon-no {
                color: #ccc;
                font-size: 1.2em;
            }
        </style>

<?php
    }

    /* ------------------------------------------------------------------
       Add new preset
    ------------------------------------------------------------------ */
    private function renderAdd(): void
    {
        // Check if duplicating
        $duplicate_id = isset($_GET['duplicate_id']) ? (int) $_GET['duplicate_id'] : 0;
        $data = [];

        if ($duplicate_id)
        {
            // Fetch the existing preset meta to prefill
            $orig = PresetRepository::get($duplicate_id);
            if ($orig)
            {
                $data = $orig;
            }
        }

        PresetForm::render(0, $data);
    }

    /* ------------------------------------------------------------------
       Edit preset
    ------------------------------------------------------------------ */
    private function renderEdit(int $id): void
    {
        if (! $id || get_post_type($id) !== PresetPostType::POST_TYPE)
        {
            $this->error(__('Invalid preset ID.', 'content-egg'));
            return;
        }
        if (! current_user_can('edit_post', $id))
        {
            $this->error(__('You do not have permission to edit this preset.', 'content-egg'));
            return;
        }
        PresetForm::render($id);
    }

    /* ------------------------------------------------------------------
    Delete handler
    ------------------------------------------------------------------ */
    private function handleDelete(int $id): void
    {
        if (! $id || get_post_type($id) !== PresetPostType::POST_TYPE)
        {
            wp_die(esc_html__('Invalid preset ID.', 'content-egg'));
        }

        if (! current_user_can('delete_post', $id))
        {
            wp_die(esc_html__('You do not have permission to delete this preset.', 'content-egg'));
        }

        check_admin_referer('cegg_delete_preset_' . $id);

        // build the base redirect URL
        $redirect_url = add_query_arg(
            [
                'page'    => ProductImportController::SLUG,
                'tab'     => $this->getSlug(),
                'deleted' => 'true',
            ],
            admin_url('admin.php')
        );

        // 1) Prevent deleting the very last preset
        $counts = wp_count_posts(PresetPostType::POST_TYPE);
        $published_count = isset($counts->publish) ? (int) $counts->publish : 0;

        if ($published_count <= 1 && !Plugin::isDevEnvironment())
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'preset_delete_error', 'error');
            AdminHelper::redirect($redirect_url);
        }

        // 2) Prevent deleting if any import-queue entries still reference this preset
        if (ImportQueueModel::model()->countActiveByPreset($id) > 0)
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'preset_in_use', 'error');
            AdminHelper::redirect($redirect_url);
        }

        // 3) All checks passed, delete
        wp_delete_post($id, true);
        $redirect_url = AdminNotice::add2Url($redirect_url, 'preset_deleted', 'success');
        AdminHelper::redirect($redirect_url);
    }

    private function error(string $msg): void
    {
        printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($msg));
    }
}
