<?php
// File: modules/giveaways/includes/partials/campaign-rules.php

if (!defined('ABSPATH')) exit;

$minimum_age = $_POST['minimum_age']
    ?? admin_lab_get_property($settings, 'eligible_min_age', '');

$eligible_countries_raw = $_POST['eligible_countries']
    ?? admin_lab_get_property($settings, 'state_province', '');

$selected = is_array($eligible_countries_raw)
    ? $eligible_countries_raw
    : explode(',', $eligible_countries_raw);

$selected = array_map('trim', $selected);
?>
<h3><?php _e('Participation Rules', 'me5rine-lab'); ?></h3>
<div class="mlab-rules-row-block">
  <div class="mlab-rules-row">
    <div class="mlab-rules-col">
      <label for="eligible_countries"><?php _e('Eligible countries', 'me5rine-lab'); ?></label>
      <select id="eligible_countries" name="eligible_countries[]" multiple required>
        <?php foreach (admin_lab_get_all_countries() as $code => $label): ?>
          <option value="<?php echo esc_attr($label); ?>" <?php selected(in_array($label, $selected)); ?>>
            <?php echo esc_html($label); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mlab-rules-col">
      <label for="minimum_age"><?php _e('Minimum age required', 'me5rine-lab'); ?></label>
      <input type="number" name="minimum_age" id="minimum_age" min="13" step="1"
            value="<?php echo esc_attr($minimum_age); ?>" required>
    </div>
  </div>
</div>
