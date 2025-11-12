<?php
/**
 * Encryption handler for SecureShare.
 *
 * Handles encryption and decryption of secrets using AES-256-CBC with OpenSSL.
 * Uses a configurable encryption key stored in WordPress options.
 */

if (!defined('WPINC')) {
    die;
}

class SecureShare_Encryption {

    /**
     * Encryption method/cipher.
     * AES-256-CBC provides strong encryption with good performance.
     */
    const ENCRYPTION_METHOD = 'AES-256-CBC';

    /**
     * Encrypt a secret message.
     *
     * Generates a unique token, encrypts the plaintext using AES-256-CBC,
     * and returns all components needed for later decryption.
     *
     * @param string $plaintext The secret message to encrypt.
     * @return array|WP_Error Associative array on success containing:
     *                        - 'token': 32-character hex token (unique identifier)
     *                        - 'encrypted': Base64-encoded encrypted data
     *                        - 'iv': Base64-encoded initialization vector
     */
    public static function encrypt($plaintext) {
        // Get encryption key from WordPress options
        $encryption_key = get_option('secureshare_encryption_key');

        if (empty($encryption_key)) {
            return new WP_Error('no_encryption_key', __('Encryption key is not configured', 'secureshare'));
        }

        // Generate a unique token for this secret (16 bytes = 32 hex chars)
        $token = bin2hex(random_bytes(16));

        // Create encryption key using SHA-256
        // This ensures the key is exactly 32 bytes (256 bits) for AES-256
        $key = hash('sha256', $encryption_key, true);

        // Generate a random initialization vector (IV)
        // The IV ensures that identical plaintext produces different ciphertext
        $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        // Encrypt the data using AES-256-CBC
        $encrypted = openssl_encrypt(
            $plaintext,
            self::ENCRYPTION_METHOD,
            $key,
            0, // Use default options (returns base64 encoded string)
            $iv
        );

        if ($encrypted === false) {
            $error_msg = openssl_error_string();
            return new WP_Error('encryption_failed', sprintf(__('Encryption failed: %s', 'secureshare'), $error_msg));
        }

        return array(
            'token' => $token,
            'encrypted' => $encrypted, // Already base64 encoded by openssl_encrypt
            'iv' => base64_encode($iv)
        );
    }

    /**
     * Decrypt a secret message.
     *
     * Decrypts data that was previously encrypted with the encrypt() method.
     * Requires the encrypted data and the original initialization vector.
     *
     * @param string $encrypted The encrypted data (base64 encoded).
     * @param string $iv The initialization vector (base64 encoded).
     * @return string|WP_Error The decrypted plaintext, or WP_Error on failure.
     */
    public static function decrypt($encrypted, $iv) {
        // Get encryption key from WordPress options
        $encryption_key = get_option('secureshare_encryption_key');

        if (empty($encryption_key)) {
            return new WP_Error('no_encryption_key', __('Encryption key is not configured', 'secureshare'));
        }

        // Create encryption key using SHA-256
        $key = hash('sha256', $encryption_key, true);

        // Decode the base64-encoded IV back to binary
        $iv = base64_decode($iv);

        // Decrypt the data using AES-256-CBC
        $decrypted = openssl_decrypt(
            $encrypted,
            self::ENCRYPTION_METHOD,
            $key,
            0, // Use default options (matches encryption)
            $iv
        );

        if ($decrypted === false) {
            return new WP_Error('decryption_failed', __('Failed to decrypt secret', 'secureshare'));
        }

        return $decrypted;
    }

    /**
     * Validate that a token matches the expected format.
     *
     * Tokens should be 32-character hexadecimal strings (case-insensitive).
     * This prevents injection attacks and ensures only valid tokens are processed.
     *
     * @param string $token The token to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid_token($token) {
        // Token must be exactly 32 hexadecimal characters (16 bytes hex-encoded)
        return preg_match('/^[a-f0-9]{32}$/i', $token) === 1;
    }

    /**
     * Generate a secure encryption key suitable for WordPress options.
     *
     * This is a utility method for generating new encryption keys.
     * Used in admin panel for key generation.
     *
     * @return string A 64-character hexadecimal string (32 bytes).
     */
    public static function generate_key() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if OpenSSL extension is available.
     *
     * @return bool True if OpenSSL is available.
     */
    public static function is_openssl_available() {
        return extension_loaded('openssl');
    }

    /**
     * Get information about the encryption method being used.
     *
     * @return array Information about the encryption.
     */
    public static function get_encryption_info() {
        return array(
            'method' => self::ENCRYPTION_METHOD,
            'openssl_available' => self::is_openssl_available(),
            'key_configured' => !empty(get_option('secureshare_encryption_key'))
        );
    }
}
