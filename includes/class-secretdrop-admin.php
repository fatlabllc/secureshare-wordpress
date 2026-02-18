<?php
/**
 * Admin interface for SecretDrop.
 *
 * Handles the admin settings page, configuration, and statistics display.
 */

if (!defined('WPINC')) {
    die;
}

class SecretDrop_Admin {

    /**
     * Constructor - registers admin hooks.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_secretdrop_generate_key', array($this, 'handle_generate_key'));
        add_action('admin_post_secretdrop_manual_cleanup', array($this, 'handle_manual_cleanup'));
        add_action('admin_post_secretdrop_clear_rate_limits', array($this, 'handle_clear_rate_limits'));
    }

    /**
     * Add admin menu item under Settings.
     */
    public function add_admin_menu() {
        add_options_page(
            __('SecretDrop Settings', 'secretdrop'),
            __('SecretDrop', 'secretdrop'),
            'manage_options',
            'secretdrop',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // General settings
        register_setting('secretdrop_general', 'secretdrop_encryption_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_encryption_key')
        ));

        register_setting('secretdrop_general', 'secretdrop_expiration_time', array(
            'type' => 'integer',
            'default' => 86400,
            'sanitize_callback' => array($this, 'sanitize_expiration_time')
        ));

        register_setting('secretdrop_general', 'secretdrop_max_secret_size', array(
            'type' => 'integer',
            'default' => 2000,
            'sanitize_callback' => 'absint'
        ));

        // Rate limiting settings
        register_setting('secretdrop_rate_limiting', 'secretdrop_rate_limit_enabled', array(
            'type' => 'string',
            'default' => '1',
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('secretdrop_rate_limiting', 'secretdrop_rate_limit_max', array(
            'type' => 'integer',
            'default' => 5,
            'sanitize_callback' => 'absint'
        ));

        register_setting('secretdrop_rate_limiting', 'secretdrop_rate_limit_window', array(
            'type' => 'integer',
            'default' => 3600,
            'sanitize_callback' => array($this, 'sanitize_rate_limit_window')
        ));

        // Advanced settings
        register_setting('secretdrop_advanced', 'secretdrop_debug_mode', array(
            'type' => 'string',
            'default' => '0',
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('secretdrop_advanced', 'secretdrop_custom_css', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_strip_all_tags'
        ));
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if ($hook !== 'settings_page_secretdrop') {
            return;
        }

        wp_enqueue_script(
            'secretdrop-admin',
            SECRETDROP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SECRETDROP_VERSION,
            true
        );

        wp_localize_script('secretdrop-admin', 'secretdropAdmin', array(
            'nonce' => wp_create_nonce('secretdrop_admin'),
            'adminPostUrl' => admin_url('admin-post.php'),
            'generateKeyNonce' => wp_create_nonce('secretdrop_generate_key'),
            'strings' => array(
                'confirmKeyRegen' => __('WARNING: Regenerating the encryption key will make all existing secrets unrecoverable. Are you sure?', 'secretdrop'),
                'confirmClearRateLimits' => __('Are you sure you want to clear all rate limit records?', 'secretdrop'),
                'keyCopied' => __('Encryption key copied to clipboard', 'secretdrop')
            )
        ));
    }

    /**
     * Render the admin settings page.
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'secretdrop'));
        }

        // Get current tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';

        // Load template
        include SECRETDROP_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Handle encryption key generation.
     */
    public function handle_generate_key() {
        // Verify nonce and capabilities
        if (!isset($_POST['secretdrop_nonce']) ||
            !wp_verify_nonce(wp_unslash($_POST['secretdrop_nonce']), 'secretdrop_generate_key') ||
            !current_user_can('manage_options')) {
            wp_die(esc_html__('Security check failed', 'secretdrop'));
        }

        // Generate new key
        $new_key = SecretDrop_Encryption::generate_key();
        update_option('secretdrop_encryption_key', $new_key);

        // Redirect back with success message
        wp_safe_redirect(add_query_arg(array(
            'page' => 'secretdrop',
            'tab' => 'general',
            'message' => 'key_generated'
        ), admin_url('options-general.php')));
        exit;
    }

    /**
     * Handle manual cleanup trigger.
     */
    public function handle_manual_cleanup() {
        // Verify nonce and capabilities
        if (!isset($_POST['secretdrop_nonce']) ||
            !wp_verify_nonce(wp_unslash($_POST['secretdrop_nonce']), 'secretdrop_manual_cleanup') ||
            !current_user_can('manage_options')) {
            wp_die(esc_html__('Security check failed', 'secretdrop'));
        }

        // Run cleanup
        $result = SecretDrop_Cron::manual_cleanup();

        // Redirect back with success message
        wp_safe_redirect(add_query_arg(array(
            'page' => 'secretdrop',
            'tab' => 'advanced',
            'message' => 'cleanup_done',
            'secrets_deleted' => $result['secrets_deleted'],
            'rate_limits_deleted' => $result['rate_limits_deleted']
        ), admin_url('options-general.php')));
        exit;
    }

    /**
     * Handle rate limits clearing.
     */
    public function handle_clear_rate_limits() {
        // Verify nonce and capabilities
        if (!isset($_POST['secretdrop_nonce']) ||
            !wp_verify_nonce(wp_unslash($_POST['secretdrop_nonce']), 'secretdrop_clear_rate_limits') ||
            !current_user_can('manage_options')) {
            wp_die(esc_html__('Security check failed', 'secretdrop'));
        }

        // Clear rate limits
        SecretDrop_DB::clear_all_rate_limits();

        // Redirect back with success message
        wp_safe_redirect(add_query_arg(array(
            'page' => 'secretdrop',
            'tab' => 'rate_limiting',
            'message' => 'rate_limits_cleared'
        ), admin_url('options-general.php')));
        exit;
    }

    /**
     * Sanitize encryption key.
     */
    public function sanitize_encryption_key($value) {
        // Remove whitespace
        $value = trim($value);

        // Must be 64 character hex string
        if (!preg_match('/^[a-f0-9]{64}$/i', $value)) {
            add_settings_error(
                'secretdrop_encryption_key',
                'invalid_key',
                __('Encryption key must be a 64-character hexadecimal string', 'secretdrop')
            );
            // Return old value
            return get_option('secretdrop_encryption_key');
        }

        return $value;
    }

    /**
     * Sanitize checkbox value.
     */
    public function sanitize_checkbox($value) {
        return $value === '1' ? '1' : '0';
    }

    /**
     * Sanitize expiration time (convert hours to seconds).
     */
    public function sanitize_expiration_time($value) {
        // Convert to integer
        $hours = absint($value);

        // Minimum 1 hour
        if ($hours < 1) {
            add_settings_error(
                'secretdrop_expiration_time',
                'invalid_expiration',
                __('Expiration time must be at least 1 hour', 'secretdrop')
            );
            // Return default (24 hours in seconds)
            return 86400;
        }

        // Convert hours to seconds
        return $hours * 3600;
    }

    /**
     * Sanitize rate limit window (convert hours to seconds).
     */
    public function sanitize_rate_limit_window($value) {
        // Convert to integer
        $hours = absint($value);

        // Minimum 1 hour
        if ($hours < 1) {
            add_settings_error(
                'secretdrop_rate_limit_window',
                'invalid_window',
                __('Rate limit window must be at least 1 hour', 'secretdrop')
            );
            // Return default (1 hour in seconds)
            return 3600;
        }

        // Convert hours to seconds
        return $hours * 3600;
    }

    /**
     * Get statistics for display.
     */
    public static function get_statistics() {
        return SecretDrop_DB::get_statistics();
    }

    /**
     * Get rate limit records for display.
     */
    public static function get_rate_limit_records() {
        return SecretDrop_DB::get_rate_limit_records(20);
    }

    /**
     * Format time duration for display.
     */
    public static function format_duration($seconds) {
        if ($seconds < 60) {
            /* translators: %d: number of seconds */
            return sprintf(_n('%d second', '%d seconds', $seconds, 'secretdrop'), $seconds);
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            /* translators: %d: number of minutes */
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'secretdrop'), $minutes);
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            /* translators: %d: number of hours */
            return sprintf(_n('%d hour', '%d hours', $hours, 'secretdrop'), $hours);
        } else {
            $days = floor($seconds / 86400);
            /* translators: %d: number of days */
            return sprintf(_n('%d day', '%d days', $days, 'secretdrop'), $days);
        }
    }
}
