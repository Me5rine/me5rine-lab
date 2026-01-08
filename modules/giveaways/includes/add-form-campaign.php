<?php
// File: modules/giveaways/includes/add-form-campaign.php

if (!defined('ABSPATH')) exit;

if (!admin_lab_require_access('giveaways', __('Create a campaign', 'me5rine-lab'))) {
    return;
}

if (!isset($settings)) {
    $settings = [];
}

$user_id = get_current_user_id();
$socials = get_socials_for_giveaway($user_id);

?>

<div class="campaign-form-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Create a Giveaway Campaign', 'me5rine-lab'); ?></h2>
    
    <?php
    // Notices front-end unifiées
    display_transient_message_front('rafflepress_duplicate_error', 'error');
    display_transient_message_front('rafflepress_file_error', 'error');
    display_transient_message_front('rafflepress_sync_error', 'error');
    display_transient_message_front('rafflepress_campaign_success', 'success');
    
    // Notice via paramètres GET (si redirection avec notice)
    me5rine_display_profile_notice();
    ?>

    <form id="rafflepress-campaign-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('create_rafflepress_campaign', 'campaign_nonce'); ?>
        <input type="hidden" name="submit_campaign" value="1">
    <div class="campaign-form-block me5rine-lab-form-container me5rine-lab-form-container-flex">
        <div class="me5rine-lab-form-section">
            <h3 class="me5rine-lab-title-medium"><?php _e('Title and dates', 'me5rine-lab'); ?></h3>
            <div class="campaign-start-end-block me5rine-lab-form-block me5rine-lab-form-block-flex me5rine-lab-form-col-gap">
            <div class="me5rine-lab-form-field">
                <label for="campaign_title" class="me5rine-lab-form-label"><?php _e('Title', 'me5rine-lab'); ?></label>
                <input type="text" name="campaign_title" id="campaign_title" class="me5rine-lab-form-input" value="<?php echo isset($_POST['campaign_title']) ? esc_attr($_POST['campaign_title']) : ''; ?>" required>
                <textarea name="campaign_description" id="campaign_description" hidden><?php echo isset($_POST['campaign_description']) ? esc_textarea($_POST['campaign_description']) : ''; ?></textarea>
            </div>
            <div class="campaign-start-block me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label for="campaign_start" class="me5rine-lab-form-label"><?php _e('Start Date', 'me5rine-lab'); ?></label>
                    <input type="date" name="campaign_start" id="campaign_start" class="me5rine-lab-form-input" value="<?php echo isset($_POST['campaign_start']) ? esc_attr($_POST['campaign_start']) : ''; ?>" required>
                </div>

                <div class="me5rine-lab-form-field">
                    <label for="campaign_start_hour" class="me5rine-lab-form-label"><?php _e('Start Time', 'me5rine-lab'); ?></label>
                    <div class="time-selection me5rine-lab-form-time-selection">
                        <select name="campaign_start_hour" id="campaign_start_hour" class="me5rine-lab-form-select" required>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?php printf('%02d', $h); ?>" <?php selected($_POST['campaign_start_hour'] ?? '', sprintf('%02d', $h)); ?>>
                                    <?php printf('%02d', $h); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span>H</span>
                        <select name="campaign_start_minute" id="campaign_start_minute" class="me5rine-lab-form-select" required>
                            <option value="00" <?php selected($_POST['campaign_start_minute'] ?? '', '00'); ?>>00</option>
                            <option value="30" <?php selected($_POST['campaign_start_minute'] ?? '', '30'); ?>>30</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="campaign-end-block me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label for="campaign_end" class="me5rine-lab-form-label"><?php _e('End Date', 'me5rine-lab'); ?></label>
                    <input type="date" name="campaign_end" id="campaign_end" class="me5rine-lab-form-input" value="<?php echo isset($_POST['campaign_end']) ? esc_attr($_POST['campaign_end']) : ''; ?>" required>
                </div>

                <div class="me5rine-lab-form-field">
                    <label for="campaign_end_hour" class="me5rine-lab-form-label"><?php _e('End Time', 'me5rine-lab'); ?></label>
                    <div class="time-selection me5rine-lab-form-time-selection">
                        <select name="campaign_end_hour" id="campaign_end_hour" class="me5rine-lab-form-select" required>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?php printf('%02d', $h); ?>" <?php selected($_POST['campaign_end_hour'] ?? '', sprintf('%02d', $h)); ?>>
                                    <?php printf('%02d', $h); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span>H</span>
                        <select name="campaign_end_minute" id="campaign_end_minute" class="me5rine-lab-form-select" required>
                            <option value="00" <?php selected($_POST['campaign_end_minute'] ?? '', '00'); ?>>00</option>
                            <option value="30" <?php selected($_POST['campaign_end_minute'] ?? '', '30'); ?>>30</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="me5rine-lab-form-section">
            <h3 class="me5rine-lab-title-medium"><?php _e('Prizes', 'me5rine-lab'); ?></h3>
            <div id="prizes-wrapper">
            <?php if (!empty($_POST['prize_name'])): ?>
                <?php foreach ($_POST['prize_name'] as $i => $name): ?>
                    <div class="prize-item me5rine-lab-card">
                        <div class="me5rine-lab-form-field">
                            <label class="me5rine-lab-form-label"><?php _e('Prize Name', 'me5rine-lab'); ?></label>
                            <input type="text" name="prize_name[]" class="me5rine-lab-form-input" value="<?php echo esc_attr($name); ?>" required>
                        </div>

                        <div class="me5rine-lab-form-field">
                            <label class="me5rine-lab-form-label"><?php _e('Prize Description', 'me5rine-lab'); ?></label>
                            <textarea name="prize_description[]" class="me5rine-lab-form-textarea"><?php echo esc_textarea($_POST['prize_description'][$i]); ?></textarea>
                        </div>

                        <?php
                        $is_edit_mode = isset($editing) && $editing === true;
                        $required_attr = $is_edit_mode ? '' : 'required';
                        ?>

                        <div class="me5rine-lab-form-field">
                            <label class="me5rine-lab-form-label"><?php _e('Prize Image (upload)', 'me5rine-lab'); ?></label>
                            <input class="me5rine-lab-form-button-file" type="file" name="prize_image_file[]" <?php echo $required_attr; ?>>
                        </div>

                        <?php if ($i > 0): ?>
                            <button type="button" class="remove-prize me5rine-lab-form-button me5rine-lab-form-button-remove"><?php _e('Remove Prize', 'me5rine-lab'); ?></button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="prize-item me5rine-lab-card">
                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Prize Name', 'me5rine-lab'); ?></label>
                        <input type="text" name="prize_name[]" class="me5rine-lab-form-input" required>
                    </div>

                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Prize Description', 'me5rine-lab'); ?></label>
                        <textarea name="prize_description[]" class="me5rine-lab-form-textarea"></textarea>
                    </div>

                    <div class="me5rine-lab-form-field">
                        <label class="me5rine-lab-form-label"><?php _e('Prize Image (upload)', 'me5rine-lab'); ?></label>
                        <input class="me5rine-lab-form-button-file" type="file" name="prize_image_file[]">
                    </div>
                </div>
            <?php endif; ?>
        </div>

            <button type="button" id="add-prize" class="me5rine-lab-form-button me5rine-lab-form-button-secondary uppercase"><?php _e('Add Prize', 'me5rine-lab'); ?></button>

            <script type="text/javascript">
                var removePrizeText = "<?php echo esc_js(__('Remove Prize', 'me5rine-lab')); ?>";
            </script>
            
            <?php
            include plugin_dir_path(__FILE__) . 'partials/campaign-rules.php';
            ?>    
        </div>

        <div class="me5rine-lab-form-section">
            <h3 class="me5rine-lab-title-medium"><?php _e('Social Actions', 'me5rine-lab'); ?></h3>
            <div class="social-actions-wrapper me5rine-lab-social-actions-wrapper">
            <div class="social-actions-block me5rine-lab-form-block">
                <?php
                $entry_options_by_type = $_POST['actions'] ?? [];

                // Ajout de 'visit-a-page' dans le tableau $socials
                $socials['visit-a-page'] = [
                    'url'          => '',  // Pas d'URL spécifique pour cette entrée
                    'type'         => 'visit-a-page',
                    'field'        => 'url',
                    'label'        => 'Visit a page',
                    'custom_label' => 'Visit a page',  // Utiliser le même label personnalisé
                    'text'         => 'Visit',
                ];

                // Parcours de chaque réseau social dans $socials
                foreach ($socials as $social_key => $social):;
                    $enabled = $entry_options_by_type[$social_key]['enabled'] ?? 0;
                    $score = isset($entry_options_by_type[$social_key]['points']) ? (int) $entry_options_by_type[$social_key]['points'] : null;
                    $field_name = $social['field'];
                    $label = $social['custom_label'];

                    // Traitement spécifique pour Twitter
                    if ($social_key === 'twitter') {
                        // Extraire le pseudo de Twitter (enlever le préfixe 'https://twitter.com/' ou '@')
                        $field_value = preg_replace('#^(https?://(www\.)?(twitter|x)\.com/)?(@?)([A-Za-z0-9_]+)$#', '$4', $social['url']);
                    } elseif ($social_key === 'visit-a-page') {
                        $field_value = $_POST['actions']['visit-a-page']['url'] ?? '';
                    } else {
                        // Pour les autres réseaux sociaux, prendre directement l'URL
                        $field_value = $social['url'];
                    }

                ?>

                <div class="social-action-tile me5rine-lab-social-action-tile" data-key="<?= esc_attr($social_key) ?>">
                    <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary btn-activate" id="activate_<?= esc_attr($social_key) ?>" data-key="<?= esc_attr($social_key) ?>" data-label="<?= esc_attr($label) ?>"></button>

                    <div class="score-options me5rine-lab-score-options <?= $enabled ? '' : 'me5rine-lab-hidden' ?>" id="score-options-<?= esc_attr($social_key) ?>">
                        <div class="btn-group" data-key="<?= esc_attr($social_key) ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="me5rine-lab-btn-score btn-score <?= ($score === $i) ? 'active' : '' ?>" data-score="<?= $i ?>">+ <?= $i ?></button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][enabled]" id="enabled_<?= esc_attr($social_key) ?>" value="<?= $enabled ? '1' : '0' ?>">
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][<?= esc_attr($field_name) ?>]" value="<?= $field_value ?>">
                        <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][points]" id="actions_<?= esc_attr($social_key) ?>_points" value="<?= esc_attr($score ?? '') ?>">
                    </div>

                    <?php if ($social_key === 'visit-a-page'): ?>
                        <div class="visit-url me5rine-lab-form-field <?= $enabled ? '' : 'me5rine-lab-hidden' ?>" id="visit-url-<?= esc_attr($social_key) ?>">
                            <label for="actions_name" class="me5rine-lab-form-label"><?php _e('Action title', 'me5rine-lab'); ?></label>
                            <input type="text" name="actions[visit-a-page][name]" id="actions_name" class="me5rine-lab-form-input" placeholder="<?php esc_attr_e('Visit a page', 'me5rine-lab'); ?>" value="<?= esc_attr($_POST['actions']['visit-a-page']['name'] ?? '') ?>">

                            <label for="actions_url" class="me5rine-lab-form-label"><?php _e('URL to visit', 'me5rine-lab'); ?></label>
                            <input type="url" name="actions[visit-a-page][url]" id="actions_url" class="me5rine-lab-form-input" placeholder="https://..." value="<?= $field_value ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>
        <div class="me5rine-lab-form-button-block">
            <input type="submit" name="submit_campaign" class="me5rine-lab-form-button" value="<?php esc_attr_e('Create Campaign', 'me5rine-lab'); ?>">
        </div>
    </form>
</div>
