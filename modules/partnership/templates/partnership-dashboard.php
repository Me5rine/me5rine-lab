<?php
// File: modules/partnership/templates/partner-dashboard.php

if (!defined('ABSPATH')) exit;

if (!admin_lab_require_access('partnership', __('Access the partnership dashboard', 'me5rine-lab'))) {
    return;
}

$user_id = get_current_user_id();
$args = [
    'post_type' => 'giveaway',
    'post_status' => ['publish', 'draft'],
    'meta_query' => [
        [
            'key' => '_giveaway_partner_id',
            'value' => $user_id,
            'compare' => '='
        ]
    ],
    'posts_per_page' => -1
];

$query = new WP_Query($args);
$posts = $query->posts;

$total = count($posts);
$ongoing = $upcoming = $finished = 0;
$participants_total = $entries_total = $total_prizes = 0;
$top_3 = [];

foreach ($posts as $post) {
    $status = get_post_meta($post->ID, '_giveaway_status', true);
    switch ($status) {
        case 'Ongoing': $ongoing++; break;
        case 'Upcoming': $upcoming++; break;
        case 'Finished': $finished++; break;
    }

    $participants = (int) get_post_meta($post->ID, '_giveaway_participants_count', true);
    $entries = (int) get_post_meta($post->ID, '_giveaway_entries_count', true);

    $participants_total += $participants;
    $entries_total += $entries;

    $campaign_id = get_post_meta($post->ID, '_rafflepress_campaign', true);
    if ($campaign_id) {
        global $wpdb;
        $settings_json = $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d", $campaign_id));
        if ($settings_json) {
            $settings = json_decode($settings_json, true);
            if (!empty($settings['prizes']) && is_array($settings['prizes'])) {
                $total_prizes += count($settings['prizes']);
            }
        }
    }

    $top_3[] = [
        'post' => $post,
        'participants' => $participants
    ];
}

$avg_participants = $total > 0 ? round($participants_total / $total) : 0;
$avg_entries = $total > 0 ? round($entries_total / $total) : 0;

usort($top_3, fn($a, $b) => $b['participants'] <=> $a['participants']);
$top_3 = array_slice($top_3, 0, 3);

?>
<div class="wrap partner-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php esc_html_e('Partner Dashboard', 'me5rine-lab'); ?></h2>

    <div class="me5rine-lab-card">
        <div class="me5rine-lab-tile-header">
            <h2 class="me5rine-lab-title-medium"><?php esc_html_e('Giveaways', 'me5rine-lab'); ?></h2>
            <div class="me5rine-lab-tile-actions">
                <a href="<?php echo esc_url(home_url('/admin-giveaways/')); ?>" class="me5rine-lab-form-button">
                    <?php esc_html_e('View Giveaways', 'me5rine-lab'); ?>
                </a>
                <?php
            $link = do_shortcode('[giveaway_redirect_link]');
            ?>
            <a href="<?php echo esc_url($link); ?>" class="me5rine-lab-form-button">
                <?php esc_html_e('Add Giveaway', 'me5rine-lab'); ?>
            </a>
            </div>
        </div>

        <div class="tiles-grid me5rine-lab-tiles-grid">
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($total); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Total Giveaways', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($ongoing); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Active', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($upcoming); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Upcoming', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($finished); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Finished', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($participants_total); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Participants', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($entries_total); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Entries', 'me5rine-lab'); ?></span></div>
        </div>

        <?php if (!empty($top_3)): ?>
            <div>
                <h3 class="me5rine-lab-card-section-title"><?php esc_html_e('Top 3 Giveaways (by Participants)', 'me5rine-lab'); ?></h3>
                <div class="me5rine-lab-podium-wrapper">
                    <div class="me5rine-lab-podium">
                        <?php if (isset($top_3[1])): ?>
                            <div class="me5rine-lab-podium-step me5rine-lab-podium-2 animate">
                                <div class="me5rine-lab-podium-rank">2</div>
                                <a href="<?php echo esc_url(get_permalink($top_3[1]['post'])); ?>">
                                    <?php echo esc_html($top_3[1]['post']->post_title); ?>
                                </a>
                                <div class="me5rine-lab-podium-info">
                                    <?php echo esc_html($top_3[1]['participants']); ?> <?php esc_html_e('participants', 'me5rine-lab'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($top_3[0])): ?>
                            <div class="me5rine-lab-podium-step me5rine-lab-podium-1 animate">
                                <div class="me5rine-lab-podium-rank">1</div>
                                <a href="<?php echo esc_url(get_permalink($top_3[0]['post'])); ?>">
                                    <?php echo esc_html($top_3[0]['post']->post_title); ?>
                                </a>
                                <div class="me5rine-lab-podium-info">
                                    <?php echo esc_html($top_3[0]['participants']); ?> <?php esc_html_e('participants', 'me5rine-lab'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($top_3[2])): ?>
                            <div class="me5rine-lab-podium-step me5rine-lab-podium-3 animate">
                                <div class="me5rine-lab-podium-rank">3</div>
                                <a href="<?php echo esc_url(get_permalink($top_3[2]['post'])); ?>">
                                    <?php echo esc_html($top_3[2]['post']->post_title); ?>
                                </a>
                                <div class="me5rine-lab-podium-info">
                                    <?php echo esc_html($top_3[2]['participants']); ?> <?php esc_html_e('participants', 'me5rine-lab'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h3 class="me5rine-lab-card-section-title"><?php esc_html_e('Other Statistics', 'me5rine-lab'); ?></h3>
        <div class="giveaway-stats-tiles me5rine-lab-tiles-grid">
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($avg_participants); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Avg. Participants', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($avg_entries); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Avg. Entries', 'me5rine-lab'); ?></span></div>
            <div class="stat-tile me5rine-lab-tile"><span class="stat-number me5rine-lab-tile-number"><?php echo esc_html($total_prizes); ?></span><span class="stat-label me5rine-lab-tile-label"><?php esc_html_e('Prizes Offered', 'me5rine-lab'); ?></span></div>
        </div>
    </div>

    <div class="me5rine-lab-card">
        <h2 class="me5rine-lab-title-medium"><?php esc_html_e('Affiliate', 'me5rine-lab'); ?></h2>
        <p class="me5rine-lab-subtitle"><?php esc_html_e('Soon you will be able to manage your affiliation.', 'me5rine-lab'); ?></p>
    </div>

    <div class="me5rine-lab-card">
        <h2 class="me5rine-lab-title-medium"><?php esc_html_e('Analytics', 'me5rine-lab'); ?></h2>
        <p class="me5rine-lab-subtitle"><?php esc_html_e('Soon you will be able to see performance insights other features.', 'me5rine-lab'); ?></p>
    </div>
</div>
