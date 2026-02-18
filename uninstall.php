<?php
/**
 * Uninstall script for SecretDrop.
 *
 * Fired when the plugin is uninstalled via WordPress admin.
 * Removes all plugin data from the database.
 *
 * @package SecretDrop
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Remove all plugin options.
 */
$secretdrop_options = array(
    'secretdrop_encryption_key',
    'secretdrop_expiration_time',
    'secretdrop_max_secret_size',
    'secretdrop_rate_limit_enabled',
    'secretdrop_rate_limit_max',
    'secretdrop_rate_limit_window',
    'secretdrop_debug_mode',
    'secretdrop_custom_css',
    'secretdrop_last_cleanup',
    'secretdrop_version'
);

foreach ($secretdrop_options as $secretdrop_option) {
    delete_option($secretdrop_option);
}

/**
 * Drop plugin database tables.
 */
$secretdrop_secrets_table = $wpdb->prefix . 'secretdrop_secrets';
$secretdrop_rate_limits_table = $wpdb->prefix . 'secretdrop_rate_limits';

$wpdb->query("DROP TABLE IF EXISTS $secretdrop_secrets_table");
$wpdb->query("DROP TABLE IF EXISTS $secretdrop_rate_limits_table");

/**
 * Unschedule cron jobs.
 */
$secretdrop_timestamp = wp_next_scheduled('secretdrop_cleanup_expired');
if ($secretdrop_timestamp) {
    wp_unschedule_event($secretdrop_timestamp, 'secretdrop_cleanup_expired');
}

/**
 * Clear any cached data.
 */
wp_cache_flush();
