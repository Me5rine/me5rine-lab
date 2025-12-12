<?php
// File: config.php

if (!defined('ABSPATH')) exit;

// Plugin configuration class
class Admin_Lab_Config {
    private static $instance = null;

    private function __construct() {}

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_option( $key, $default = '' ) {
        return get_option( $key, $default );
    }

    public function update_option( $key, $value ) {
        update_option( $key, $value );
    }
}

// Initialize configuration instance
$admin_lab_config = Admin_Lab_Config::get_instance();
