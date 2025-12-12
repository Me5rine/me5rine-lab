<?php
// File: modules/remote-news/functions/remote-news-front.php
if (!defined('ABSPATH')) exit;

// Afficher l'image distante pour remote_news si pas de thumbnail local
add_filter('post_thumbnail_html', function($html, $post_id, $post_thumbnail_id, $size, $attr) {
    // On ne touche qu'au CPT remote_news
    if (get_post_type($post_id) !== 'remote_news') {
        return $html;
    }

    // Si on a déjà un thumbnail local, on laisse tel quel
    if (!empty($html)) {
        return $html;
    }

    // Sinon on regarde la meta _remote_thumbnail_url
    $url = get_post_meta($post_id, '_remote_thumbnail_url', true);
    if (!$url) {
        return $html;
    }

    // Alt = titre du post
    $alt = get_the_title($post_id);

    // Tu peux ajuster la classe CSS si besoin
    $attrs = '';
    if (is_array($attr)) {
        $class = isset($attr['class']) ? $attr['class'] : 'attachment-' . esc_attr(is_string($size) ? $size : 'thumbnail');
        $attrs = ' class="' . esc_attr($class) . '"';
    }

    return sprintf(
        '<img src="%s" alt="%s"%s />',
        esc_url($url),
        esc_attr($alt),
        $attrs
    );
}, 10, 5);

// Forcer le lien des posts remote_news vers l'URL distante (_remote_url)
add_filter('post_type_link', function($post_link, $post, $leavename, $sample) {
    if ($post->post_type !== 'remote_news') {
        return $post_link;
    }

    // Si on est en admin (liste des posts, édition...), on garde l'URL locale
    if (is_admin()) {
        return $post_link;
    }

    $remote = get_post_meta($post->ID, '_remote_url', true);
    if (!empty($remote)) {
        return esc_url($remote);
    }

    return $post_link;
}, 10, 4);

