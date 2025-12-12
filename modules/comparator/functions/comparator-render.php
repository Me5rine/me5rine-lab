<?php
// File: modules/comparator/functions/comparator-render.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendu "classic"
 * @param array|WP_Error $game_data réponse API /games/{id}?populate=*
 */
function admin_lab_comparator_render_classic($game_data) {
    if (is_wp_error($game_data)) {
        if (current_user_can('manage_options')) {
            return '<p class="admin-lab-comparator-error">' . esc_html($game_data->get_error_message()) . '</p>';
        }
        return '';
    }

    // Normalisation Strapi : ['data' => ['id' => ..., 'attributes' => [...]]]
    $game  = isset($game_data['data']) ? $game_data['data'] : $game_data;
    $attrs = isset($game['attributes']) ? $game['attributes'] : [];

    // ID du jeu
    $game_id = isset($game['id']) ? (int) $game['id'] : 0;

    // Champs principaux
    $name         = isset($attrs['name']) ? $attrs['name'] : '';
    $poster_url   = isset($attrs['poster']['data']['attributes']['url'])
        ? $attrs['poster']['data']['attributes']['url']
        : '';
    $release_date = isset($attrs['release_date']) ? $attrs['release_date'] : '';
    $formatted_release_date = '';
    if (!empty($release_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
        $timestamp = strtotime($release_date);

        // Format localisé (si locale FR chargée sous WordPress)
        $formatted_release_date = date_i18n('j F Y', $timestamp);
    }
    $description  = isset($attrs['description']) ? $attrs['description'] : '';

    // Studios (many-to-many "studios")
    $studios = !empty($attrs['studios'])
        ? admin_lab_comparator_extract_relation($attrs['studios'])
        : ['names' => '', 'count' => 0, 'list' => []];

    // Editors (many-to-many "editors")
    $editors = !empty($attrs['editors'])
        ? admin_lab_comparator_extract_relation($attrs['editors'])
        : ['names' => '', 'count' => 0, 'list' => []];

    // Plateformes
    $platforms = !empty($attrs['platforms'])
        ? admin_lab_comparator_extract_relation($attrs['platforms'])
        : ['names' => '', 'count' => 0, 'list' => []];

    // Types / genres
    $genres = !empty($attrs['game_genres'])
        ? admin_lab_comparator_extract_relation($attrs['game_genres'])
        : ['names' => '', 'count' => 0, 'list' => []];

    // Si le nom est vide, là oui on ne peut vraiment rien faire
    if ( empty( $name ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<p class="admin-lab-comparator-error">Comparator: missing game name.</p>';
        }
        return '';
    }

    // Placeholder si pas de poster
    $placeholder_url = ME5RINE_LAB_URL . 'assets/img/placeholder-game.jpg';

    if ( empty( $poster_url ) ) {
        $poster_url = $placeholder_url;
    }

    // Offres de prix (nouveau modèle uniquement)
    $offers = admin_lab_comparator_extract_offers($attrs, 3);
    $best_offer = !empty($offers) ? $offers[0] : null;

    // URL "See all prices"
    $all_prices_url = '';
    if (!empty($best_offer)) {
        $all_prices_url = admin_lab_comparator_get_all_prices_url($game, $attrs, $offers, $best_offer);
    }

    // Label des boutons "Buy" / "Preorder" en fonction de la date de sortie
    $now_date    = date('Y-m-d');
    $is_preorder = (!empty($release_date) && $release_date > $now_date);

    $line_buy_text = $is_preorder
        ? __( 'Preorder', 'me5rine-lab' )
        : __( 'Buy', 'me5rine-lab' );

    ob_start();
    ?>

    <div class="admin-lab-comparator admin-lab-comparator--classic">

        <h2 class="admin-lab-comparator__title">
            <?php echo esc_html($name); ?>
        </h2>

        <div class="admin-lab-comparator-classic-header"></div>
        <?php if ($genres['count'] > 0 && !empty($genres['list'])) : ?>
            <p class="admin-lab-comparator-header-line">
                <span class="admin-lab-comparator-header-meta-label">
                    <?php echo esc_html( _n( 'Type', 'Types', $genres['count'], 'me5rine-lab' ) ); ?> :
                </span>
                <span class="admin-lab-comparator-header-meta-value">
                    <?php foreach ($genres['list'] as $genre_name) : ?>
                        <span class="admin-lab-comparator-header-chip admin-lab-comparator-header-chip__genres">
                            <?php echo esc_html($genre_name); ?>
                        </span>
                    <?php endforeach; ?>
                </span>
            </p>
        <?php endif; ?>

        <?php if ($platforms['count'] > 0 && !empty($platforms['list'])) : ?>
            <p class="admin-lab-comparator-header-line">
                <span class="admin-lab-comparator-header-meta-label">
                    <?php echo esc_html( _n( 'Platform', 'Platforms', $platforms['count'], 'me5rine-lab' ) ); ?> :
                </span>
                <span class="admin-lab-comparator-header-meta-value">
                    <?php foreach ($platforms['list'] as $platform_name) : ?>
                        <span class="admin-lab-comparator-header-chip admin-lab-comparator-header-chip__platforms">
                            <?php echo esc_html($platform_name); ?>
                        </span>
                    <?php endforeach; ?>
                </span>
            </p>
        <?php endif; ?>

        <!-- Top: poster + meta -->
        <div class="admin-lab-comparator-classic-main-container">
            <div class="admin-lab-comparator-classic__poster">
                <img
                    class="admin-lab-comparator-classic__poster-img"
                    src="<?php echo esc_url($poster_url); ?>"
                    alt="<?php echo esc_attr($name); ?>"
                />

                <?php if (!empty($attrs['logo']['data']['attributes']['url'])): ?>
                    <div class="admin-lab-comparator-classic__poster-logo">
                        <img src="<?php echo esc_url($attrs['logo']['data']['attributes']['url']); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-lab-comparator-classic__meta">
                <?php if ($editors['count'] > 0) : ?>
                    <p class="admin-lab-comparator-meta-line">
                        <span class="admin-lab-comparator-meta-label">
                            <?php echo esc_html( _n( 'Editor', 'Editors', $editors['count'], 'me5rine-lab' ) ); ?> :
                        </span>
                        <span class="admin-lab-comparator-meta-value">
                            <?php echo esc_html( $editors['names'] ); ?>
                        </span>
                    </p>
                <?php endif; ?>

                <?php if ($studios['count'] > 0) : ?>
                    <p class="admin-lab-comparator-meta-line">
                        <span class="admin-lab-comparator-meta-label">
                            <?php echo esc_html( _n( 'Developer', 'Developers', $studios['count'], 'me5rine-lab' ) ); ?> :
                        </span>
                        <span class="admin-lab-comparator-meta-value">
                            <?php echo esc_html( $studios['names'] ); ?>
                        </span>
                    </p>
                <?php endif; ?>

                <?php if (!empty($release_date)) : ?>
                    <p class="admin-lab-comparator-meta-line admin-lab-comparator-meta-line--release">
                        <span class="admin-lab-comparator-meta-label">
                            <?php echo esc_html( __( 'Release date', 'me5rine-lab' ) ); ?> :
                        </span>
                        <span class="admin-lab-comparator-meta-value">
                            <?php echo esc_html( $formatted_release_date ); ?>
                        </span>
                    </p>
                <?php endif; ?>

            </div>
        </div>

        <!-- Description -->
        <?php if (!empty($description)) : ?>
            <div class="admin-lab-comparator__description">
                <?php echo wp_kses_post( wpautop( $description ) ); ?>
            </div>
        <?php endif; ?>

        <!-- Prix -->
        <?php if (!empty($offers)) : ?>
            <div class="admin-lab-comparator-classic__offers">

                <h3 class="admin-lab-comparator__section-title">
                    <?php echo esc_html( __( 'Best prices', 'me5rine-lab' ) ); ?>
                </h3>

                <?php foreach ($offers as $offer) : ?>
                    <?php
                    $store      = $offer['store'] ?: __( 'Store', 'me5rine-lab' );
                    $platform   = $offer['platform'] ?: '';
                    $price_val  = number_format($offer['price'], 2, ',', ' ');
                    $store_slug = $store ? sanitize_title($store) : 'store';

                    $tracked_url = admin_lab_comparator_get_tracked_url($game_id, [
                        'target_url' => $offer['url'],
                        'store'      => $store,
                        'platform'   => $platform,
                        'click_type' => 'offer',
                        'context'    => 'classic',
                        'post_id'    => get_the_ID(),
                    ]);
                    ?>
                    <div class="admin-lab-comparator-offer-line">

                        <div class="admin-lab-comparator-offer-line__left">
                            <span class="admin-lab-comparator-store-logo admin-lab-comparator-store-logo--<?php echo esc_attr($store_slug); ?>">
                                <!-- Logo via CSS -->
                            </span>
                            <span class="admin-lab-comparator-offer-line__text">
                                <?php
                                $label = trim(
                                    $price_val . ' €' .
                                    ($platform ? ' – ' . $platform : '') .
                                    ($store ? ' (' . $store . ')' : '')
                                );

                                echo esc_html($label);
                                ?>
                            </span>
                        </div>

                        <div class="admin-lab-comparator-offer-line__right">
                            <a href="<?php echo esc_url($tracked_url); ?>"
                               target="_blank"
                               class="admin-lab-comparator-offer-line__buy-btn">
                                <?php echo esc_html( $line_buy_text ); ?>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>

                <?php if (!empty($all_prices_url)) : ?>
                    <?php
                    $tracked_all = admin_lab_comparator_get_tracked_url($game_id, [
                        'target_url' => $all_prices_url,
                        'store'      => __( 'All prices button', 'me5rine-lab' ),
                        'platform'   => '',
                        'click_type' => 'all_prices',
                        'context'    => 'classic',
                        'post_id'    => get_the_ID(),
                    ]);
                    ?>
                    <div class="admin-lab-comparator-classic__all-prices">
                        <a href="<?php echo esc_url($tracked_all); ?>"
                           target="_blank"
                           class="admin-lab-comparator-all-prices-btn">
                            <?php echo esc_html( __( 'See all prices', 'me5rine-lab' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
}

/**
 * Rendu "banner"
 * @param array|WP_Error $game_data réponse API /games/{id}?populate=*
 */
function admin_lab_comparator_render_banner($game_data) {
    if (is_wp_error($game_data)) {
        if (current_user_can('manage_options')) {
            return '<p class="admin-lab-comparator-error">' . esc_html($game_data->get_error_message()) . '</p>';
        }
        return '';
    }

    // Normalisation Strapi
    $game  = isset($game_data['data']) ? $game_data['data'] : $game_data;
    $attrs = isset($game['attributes']) ? $game['attributes'] : [];

    $game_id      = isset($game['id']) ? (int) $game['id'] : 0;
    $name         = isset($attrs['name']) ? $attrs['name'] : '';
    $release_date = isset($attrs['release_date']) ? $attrs['release_date'] : '';

    $banner_url = isset($attrs['banner']['data']['attributes']['url'])
        ? $attrs['banner']['data']['attributes']['url']
        : '';
    $logo_url = isset($attrs['logo']['data']['attributes']['url'])
        ? $attrs['logo']['data']['attributes']['url']
        : '';

    // Si pas de nom → là oui, on ne peut vraiment rien afficher
    if ( empty( $name ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<p class="admin-lab-comparator-error">Comparator banner: missing game name.</p>';
        }
        return '';
    }

    // Placeholders si pas de bannière ou pas de logo
    $banner_placeholder = ME5RINE_LAB_URL . 'assets/img/placeholder-banner.jpg';
    $logo_placeholder   = ME5RINE_LAB_URL . 'assets/img/placeholder-game-logo.png';

    if ( empty( $banner_url ) ) {
        $banner_url = $banner_placeholder;
    }
    if ( empty( $logo_url ) ) {
        $logo_url = $logo_placeholder;
    }

    // Offres de prix (nouveau modèle uniquement)
    $offers = admin_lab_comparator_extract_offers($attrs, 3);

    $best_offer       = null;
    $price            = null;
    $original_price   = null;
    $discount         = null;
    $tracked_page_url = null;

    if (!empty($offers)) {
        $best_offer = $offers[0];

        $price          = isset($best_offer['price']) ? (float) $best_offer['price'] : null;
        $original_price = isset($best_offer['original_price']) && $best_offer['original_price'] > 0
            ? (float) $best_offer['original_price']
            : null;
        $discount       = isset($best_offer['discount_percentage'])
            ? (int) $best_offer['discount_percentage']
            : null;

        $raw_url = !empty($best_offer['url']) ? $best_offer['url'] : '';

        if ($price !== null && !empty($raw_url)) {
            $tracked_page_url = admin_lab_comparator_get_tracked_url($game_id, [
                'target_url' => $raw_url,
                'store'      => !empty($best_offer['store'])    ? $best_offer['store']    : '',
                'platform'   => !empty($best_offer['platform']) ? $best_offer['platform'] : '',
                'click_type' => 'banner',
                'context'    => 'banner',
                'post_id'    => get_the_ID(),
            ]);
        } else {
            $tracked_page_url = $raw_url;
        }
    }

    // Calcul auto de la réduction si pas fournie mais original_price dispo
    if ($discount === null && $original_price !== null && $price !== null && $original_price > $price) {
        $discount = (int) round(100 - ($price / $original_price * 100));
    }

    // Formatage d'affichage
    $display_price = $price !== null ? number_format($price, 2, ',', ' ') : null;

    // Texte du CTA : Preorder si le jeu n'est pas encore sorti, sinon Buy
    $cta_label = __('Buy', 'me5rine-lab');
    if (!empty($release_date) && $release_date > date('Y-m-d')) {
        $cta_label = __('Preorder', 'me5rine-lab');
    }

    ob_start();
    ?>

    <a href="<?php echo esc_url($tracked_page_url); ?>"
       target="_blank"
       rel="noopener noreferrer"
       class="admin-lab-comparator-banner"
       style="background-image:url('<?php echo esc_url($banner_url); ?>');">
        <div class="admin-lab-comparator-banner__inner">
            <img
                class="admin-lab-comparator-banner__logo"
                src="<?php echo esc_url($logo_url); ?>"
                alt="<?php echo esc_attr($name); ?>"
            />

            <div class="admin-lab-comparator-banner__columns">

                <div class="admin-lab-comparator-banner__left">
                    <?php if ($price !== null) : ?>
                        <div class="admin-lab-comparator-banner-price">
                            <div class="admin-lab-comparator-banner-price__current">
                                <?php echo esc_html($display_price); ?> €
                            </div>
                            <?php if ($discount !== null) : ?>
                                <div class="admin-lab-comparator-banner-price__badge">
                                    -<?php echo esc_html($discount); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="admin-lab-comparator-banner__right">
                    <?php if (!empty($tracked_page_url)) : ?>
                        <button type="button" class="admin-lab-comparator-banner__cta-btn">
                            <span><?php echo esc_html($cta_label); ?></span>
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </a>

    <?php
    return ob_get_clean();
}
