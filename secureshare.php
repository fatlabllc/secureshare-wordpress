<?php
/**
 * Plugin Name: SecureShare
 * Plugin URI: https://github.com/fatlabllc/secureshare-wordpress
 * Description: Securely share passwords and sensitive information via time-limited, encrypted links. Create secrets that automatically expire after 24 hours.
 * Version: 1.0.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: FatLab Web Support
 * Author URI: https://fatlabwebsupport.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: secureshare
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('SECURESHARE_VERSION', '1.0.4');

/**
 * Plugin directory path.
 */
define('SECURESHARE_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('SECURESHARE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('SECURESHARE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Database table names.
 */
function secureshare_get_secrets_table() {
    global $wpdb;
    return $wpdb->prefix . 'secureshare_secrets';
}

function secureshare_get_rate_limits_table() {
    global $wpdb;
    return $wpdb->prefix . 'secureshare_rate_limits';
}

/**
 * Plugin activation hook.
 * Creates database tables and sets default options.
 */
function secureshare_activate() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // Create secrets table
    $secrets_table = secureshare_get_secrets_table();
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
    $rate_limits_table = secureshare_get_rate_limits_table();
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
    if (!get_option('secureshare_encryption_key')) {
        // Generate a random encryption key
        $key = bin2hex(random_bytes(32));
        add_option('secureshare_encryption_key', $key);
    }

    if (!get_option('secureshare_expiration_time')) {
        add_option('secureshare_expiration_time', 86400); // 24 hours
    }

    if (!get_option('secureshare_max_secret_size')) {
        add_option('secureshare_max_secret_size', 2000);
    }

    if (!get_option('secureshare_rate_limit_enabled')) {
        add_option('secureshare_rate_limit_enabled', '1');
    }

    if (!get_option('secureshare_rate_limit_max')) {
        add_option('secureshare_rate_limit_max', 5);
    }

    if (!get_option('secureshare_rate_limit_window')) {
        add_option('secureshare_rate_limit_window', 3600); // 1 hour
    }

    if (!get_option('secureshare_debug_mode')) {
        add_option('secureshare_debug_mode', '0');
    }

    if (!get_option('secureshare_custom_css')) {
        add_option('secureshare_custom_css', '');
    }

    // Schedule cleanup cron job
    if (!wp_next_scheduled('secureshare_cleanup_expired')) {
        wp_schedule_event(time(), 'hourly', 'secureshare_cleanup_expired');
    }

    // Store plugin version
    add_option('secureshare_version', SECURESHARE_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'secureshare_activate');

/**
 * Plugin deactivation hook.
 * Unschedules cron jobs.
 */
function secureshare_deactivate() {
    // Unschedule cleanup cron job
    $timestamp = wp_next_scheduled('secureshare_cleanup_expired');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'secureshare_cleanup_expired');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'secureshare_deactivate');

/**
 * Load plugin classes.
 */
function secureshare_load_classes() {
    require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-encryption.php';
    require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-db.php';
    require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-api.php';
    require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-shortcodes.php';
    require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-cron.php';

    // Load admin class only in admin area
    if (is_admin()) {
        require_once SECURESHARE_PLUGIN_DIR . 'includes/class-secureshare-admin.php';
    }
}
add_action('plugins_loaded', 'secureshare_load_classes');

/**
 * Initialize the plugin.
 */
function secureshare_init() {
    // Initialize API endpoints
    new SecureShare_API();

    // Initialize shortcodes
    new SecureShare_Shortcodes();

    // Initialize cron handlers
    new SecureShare_Cron();

    // Initialize admin (only in admin area)
    if (is_admin()) {
        new SecureShare_Admin();
    }

    // Load text domain for translations
    load_plugin_textdomain('secureshare', false, dirname(SECURESHARE_PLUGIN_BASENAME) . '/languages');
}
add_action('init', 'secureshare_init');

/**
 * Add settings link on plugins page.
 */
function secureshare_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=secureshare') . '">' . __('Settings', 'secureshare') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . SECURESHARE_PLUGIN_BASENAME, 'secureshare_plugin_action_links');
