<?php
// File: db.php

/**
 * Database Handler for Me5rine LAB
 *
 * @category Database
 * @package  Me5rine_LAB
 * @author   Me5rine
 * @license  MIT
 * @link     https://me5rine.com
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Admin_Lab_DB
 * Handles database interactions for the plugin.
 */
class Admin_Lab_DB {

    /**
     * Singleton instance
     *
     * @var Admin_Lab_DB
     */
    private static $_instance;

    /**
     * Get the singleton instance
     *
     * @return Admin_Lab_DB
     */
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Crée les tables uniquement pour les modules activés.
     */
    public function createTables($active_modules = []) {
        if (in_array('giveaways', $active_modules)) {
            $this->createRafflepressIndexTable();
        }
        if (in_array('marketing_campaigns', $active_modules)) {
            $this->createMarketingTables();
        }
    
        if (in_array('shortcodes', $active_modules)) {
            self::createShortcodesTable();
        }
    
        if (in_array('user_management', $active_modules)) {
            $this->createUserSlugsTable();
            $this->createAccountTypesTable();
        }
        if (in_array('remote_news', $active_modules)) {
            $this->createRemoteNewsTables();
        }
        if (in_array('comparator', $active_modules)) {
            $this->createComparatorClicksTable();
        }
        if (in_array('subscription', $active_modules)) {
            $this->createSubscriptionTables();
        }
    }
    
    /**
     * Crée la table d'index pour les campagnes RafflePress liées aux concours.
     *
     * @param bool $use_site_prefix Si vrai, utilise le prefixe local au site (par défaut : true)
     */
    public function createRafflepressIndexTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('rafflepress_index', false);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            rafflepress_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (rafflepress_id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crée la table pour les slugs utilisateurs (module user_management)
     */
    public function createUserSlugsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('user_slugs');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            user_slug VARCHAR(255) NOT NULL,
            user_slug_id VARCHAR(10) NOT NULL,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crée la table pour les types de compte (module user_management)
     */
    public function createAccountTypesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('account_types');

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            slug VARCHAR(100) NOT NULL PRIMARY KEY,
            label VARCHAR(255) NOT NULL,
            role VARCHAR(100) DEFAULT NULL,
            role_name VARCHAR(255) DEFAULT NULL,
            capabilities TEXT DEFAULT NULL,
            scope TEXT DEFAULT NULL,
            modules TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crée les tables du module Marketing Campaigns
     */
    public function createMarketingTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            "CREATE TABLE IF NOT EXISTS " . admin_lab_getTable('marketing_links') . " (
                id INT NOT NULL AUTO_INCREMENT,
                partner_name VARCHAR(255) NOT NULL,
                campaign_slug VARCHAR(191) NOT NULL UNIQUE,
                campaign_url VARCHAR(255) NOT NULL,
                image_url_sidebar_1 TEXT DEFAULT NULL,
                image_url_sidebar_2 TEXT DEFAULT NULL,
                image_url_banner_1 TEXT DEFAULT NULL,
                image_url_banner_2 TEXT DEFAULT NULL,
                image_url_background TEXT DEFAULT NULL,
                background_color VARCHAR(7) DEFAULT NULL,
                is_trashed TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",
        ];

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Crée la table pour stocker les shortcodes personnalisés
     */
    public static function createShortcodesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = admin_lab_getTable('shortcodes');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id MEDIUMINT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            content TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY name_unique (name(191))
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /** Crée toutes les tables Remote News */
    public function createRemoteNewsTables() {
        $this->createRemoteNewsSourcesTable();
        $this->createRemoteNewsQueriesTable();
        $this->createRemoteNewsCategoryMapTable();
    }

    /** Sources distantes */
    public function createRemoteNewsSourcesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('remote_news_sources', false);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_key VARCHAR(64) NOT NULL,
            table_prefix VARCHAR(64) NOT NULL,
            site_url VARCHAR(255) NOT NULL,
            include_cats TEXT NULL,
            limit_items INT UNSIGNED NOT NULL DEFAULT 10,
            max_age_days INT UNSIGNED NOT NULL DEFAULT 14,
            sideload_images TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_source_key (source_key)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /** Requêtes Elementor (Query IDs) */
    public function createRemoteNewsQueriesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('remote_news_queries', false);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            query_id VARCHAR(96) NOT NULL,
            label VARCHAR(255) DEFAULT NULL,
            include_cats TEXT DEFAULT NULL,
            exclude_cats TEXT DEFAULT NULL,
            sources TEXT DEFAULT NULL,
            limit_items INT UNSIGNED NOT NULL DEFAULT 12,
            orderby ENUM('date','modified','title','rand') NOT NULL DEFAULT 'date',
            sort_order ENUM('ASC','DESC') NOT NULL DEFAULT 'DESC',
            post_type VARCHAR(64) NOT NULL DEFAULT 'remote_news',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_query_id (query_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /** Mapping slug distant -> slug local (par source) */
    public function createRemoteNewsCategoryMapTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('remote_news_category_map', false);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_key VARCHAR(64) NOT NULL,
            remote_slug VARCHAR(191) NOT NULL,
            local_slug  VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            -- NOTE: prefix index on remote_slug to keep composite length under limits
            UNIQUE KEY uq_map_slug (source_key, remote_slug(100))
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crée la table de stats de clics pour le module comparator.
     *
     * Table : {prefix}admin_lab_comparator_clicks
     */
    public function createComparatorClicksTable() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = admin_lab_getTable('comparator_clicks', false);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            game_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            store VARCHAR(100) NOT NULL DEFAULT '',
            platform VARCHAR(100) NOT NULL DEFAULT '',
            click_type VARCHAR(50) NOT NULL DEFAULT '',
            context VARCHAR(50) NOT NULL DEFAULT '',
            clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_game (game_id),
            KEY idx_post (post_id),
            KEY idx_clicked_at (clicked_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates all subscription module tables
     */
    public function createSubscriptionTables() {
        $this->createSubscriptionProvidersTable();
        $this->createSubscriptionAccountsTable();
        $this->createSubscriptionLevelsTable();
        $this->createSubscriptionTiersTable();
        $this->createSubscriptionTierMappingsTable();
        $this->createSubscriptionChannelsTable();
        $this->createSubscriptionProviderAccountTypesTable();
        $this->createUserSubscriptionsTable();
    }

    /**
     * Creates subscription_providers table
     */
    public function createSubscriptionProvidersTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_providers');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            provider_slug VARCHAR(50) NOT NULL,
            provider_name VARCHAR(255) NOT NULL,
            api_endpoint TEXT DEFAULT NULL,
            auth_type VARCHAR(50) DEFAULT NULL,
            client_id VARCHAR(255) DEFAULT NULL,
            client_secret VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            settings TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY provider_slug_unique (provider_slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates subscription_accounts table
     */
    public function createSubscriptionAccountsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_accounts');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            provider_slug VARCHAR(50) NOT NULL,
            external_user_id VARCHAR(255) NOT NULL,
            external_username VARCHAR(255) DEFAULT NULL,
            keycloak_identity_id VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_sync_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY provider_slug (provider_slug),
            KEY external_user_id (external_user_id),
            KEY keycloak_identity_id (keycloak_identity_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Remove token columns if they exist (migration)
        $columns_to_remove = ['access_token', 'refresh_token', 'token_expires_at'];
        foreach ($columns_to_remove as $column) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, $column
            ));
            if (!empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} DROP COLUMN {$column}");
            }
        }
    }

    /**
     * Creates subscription_levels table
     * Stores subscription types retrieved from providers (tier1, tier2, tier3, booster, etc.)
     * Note: account_type_slug is NOT stored here - it's linked to providers only
     */
    public function createSubscriptionLevelsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_levels');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            provider_slug VARCHAR(50) NOT NULL,
            level_slug VARCHAR(100) NOT NULL,
            level_name VARCHAR(255) NOT NULL,
            level_tier VARCHAR(50) DEFAULT NULL,
            discord_role_id VARCHAR(255) DEFAULT NULL,
            subscription_type VARCHAR(50) DEFAULT NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY provider_level_unique (provider_slug, level_slug),
            KEY provider_slug (provider_slug),
            KEY level_slug (level_slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates subscription_tiers table
     * Stores internal subscription tiers (Bronze, Silver, Gold, Platinum, Emerald, Diamond, etc.)
     * Note: account_type_slug is NOT stored here - it's linked to providers only
     */
    public function createSubscriptionTiersTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_tiers');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            tier_slug VARCHAR(50) NOT NULL,
            tier_name VARCHAR(255) NOT NULL,
            tier_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tier_slug_unique (tier_slug),
            KEY tier_order (tier_order)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates subscription_tier_mappings table
     */
    public function createSubscriptionTierMappingsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_tier_mappings');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            tier_slug VARCHAR(50) NOT NULL,
            provider_slug VARCHAR(50) NOT NULL,
            level_slug VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tier_provider_level_unique (tier_slug, provider_slug, level_slug),
            KEY tier_slug (tier_slug),
            KEY provider_slug (provider_slug),
            KEY level_slug (level_slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates subscription_channels table
     */
    public function createSubscriptionChannelsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_channels');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            provider_slug VARCHAR(50) NOT NULL,
            channel_name VARCHAR(255) NOT NULL,
            channel_identifier VARCHAR(255) NOT NULL,
            channel_type VARCHAR(50) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            settings TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_slug (provider_slug),
            KEY channel_identifier (channel_identifier)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates subscription_provider_account_types table
     */
    public function createSubscriptionProviderAccountTypesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('subscription_provider_account_types');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            provider_slug VARCHAR(50) NOT NULL,
            account_type_slug VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_slug (provider_slug),
            KEY account_type_slug (account_type_slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates user_subscriptions table
     */
    public function createUserSubscriptionsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = admin_lab_getTable('user_subscriptions');

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT 0,
            account_id INT DEFAULT 0,
            provider_slug VARCHAR(100) NOT NULL,
            provider_target_slug VARCHAR(100) DEFAULT NULL,
            level_slug VARCHAR(100) NOT NULL,
            external_subscription_id VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            started_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            last_verified_at DATETIME DEFAULT NULL,
            metadata TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY provider_level (provider_slug, level_slug),
            KEY provider_target_slug (provider_target_slug),
            KEY status (status),
            KEY external_subscription_id (external_subscription_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}