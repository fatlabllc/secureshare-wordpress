<?php
/**
 * Database operations for SecureShare.
 *
 * Handles storage and retrieval of encrypted secrets,
 * rate limiting, and cleanup operations.
 */

if (!defined('WPINC')) {
    die;
}

class SecureShare_DB {

    /**
     * Store a new secret in the database.
     *
     * @param string $token The unique token for this secret.
     * @param string $encrypted The encrypted secret data.
     * @param string $iv The initialization vector used for encryption.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function store_secret($token, $encrypted, $iv) {
        global $wpdb;

        $table = secureshare_get_secrets_table();
        $expiration_time = get_option('secureshare_expiration_time', 86400);

        $current_time = current_time('mysql');
        $expires_at = gmdate('Y-m-d H:i:s', strtotime($current_time) + $expiration_time);

        $result = $wpdb->insert(
            $table,
            array(
                'token' => $token,
                'encrypted' => $encrypted,
                'iv' => $iv,
                'created_at' => $current_time,
                'expires_at' => $expires_at
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', __('Failed to store secret', 'secureshare'));
        }

        // Cleanup expired secrets (opportunistic)
        self::cleanup_expired_secrets();

        return true;
    }

    /**
     * Retrieve a secret from the database.
     *
     * @param string $token The token to retrieve.
     * @return array|WP_Error Secret data array on success, WP_Error on failure.
     */
    public static function retrieve_secret($token) {
        global $wpdb;

        $table = secureshare_get_secrets_table();
        $current_time = current_time('mysql');

        $secret = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND expires_at > %s",
            $token,
            $current_time
        ), ARRAY_A);

        if (!$secret) {
            return new WP_Error('secret_not_found', __('Secret not found or expired', 'secureshare'));
        }

        return $secret;
    }

    /**
     * Delete a specific secret.
     *
     * @param string $token The token to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete_secret($token) {
        global $wpdb;

        $table = secureshare_get_secrets_table();

        $result = $wpdb->delete(
            $table,
            array('token' => $token),
            array('%s')
        );

        return $result !== false;
    }

    /**
     * Cleanup expired secrets from the database.
     *
     * @return int Number of secrets deleted.
     */
    public static function cleanup_expired_secrets() {
        global $wpdb;

        $table = secureshare_get_secrets_table();
        $current_time = current_time('mysql');

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE expires_at <= %s",
            $current_time
        ));

        // Update last cleanup time
        update_option('secureshare_last_cleanup', current_time('timestamp'));

        return $deleted !== false ? $deleted : 0;
    }

    /**
     * Check if the current IP has exceeded rate limits.
     *
     * @return bool|WP_Error True if within limits, WP_Error if exceeded.
     */
    public static function check_rate_limit() {
        // Check if rate limiting is enabled
        if (!get_option('secureshare_rate_limit_enabled', '1')) {
            return true;
        }

        global $wpdb;

        $table = secureshare_get_rate_limits_table();
        $max_requests = intval(get_option('secureshare_rate_limit_max', 5));
        $window_seconds = intval(get_option('secureshare_rate_limit_window', 3600));

        // Get client IP and hash it for privacy
        $ip = self::get_client_ip();
        $ip_hash = hash('sha256', $ip . wp_salt('nonce'));

        $current_time = current_time('mysql');
        $window_start = gmdate('Y-m-d H:i:s', strtotime($current_time) - $window_seconds);

        // Get existing rate limit record
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE ip_hash = %s",
            $ip_hash
        ), ARRAY_A);

        if ($record) {
            // Check if we're in a new window
            if (strtotime($record['window_start']) < strtotime($window_start)) {
                // New window, reset counter
                $wpdb->update(
                    $table,
                    array(
                        'request_count' => 1,
                        'window_start' => $current_time
                    ),
                    array('ip_hash' => $ip_hash),
                    array('%d', '%s'),
                    array('%s')
                );
                return true;
            } else {
                // Same window, check if limit exceeded
                if ($record['request_count'] >= $max_requests) {
                    $time_remaining = strtotime($record['window_start']) + $window_seconds - current_time('timestamp');
                    return new WP_Error(
                        'rate_limit_exceeded',
                        sprintf(
                            /* translators: %d: number of minutes until rate limit resets */
                            __('Rate limit exceeded. Please try again in %d minutes.', 'secureshare'),
                            ceil($time_remaining / 60)
                        )
                    );
                } else {
                    // Increment counter
                    $wpdb->update(
                        $table,
                        array('request_count' => $record['request_count'] + 1),
                        array('ip_hash' => $ip_hash),
                        array('%d'),
                        array('%s')
                    );
                    return true;
                }
            }
        } else {
            // New IP, create record
            $wpdb->insert(
                $table,
                array(
                    'ip_hash' => $ip_hash,
                    'request_count' => 1,
                    'window_start' => $current_time
                ),
                array('%s', '%d', '%s')
            );
            return true;
        }
    }

    /**
     * Cleanup old rate limit records.
     *
     * @return int Number of records deleted.
     */
    public static function cleanup_rate_limits() {
        global $wpdb;

        $table = secureshare_get_rate_limits_table();
        $cutoff = gmdate('Y-m-d H:i:s', current_time('timestamp') - (7 * 24 * 3600)); // 7 days ago

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE window_start < %s",
            $cutoff
        ));

        return $deleted !== false ? $deleted : 0;
    }

    /**
     * Get statistics about stored secrets.
     *
     * @return array Statistics array.
     */
    public static function get_statistics() {
        global $wpdb;

        $secrets_table = secureshare_get_secrets_table();
        $rate_limits_table = secureshare_get_rate_limits_table();
        $current_time = current_time('mysql');

        // Total secrets created (we can only count current ones, not historical)
        $total_secrets = $wpdb->get_var("SELECT COUNT(*) FROM $secrets_table");

        // Active secrets (not expired)
        $active_secrets = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $secrets_table WHERE expires_at > %s",
            $current_time
        ));

        // Expired secrets (ready for cleanup)
        $expired_secrets = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $secrets_table WHERE expires_at <= %s",
            $current_time
        ));

        // Rate limit records (last 7 days)
        $seven_days_ago = gmdate('Y-m-d H:i:s', current_time('timestamp') - (7 * 24 * 3600));
        $rate_limit_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $rate_limits_table WHERE window_start > %s",
            $seven_days_ago
        ));

        // Last cleanup time
        $last_cleanup = get_option('secureshare_last_cleanup', 0);

        return array(
            'total_secrets' => intval($total_secrets),
            'active_secrets' => intval($active_secrets),
            'expired_secrets' => intval($expired_secrets),
            'rate_limit_records' => intval($rate_limit_records),
            'last_cleanup' => $last_cleanup
        );
    }

    /**
     * Get the client's IP address.
     *
     * @return string The client IP address.
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', sanitize_text_field(wp_unslash($_SERVER[$key]))) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validate secret size against configured maximum.
     *
     * @param string $secret The secret to validate.
     * @return bool|WP_Error True if valid, WP_Error if too large.
     */
    public static function validate_secret_size($secret) {
        $max_size = intval(get_option('secureshare_max_secret_size', 2000));

        if (strlen($secret) > $max_size) {
            return new WP_Error(
                'secret_too_large',
                sprintf(
                    /* translators: %d: maximum number of characters allowed */
                    __('Secret exceeds maximum size of %d characters', 'secureshare'),
                    $max_size
                )
            );
        }

        return true;
    }

    /**
     * Get all rate limit records (for admin display).
     *
     * @return array Rate limit records.
     */
    public static function get_rate_limit_records($limit = 50) {
        global $wpdb;

        $table = secureshare_get_rate_limits_table();

        $records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY window_start DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $records ? $records : array();
    }

    /**
     * Clear all rate limit records (admin action).
     *
     * @return bool True on success.
     */
    public static function clear_all_rate_limits() {
        global $wpdb;

        $table = secureshare_get_rate_limits_table();
        $result = $wpdb->query("TRUNCATE TABLE $table");

        return $result !== false;
    }
}
