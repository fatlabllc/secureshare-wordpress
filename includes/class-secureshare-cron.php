<?php
/**
 * Cron job handlers for SecureShare.
 *
 * Handles automatic cleanup of expired secrets and old rate limit records.
 */

if (!defined('WPINC')) {
    die;
}

class SecureShare_Cron {

    /**
     * Constructor - registers cron hooks.
     */
    public function __construct() {
        add_action('secureshare_cleanup_expired', array($this, 'cleanup_expired_secrets'));
    }

    /**
     * Cleanup expired secrets and old rate limit records.
     *
     * This function is called by WP-Cron on an hourly schedule.
     */
    public function cleanup_expired_secrets() {
        // Cleanup expired secrets
        $secrets_deleted = SecureShare_DB::cleanup_expired_secrets();

        // Cleanup old rate limit records (older than 7 days)
        $rate_limits_deleted = SecureShare_DB::cleanup_rate_limits();

        // Log cleanup activity if debug mode is enabled
        if (get_option('secureshare_debug_mode', '0') === '1') {
            error_log(sprintf(
                'SecureShare Cleanup: Deleted %d expired secrets and %d old rate limit records',
                $secrets_deleted,
                $rate_limits_deleted
            ));
        }

        // Update last cleanup timestamp
        update_option('secureshare_last_cleanup', current_time('timestamp'));
    }

    /**
     * Manually trigger cleanup (for admin use).
     *
     * @return array Array with cleanup results.
     */
    public static function manual_cleanup() {
        $secrets_deleted = SecureShare_DB::cleanup_expired_secrets();
        $rate_limits_deleted = SecureShare_DB::cleanup_rate_limits();

        return array(
            'secrets_deleted' => $secrets_deleted,
            'rate_limits_deleted' => $rate_limits_deleted,
            'timestamp' => current_time('timestamp')
        );
    }
}
