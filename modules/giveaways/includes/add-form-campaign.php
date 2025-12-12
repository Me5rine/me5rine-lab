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

<form id="rafflepress-campaign-form" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('create_rafflepress_campaign', 'campaign_nonce'); ?>
    <input type="hidden" name="submit_campaign" value="1">

    <?php
    display_transient_message('rafflepress_duplicate_error');
    display_transient_message('rafflepress_file_error');

    if (get_transient('rafflepress_campaign_success')) {
        echo '<div class="mlab-campaign-success notice notice-success success-message">';
        echo esc_html__('Your giveaway was successfully created! You will be redirected.', 'me5rine-lab');
        echo '</div>';
        delete_transient('rafflepress_campaign_success');
    }
    ?>

    <h2><?php _e('Create a Giveaway Campaign', 'me5rine-lab'); ?></h2>
    <h3><?php _e('Title and dates', 'me5rine-lab'); ?></h3>
    <div class="campaign-title-block">
        <label for="campaign_title"><?php _e('Title', 'me5rine-lab'); ?></label>
        <input type="text" name="campaign_title" id="campaign_title" value="<?php echo isset($_POST['campaign_title']) ? esc_attr($_POST['campaign_title']) : ''; ?>" required>
        <textarea name="campaign_description" id="campaign_description" hidden><?php echo isset($_POST['campaign_description']) ? esc_textarea($_POST['campaign_description']) : ''; ?></textarea>
    </div>
    <div class="campaign-start-end-block">
        <div class="campaign-start-block">
            <label for="campaign_start"><?php _e('Start Date', 'me5rine-lab'); ?></label>
            <input type="date" name="campaign_start" id="campaign_start" value="<?php echo isset($_POST['campaign_start']) ? esc_attr($_POST['campaign_start']) : ''; ?>" required>

            <label for="campaign_start_hour"><?php _e('Start Time', 'me5rine-lab'); ?></label>
            <div class="time-selection">
                <select name="campaign_start_hour" id="campaign_start_hour" required>
                    <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?php printf('%02d', $h); ?>" <?php selected($_POST['campaign_start_hour'] ?? '', sprintf('%02d', $h)); ?>>
                            <?php printf('%02d', $h); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <span>H</span>
                <select name="campaign_start_minute" id="campaign_start_minute" required>
                    <option value="00" <?php selected($_POST['campaign_start_minute'] ?? '', '00'); ?>>00</option>
                    <option value="30" <?php selected($_POST['campaign_start_minute'] ?? '', '30'); ?>>30</option>
                </select>
            </div>
        </div>

        <div class="campaign-end-block">
            <label for="campaign_end"><?php _e('End Date', 'me5rine-lab'); ?></label>
            <input type="date" name="campaign_end" id="campaign_end" value="<?php echo isset($_POST['campaign_end']) ? esc_attr($_POST['campaign_end']) : ''; ?>" required>

            <label for="campaign_end_hour"><?php _e('End Time', 'me5rine-lab'); ?></label>
            <div class="time-selection">
                <select name="campaign_end_hour" id="campaign_end_hour" required>
                    <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?php printf('%02d', $h); ?>" <?php selected($_POST['campaign_end_hour'] ?? '', sprintf('%02d', $h)); ?>>
                            <?php printf('%02d', $h); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <span>H</span>
                <select name="campaign_end_minute" id="campaign_end_minute" required>
                    <option value="00" <?php selected($_POST['campaign_end_minute'] ?? '', '00'); ?>>00</option>
                    <option value="30" <?php selected($_POST['campaign_end_minute'] ?? '', '30'); ?>>30</option>
                </select>
            </div>
        </div>
    </div>

    <h3><?php _e('Prizes', 'me5rine-lab'); ?></h3>
    <div id="prizes-wrapper">
        <?php if (!empty($_POST['prize_name'])): ?>
            <?php foreach ($_POST['prize_name'] as $i => $name): ?>
                <div class="prize-item">
                    <label><?php _e('Prize Name', 'me5rine-lab'); ?></label>
                    <input type="text" name="prize_name[]" value="<?php echo esc_attr($name); ?>" required>

                    <label><?php _e('Prize Description', 'me5rine-lab'); ?></label>
                    <textarea name="prize_description[]"><?php echo esc_textarea($_POST['prize_description'][$i]); ?></textarea>

                    <?php
                    $is_edit_mode = isset($editing) && $editing === true;
                    $required_attr = $is_edit_mode ? '' : 'required';
                    ?>

                    <label><?php _e('Prize Image (upload)', 'me5rine-lab'); ?></label>
                    <input type="file" name="prize_image_file[]" <?php echo $required_attr; ?>>

                    <?php if ($i > 0): ?>
                        <button type="button" class="remove-prize"><?php _e('Remove Prize', 'me5rine-lab'); ?></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="prize-item">
                <label><?php _e('Prize Name', 'me5rine-lab'); ?></label>
                <input type="text" name="prize_name[]" required>

                <label><?php _e('Prize Description', 'me5rine-lab'); ?></label>
                <textarea name="prize_description[]"></textarea>

                <label><?php _e('Prize Image (upload)', 'me5rine-lab'); ?></label>
                <input type="file" name="prize_image_file[]">
            </div>
        <?php endif; ?>
    </div>

    <button type="button" id="add-prize"><?php _e('Add Prize', 'me5rine-lab'); ?></button>

    <script type="text/javascript">
        var removePrizeText = "<?php echo esc_js(__('Remove Prize', 'me5rine-lab')); ?>";
    </script>
    
    <?php
    include plugin_dir_path(__FILE__) . 'partials/campaign-rules.php';
    ?>    

    <h3><?php _e('Social Actions', 'me5rine-lab'); ?></h3>
    <div class="social-actions-wrapper">
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
            }else {
                // Pour les autres réseaux sociaux, prendre directement l'URL
                $field_value = $social['url'];
            }

        ?>

        <div class="social-action-tile" data-key="<?= esc_attr($social_key) ?>">
            <button type="button" class="btn btn-activate" id="activate_<?= esc_attr($social_key) ?>" data-key="<?= esc_attr($social_key) ?>" data-label="<?= esc_attr($label) ?>"></button>

            <div class="score-options" id="score-options-<?= esc_attr($social_key) ?>" style="<?= $enabled ? 'display:block;' : 'display:none;' ?>">
                <div class="btn-group" data-key="<?= esc_attr($social_key) ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="btn btn-score <?= ($score === $i) ? 'active' : '' ?>" data-score="<?= $i ?>">+ <?= $i ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][enabled]" id="enabled_<?= esc_attr($social_key) ?>" value="<?= $enabled ? '1' : '0' ?>">
                <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][<?= esc_attr($field_name) ?>]" value="<?= $field_value ?>">
                <input type="hidden" name="actions[<?= esc_attr($social_key) ?>][points]" id="actions_<?= esc_attr($social_key) ?>_points" value="<?= esc_attr($score ?? '') ?>">
            </div>

            <?php if ($social_key === 'visit-a-page'): ?>
                <div class="visit-url" id="visit-url-<?= esc_attr($social_key) ?>" style="<?= $enabled ? 'display:block;' : 'display:none;' ?>">
                    <label for="actions_name"><?php _e('Action title', 'me5rine-lab'); ?></label>
                    <input type="text" name="actions[visit-a-page][name]" id="actions_name" placeholder="<?php esc_attr_e('Visit a page', 'me5rine-lab'); ?>" value="<?= esc_attr($_POST['actions']['visit-a-page']['name'] ?? '') ?>">

                    <label for="actions_url"><?php _e('URL to visit', 'me5rine-lab'); ?></label>
                    <input type="url" name="actions[visit-a-page][url]" id="actions_url" placeholder="https://..." value="<?= $field_value ?>">
                </div>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>
    </div>

    <input type="submit" name="submit_campaign" value="<?php esc_attr_e('Create Campaign', 'me5rine-lab'); ?>">
</form>
