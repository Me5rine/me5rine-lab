<?php defined('ABSPATH') || exit; ?>
<style>
    .cegg5-container .card {
        margin-top: 0;
        padding: 0;
        /* Bootstrap card handles spacing via header/body */
        min-width: auto;
        max-width: none;
        border: var(--bs-card-border-width, 1px) solid var(--bs-card-border-color, rgba(0, 0, 0, .125));
        border-radius: var(--bs-card-border-radius, .375rem);
        background-color: var(--bs-card-bg, #fff);
        box-shadow: var(--bs-card-box-shadow, 0 0 #0000);
    }

    .cegg5-container .card-header {
        padding: var(--bs-card-cap-padding-y, .5rem) var(--bs-card-cap-padding-x, 1rem);
        margin-bottom: 0;
        background-color: var(--bs-card-cap-bg, rgba(0, 0, 0, .03));
        border-bottom: var(--bs-card-border-width, 1px) solid var(--bs-card-border-color, rgba(0, 0, 0, .125));
    }

    .cegg5-container .card-body {
        padding: var(--bs-card-spacer-y, 1rem) var(--bs-card-spacer-x, 1rem);
    }

    /* Fixed width for the clicks column */
    .cegg5-container .cegg-clicks-col {
        width: 160px;
    }

    /* Keep number and delta on one line, right-aligned */
    .cegg5-container .cegg-clicks-wrap {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: .1rem;
        white-space: nowrap;
    }

    /* Optional: ensure consistent digit alignment look */
    .cegg5-container .cegg-clicks-val {
        font-variant-numeric: tabular-nums;
    }

    /* Give the delta a predictable width so the number column aligns perfectly */
    .cegg5-container .cegg-clicks-delta {
        width: 60px;
        text-align: right;
    }

    /* Slightly tighter on small screens */
    @media (max-width: 576px) {
        .cegg5-container .cegg-clicks-col {
            width: 132px;
        }

        .cegg5-container .cegg-clicks-delta {
            width: 60px;
        }
    }
</style>
<div class="wrap">
    <div class="cegg5-container">
        <h1 class="wp-heading-inline h3">
            <?php esc_html_e('Clicks Statistics', 'content-egg'); ?>
        </h1>

        <?php
        $range       = isset($filters['range']) ? (string) $filters['range'] : '30d';
        $range_label = isset($filters['range_label']) ? (string) $filters['range_label'] : '';
        $date_from   = isset($filters['date_from']) ? (string) $filters['date_from'] : '';
        $date_to     = isset($filters['date_to'])   ? (string) $filters['date_to']   : '';
        $module_sel  = isset($filters['module_id']) ? (string) $filters['module_id'] : '';

        // Range-first: safe locals for summary & comparison
        $range_summary = (isset($range_summary) && is_array($range_summary)) ? $range_summary : [
            'range_total' => 0,
            'prev_total'  => 0,
            'days'        => 0,
            'date_from'   => $date_from,
            'date_to'     => $date_to,
            'range_label' => $range_label,
        ];
        $range_comparison = (isset($range_comparison) && is_array($range_comparison)) ? $range_comparison : [
            'label' => '',
            'class' => 'text-muted',
            'icon'  => 'bi-dash',
        ];
        $range_total  = (int) ($range_summary['range_total'] ?? 0);
        $prev_total   = (int) ($range_summary['prev_total']  ?? 0);
        $days_in_rng  = (int) ($range_summary['days']        ?? 0);
        $avg_per_day  = $days_in_rng > 0 ? (int) round($range_total / $days_in_rng) : 0;
        ?>

        <!-- Filters -->
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="row gy-2 gx-3 align-items-end mt-3 mb-3" id="cegg-stats-filters">
            <input type="hidden" name="page" value="content-egg-stats">

            <!-- Range -->
            <div class="col-12 col-md-auto">
                <label for="cegg-range" class="form-label mb-0 small text-muted"><?php esc_html_e('Date range', 'content-egg'); ?></label>
                <select class="form-select form-select-sm" id="cegg-range" name="range">
                    <option value="today" <?php selected($range, 'today'); ?>><?php esc_html_e('Today', 'content-egg'); ?></option>
                    <option value="7d" <?php selected($range, '7d'); ?>><?php esc_html_e('Last 7 days', 'content-egg'); ?></option>
                    <option value="30d" <?php selected($range, '30d'); ?>><?php esc_html_e('Last 30 days', 'content-egg'); ?></option>
                    <option value="90d" <?php selected($range, '90d'); ?>><?php esc_html_e('Last 90 days', 'content-egg'); ?></option>
                    <option value="this_month" <?php selected($range, 'this_month'); ?>><?php esc_html_e('This month', 'content-egg'); ?></option>
                    <option value="last_month" <?php selected($range, 'last_month'); ?>><?php esc_html_e('Last month', 'content-egg'); ?></option>
                    <option value="custom" <?php selected($range, 'custom'); ?>><?php esc_html_e('Custom', 'content-egg'); ?></option>
                </select>
            </div>

            <!-- Custom dates -->
            <div class="col-6 col-md-auto cegg-range-custom">
                <label for="cegg-from" class="form-label mb-0 small text-muted"><?php esc_html_e('From', 'content-egg'); ?></label>
                <input type="date" class="form-control form-control-sm" id="cegg-from" name="from" value="<?php echo esc_attr($date_from); ?>">
            </div>
            <div class="col-6 col-md-auto cegg-range-custom">
                <label for="cegg-to" class="form-label mb-0 small text-muted"><?php esc_html_e('To', 'content-egg'); ?></label>
                <input type="date" class="form-control form-control-sm" id="cegg-to" name="to" value="<?php echo esc_attr($date_to); ?>">
            </div>

            <!-- Module filter -->
            <div class="col-12 col-md-auto">
                <label for="cegg-module" class="form-label mb-0 small text-muted"><?php esc_html_e('Module', 'content-egg'); ?></label>
                <select class="form-select form-select-sm" id="cegg-module" name="module_id">
                    <option value=""><?php esc_html_e('All modules', 'content-egg'); ?></option>
                    <?php foreach ($modules as $mod_id => $mod_name): ?>
                        <option value="<?php echo esc_attr($mod_id); ?>" <?php selected($module_sel, $mod_id); ?>><?php echo esc_html($mod_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e('Apply', 'content-egg'); ?></button>
                <a class="btn btn-link btn-sm" href="<?php echo esc_url(admin_url('admin.php?page=content-egg-stats')); ?>">
                    <?php esc_html_e('Reset', 'content-egg'); ?>
                </a>
            </div>
        </form>

        <!-- Chart card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?php esc_html_e('Clicks over time', 'content-egg'); ?></span>
                <span class="text-muted small"><?php echo esc_html($range_label); ?></span>
            </div>
            <div class="card-body">
                <?php
                $chartOpts = $clicksChartOptions;

                if (is_array($chartOpts) && !empty($chartOpts['data']))
                {
                    // Sensible defaults if not provided
                    if (empty($chartOpts['chartType'])) $chartOpts['chartType'] = 'Area';
                    if (empty($chartOpts['xkey']))      $chartOpts['xkey']      = 'date';
                    if (empty($chartOpts['ykeys']))     $chartOpts['ykeys']     = ['clicks'];
                    if (empty($chartOpts['labels']))    $chartOpts['labels']    = [__('Clicks', 'content-egg')];

                    \ContentEgg\application\helpers\TemplateHelper::viewMorrisChart(
                        'cegg-clicks-chart',
                        $chartOpts,
                        ['style' => 'height: 280px;']
                    );
                }
                else
                {
                    // Fallback placeholder when there is no chart data yet
                    echo '<div id="cegg-clicks-chart" class="bg-light-subtle border rounded" style="height: 280px;">'
                        . '<div class="h-100 w-100 d-flex align-items-center justify-content-center text-muted">'
                        . esc_html__("Clicks chart will appear here.", 'content-egg')
                        . '</div></div>';
                }
                ?>
            </div>
        </div>

        <!-- Range summary -->
        <div class="row g-3 mb-4">

            <div class="col-6 col-md-4">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small"><?php esc_html_e('Previous period (same length)', 'content-egg'); ?></div>
                        <div class="h4 mb-0"><?php echo esc_html(number_format_i18n($prev_total)); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small"><?php esc_html_e('Clicks in range', 'content-egg'); ?></div>

                        <div class="d-flex align-items-baseline gap-2 flex-wrap">
                            <div class="h4 mb-0"><?php echo esc_html(number_format_i18n($range_total)); ?></div>
                            <div class="small <?php echo esc_attr($range_comparison['class']); ?> text-nowrap">
                                <i class="bi <?php echo esc_attr($range_comparison['icon']); ?>" aria-hidden="true"></i>
                                <?php echo esc_html($range_comparison['label']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small"><?php esc_html_e('Average per day', 'content-egg'); ?></div>
                        <div class="h4 mb-0"><?php echo esc_html(number_format_i18n($avg_per_day)); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grids: Top links / Top posts  -->
        <div class="row g-3 mb-4">

            <!-- Top links (products) -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Top Links (products) — this period', 'content-egg'); ?>
                    </div>
                    <div class="card-body p-0 m-2 mb-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Product', 'content-egg'); ?></th>
                                        <th scope="col" class="text-center cegg-clicks-col"><?php esc_html_e('Clicks', 'content-egg'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_links)) : ?>
                                        <?php foreach ($top_links as $row) : ?>
                                            <?php
                                            $title   = isset($row['title']) ? (string) $row['title'] : __('(no title)', 'content-egg');
                                            $edit    = isset($row['post_edit_url']) ? (string) $row['post_edit_url'] : '';
                                            $clicks  = isset($row['clicks']) ? (int) $row['clicks'] : 0;
                                            $prev    = isset($row['prev_clicks']) ? (int) $row['prev_clicks'] : 0;
                                            ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 280px;">
                                                    <?php if ($edit) : ?>
                                                        <a class="text-decoration-none" href="<?php echo esc_url($edit); ?>"><?php echo esc_html($title); ?></a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($title); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end cegg-clicks-col">
                                                    <div class="cegg-clicks-wrap">
                                                        <strong class="cegg-clicks-val"><?php echo esc_html(number_format_i18n($clicks)); ?></strong>
                                                        <span class="cegg-clicks-delta">
                                                            <?php
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo \ContentEgg\application\admin\ClicksStatsController::renderDeltaBadge((int) $clicks, (int) $prev);
                                                            ?>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="text-muted small py-3 text-center" data-cegg-placeholder="top-links">
                                                <?php esc_html_e('Top links will appear here.', 'content-egg'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top posts -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Top Posts — this period', 'content-egg'); ?>
                    </div>
                    <div class="card-body p-0 m-2 mb-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Post', 'content-egg'); ?></th>
                                        <th scope="col" class="text-center cegg-clicks-col"><?php esc_html_e('Clicks', 'content-egg'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_posts)) : ?>
                                        <?php foreach ($top_posts as $row) : ?>
                                            <?php
                                            $title   = isset($row['post_title']) ? (string) $row['post_title'] : __('(no title)', 'content-egg');
                                            $edit    = isset($row['post_edit_url']) ? (string) $row['post_edit_url'] : '';
                                            $clicks  = isset($row['clicks']) ? (int) $row['clicks'] : 0;
                                            $prev    = isset($row['prev_clicks']) ? (int) $row['prev_clicks'] : 0;

                                            ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 280px;">
                                                    <?php if ($edit) : ?>
                                                        <a class="text-decoration-none" href="<?php echo esc_url($edit); ?>"><?php echo esc_html($title); ?></a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($title); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end cegg-clicks-col">
                                                    <div class="cegg-clicks-wrap">
                                                        <strong class="cegg-clicks-val"><?php echo esc_html(number_format_i18n($clicks)); ?></strong>
                                                        <span class="cegg-clicks-delta">
                                                            <?php
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo \ContentEgg\application\admin\ClicksStatsController::renderDeltaBadge((int) $clicks, (int) $prev);
                                                            ?>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="text-muted small py-3 text-center" data-cegg-placeholder="top-posts">
                                                <?php esc_html_e('Top posts will appear here.', 'content-egg'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row g-4 -->

        <!-- Grids: Top links / Top posts -->
        <!-- /.row g-4 -->

        <!-- Tracking overview / hints -->
        <?php
        $trackRedirect = isset($track_redirect_enabled) ? (bool)$track_redirect_enabled : false;
        $trackDirect   = isset($track_direct_enabled)   ? (bool)$track_direct_enabled   : false;
        $modsRedirect  = isset($mods_redirect) && is_array($mods_redirect) ? $mods_redirect : [];
        $modsDirect    = isset($mods_direct)   && is_array($mods_direct)   ? $mods_direct   : [];
        $modsNone      = isset($mods_none)     && is_array($mods_none)     ? $mods_none     : [];

        // Show block if either tracking mode is disabled OR some modules are not tracked at all
        $showTrackingBlock = (!$trackRedirect || !$trackDirect || !empty($modsNone));

        // Compact list formatter
        $fmtList = function (array $list): string
        {
            if (!$list) return '—';
            $names = array_values($list);
            return esc_html(implode(', ', $names));
        };
        ?>

        <?php
        // Safe locals
        $no_click_links = isset($no_click_links) && is_array($no_click_links) ? $no_click_links : [];
        $no_click_posts = isset($no_click_posts) && is_array($no_click_posts) ? $no_click_posts : [];
        ?>

        <div class="row g-3 mb-4">

            <!-- Products with no clicks — this period -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Products with no clicks — this period', 'content-egg'); ?>
                    </div>
                    <div class="card-body p-0 m-2 mb-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Product', 'content-egg'); ?></th>
                                        <th scope="col" class="text-end"><?php esc_html_e('Module', 'content-egg'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($no_click_links)) : ?>
                                        <?php foreach ($no_click_links as $row) : ?>
                                            <?php
                                            $purl          = isset($row['post_edit_url']) ? (string) $row['post_edit_url'] : '';
                                            $productTitle  = isset($row['title']) ? (string) $row['title'] : esc_html__('(no title)', 'content-egg');
                                            $moduleId      = isset($row['module_id']) ? (string) $row['module_id'] : '';
                                            $moduleName    = \ContentEgg\application\components\ModuleManager::getInstance()->getModuleNameById($moduleId);

                                            ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 380px;">
                                                    <?php if (!empty($purl)) : ?>
                                                        <a class="text-decoration-none" href="<?php echo esc_url($purl); ?>">
                                                            <?php echo esc_html($productTitle); ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($productTitle); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-muted"><?php echo esc_html($moduleName); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="text-muted small py-3 text-center" data-cegg-placeholder="no-click-links">
                                                <?php esc_html_e('No zero-click products for the selected period.', 'content-egg'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts with no clicks — this period -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Posts with no clicks — this period', 'content-egg'); ?>
                    </div>
                    <div class="card-body p-0 m-2 mb-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Post', 'content-egg'); ?></th>
                                        <th scope="col" class="text-end"><?php esc_html_e('Links', 'content-egg'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($no_click_posts)) : ?>
                                        <?php foreach ($no_click_posts as $row) : ?>
                                            <?php
                                            $pid    = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                                            $ptitle = isset($row['post_title']) ? (string) $row['post_title'] : '';
                                            if ($ptitle === '') $ptitle = esc_html__('(no title)', 'content-egg');
                                            $purl   = isset($row['post_edit_url']) ? (string) $row['post_edit_url'] : ($pid > 0 ? admin_url('post.php?post=' . $pid . '&action=edit') : '');
                                            $linksN = isset($row['links_count']) ? (int) $row['links_count'] : 0;
                                            ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 420px;">
                                                    <?php if (!empty($purl)) : ?>
                                                        <a class="text-decoration-none" href="<?php echo esc_url($purl); ?>">
                                                            <?php echo esc_html($ptitle); ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <?php echo esc_html($ptitle); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <strong><?php echo esc_html(number_format_i18n($linksN)); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="text-muted small py-3 text-center" data-cegg-placeholder="no-click-posts">
                                                <?php esc_html_e('No zero-click posts for the selected period.', 'content-egg'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-3 mb-4">

            <!-- Top module this period (Donut) -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Clicks by Module — this period', 'content-egg'); ?>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($moduleShareChartOptions)): ?>
                            <?php
                            \ContentEgg\application\helpers\TemplateHelper::viewMorrisChart(
                                'cegg-mod-share-' . wp_generate_uuid4(),
                                $moduleShareChartOptions,
                                ['style' => 'height: 280px;']
                            );
                            ?>
                        <?php else: ?>
                            <div id="cegg-mod-share-empty" class="bg-light-subtle border rounded" style="height: 280px;">
                                <div class="h-100 w-100 d-flex align-items-center justify-content-center text-muted">
                                    <?php esc_html_e('Module share chart will appear here.', 'content-egg'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tracking overview / hints -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <?php esc_html_e('Tracking overview', 'content-egg'); ?>
                    </div>
                    <div class="card-body small">
                        <div class="mb-2">
                            <strong><?php esc_html_e('Track Clicks With Redirect:', 'content-egg'); ?></strong>
                            <span class="badge rounded-pill <?php echo $trackRedirect ? 'bg-success' : 'bg-secondary'; ?> ms-1">
                                <?php echo $trackRedirect ? esc_html__('Enabled', 'content-egg') : esc_html__('Disabled', 'content-egg'); ?>
                            </span>
                            <?php if (!$trackRedirect): ?>
                                <span class="text-muted ms-2">
                                    <?php esc_html_e('You can enable this in Content Egg → Settings → Track Clicks With Redirect.', 'content-egg'); ?>
                                </span>
                            <?php endif; ?>
                            <br />
                            <span class="text-muted"><?php esc_html_e('Modules tracked (redirect):', 'content-egg'); ?></span>
                            <span class="ms-1">
                                <?php echo $fmtList($modsRedirect); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <strong><?php esc_html_e('Track Clicks Without Redirect:', 'content-egg'); ?></strong>
                            <span class="badge rounded-pill <?php echo $trackDirect ? 'bg-success' : 'bg-secondary'; ?> ms-1">
                                <?php echo $trackDirect ? esc_html__('Enabled', 'content-egg') : esc_html__('Disabled', 'content-egg'); ?>
                            </span>
                            <?php if (!$trackDirect): ?>
                                <span class="text-muted ms-2">
                                    <?php esc_html_e('You can enable this in Content Egg → Settings → Track Clicks Without Redirect.', 'content-egg'); ?>
                                </span>
                            <?php endif; ?>
                            <br />
                            <span class="text-muted"><?php esc_html_e('Modules tracked (direct):', 'content-egg'); ?></span>
                            <span class="ms-1">
                                <?php echo $fmtList($modsDirect); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </span>
                        </div>

                        <?php if (!empty($modsNone)): ?>
                            <div>
                                <strong><?php esc_html_e('Modules without tracking:', 'content-egg'); ?></strong>
                                <span class="ms-1">
                                    <?php echo $fmtList($modsNone); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    (function() {
        var sel = document.getElementById('cegg-range');
        var customBlocks = document.querySelectorAll('.cegg-range-custom');
        if (!sel) return;

        function sync() {
            var isCustom = sel.value === 'custom';
            for (var i = 0; i < customBlocks.length; i++) {
                customBlocks[i].style.display = isCustom ? '' : 'none';
            }
        }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>