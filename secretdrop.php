<?php
/**
 * Plugin Name: SecretDrop
 * Plugin URI: https://github.com/fatlabllc/secretdrop-wordpress
 * Description: Securely share passwords and sensitive information via time-limited, encrypted links. Create secrets that automatically expire after 24 hours.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: FatLab Web Support
 * Author URI: https://fatlabwebsupport.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: secretdrop
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('SECRETDROP_VERSION', '1.1.0');

/**
 * Plugin directory path.
 */
define('SECRETDROP_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('SECRETDROP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('SECRETDROP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Database table names.
 */
function secretdrop_get_secrets_table() {
    global $wpdb;
    return $wpdb->prefix . 'secretdrop_secrets';
}

function secretdrop_get_rate_limits_table() {
    global $wpdb;
    return $wpdb->prefix . 'secretdrop_rate_limits';
}

/**
 * Plugin activation hook.
 * Creates database tables and sets default options.
 */
function secretdrop_activate() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // Create secrets table
    $secrets_table = secretdrop_get_secrets_table();
    $sql_secrets = "CREATE TABLE $secrets_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        token char(32) NOT NULL,
        encrypted longtext NOT NULL,
        iv varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        expires_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    dbDelta($sql_secrets);

    // Create rate limits table
    $rate_limits_table = secretdrop_get_rate_limits_table();
    $sql_rate_limits = "CREATE TABLE $rate_limits_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_hash varchar(64) NOT NULL,
        request_count int(11) NOT NULL DEFAULT 0,
        window_start datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ip_hash (ip_hash),
        KEY window_start (window_start)
    ) $charset_collate;";

    dbDelta($sql_rate_limits);

    // Set default options if they don't exist
    if (!get_option('secretdrop_encryption_key')) {
        // Generate a random encryption key
        $key = bin2hex(random_bytes(32));
        add_option('secretdrop_encryption_key', $key);
    }

    if (!get_option('secretdrop_expiration_time')) {
        add_option('secretdrop_expiration_time', 86400); // 24 hours
    }

    if (!get_option('secretdrop_max_secret_size')) {
        add_option('secretdrop_max_secret_size', 2000);
    }

    if (!get_option('secretdrop_rate_limit_enabled')) {
        add_option('secretdrop_rate_limit_enabled', '1');
    }

    if (!get_option('secretdrop_rate_limit_max')) {
        add_option('secretdrop_rate_limit_max', 5);
    }

    if (!get_option('secretdrop_rate_limit_window')) {
        add_option('secretdrop_rate_limit_window', 3600); // 1 hour
    }

    if (!get_option('secretdrop_debug_mode')) {
        add_option('secretdrop_debug_mode', '0');
    }

    if (!get_option('secretdrop_custom_css')) {
        add_option('secretdrop_custom_css', '');
    }

    // Schedule cleanup cron job
    if (!wp_next_scheduled('secretdrop_cleanup_expired')) {
        wp_schedule_event(time(), 'hourly', 'secretdrop_cleanup_expired');
    }

    // Store plugin version
    add_option('secretdrop_version', SECRETDROP_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'secretdrop_activate');

/**
 * Plugin deactivation hook.
 * Unschedules cron jobs.
 */
function secretdrop_deactivate() {
    // Unschedule cleanup cron job
    $timestamp = wp_next_scheduled('secretdrop_cleanup_expired');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'secretdrop_cleanup_expired');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'secretdrop_deactivate');

/**
 * Load plugin classes.
 */
function secretdrop_load_classes() {
    require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-encryption.php';
    require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-db.php';
    require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-api.php';
    require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-shortcodes.php';
    require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-cron.php';

    // Load admin class only in admin area
    if (is_admin()) {
        require_once SECRETDROP_PLUGIN_DIR . 'includes/class-secretdrop-admin.php';
    }
}
add_action('plugins_loaded', 'secretdrop_load_classes');

/**
 * Initialize the plugin.
 */
function secretdrop_init() {
    // Initialize API endpoints
    new SecretDrop_API();

    // Initialize shortcodes
    new SecretDrop_Shortcodes();

    // Initialize cron handlers
    new SecretDrop_Cron();

    // Initialize admin (only in admin area)
    if (is_admin()) {
        new SecretDrop_Admin();
    }
}
add_action('init', 'secretdrop_init');

/**
 * Add settings link on plugins page.
 */
function secretdrop_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=secretdrop') . '">' . __('Settings', 'secretdrop') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . SECRETDROP_PLUGIN_BASENAME, 'secretdrop_plugin_action_links');
