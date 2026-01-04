<?php
// File: modules/giveaways/includes/edit-form-campaign.php

if (!defined('ABSPATH')) exit;

if (!admin_lab_require_access('giveaways', __('Edit this giveaway', 'me5rine-lab'))) {
    return;
}

function admin_lab_old_input($name, $default = '') {
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $giveaway_id = isset($_GET['giveaway_id']) ? intval($_GET['giveaway_id']) : 0;
    if ($giveaway_id === 0) {
        echo '<p>' . esc_html__('Invalid giveaway ID.', 'me5rine-lab') . '</p>';
        return;
    }

    global $wpdb;
    $giveaway = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d AND active = 1", $giveaway_id));
    if (!$giveaway) {
        echo '<p>' . esc_html__('Giveaway not found or you do not have permission to edit it.', 'me5rine-lab') . '</p>';
        return;
    }

    $post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_rafflepress_campaign' AND meta_value = %d", $giveaway->id));
    if (!$post_id) {
        echo '<p>' . esc_html__('Giveaway post not found.', 'me5rine-lab') . '</p>';
        return;
    }

    $post = get_post($post_id);
    $partner_id = get_post_meta($post_id, '_giveaway_partner_id', true);
    if ((string) get_current_user_id() !== (string) $partner_id) {
        echo '<p>' . esc_html__('You do not have permission to edit this giveaway.', 'me5rine-lab') . '</p>';
        return;
    }

    $tz = admin_lab_get_wp_timezone();
    $start_date = new DateTime($giveaway->starts, new DateTimeZone('GMT'));
    $start_date->setTimezone($tz);
    $end_date = new DateTime($giveaway->ends, new DateTimeZone('GMT'));
    $end_date->setTimezone($tz);

    $start_date_local = admin_lab_old_input('campaign_start', $start_date->format('Y-m-d'));
    $start_hour = admin_lab_old_input('campaign_start_hour', $start_date->format('H'));
    $start_minute = admin_lab_old_input('campaign_start_minute', $start_date->format('i'));

    $end_date_local = admin_lab_old_input('campaign_end', $end_date->format('Y-m-d'));
    $end_hour = admin_lab_old_input('campaign_end_hour', $end_date->format('H'));
    $end_minute = admin_lab_old_input('campaign_end_minute', $end_date->format('i'));

    $settings = json_decode($giveaway->settings);
    $prizes = $settings->prizes ?? [];
    $entry_options = $settings->entry_options ?? [];

    $entry_options_by_social = [];
    foreach ($entry_options as $option) {
        if (isset($option->social)) {
            $entry_options_by_social[$option->social] = $option;
        }
    }

    $user_id = get_current_user_id();
    $socials = get_socials_for_giveaway($user_id);

    $socials['visit-a-page'] = [
        'url'          => '',  // Pas d'URL spécifique pour cette entrée
        'type'         => 'visit-a-page',
        'field'        => 'url',
        'label'        => 'Visit a page',
        'custom_label' => 'Visit a page',  // Utiliser le même label personnalisé
        'text'         => 'Visit',
    ];

} else {
    echo '<p>' . esc_html__('Invalid action. You must be in edit mode to access this page.', 'me5rine-lab') . '</p>';
    return;
}
?>

<form id="rafflepress-campaign-form" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('edit_rafflepress_campaign', 'campaign_nonce'); ?>
    <input type="hidden" name="edit_campaign" value="1">
    <input type="hidden" name="campaign_id" value="<?php echo esc_attr($giveaway->id); ?>">

    <?php
    display_transient_message('rafflepress_duplicate_error');
    display_transient_message('rafflepress_file_error');

    if (get_transient('rafflepress_campaign_success')) {
        echo '<div class="me5rine-lab-campaign-success notice notice-success success-message">' . esc_html__('Your giveaway was successfully updated! You will be redirected.', 'me5rine-lab') . '</div>';
        delete_transient('rafflepress_campaign_success');
    }
    ?>

    <h2 class="me5rine-lab-title-large"><?php _e('Edit Giveaway Campaign', 'me5rine-lab'); ?></h2>
    <div class="campaign-form-block me5rine-lab-form-container me5rine-lab-form-container-flex">
        <h3 class="me5rine-lab-title-medium"><?php _e('Title and dates', 'me5rine-lab'); ?></h3>
        <div class="campaign-start-end-block me5rine-lab-form-block me5rine-lab-form-block-flex me5rine-lab-form-col-gap">
            <div class="me5rine-lab-form-field">
                <label for="campaign_title" class="me5rine-lab-form-label"><?php _e('Title', 'me5rine-lab'); ?></label>
                <input type="text" name="campaign_title" id="campaign_title" class="me5rine-lab-form-input" value="<?php echo esc_attr(admin_lab_old_input('campaign_title', $post->post_title)); ?>" required>
                <textarea name="campaign_description" id="campaign_description" hidden><?php echo esc_textarea(admin_lab_old_input('campaign_description', $post->post_content)); ?></textarea>
            </div>
            <div class="campaign-start-block me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label for="campaign_start" class="me5rine-lab-form-label"><?php _e('Start Date', 'me5rine-lab'); ?></label>
                    <input type="date" name="campaign_start" id="campaign_start" class="me5rine-lab-form-input" value="<?php echo esc_attr($start_date_local); ?>" required>
                </div>

                <div class="me5rine-lab-form-field">
                    <label for="campaign_start_hour" class="me5rine-lab-form-label"><?php _e('Start Time', 'me5rine-lab'); ?></label>
                    <div class="time-selection me5rine-lab-form-time-selection">
                        <select name="campaign_start_hour" id="campaign_start_hour" class="me5rine-lab-form-select" required>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?= sprintf('%02d', $h) ?>" <?= ($start_hour == sprintf('%02d', $h)) ? 'selected' : '' ?>><?= sprintf('%02d', $h) ?></option>
                            <?php endfor; ?>
                        </select>
                        <span>H</span>
                        <select name="campaign_start_minute" id="campaign_start_minute" class="me5rine-lab-form-select" required>
                            <option value="00" <?= ($start_minute == '00') ? 'selected' : '' ?>>00</option>
                            <option value="30" <?= ($start_minute == '30') ? 'selected' : '' ?>>30</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="campaign-end-block me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label for="campaign_end" class="me5rine-lab-form-label"><?php _e('End Date', 'me5rine-lab'); ?></label>
                    <input type="date" name="campaign_end" id="campaign_end" class="me5rine-lab-form-input" value="<?php echo esc_attr($end_date_local); ?>" required>
                </div>

                <div class="me5rine-lab-form-field">
                    <label for="campaign_end_hour" class="me5rine-lab-form-label"><?php _e('End Time', 'me5rine-lab'); ?></label>
                    <div class="time-selection me5rine-lab-form-time-selection">
                        <select name="campaign_end_hour" id="campaign_end_hour" class="me5rine-lab-form-select" required>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?= sprintf('%02d', $h) ?>" <?= ($end_hour == sprintf('%02d', $h)) ? 'selected' : '' ?>><?= sprintf('%02d', $h) ?></option>
                            <?php endfor; ?>
                        </select>
                        <span>H</span>
                        <select name="campaign_end_minute" id="campaign_end_minute" class="me5rine-lab-form-select" required>
                            <option value="00" <?= ($end_minute == '00') ? 'selected' : '' ?>>00</option>
                            <option value="30" <?= ($end_minute == '30') ? 'selected' : '' ?>>30</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $prize_names = $_POST['prize_name'] ?? array_map(fn($p) => stripslashes($p->name), $prizes);
        $prize_descriptions = $_POST['prize_description'] ?? array_map(fn($p) => stripslashes($p->description), $prizes);
        $prize_images = $_POST['existing_prize_image'] ?? array_map(fn($p) => $p->image ?? '', $prizes);
        ?>

        <h3 class="me5rine-lab-title-medium"><?php _e('Prizes', 'me5rine-lab'); ?></h3>
        <div id="prizes-wrapper">
            <?php foreach ($prize_names as $index => $name): ?>
                <div class="prize-item me5rine-lab-card">
                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Prize Name', 'me5rine-lab'); ?></label>
                        <input type="text" name="prize_name[]" class="me5rine-lab-form-input" value="<?php echo esc_attr($name); ?>" required>
                    </div>

                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Description', 'me5rine-lab'); ?></label>
                        <textarea name="prize_description[]" class="me5rine-lab-form-textarea"><?php echo esc_textarea($prize_descriptions[$index] ?? ''); ?></textarea>
                    </div>

                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Image (optional)', 'me5rine-lab'); ?></label>
                        <input class="me5rine-lab-form-button-file" type="file" name="prize_image_file[]">
                        <input type="hidden" name="existing_prize_image[]" value="<?php echo esc_url($prize_images[$index]); ?>">
                    </div>

                    <?php if (!empty($prize_images[$index])): ?>
                        <div class="current-image">
                            <img src="<?php echo esc_url($prize_images[$index]); ?>" class="me5rine-lab-image-preview">
                        </div>
                    <?php endif; ?>
                    <?php if ($index > 0): ?>
                        <button type="button" class="remove-prize me5rine-lab-form-button me5rine-lab-form-button-remove"><?php _e('Remove Prize', 'me5rine-lab'); ?></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-prize" class="me5rine-lab-form-button me5rine-lab-form-button-secondary uppercase"><?php _e('Add Prize', 'me5rine-lab'); ?></button>
        <script type="text/javascript">
            var removePrizeText = "<?php echo esc_js(__('Remove Prize', 'me5rine-lab')); ?>";
        </script>
        
        <?php
        include plugin_dir_path(__FILE__) . 'partials/campaign-rules.php';
        ?>    

        <h3 class="me5rine-lab-title-medium"><?php _e('Social Actions', 'me5rine-lab'); ?></h3>
        <div class="social-actions-wrapper me5rine-lab-social-actions-wrapper">
            <div class="social-actions-block me5rine-lab-form-block">
                <?php foreach ($socials as $social_key => $social):
                    $label = $social['custom_label'];
                    $field_name = $social['field'];
                    $type = $social['type'];
                
                    $option = $_POST['actions'][$social_key] ?? ($entry_options_by_social[$social_key] ?? null);

                    $enabled = false;
                    $score = '';
                    $field_value = get_user_meta($user_id, str_replace(['-like-share', '-follow'], '', $type), true);

                    $action_name = '';
                    if ($social_key === 'visit-a-page') {
                        if (isset($_POST['actions']['visit-a-page']['name'])) {
                            $action_name = sanitize_text_field($_POST['actions']['visit-a-page']['name']);
                        } elseif (is_object($option) && isset($option->name)) {
                            $action_name = $option->name;
                        } else {
                            $action_name = __('Visit a page', 'me5rine-lab');
                        }
                    }

                    if (is_array($option) && isset($option['enabled']) && $option['enabled'] === '1') {
                        $enabled = true;
                        $score = isset($option['points']) ? (int) $option['points'] : 1;
                        $field_value = $option[$field_name] ?? $field_value;
                    } elseif (is_object($option)) {
                        $enabled = true;
                        $score = isset($option->value) ? (int) $option->value : 1;
                        $field_value = isset($option->$field_name) ? $option->$field_name : $field_value;
                    }
                ?>

                <div class="social-action-tile me5rine-lab-social-action-tile" data-key="<?= esc_attr($social_key) ?>">
                    <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary btn-activate" id="activate_<?= esc_attr($social_key) ?>" data-key="<?= esc_attr($social_key) ?>" data-label="<?= esc_attr($label) ?>">
                        <?= esc_html($label); ?>
                    </button>
                    <div class="score-options me5rine-lab-score-options <?= $enabled ? '' : 'me5rine-lab-hidden' ?>" id="score-options-<?= esc_attr($social_key) ?>">
                        <div class="btn-group" data-key="<?= esc_attr($social_key) ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="me5rine-lab-btn-score btn-score <?= ($i === intval($score)) ? 'active' : '' ?>" data-score="<?= $i ?>">
                                    + <?= $i ?>
                                </button>
                            <?php endfor; ?>
                        </div>

                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][enabled]" id="enabled_<?= esc_attr($social_key) ?>" value="<?= $enabled ? '1' : '0' ?>">
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][<?= esc_attr($field_name) ?>]" value="<?= esc_attr($field_value) ?>">
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][type]" id="type_<?= esc_attr($social_key) ?>" value="<?= esc_attr($type) ?>">
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][points]" id="actions_<?= esc_attr($social_key) ?>_points" value="<?= esc_attr($score) ?>">
                    </div>

                    <?php if ($social_key === 'visit-a-page'): ?>
                        <div class="visit-url me5rine-lab-form-field <?= $enabled ? '' : 'me5rine-lab-hidden' ?>" id="visit-url-<?= esc_attr($social_key) ?>">
                            <label for="actions_name" class="me5rine-lab-form-label"><?php _e('Action title', 'me5rine-lab'); ?></label>
                            <input type="text" name="actions[visit-a-page][name]" id="actions_name" class="me5rine-lab-form-input" placeholder="<?php esc_attr_e('Visit a page', 'me5rine-lab'); ?>" value="<?= esc_attr($action_name) ?>">

                            <label for="actions_url" class="me5rine-lab-form-label"><?php _e('URL to visit', 'me5rine-lab'); ?></label>
                            <input type="url" name="actions[visit-a-page][url]" id="actions_url" class="me5rine-lab-form-input" placeholder="https://..." value="<?= esc_attr($field_value) ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
        
    <div class="me5rine-lab-form-button-block">
        <input type="submit" name="edit_campaign" class="me5rine-lab-form-button" value="<?php esc_attr_e('Save Changes', 'me5rine-lab'); ?>">
    </div>
</form>
