<?php
/**
 * Shortcode handlers for SecureShare.
 *
 * Registers and handles shortcodes for displaying secret creation
 * and viewing interfaces.
 */

if (!defined('WPINC')) {
    die;
}

class SecureShare_Shortcodes {

    /**
     * Constructor - registers shortcodes.
     */
    public function __construct() {
        add_shortcode('secureshare', array($this, 'smart_shortcode'));
        add_shortcode('secureshare_create', array($this, 'create_shortcode'));
        add_shortcode('secureshare_view', array($this, 'view_shortcode'));

        // Enqueue assets when shortcode is detected
        add_filter('the_content', array($this, 'detect_shortcodes'), 1);
    }

    /**
     * Detect if any SecureShare shortcodes are present and enqueue assets.
     *
     * @param string $content The post content.
     * @return string The unmodified content.
     */
    public function detect_shortcodes($content) {
        if (has_shortcode($content, 'secureshare') ||
            has_shortcode($content, 'secureshare_create') ||
            has_shortcode($content, 'secureshare_view')) {
            $this->enqueue_assets();
        }

        return $content;
    }

    /**
     * Enqueue frontend assets (CSS and JavaScript).
     */
    private function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'secureshare-styles',
            SECURESHARE_PLUGIN_URL . 'assets/css/secureshare.css',
            array(),
            SECURESHARE_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'secureshare-script',
            SECURESHARE_PLUGIN_URL . 'assets/js/secureshare.js',
            array(),
            SECURESHARE_VERSION,
            true
        );

        // Localize script with REST API data
        wp_localize_script('secureshare-script', 'secureshareData', array(
            'restUrl' => rest_url('secureshare/v1/'),
            'nonce' => wp_create_nonce('secureshare_create'),
            'maxSecretSize' => intval(get_option('secureshare_max_secret_size', 2000)),
            'strings' => array(
                'copySuccess' => __('Copied to clipboard!', 'secureshare'),
                'copyError' => __('Failed to copy', 'secureshare'),
                'creating' => __('Creating secure link...', 'secureshare'),
                'error' => __('Error', 'secureshare')
            )
        ));

        // Add custom CSS if configured
        $custom_css = get_option('secureshare_custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style('secureshare-styles', $custom_css);
        }
    }

    /**
     * Smart shortcode handler - detects context and displays appropriate view.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered shortcode output.
     */
    public function smart_shortcode($atts) {
        // Check for token in URL parameters
        $token = $this->get_token_from_request();

        if ($token) {
            return $this->render_view_template($token);
        } else {
            return $this->render_create_template();
        }
    }

    /**
     * Create form shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered shortcode output.
     */
    public function create_shortcode($atts) {
        return $this->render_create_template();
    }

    /**
     * View secret shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered shortcode output.
     */
    public function view_shortcode($atts) {
        // Check for token parameter in shortcode attributes or URL
        $token = isset($atts['token']) ? $atts['token'] : $this->get_token_from_request();

        if (!$token) {
            return '<div class="secureshare-error">' .
                   esc_html__('No secret token provided', 'secureshare') .
                   '</div>';
        }

        return $this->render_view_template($token);
    }

    /**
     * Render the create secret template.
     *
     * @return string The rendered template.
     */
    private function render_create_template() {
        ob_start();

        $data = array(
            'max_secret_size' => intval(get_option('secureshare_max_secret_size', 2000)),
            'expiration_time' => intval(get_option('secureshare_expiration_time', 86400))
        );

        // Load template
        $template_path = SECURESHARE_PLUGIN_DIR . 'templates/shortcode-create.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="secureshare-error">' .
                 esc_html__('Template not found', 'secureshare') .
                 '</div>';
        }

        return ob_get_clean();
    }

    /**
     * Render the view secret template.
     *
     * @param string $token The secret token.
     * @return string The rendered template.
     */
    private function render_view_template($token) {
        // Validate token format
        if (!SecureShare_Encryption::is_valid_token($token)) {
            return '<div class="secureshare-error">' .
                   esc_html__('Invalid secret token format', 'secureshare') .
                   '</div>';
        }

        // Retrieve secret from database
        $secret_data = SecureShare_DB::retrieve_secret($token);

        if (is_wp_error($secret_data)) {
            return '<div class="secureshare-error">' .
                   '<h2>' . esc_html__('Secret Not Found', 'secureshare') . '</h2>' .
                   '<p>' . esc_html($secret_data->get_error_message()) . '</p>' .
                   '</div>';
        }

        // Decrypt the secret
        $decrypted = SecureShare_Encryption::decrypt(
            $secret_data['encrypted'],
            $secret_data['iv']
        );

        if (is_wp_error($decrypted)) {
            return '<div class="secureshare-error">' .
                   '<h2>' . esc_html__('Decryption Failed', 'secureshare') . '</h2>' .
                   '<p>' . esc_html($decrypted->get_error_message()) . '</p>' .
                   '</div>';
        }

        ob_start();

        $data = array(
            'secret' => $decrypted,
            'created_at' => $secret_data['created_at'],
            'expires_at' => $secret_data['expires_at'],
            'token' => $token
        );

        // Load template
        $template_path = SECURESHARE_PLUGIN_DIR . 'templates/shortcode-view.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="secureshare-error">' .
                 esc_html__('Template not found', 'secureshare') .
                 '</div>';
        }

        return ob_get_clean();
    }

    /**
     * Get token from request (query string or custom parameter).
     *
     * @return string|null The token if found, null otherwise.
     */
    private function get_token_from_request() {
        // Check standard token parameter
        if (isset($_GET['token'])) {
            return sanitize_text_field($_GET['token']);
        }

        // Check secureshare_token parameter (fallback)
        if (isset($_GET['secureshare_token'])) {
            return sanitize_text_field($_GET['secureshare_token']);
        }

        return null;
    }
}
