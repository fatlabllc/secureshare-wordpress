<?php
/**
 * Uninstall script for SecureShare.
 *
 * Fired when the plugin is uninstalled via WordPress admin.
 * Removes all plugin data from the database.
 *
 * @package SecureShare
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Remove all plugin options.
 */
$options = array(
    'secureshare_encryption_key',
    'secureshare_expiration_time',
    'secureshare_max_secret_size',
    'secureshare_rate_limit_enabled',
    'secureshare_rate_limit_max',
    'secureshare_rate_limit_window',
    'secureshare_debug_mode',
    'secureshare_custom_css',
    'secureshare_last_cleanup',
    'secureshare_version'
);

foreach ($options as $option) {
    delete_option($option);
}

/**
 * Drop plugin database tables.
 */
$secrets_table = $wpdb->prefix . 'secureshare_secrets';
$rate_limits_table = $wpdb->prefix . 'secureshare_rate_limits';

$wpdb->query("DROP TABLE IF EXISTS $secrets_table");
$wpdb->query("DROP TABLE IF EXISTS $rate_limits_table");

/**
 * Unschedule cron jobs.
 */
$timestamp = wp_next_scheduled('secureshare_cleanup_expired');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'secureshare_cleanup_expired');
}

/**
 * Clear any cached data.
 */
wp_cache_flush();
