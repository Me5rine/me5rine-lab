<?php
// File: modules/game-servers/templates/list-default.php
// Template par défaut pour la liste des serveurs - Style moderne avec bannière pleine largeur

if (!defined('ABSPATH')) exit;
?>
<div class="game-servers-list">
    <?php foreach ($servers as $server) : 
        $game = null;
        if (!empty($server['game_id'])) {
            $game_raw = admin_lab_game_servers_get_game($server['game_id']);
            $game = (is_array($game_raw) && !is_wp_error($game_raw)) ? $game_raw : null;
        }
        $address = admin_lab_game_servers_format_address(admin_lab_game_servers_get_display_address($server), $server['port']);
        $fill_percentage = admin_lab_game_servers_get_fill_percentage($server['current_players'], $server['max_players']);
        $tags = admin_lab_game_servers_parse_tags($server['tags']);
        $has_banner = !empty($server['banner_url']);
        // Logo du jeu (ClicksNGames) en priorité à gauche, sinon logo serveur
        $game_ok = $game !== null;
        $game_logo_url = ($game_ok && !empty($game['logo'])) ? $game['logo'] : '';
        $logo_url = $game_logo_url ?: ($server['logo_url'] ?? '');
        $logo_alt = ($game_ok && !empty($game['name'])) ? $game['name'] : $server['name'];
    ?>
        <div class="game-server-card" id="server-<?php echo esc_attr($server['id']); ?>" data-server-id="<?php echo esc_attr($server['id']); ?>" data-expanded="false">
            <?php if ($has_banner) : ?>
                <div class="game-server-banner">
                    <img src="<?php echo esc_url($server['banner_url']); ?>" alt="<?php echo esc_attr($server['name']); ?>">
                    <div class="game-server-banner-overlay"></div>
                </div>
            <?php endif; ?>
            
            <!-- Mode réduit (compact) -->
            <div class="game-server-compact">
                <div class="game-server-compact-content">
                    <?php if (!empty($logo_url)) : ?>
                        <div class="game-server-logo-wrapper">
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="game-server-logo">
                        </div>
                    <?php endif; ?>
                    
                    <div class="game-server-compact-info">
                        <div class="game-server-compact-label"><?php _e('SERVER NAME', 'me5rine-lab'); ?></div>
                        <h3 class="game-server-name"><?php echo esc_html($server['name']); ?></h3>
                        <?php if ($game_ok && !empty($game['name'])) : ?>
                            <p class="game-server-game"><?php echo esc_html($game['name']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="game-server-compact-players">
                        <div class="game-server-compact-label"><?php _e('PLAYERS', 'me5rine-lab'); ?></div>
                        <span class="players-count" data-current="<?php echo esc_attr($server['current_players']); ?>" data-max="<?php echo esc_attr($server['max_players']); ?>">
                            <?php printf(__('%d / %d', 'me5rine-lab'), $server['current_players'], $server['max_players']); ?>
                        </span>
                    </div>
                    
                    <div class="game-server-compact-actions game-server-more-info-wrap">
                        <button class="game-server-toggle-expand" data-expand-text="<?php esc_attr_e('More Info', 'me5rine-lab'); ?>" data-collapse-text="<?php esc_attr_e('Less Info', 'me5rine-lab'); ?>">
                            <?php _e('More Info', 'me5rine-lab'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mode étendu (détaillé) -->
            <div class="game-server-expanded">
                <div class="game-server-expanded-content">
                    <div class="game-server-expanded-header">
                        <?php if (!empty($logo_url)) : ?>
                            <div class="game-server-logo-wrapper-expanded">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="game-server-logo-expanded">
                            </div>
                        <?php endif; ?>
                        
                        <div class="game-server-expanded-info">
                            <div class="game-server-expanded-label"><?php _e('SERVER NAME', 'me5rine-lab'); ?></div>
                            <h3 class="game-server-name-expanded"><?php echo esc_html($server['name']); ?></h3>
                            <?php if ($game_ok && !empty($game['name'])) : ?>
                                <p class="game-server-game-expanded"><?php echo esc_html($game['name']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="game-server-expanded-status">
                            <button class="game-server-toggle-expand-expanded" data-expand-text="<?php esc_attr_e('More Info', 'me5rine-lab'); ?>" data-collapse-text="<?php esc_attr_e('Less Info', 'me5rine-lab'); ?>">
                                <?php _e('Less Info', 'me5rine-lab'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="game-server-details-grid">
                        <div class="game-server-detail-item">
                            <div class="game-server-detail-label"><?php _e('IP', 'me5rine-lab'); ?></div>
                            <div class="game-server-detail-value game-server-ip-with-copy">
                                <code><?php echo esc_html($address); ?></code>
                                <button type="button" class="game-server-copy-ip-icon" data-ip="<?php echo esc_attr($address); ?>" title="<?php esc_attr_e('Copy address', 'me5rine-lab'); ?>" aria-label="<?php esc_attr_e('Copy address', 'me5rine-lab'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="game-server-detail-item">
                            <div class="game-server-detail-label"><?php _e('STATUS', 'me5rine-lab'); ?></div>
                            <div class="game-server-detail-value">
                                <div class="game-server-status-badge" data-status-field="status">
                                    <?php echo admin_lab_game_servers_get_status_badge($server['status']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="game-server-detail-item">
                            <div class="game-server-detail-label"><?php _e('PLAYERS', 'me5rine-lab'); ?></div>
                            <div class="game-server-detail-value">
                                <span class="players-count-expanded" data-current="<?php echo esc_attr($server['current_players']); ?>" data-max="<?php echo esc_attr($server['max_players']); ?>">
                                    <?php printf(__('%d / %d', 'me5rine-lab'), $server['current_players'], $server['max_players']); ?>
                                </span>
                                <div class="players-bar">
                                    <div class="players-bar-fill" style="width: <?php echo esc_attr($fill_percentage); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($server['version'])) : ?>
                            <div class="game-server-detail-item" data-version-field="version">
                                <div class="game-server-detail-label"><?php _e('VERSION', 'me5rine-lab'); ?></div>
                                <div class="game-server-detail-value">
                                    <span class="version-text"><?php echo esc_html($server['version']); ?></span>
                                </div>
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
                    
                    <?php if (!empty($server['description'])) : ?>
                        <div class="game-server-description">
                            <?php echo wp_kses_post($server['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="game-server-page-link-wrap">
                        <a href="<?php echo esc_url(admin_lab_game_servers_get_server_page_url($server['id'])); ?>" class="game-server-page-link">
                            <?php _e('Voir la fiche serveur', 'me5rine-lab'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
/* Statut : doc FRONT_CSS.md (admin_lab_render_status) — classes .me5rine-lab-status / .me5rine-lab-status-success|error */
.game-server-status-badge {
    display: inline-block;
}

/* Layout pleine largeur */
.game-servers-list {
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-md, 16px);
    margin: var(--me5rine-lab-spacing-lg, 24px) 0;
}

.game-server-card {
    position: relative;
    overflow: hidden;
    border-radius: var(--me5rine-lab-radius-md, 8px);
    background: var(--me5rine-lab-bg-secondary, #F9FAFB);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s ease;
}

.game-server-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.game-server-banner {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
}

.game-server-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.game-server-banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
}

/* Mode réduit (compact) */
.game-server-compact {
    position: relative;
    z-index: 1;
    padding: var(--me5rine-lab-spacing-md, 16px) var(--me5rine-lab-spacing-lg, 24px);
    display: flex;
    align-items: center;
    min-height: 100px;
}

.game-server-compact-content {
    display: flex;
    align-items: center;
    width: 100%;
    gap: var(--me5rine-lab-spacing-lg, 24px);
}

.game-server-logo-wrapper {
    flex-shrink: 0;
}

.game-server-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

.game-server-compact-info {
    flex: 1;
    min-width: 0;
}

.game-server-compact-label {
    font-size: 11px;
    font-weight: 500;
    margin-bottom: 4px;
    color: rgba(255,255,255,0.7);
}

.game-server-card:not([data-banner]) .game-server-compact-label {
    color: var(--me5rine-lab-text-light, #5D697D);
}

.game-server-name {
    margin: 0;
    font-size: 1.3em;
    font-weight: bold;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.game-server-card:not([data-banner]) .game-server-name {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-game {
    margin: 4px 0 0;
    font-size: 0.9em;
    color: rgba(255,255,255,0.8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.game-server-card:not([data-banner]) .game-server-game {
    color: var(--me5rine-lab-text-light, #5D697D);
}

.game-server-compact-players {
    flex-shrink: 0;
    text-align: right;
}

.players-count {
    font-size: 1.2em;
    font-weight: bold;
    color: #fff;
}

.game-server-card:not([data-banner]) .players-count {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-compact-actions {
    flex-shrink: 0;
    margin-left: auto;
    display: flex;
    align-items: center;
}

.game-server-toggle-expand {
    padding: 10px 20px;
    background: var(--me5rine-lab-button-primary-bg, #0485C8);
    color: #fff;
    border: none;
    border-radius: var(--me5rine-lab-radius-md, 8px);
    font-weight: 500;
    text-transform: uppercase;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.game-server-toggle-expand:hover {
    background: var(--me5rine-lab-button-primary-hover, #2E576F);
}

/* Mode étendu (détaillé) - caché par défaut */
.game-server-expanded {
    position: relative;
    z-index: 1;
    display: none;
    padding: var(--me5rine-lab-spacing-lg, 24px);
}

.game-server-card[data-expanded="true"] .game-server-compact {
    display: none;
}

.game-server-card[data-expanded="true"] .game-server-expanded {
    display: block;
}

.game-server-expanded-header {
    display: flex;
    align-items: flex-start;
    gap: var(--me5rine-lab-spacing-lg, 24px);
    margin-bottom: var(--me5rine-lab-spacing-md, 16px);
}

.game-server-logo-wrapper-expanded {
    flex-shrink: 0;
}

.game-server-logo-expanded {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.game-server-expanded-info {
    flex: 1;
}

.game-server-expanded-label {
    font-size: 11px;
    font-weight: 500;
    margin-bottom: 4px;
    color: rgba(255,255,255,0.7);
}

.game-server-card:not([data-banner]) .game-server-expanded-label {
    color: var(--me5rine-lab-text-light, #5D697D);
}

.game-server-name-expanded {
    margin: 0;
    font-size: 1.5em;
    font-weight: bold;
    color: #fff;
}

.game-server-card:not([data-banner]) .game-server-name-expanded {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-game-expanded {
    margin: 4px 0 0;
    font-size: 0.9em;
    color: rgba(255,255,255,0.8);
}

.game-server-card:not([data-banner]) .game-server-game-expanded {
    color: var(--me5rine-lab-text-light, #5D697D);
}

.game-server-expanded-status {
    flex-shrink: 0;
}

.game-server-toggle-expand-expanded {
    padding: 10px 20px;
    background: var(--me5rine-lab-button-primary-bg, #0485C8);
    color: #fff;
    border: none;
    border-radius: var(--me5rine-lab-radius-md, 8px);
    font-weight: 500;
    text-transform: uppercase;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.game-server-toggle-expand-expanded:hover {
    background: var(--me5rine-lab-button-primary-hover, #2E576F);
}

.game-server-description {
    margin-bottom: var(--me5rine-lab-spacing-md, 16px);
    color: rgba(255,255,255,0.9);
    line-height: 1.6;
}

.game-server-card:not([data-banner]) .game-server-description {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--me5rine-lab-spacing-md, 16px);
    margin-bottom: var(--me5rine-lab-spacing-md, 16px);
}

.game-server-detail-label {
    font-size: 11px;
    font-weight: 500;
    margin-bottom: 4px;
    color: rgba(255,255,255,0.7);
}

.game-server-card:not([data-banner]) .game-server-detail-label {
    color: var(--me5rine-lab-text-light, #5D697D);
}

.game-server-detail-value {
    display: flex;
    align-items: center;
    gap: var(--me5rine-lab-spacing-xs, 4px);
    flex-wrap: wrap;
    color: #fff;
}

.game-server-card:not([data-banner]) .game-server-detail-value {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-detail-value code {
    padding: 4px 8px;
    background: rgba(255,255,255,0.2);
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    font-family: monospace;
    color: #fff;
}

.game-server-card:not([data-banner]) .game-server-detail-value code {
    background: var(--me5rine-lab-bg-odd, #f6f7f7);
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-ip-with-copy {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.game-server-copy-ip-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    background: transparent;
    border: none;
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    cursor: pointer;
    color: inherit;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.game-server-copy-ip-icon:hover {
    opacity: 1;
}

.game-server-card[data-banner] .game-server-copy-ip-icon {
    color: #fff;
}

.game-server-card:not([data-banner]) .game-server-copy-ip-icon {
    color: var(--me5rine-lab-text, #11161E);
}

.game-server-page-link-wrap {
    margin: var(--me5rine-lab-spacing-md, 16px) 0 0;
}

.game-server-page-link {
    color: var(--me5rine-lab-button-primary-bg, #0485C8);
    text-decoration: none;
    font-weight: 500;
}

.game-server-card[data-banner] .game-server-page-link {
    color: #90caf9;
}

.game-server-page-link:hover {
    text-decoration: underline;
}

.players-count-expanded {
    font-size: 1.1em;
    font-weight: bold;
    color: #fff;
    display: block;
    margin-bottom: 4px;
}

.game-server-card:not([data-banner]) .players-count-expanded {
    color: var(--me5rine-lab-text, #11161E);
}

.players-bar {
    width: 100%;
    height: 6px;
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
    overflow: hidden;
}

.game-server-card:not([data-banner]) .players-bar {
    background: var(--me5rine-lab-border, #DEE5EC);
}

.players-bar-fill {
    height: 100%;
    background: var(--me5rine-lab-button-primary-bg, #0485C8);
    transition: width 0.3s ease;
}

.game-server-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--me5rine-lab-spacing-xs, 4px);
    margin-bottom: var(--me5rine-lab-spacing-md, 16px);
}

.game-server-tag {
    padding: 4px 10px;
    background: rgba(255,255,255,0.2);
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    font-size: 12px;
    color: #fff;
}

.game-server-card:not([data-banner]) .game-server-tag {
    background: var(--me5rine-lab-bg-odd, #f6f7f7);
    color: var(--me5rine-lab-text, #11161E);
}

/* Responsive */
@media (max-width: 768px) {
    .game-server-compact-content {
        flex-wrap: wrap;
        gap: var(--me5rine-lab-spacing-md, 16px);
    }
    
    .game-server-compact-players {
        text-align: left;
    }
    
    .game-server-details-grid {
        grid-template-columns: 1fr;
    }
    
    .game-server-expanded-header {
        flex-wrap: wrap;
    }
}
</style>

<script>
(function() {
    // Toggle expand/collapse : une seule logique, on met à jour les deux boutons de la carte
    document.querySelectorAll('.game-server-card').forEach(function(card) {
        var btnCompact = card.querySelector('.game-server-toggle-expand');
        var btnExpanded = card.querySelector('.game-server-toggle-expand-expanded');
        var expandText = (btnCompact && btnCompact.getAttribute('data-expand-text')) || 'More Info';
        var collapseText = (btnCompact && btnCompact.getAttribute('data-collapse-text')) || 'Less Info';

        function updateButtonsText() {
            var isExpanded = card.getAttribute('data-expanded') === 'true';
            var label = isExpanded ? collapseText : expandText;
            if (btnCompact) btnCompact.textContent = label;
            if (btnExpanded) btnExpanded.textContent = label;
        }

        function toggleCard() {
            var isExpanded = card.getAttribute('data-expanded') === 'true';
            card.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
            updateButtonsText();
        }

        if (btnCompact) btnCompact.addEventListener('click', function(e) { e.preventDefault(); toggleCard(); });
        if (btnExpanded) btnExpanded.addEventListener('click', function(e) { e.preventDefault(); toggleCard(); });

        updateButtonsText();
    });
    
    // Copier l'adresse IP (icône)
    document.querySelectorAll('.game-server-copy-ip-icon').forEach(function(button) {
        button.addEventListener('click', function() {
            var ip = this.getAttribute('data-ip');
            var btn = this;
            navigator.clipboard.writeText(ip).then(function() {
                var title = btn.getAttribute('title');
                btn.setAttribute('title', '<?php echo esc_js(__('Copié!', 'me5rine-lab')); ?>');
                setTimeout(function() {
                    btn.setAttribute('title', title);
                }, 2000);
            });
        });
    });
    
    // Définir l'attribut data-banner pour les cartes avec bannière
    document.querySelectorAll('.game-server-card').forEach(function(card) {
        var banner = card.querySelector('.game-server-banner');
        if (banner) {
            card.setAttribute('data-banner', 'true');
        }
    });
    
    // Rafraîchissement automatique des stats
    var refreshInterval = 30000; // 30 secondes
    var apiUrl = '<?php echo esc_url(rest_url('me5rine-lab/v1/game-servers/stats')); ?>';
    var serverCards = document.querySelectorAll('.game-server-card[data-server-id]');
    
    if (serverCards.length > 0) {
        // Récupérer les IDs des serveurs
        var serverIds = Array.from(serverCards).map(function(card) {
            return card.getAttribute('data-server-id');
        });
        
        function updateServerStats() {
            var url = apiUrl + '?ids=' + serverIds.join(',');
            
            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success && data.servers) {
                        data.servers.forEach(function(server) {
                            var card = document.querySelector('.game-server-card[data-server-id="' + server.id + '"]');
                            if (!card) return;
                            
                            // Mettre à jour les joueurs (mode compact)
                            var playersCount = card.querySelector('.players-count');
                            if (playersCount) {
                                playersCount.setAttribute('data-current', server.current_players);
                                playersCount.setAttribute('data-max', server.max_players);
                                playersCount.textContent = server.current_players + ' / ' + server.max_players;
                            }
                            
                            // Mettre à jour les joueurs (mode étendu)
                            var playersCountExpanded = card.querySelector('.players-count-expanded');
                            if (playersCountExpanded) {
                                playersCountExpanded.setAttribute('data-current', server.current_players);
                                playersCountExpanded.setAttribute('data-max', server.max_players);
                                playersCountExpanded.textContent = server.current_players + ' / ' + server.max_players;
                                
                                // Mettre à jour la barre de progression
                                var maxPlayers = server.max_players || 1;
                                var fillPercentage = Math.min(100, (server.current_players / maxPlayers) * 100);
                                var fillBar = card.querySelector('.players-bar-fill');
                                if (fillBar) {
                                    fillBar.style.width = fillPercentage + '%';
                                }
                            }
                            
                            // Mettre à jour le statut (doc FRONT_CSS.md : admin_lab_render_status)
                            var statusBadges = card.querySelectorAll('.game-server-status-badge');
                            statusBadges.forEach(function(statusBadge) {
                                var isOnline = server.online;
                                var statusType = isOnline ? 'success' : 'error';
                                var statusText = isOnline ? '<?php echo esc_js(__('Online', 'me5rine-lab')); ?>' : '<?php echo esc_js(__('Offline', 'me5rine-lab')); ?>';
                                statusBadge.innerHTML = '<span class="me5rine-lab-status me5rine-lab-status-' + statusType + '">' + statusText + '</span>';
                            });
                            
                            // Mettre à jour la version
                            var versionDiv = card.querySelector('[data-version-field="version"]');
                            var versionText = card.querySelector('.version-text');
                            if (versionDiv && versionText) {
                                if (server.version) {
                                    versionText.textContent = server.version;
                                    versionDiv.style.display = '';
                                } else {
                                    versionDiv.style.display = 'none';
                                }
                            }
                        });
                    }
                })
                .catch(function(error) {
                    console.error('[Game Servers] Error updating stats:', error);
                });
        }
        
        // Rafraîchir immédiatement puis toutes les 30 secondes
        updateServerStats();
        setInterval(updateServerStats, refreshInterval);
    }
})();
</script>
