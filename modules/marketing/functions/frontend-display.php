<?php
// File: modules/marketing/functions/frontend-display.php

if (!defined('ABSPATH')) exit;

function admin_lab_render_marketing_background_script() {
    $campaign_id = get_option('admin_lab_marketing_zone_background');
    if (!$campaign_id) return;

    $campaign = admin_lab_get_campaign_by_id($campaign_id);
    if (!$campaign || empty($campaign->image_url_background) || empty($campaign->campaign_url)) return;

    $image_url = esc_url($campaign->image_url_background);
    $link_url  = esc_url($campaign->campaign_url);
    $bg_color  = esc_attr($campaign->background_color ?? '#000000');
    ?>

    <style>
    #marketing-background-link {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 0;
        background-image: url('<?php echo $image_url; ?>');
        background-color: <?php echo $bg_color; ?>;
        background-repeat: no-repeat;
        background-position: top center;
        background-size: contain;
        cursor: pointer;
    }

    body > *:not(#marketing-background-link) {
        position: relative;
        z-index: 1;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const backgroundImageUrl = '<?php echo esc_js($image_url); ?>';
    const backgroundColor = '<?php echo esc_js($bg_color); ?>';
    const linkUrl = '<?php echo esc_url($link_url); ?>';

    // Créer le fond
    const backgroundDiv = document.createElement('div');
    backgroundDiv.id = 'background-div';
    backgroundDiv.style.backgroundImage = `url('${backgroundImageUrl}')`;
    backgroundDiv.style.backgroundColor = backgroundColor;
    backgroundDiv.style.position = 'fixed';
    backgroundDiv.style.top = 0;
    backgroundDiv.style.left = 0;
    backgroundDiv.style.width = '100%';
    backgroundDiv.style.height = '100%';
    backgroundDiv.style.zIndex = '-1';
    backgroundDiv.style.backgroundRepeat = 'no-repeat';
    backgroundDiv.style.backgroundPosition = 'top center';
    backgroundDiv.style.backgroundSize = 'contain';
    document.body.appendChild(backgroundDiv);

    function isExcludedTarget(target) {
        return (
        target.closest('.no-click-zone') ||
        target.closest('a') ||
        target.matches('[data-ep-wrapper-link]') ||
        target.closest('.elementor-widget-container') ||
        target.closest('[data-elementor-type="header"]') ||
        target.closest('[data-elementor-type="footer"]')
        );
    }

    document.body.addEventListener('click', function(event) {
        if (isExcludedTarget(event.target)) return;
        window.open(linkUrl, '_blank');
    });

    document.body.addEventListener('mousemove', function(event) {
        if (isExcludedTarget(event.target)) {
        document.body.style.cursor = 'default';
        } else {
        document.body.style.cursor = 'pointer';
        }
    });
    });
    </script>

    <?php
}
add_action('wp_footer', 'admin_lab_render_marketing_background_script');
