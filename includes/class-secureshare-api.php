<?php
/**
 * REST API endpoints for SecureShare.
 *
 * Handles creation and retrieval of encrypted secrets via REST API.
 */

if (!defined('WPINC')) {
    die;
}

class SecureShare_API {

    /**
     * Constructor - registers REST API routes.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Create secret endpoint
        register_rest_route('secureshare/v1', '/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_secret'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'secret' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'validate_callback' => array($this, 'validate_secret')
                )
            )
        ));

        // Retrieve secret endpoint
        register_rest_route('secureshare/v1', '/retrieve/(?P<token>[a-f0-9]{32})', array(
            'methods' => 'GET',
            'callback' => array($this, 'retrieve_secret'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_token')
                )
            )
        ));
    }

    /**
     * Create a new secret.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response|WP_Error Response with secret URL or error.
     */
    public function create_secret($request) {
        // Verify nonce from header
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'secureshare_create')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'secureshare'),
                array('status' => 403)
            );
        }

        // Check rate limit
        $rate_limit_check = SecureShare_DB::check_rate_limit();
        if (is_wp_error($rate_limit_check)) {
            return new WP_Error(
                $rate_limit_check->get_error_code(),
                $rate_limit_check->get_error_message(),
                array('status' => 429)
            );
        }

        // Get and validate secret
        $secret = $request->get_param('secret');

        // Validate secret size
        $size_check = SecureShare_DB::validate_secret_size($secret);
        if (is_wp_error($size_check)) {
            return new WP_Error(
                $size_check->get_error_code(),
                $size_check->get_error_message(),
                array('status' => 400)
            );
        }

        // Encrypt the secret
        $encrypted_data = SecureShare_Encryption::encrypt($secret);
        if (is_wp_error($encrypted_data)) {
            return new WP_Error(
                $encrypted_data->get_error_code(),
                $encrypted_data->get_error_message(),
                array('status' => 500)
            );
        }

        // Store in database
        $store_result = SecureShare_DB::store_secret(
            $encrypted_data['token'],
            $encrypted_data['encrypted'],
            $encrypted_data['iv']
        );

        if (is_wp_error($store_result)) {
            return new WP_Error(
                $store_result->get_error_code(),
                $store_result->get_error_message(),
                array('status' => 500)
            );
        }

        // Generate the secret URL
        $secret_url = $this->generate_secret_url($encrypted_data['token']);

        // Return success response
        return new WP_REST_Response(array(
            'success' => true,
            'token' => $encrypted_data['token'],
            'url' => $secret_url,
            'expires_in' => intval(get_option('secureshare_expiration_time', 86400))
        ), 201);
    }

    /**
     * Retrieve a secret by token.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response|WP_Error Response with decrypted secret or error.
     */
    public function retrieve_secret($request) {
        $token = $request->get_param('token');

        // Validate token format
        if (!SecureShare_Encryption::is_valid_token($token)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'secureshare'),
                array('status' => 400)
            );
        }

        // Retrieve from database
        $secret_data = SecureShare_DB::retrieve_secret($token);
        if (is_wp_error($secret_data)) {
            return new WP_Error(
                $secret_data->get_error_code(),
                $secret_data->get_error_message(),
                array('status' => 404)
            );
        }

        // Decrypt the secret
        $decrypted = SecureShare_Encryption::decrypt(
            $secret_data['encrypted'],
            $secret_data['iv']
        );

        if (is_wp_error($decrypted)) {
            return new WP_Error(
                $decrypted->get_error_code(),
                $decrypted->get_error_message(),
                array('status' => 500)
            );
        }

        // Calculate time remaining
        $expires_at = strtotime($secret_data['expires_at']);
        $time_remaining = $expires_at - current_time('timestamp');

        // Return success response
        return new WP_REST_Response(array(
            'success' => true,
            'secret' => $decrypted,
            'created_at' => $secret_data['created_at'],
            'expires_at' => $secret_data['expires_at'],
            'time_remaining' => max(0, $time_remaining)
        ), 200);
    }

    /**
     * Validate secret parameter.
     *
     * @param string $value The secret value to validate.
     * @param WP_REST_Request $request The REST API request.
     * @param string $param The parameter name.
     * @return bool True if valid.
     */
    public function validate_secret($value, $request, $param) {
        if (empty($value)) {
            return new WP_Error(
                'empty_secret',
                __('Secret cannot be empty', 'secureshare'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate token parameter.
     *
     * @param string $value The token value to validate.
     * @param WP_REST_Request $request The REST API request.
     * @param string $param The parameter name.
     * @return bool True if valid.
     */
    public function validate_token($value, $request, $param) {
        if (!SecureShare_Encryption::is_valid_token($value)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'secureshare'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Generate a secret URL for a given token.
     *
     * This tries to find a page with the [secureshare] or [secureshare_view] shortcode.
     * If found, appends the token as a query parameter.
     * If not found, returns a relative URL that can be used with the shortcode.
     *
     * @param string $token The secret token.
     * @return string The secret URL.
     */
    private function generate_secret_url($token) {
        // Try to find a page with the secureshare shortcode
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            's' => '[secureshare'
        ));

        if (!empty($pages)) {
            // Use the first page found
            $page_url = get_permalink($pages[0]->ID);
            return add_query_arg('token', $token, $page_url);
        }

        // Fallback: construct URL manually
        return home_url('?secureshare_token=' . $token);
    }
}
