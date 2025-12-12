<?php
// File: modules/giveaways/admin-filters/meta-boxes.php

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'giveaway_details',
        __('Giveaway Details', 'me5rine-lab'),
        'giveaways_render_meta_box',
        'giveaway',
        'side',
        'default'
    );
});

function giveaways_render_meta_box($post)
{
    $do_not_update_rafflepress = isset($_POST['do_not_update_rafflepress_dates']) ? $_POST['do_not_update_rafflepress_dates'] : false;
    wp_nonce_field('giveaways_meta_box_nonce', 'giveaways_meta_box_nonce');

    $start_date = get_post_meta($post->ID, '_giveaway_start_date', true);
    $end_date = get_post_meta($post->ID, '_giveaway_end_date', true);
    $partner_id = get_post_meta($post->ID, '_giveaway_partner_id', true);
    $status = get_post_meta($post->ID, '_giveaway_status', true);
    $rafflepress_campaign = get_post_meta($post->ID, '_rafflepress_campaign', true);

    global $wpdb;
    $rafflepress_campaign_data = $wpdb->get_row($wpdb->prepare("SELECT ID, name FROM {$wpdb->prefix}rafflepress_giveaways WHERE ID = %d", $rafflepress_campaign));

    $tz = new DateTimeZone(get_option('timezone_string') ?: 'UTC');

    $start = $start_date ? new DateTime($start_date, new DateTimeZone('UTC')) : new DateTime('now', $tz);
    $end = $end_date ? new DateTime($end_date, new DateTimeZone('UTC')) : new DateTime('now', $tz);

    $start->setTimezone($tz);
    $end->setTimezone($tz);

    $start_date_only = $start->format('Y-m-d');
    $start_hour = $start->format('H');
    $start_minute = $start->format('i');

    $end_date_only = $end->format('Y-m-d');
    $end_hour = $end->format('H');
    $end_minute = $end->format('i');
    ?>
    <label class="rafflepress-not-update" for="do_not_update_rafflepress_dates">
        <input type="checkbox" name="do_not_update_rafflepress_dates" value="1" <?php checked($do_not_update_rafflepress, '1'); ?> />
        <?php _e('Do not update dates in RafflePress', 'me5rine-lab'); ?>
    </label><br><br>
    <label><?php _e('Start Date:', 'me5rine-lab'); ?></label><br>
    <input type="date" name="giveaway_start_date" value="<?php echo esc_attr($start_date_only); ?>">
    <select name="giveaway_start_hour">
        <?php for ($i = 0; $i < 24; $i++): ?>
            <option value="<?php echo $i; ?>" <?php selected((int)$start_hour, $i); ?>><?php printf('%02d', $i); ?></option>
        <?php endfor; ?>
    </select>
    <select name="giveaway_start_minute">
        <option value="00" <?php selected($start_minute, '00'); ?>>00</option>
        <option value="30" <?php selected($start_minute, '30'); ?>>30</option>
    </select>

    <br><br>
    <label><?php _e('End Date:', 'me5rine-lab'); ?></label><br>
    <input type="date" name="giveaway_end_date" value="<?php echo esc_attr($end_date_only); ?>">
    <select name="giveaway_end_hour">
        <?php for ($i = 0; $i < 24; $i++): ?>
            <option value="<?php echo $i; ?>" <?php selected((int)$end_hour, $i); ?>><?php printf('%02d', $i); ?></option>
        <?php endfor; ?>
    </select>
    <select name="giveaway_end_minute">
        <option value="00" <?php selected($end_minute, '00'); ?>>00</option>
        <option value="30" <?php selected($end_minute, '30'); ?>>30</option>
    </select>

    <br><br>
    <label><?php _e('Status:', 'me5rine-lab'); ?></label><br>
    <input type="text" value="<?php echo esc_attr($status); ?>" readonly>

    <br><br>
    <label><?php _e('Partner:', 'me5rine-lab'); ?></label><br>
    <select name="giveaway_partner_id" id="giveaway_partner_id">
        <option value="">-- <?php _e('No partner', 'me5rine-lab'); ?> --</option>
        <?php
        $partners = get_users([
            'role__in' => ['um_partenaire', 'um_partenaire_plus']
        ]);        
        foreach ($partners as $user) {
            echo '<option value="' . esc_attr($user->ID) . '" ' . selected($partner_id, $user->ID, false) . '>' . esc_html($user->display_name) . '</option>';
        }
        ?>
    </select>

    <br><br>
    <label><?php _e('RafflePress Campaign:', 'me5rine-lab'); ?></label><br>
    <select name="rafflepress_campaign" id="rafflepress_campaign">
        <?php if ($rafflepress_campaign_data): ?>
            <option value="<?php echo esc_attr($rafflepress_campaign_data->ID); ?>" selected>
                <?php echo esc_html($rafflepress_campaign_data->name); ?>
            </option>
        <?php endif; ?>
    </select>

    <br><br>
    <label for="disable_admin_lab_actions">
        <input type="checkbox" name="disable_admin_lab_actions" value="1" <?php checked(get_post_meta($post->ID, '_disable_admin_lab_actions', true), '1'); ?> />
        <?php _e('Disable Me5rine LAB actions for this giveaway', 'me5rine-lab'); ?>
    </label>
    <p class="description"><?php _e('If checked, Me5rine LAB social actions will not be displayed in this giveaway.', 'me5rine-lab'); ?></p>
    <script>
    jQuery(document).ready(function($){
        $('#giveaway_partner_id').select2({
            placeholder: '<?php _e("Search Partner...", "me5rine-lab"); ?>',
            allowClear: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'search_partners',
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        $('#rafflepress_campaign').select2({
            placeholder: '<?php _e("Search RafflePress campaigns...", "me5rine-lab"); ?>',
            allowClear: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'search_rafflepress_campaigns',
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 1
        });
    });
    </script>
    <?php
}

require_once dirname(__DIR__) . '/functions/meta-boxes-save.php';
