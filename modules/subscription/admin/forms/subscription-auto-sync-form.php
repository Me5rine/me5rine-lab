<?php
// File: modules/subscription/admin/forms/subscription-auto-sync-form.php

if (!defined('ABSPATH')) exit;

/**
 * Form for automatic sync configuration
 * 
 * @param array $schedule_status Status of the automatic sync schedule
 */
function admin_lab_subscription_auto_sync_form($schedule_status) {
    ?>
    <div class="subscription-auto-sync-status">
        <h3>Synchronisation automatique</h3>
        <form method="post" action="">
            <?php wp_nonce_field('toggle_auto_sync'); ?>
            <input type="hidden" name="toggle_auto_sync" value="1">
            <p>
                <label>
                    <input type="checkbox" name="auto_sync_enabled" value="1" <?php checked($schedule_status['scheduled']); ?>>
                    Activer la synchronisation automatique (toutes les heures)
                </label>
            </p>
            <?php if ($schedule_status['scheduled']): ?>
                <p class="subscription-auto-sync-status-active">
                    <strong>Prochaine synchronisation :</strong> <?php echo esc_html($schedule_status['next_run_formatted']); ?>
                </p>
            <?php else: ?>
                <p class="subscription-auto-sync-status-inactive">
                    <strong>Statut :</strong> Désactivée
                </p>
            <?php endif; ?>
            <p>
                <button type="submit" class="button button-secondary">Enregistrer</button>
            </p>
        </form>
    </div>
    <?php
}


