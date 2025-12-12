<?php
// File: modules/user-management/functions/user-management-um-custom-fields.php

if (!defined('ABSPATH')) exit;

add_filter( 'um_predefined_fields_hook', function( $predefined_fields ) {

	$custom_fields = [
		'pinterest' => [
			'title'      => __( 'Pinterest', 'ultimate-member' ),
			'icon'       => 'fab fa-pinterest',
			'color'      => '#e60023',
			'validate'   => 'url',
			'url_text'   => 'Pinterest',
			'match'      => '',
		],
		'youtube_2' => [
			'title'      => __( 'YouTube', 'ultimate-member' ),
			'icon'       => 'fab fa-youtube',
			'color'      => '#e52d27',
			'validate'   => 'url',
			'url_text'   => 'YouTube',
			'match'      => 'https://www.youtube.com/',
		],
		'youtube_3' => [
			'title'      => __( 'YouTube', 'ultimate-member' ),
			'icon'       => 'fab fa-youtube',
			'color'      => '#e52d27',
			'validate'   => 'url',
			'url_text'   => 'YouTube',
			'match'      => 'https://www.youtube.com/',
		],
		'youtube_4' => [
			'title'      => __( 'YouTube', 'ultimate-member' ),
			'icon'       => 'fab fa-youtube',
			'color'      => '#e52d27',
			'validate'   => 'url',
			'url_text'   => 'YouTube',
			'match'      => 'https://www.youtube.com/',
		],
		'discord_custom' => [
			'title'    => __( 'Discord', 'ultimate-member' ),
			'icon'     => 'fab fa-discord',
			'color'    => '#7289da',
			'validate' => 'discord_url',
			'url_text' => 'Discord',
			'match'    => '',
		],
		'threads' => [
			'title'      => __( 'Threads', 'ultimate-member' ),
			'icon'       => 'fab fa-threads',
			'color'      => '#000000',
			'validate'   => 'url',
			'url_text'   => 'Threads',
			'match'      => '',
		],
		'bluesky' => [
			'title'      => __( 'Bluesky', 'ultimate-member' ),
			'icon'       => 'fab fa-bluesky',
			'color'      => '#0085ff',
			'validate'   => 'url',
			'url_text'   => 'Bluesky',
			'match'      => '',
		],
		'paypal' => [
			'title'      => __( 'PayPal', 'ultimate-member' ),
			'icon'       => 'fab fa-paypal',
			'color'      => '#27346A',
			'validate'   => 'url',
			'url_text'   => 'PayPal',
			'match'      => '',
		],
		'amazon' => [
			'title'      => __( 'Amazon', 'ultimate-member' ),
			'icon'       => 'fab fa-amazon',
			'color'      => '#ff9900',
			'validate'   => 'url',
			'url_text'   => 'Amazon',
			'match'      => '',
		],
		'shop' => [
			'title'      => __( 'Shop', 'ultimate-member' ),
			'icon'       => 'fas fa-cart-shopping',
			'color'      => '#2e8919',
			'validate'   => 'url',
			'url_text'   => 'Bluesky',
			'match'      => '',
		],
		'donate' => [
			'title'      => __( 'Donate', 'ultimate-member' ),
			'icon'       => 'fas fa-coins',
			'color'      => '#FFD700',
			'validate'   => 'url',
			'url_text'   => 'Stream Elements',
			'match'      => '',
		],
		'instant-gaming' => [
			'title'      => __( 'Instant Gaming', 'ultimate-member' ),
			'icon'       => 'fas fa-gamepad',
			'color'      => '#ff5400',
			'validate'   => 'url',
			'url_text'   => 'Instant Gaming',
			'match'      => 'https://www.instant-gaming.com/',
		],
		'tipeee' => [
			'title'      => __( 'Tipeee', 'ultimate-member' ),
			'icon'       => 'fas fa-campground',
			'color'      => '#f44336',
			'validate'   => 'url',
			'url_text'   => 'Tipeee',
			'match'      => 'https://fr.tipeee.com/',
		],
		'youtube_join' => [
			'title'      => __( 'YouTube', 'ultimate-member' ),
			'icon'       => 'fab fa-youtube',
			'color'      => '#e52d27',
			'validate'   => 'url',
			'url_text'   => 'Join YouTube',
			'match'      => 'https://www.youtube.com/',
		],
		'youtube_join_2' => [
			'title'      => __( 'YouTube', 'ultimate-member' ),
			'icon'       => 'fab fa-youtube',
			'color'      => '#e52d27',
			'validate'   => 'url',
			'url_text'   => 'Join YouTube',
			'match'      => 'https://www.youtube.com/',
		],
		'prime' => [
			'title'      => __( 'Prime', 'ultimate-member' ),
			'icon'       => 'fab fa-twitch',
			'color'      => '#6441a5',
			'validate'   => 'url',
			'url_text'   => 'Subscribe',
			'match'      => 'https://www.twitch.tv/',
		],
		'wishlist' => [
			'title'      => __( 'Wishlist', 'ultimate-member' ),
			'icon'       => 'fas fa-gift',
			'color'      => '#247536',
			'validate'   => 'url',
			'url_text'   => 'Wishlist',
			'match'      => '',
		],
		'game-wishlist' => [
			'title'      => __( 'Wishlist Jeux', 'ultimate-member' ),
			'icon'       => 'fas fa-gamepad',
			'color'      => '#109983',
			'validate'   => 'url',
			'url_text'   => 'Wishlist Jeux',
			'match'      => '',
		],
		'website-url' => [
			'title'      => __( 'Website', 'ultimate-member' ),
			'icon'       => 'fas fa-link',
			'color'      => '#707070',
			'validate'   => 'url',
			'url_text'   => 'Website',
			'match'      => '',
		],
	];

	foreach ( $custom_fields as $key => $custom ) {
		$predefined_fields[$key] = array_merge([
			'public'     => 1,
			'editable'   => 1,
			'required'   => 0,
			'type'       => 'url',
			'label'      => $custom['title'],
			'url_target' => '_blank',
			'url_rel'    => 'nofollow',
			'advanced'   => 'social',
			'metakey'    => $key,
		], $custom);
	}

	return $predefined_fields;
});

add_filter( 'um_admin_field_validation_hook', function( $array ) {
	$array['pinterest_url'] = __('Pinterest','ultimate-member');
	$array['discord_url'] 	= __('Discord URL', 'ultimate-member');
	$array['threads_url'] 	= __('Threads','ultimate-member');
	$array['bluesky_url']   = __('Bluesky','ultimate-member');
	return $array;
});

function um_custom_validate_discord_url( $key, $array, $args ) {
	if ( isset( $args[$key] ) && !empty( $args[$key] ) ) {
		$url = trim( $args[$key] );
		if ( ! preg_match( '#^https:\/\/(discord\.gg|discord\.com\/invite)\/[a-zA-Z0-9]+$#', $url ) ) {
			UM()->form()->add_error( $key, __( 'Please enter a valid Discord invite URL.', 'ultimate-member' ) );
		}
	}
}
add_action( 'um_custom_field_validation_discord_url', 'um_custom_validate_discord_url', 30, 3 );


