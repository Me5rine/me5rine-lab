<?php
// File: modules/game-servers/templates/single-default.php
// Template par défaut pour un serveur unique

if (!defined('ABSPATH')) exit;

$game = null;
if (!empty($server['game_id'])) {
    $game = admin_lab_game_servers_get_game($server['game_id']);
}
$address = admin_lab_game_servers_format_address(admin_lab_game_servers_get_display_address($server), $server['port']);
$fill_percentage = admin_lab_game_servers_get_fill_percentage($server['current_players'], $server['max_players']);
$tags = admin_lab_game_servers_parse_tags($server['tags']);
?>
<div class="game-server-single">
    <?php if (!empty($server['banner_url'])) : ?>
        <div class="game-server-banner">
            <img src="<?php echo esc_url($server['banner_url']); ?>" alt="<?php echo esc_attr($server['name']); ?>">
        </div>
    <?php endif; ?>
    
    <div class="game-server-content">
        <div class="game-server-header">
            <?php if (!empty($server['logo_url'])) : ?>
                <img src="<?php echo esc_url($server['logo_url']); ?>" alt="<?php echo esc_attr($server['name']); ?>" class="game-server-logo">
            <?php endif; ?>
            
            <div class="game-server-info">
                <h1 class="game-server-name"><?php echo esc_html($server['name']); ?></h1>
                <?php if ($game && !empty($game['name'])) : ?>
                    <p class="game-server-game"><?php echo esc_html($game['name']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php echo admin_lab_game_servers_get_status_badge($server['status']); ?>
        </div>
        
        <?php if (!empty($server['description'])) : ?>
            <div class="game-server-description">
                <?php echo wp_kses_post($server['description']); ?>
            </div>
        <?php endif; ?>
        
        <div class="game-server-details">
            <div class="game-server-address">
                <strong><?php _e('Address:', 'me5rine-lab'); ?></strong>
                <code><?php echo esc_html($address); ?></code>
                <button class="copy-ip" data-ip="<?php echo esc_attr($address); ?>" title="<?php esc_attr_e('Copy address', 'me5rine-lab'); ?>">
                    <?php _e('Copy', 'me5rine-lab'); ?>
                </button>
            </div>
            
            <div class="game-server-players">
                <strong><?php _e('Players:', 'me5rine-lab'); ?></strong>
                <span class="players-count">
                    <?php printf(__('%d / %d', 'me5rine-lab'), $server['current_players'], $server['max_players']); ?>
                </span>
                <div class="players-bar">
                    <div class="players-bar-fill" style="width: <?php echo esc_attr($fill_percentage); ?>%"></div>
                </div>
            </div>
            
            <?php if (!empty($server['version'])) : ?>
                <div class="game-server-version">
                    <strong><?php _e('Version:', 'me5rine-lab'); ?></strong>
                    <span><?php echo esc_html($server['version']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($server['provider'])) : ?>
                <div class="game-server-provider">
                    <strong><?php _e('Provider:', 'me5rine-lab'); ?></strong>
                    <span><?php echo esc_html($server['provider']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($tags)) : ?>
            <div class="game-server-tags">
                <?php foreach ($tags as $tag) : ?>
                    <span class="game-server-tag"><?php echo esc_html($tag); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var button = document.querySelector('.copy-ip');
    if (button) {
        button.addEventListener('click', function() {
            var ip = this.getAttribute('data-ip');
            navigator.clipboard.writeText(ip).then(function() {
                var originalText = button.textContent;
                button.textContent = '<?php echo esc_js(__('Copié!', 'me5rine-lab')); ?>';
                setTimeout(function() {
                    button.textContent = originalText;
                }, 2000);
            });
        });
    }
})();
</script>

