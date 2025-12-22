<?php
// File: modules/subscription/functions/subscription-encryption.php

if (!defined('ABSPATH')) exit;

/**
 * Encrypt data using AES-256-CBC
 * 
 * @param string $data Data to encrypt
 * @return string|false Encrypted data (base64 encoded) or false on failure
 */
if (!function_exists('admin_lab_encrypt_data')) {
    function admin_lab_encrypt_data($data) {
        if (empty($data)) {
            return $data;
        }
        
        // Get encryption key from wp-config.php
        // The key should be defined as: define('ME5RINE_LAB_ENCRYPTION_KEY', 'your-32-byte-key-here');
        $key = defined('ME5RINE_LAB_ENCRYPTION_KEY') ? ME5RINE_LAB_ENCRYPTION_KEY : null;
        
        if (!$key) {
            // Fallback: use a hash of AUTH_KEY and AUTH_SALT if available
            if (defined('AUTH_KEY') && defined('AUTH_SALT')) {
                $key = hash('sha256', AUTH_KEY . AUTH_SALT, true);
            } else {
                // Last resort: use a default key (not secure, but better than nothing)
                // This should be replaced with a proper key in wp-config.php
                error_log('[ENCRYPTION] WARNING: No encryption key defined. Using fallback key. Please define ME5RINE_LAB_ENCRYPTION_KEY in wp-config.php');
                $key = hash('sha256', 'me5rine-lab-default-key-change-in-wp-config', true);
            }
        } else {
            // Ensure key is 32 bytes (256 bits) for AES-256
            if (strlen($key) < 32) {
                $key = hash('sha256', $key, true);
            } elseif (strlen($key) > 32) {
                $key = substr($key, 0, 32);
            }
        }
        
        // Generate a random IV (Initialization Vector)
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        // Encrypt the data
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($encrypted === false) {
            error_log('[ENCRYPTION] ERROR: Encryption failed');
            return false;
        }
        
        // Prepend IV to encrypted data and encode in base64
        return base64_encode($iv . $encrypted);
    }
}

/**
 * Decrypt data using AES-256-CBC
 * 
 * @param string $encrypted_data Encrypted data (base64 encoded)
 * @return string|false Decrypted data or false on failure
 */
if (!function_exists('admin_lab_decrypt_data')) {
    function admin_lab_decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return $encrypted_data;
        }
        
        // If the data doesn't look encrypted (not base64 or too short), return as-is
        // This allows backward compatibility with unencrypted data
        $decoded = base64_decode($encrypted_data, true);
        if ($decoded === false || strlen($decoded) < 16) {
            // Probably not encrypted, return as-is
            return $encrypted_data;
        }
        
        // Get encryption key from wp-config.php
        $key = defined('ME5RINE_LAB_ENCRYPTION_KEY') ? ME5RINE_LAB_ENCRYPTION_KEY : null;
        
        if (!$key) {
            // Fallback: use a hash of AUTH_KEY and AUTH_SALT if available
            if (defined('AUTH_KEY') && defined('AUTH_SALT')) {
                $key = hash('sha256', AUTH_KEY . AUTH_SALT, true);
            } else {
                // Last resort: use a default key (not secure, but better than nothing)
                $key = hash('sha256', 'me5rine-lab-default-key-change-in-wp-config', true);
            }
        } else {
            // Ensure key is 32 bytes (256 bits) for AES-256
            if (strlen($key) < 32) {
                $key = hash('sha256', $key, true);
            } elseif (strlen($key) > 32) {
                $key = substr($key, 0, 32);
            }
        }
        
        // Decode from base64
        $data = base64_decode($encrypted_data, true);
        if ($data === false) {
            // Not base64 encoded, probably not encrypted
            return $encrypted_data;
        }
        
        // Extract IV (first 16 bytes for AES-256-CBC)
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        if (strlen($data) < $iv_length) {
            // Data too short, probably not encrypted
            return $encrypted_data;
        }
        
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            // Decryption failed, probably not encrypted with this key
            // Return original data for backward compatibility
            return $encrypted_data;
        }
        
        return $decrypted;
    }
}

