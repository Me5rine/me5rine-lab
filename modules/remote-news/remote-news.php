<?php
// File: modules/remote-news/remote-news.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('remote_news', $active_modules, true)) return;

// Chargement des fonctions du module
require_once __DIR__ . '/register/remote-news-register-types.php';
require_once __DIR__ . '/functions/remote-news-ingestion.php';
require_once __DIR__ . '/functions/remote-news-front.php';
require_once __DIR__ . '/functions/remote-news-db.php';
require_once __DIR__ . '/functions/remote-news-admin-handlers.php';
require_once __DIR__ . '/elementor/remote-news-elementor-queries.php';
require_once __DIR__ . '/forms/remote-news-add-forms.php';

// Chargement de l’interface d’administration
if (is_admin()) {
    include_once __DIR__ . '/admin/remote-news-admin-ui.php';
}