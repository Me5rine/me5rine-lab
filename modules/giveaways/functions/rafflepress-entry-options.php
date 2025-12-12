<?php
// File: modules/giveaways/functions/rafflepress-entry-options.php

if (!defined('ABSPATH')) exit;

function get_socials_order(): array {
    return [
        'facebook', 'twitter', 'bluesky', 'instagram', 'threads', 'youtube', 'youtube_2', 'youtube_3', 'youtube_4',
        'tiktok', 'pinterest', 'linkedin', 'twitch', 'discord_custom', 'visit-a-page'
    ];
}

function get_socials_for_giveaway(int $user_id): array {
    $base_socials = [
        'facebook'       => ['type' => 'facebook-like-share', 'field' => 'fb_url', 'label' => __('Facebook', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'twitter'        => ['type' => 'twitter-follow', 'field' => 'twitter_username', 'label' => __('X', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'bluesky'        => ['type' => 'bluesky-follow', 'field' => 'bluesky_url', 'label' => __('Bluesky', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'instagram'      => ['type' => 'instagram-follow', 'field' => 'instagram_url', 'label' => __('Instagram', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'threads'        => ['type' => 'threads-follow', 'field' => 'threads_url', 'label' => __('Threads', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'youtube'        => ['type' => 'youtube-follow', 'field' => 'youtube_url', 'label' => __('YouTube', 'me5rine-lab'), 'text' => __('Subscribe', 'me5rine-lab')],
        'tiktok'         => ['type' => 'tiktok-follow', 'field' => 'tiktok_url', 'label' => __('TikTok', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'pinterest'      => ['type' => 'pinterest-follow', 'field' => 'pinterest_username', 'label' => __('Pinterest', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'linkedin'       => ['type' => 'linkedin-follow', 'field' => 'linkedin_username', 'label' => __('LinkedIn', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'twitch'         => ['type' => 'twitch-follow', 'field' => 'twitch_username', 'label' => __('Twitch', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'discord_custom' => ['type' => 'discord-follow', 'field' => 'discord_url', 'label' => __('Discord', 'me5rine-lab'), 'text' => __('Join', 'me5rine-lab')],
    ];

    if (in_array('um_partenaire_plus', (array) get_userdata($user_id)->roles)) {
        for ($i = 2; $i <= 4; $i++) {
            $base_socials["youtube_$i"] = [
                'type' => 'youtube-follow',
                'field' => 'youtube_url',
                'label' => __("YouTube $i", 'me5rine-lab'),
                'text'  => __('Subscribe', 'me5rine-lab')
            ];
        }
    }

    $socials = [];

    foreach ($base_socials as $key => $data) {
        $url = get_user_meta($user_id, $key, true);
        if (empty($url)) continue;

        $custom_label = $data['label'];
        if (strpos($key, 'youtube') === 0) {
            $custom_label = admin_lab_get_youtube_channel_label_cached($url, $user_id, $key);
        }

        $socials[$key] = [
            'url'          => $url,
            'type'         => $data['type'],
            'field'        => $data['field'],
            'label'        => $data['label'],
            'custom_label' => $custom_label,
            'text'         => $data['text'],
        ];
    }

    $ordered = get_socials_order();
    $sorted = [];
    foreach ($ordered as $key) {
        if (isset($socials[$key])) $sorted[$key] = $socials[$key];
    }

    return $sorted;
}

function generate_entry_options_from_actions(int $user_id, array $actions): array {
    $entry_options = [];
    $socials = get_socials_for_giveaway($user_id);

    foreach ($socials as $key => $info) {
        if ($key === 'discord_custom') continue;
        
        if ($key === 'threads') continue;

        if ($key === 'bluesky') continue;

        if (!isset($actions[$key]) || empty($actions[$key]['enabled']) || empty($actions[$key]['points'])) continue;

        $value = $info['url'];
        $field_key = ($info['type'] === 'youtube-follow') ? 'youtube_url' : $info['field'];

        if ($key === 'twitter') {
            $value = str_replace(['https://twitter.com/', 'https://x.com/', '@'], '', $value);
        }

        $entry_options[] = [
            'social'      => $key,
            'id'          => ($info['type'] === 'youtube-follow') ? $key : wp_generate_password(6, false),
            'type'        => $info['type'],
            'name'        => ($info['type'] === 'youtube-follow')
                ? sprintf(__('Subscribe to %s on YouTube', 'me5rine-lab'), $info['custom_label'])
                : sprintf(__('%s %s on %s', 'me5rine-lab'), $info['text'], wp_get_current_user()->display_name, $info['label']),
            'value'       => (string) max(1, min(5, (int) $actions[$key]['points'])),
            $field_key    => ($info['type'] === 'twitter-follow') ? sanitize_text_field($value) : esc_url_raw($value),
            'action_text' => $info['text'],
        ];
    }

    if (!empty($actions['threads']['enabled']) && $actions['threads']['enabled'] === '1') {
        $url = get_user_meta($user_id, 'threads', true);
        if (!empty($url)) {
            $entry_options[] = [
                'social'      => 'threads',
                'id'          => 'threads',
                'type'        => 'visit-a-page',
                'name'        => sprintf(__('Follow %s on Threads', 'me5rine-lab'), wp_get_current_user()->display_name),
                'value'       => (string) max(1, min(5, (int) $actions['threads']['points'] ?? 1)),
                'url'         => esc_url_raw($url),
                'action_text' => __('Follow', 'me5rine-lab'),
            ];
        }
    }

    if (!empty($actions['bluesky']['enabled']) && $actions['bluesky']['enabled'] === '1') {
        $url = get_user_meta($user_id, 'bluesky', true);
        if (!empty($url)) {
            $entry_options[] = [
                'social'      => 'bluesky',
                'id'          => 'bluesky',
                'type'        => 'visit-a-page',
                'name'        => sprintf(__('Follow %s on Bluesky', 'me5rine-lab'), wp_get_current_user()->display_name),
                'value'       => (string) max(1, min(5, (int) $actions['bluesky']['points'] ?? 1)),
                'url'         => esc_url_raw($url),
                'action_text' => __('Follow', 'me5rine-lab'),
            ];
        }
    }

    if (!empty($actions['discord_custom']['enabled']) && $actions['discord_custom']['enabled'] === '1') {
        $url = get_user_meta($user_id, 'discord_custom', true);
        if (!empty($url)) {
            $entry_options[] = [
                'social'      => 'discord_custom',
                'id'          => 'discord',
                'type'        => 'visit-a-page',
                'name'        => sprintf(__('Join %s on Discord', 'me5rine-lab'), wp_get_current_user()->display_name),
                'value'       => (string) max(1, min(5, (int) $actions['discord_custom']['points'] ?? 1)),
                'url'         => esc_url_raw($url),
                'action_text' => __('Join', 'me5rine-lab'),
            ];
        }
    }

    if (!empty($actions['visit-a-page']['enabled']) && $actions['visit-a-page']['enabled'] === '1') {
        $url = esc_url_raw($actions['visit-a-page']['url'] ?? '');
        $name = sanitize_text_field($actions['visit-a-page']['name'] ?? __('Visit a page', 'me5rine-lab'));
        if (!empty($url) && strpos($url, 'discord.gg') === false) {
            $entry_options[] = [
                'social'      => 'visit-a-page',
                'id'          => wp_generate_password(6, false),
                'type'        => 'visit-a-page',
                'name'        => $name,
                'value'       => (string) max(1, min(5, (int) $actions['visit-a-page']['points'] ?? 1)),
                'url'         => $url,
                'action_text' => __('Visit', 'me5rine-lab'),
            ];
        }
    }

    $ordered = get_socials_order();
    $ordered_entry_options = [];

    foreach ($ordered as $key) {
        foreach ($entry_options as $entry) {
            if (isset($entry['social']) && $entry['social'] === $key) {
                $ordered_entry_options[] = $entry;
            }
        }
    }

    return $ordered_entry_options;
}

function get_socials_for_admin_lab(): array {
    $user_id = (int) admin_lab_get_global_option('admin_lab_account_id');
    if (!$user_id) return [];

    $base_socials = [
        'facebook'       => ['type' => 'facebook-like-share', 'field' => 'fb_url', 'label' => __('Facebook', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'twitter'        => ['type' => 'twitter-follow', 'field' => 'twitter_username', 'label' => __('X', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'instagram'      => ['type' => 'instagram-follow', 'field' => 'instagram_url', 'label' => __('Instagram', 'me5rine-lab'), 'text' => __('Follow', 'me5rine-lab')],
        'discord_custom' => ['type' => 'discord-follow', 'field' => 'discord_url', 'label' => __('Discord', 'me5rine-lab'), 'text' => __('Join', 'me5rine-lab')],
    ];

    $socials = [];
    foreach ($base_socials as $key => $data) {
        $url = get_user_meta($user_id, $key, true);
        if (empty($url)) continue;

        $socials[$key] = [
            'url'          => $url,
            'type'         => $data['type'],
            'field'        => $data['field'],
            'label'        => $data['label'],
            'custom_label' => $data['label'],
            'text'         => $data['text'],
        ];
    }

    return $socials;
}

function generate_combined_entry_options(int $user_id, array $actions, ?int $post_id = null): array {
    $entry_options = generate_entry_options_from_actions($user_id, $actions);

    $user_data = get_userdata($user_id);
    $roles = (array) $user_data->roles;

    $is_partenaire = in_array('um_partenaire', $roles, true);
    $is_partenaire_plus = in_array('um_partenaire_plus', $roles, true);
    $disable_me5rine = !empty($_POST['disable_admin_lab_actions']) || ($post_id && get_post_meta($post_id, '_disable_admin_lab_actions', true));

    if ($is_partenaire && !$is_partenaire_plus && !$disable_me5rine) {
        $socials = get_socials_for_admin_lab();
    
        if (!empty($socials)) {
            $entry_options[] = [
                'id'    => 'separator',
                'type'  => 'automatic-entry',
                'name'  => 'Automatic Entry',
                'value' => '1',
                'is_admin_lab'  => true,
            ];

            $admin_lab_user = get_userdata((int) admin_lab_get_global_option('admin_lab_account_id'));
            $account_display_name = $admin_lab_user ? $admin_lab_user->display_name : __('Me5rine', 'me5rine-lab');
    
            foreach ($socials as $key => $info) {
                $type = ($key === 'discord_custom') ? 'visit-a-page' : $info['type'];
                $id   = ($key === 'discord_custom') ? 'discord_2' : wp_generate_password(6, false);
                $value = $info['url'];
                $field_key = $info['field'];
    
                if ($key === 'twitter') {
                    $value = str_replace(['https://twitter.com/', 'https://x.com/', '@'], '', $value);
                }
    
                $entry_options[] = [
                    'social'      => $key,
                    'id'          => $id,
                    'type'        => $type,
                    'name'        => sprintf(__('%s %s on %s', 'me5rine-lab'), $info['text'], $account_display_name, $info['label']),
                    'value'       => '1',
                    $field_key    => ($info['type'] === 'twitter-follow') ? sanitize_text_field($value) : esc_url_raw($value),
                    'action_text' => $info['text'],
                    'is_admin_lab'  => true,
                ];
            }
        }
    }    

    return $entry_options;
}