<?php
/**
 * REST API endpoints for SecretDrop.
 *
 * Handles creation and retrieval of encrypted secrets via REST API.
 */

if (!defined('WPINC')) {
    die;
}

class SecretDrop_API {

    /**
     * Option name holding the SHA-256 hash of the API key. The key itself is never stored.
     */
    const API_KEY_HASH_OPTION = 'secretdrop_api_key_hash';

    /**
     * Option name holding the Unix timestamp of when the API key was created.
     */
    const API_KEY_CREATED_OPTION = 'secretdrop_api_key_created';

    /**
     * Constructor - registers REST API routes.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_post_dispatch', array($this, 'add_no_store_header'), 10, 3);
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Create secret endpoint
        //
        // Permission: Intentionally public.
        // Anyone on a page with the [secretdrop] shortcode can create secrets.
        // Protected by:
        //   - WP REST nonce (X-WP-Nonce header) for CSRF protection
        //   - IP-based rate limiting (configurable in admin)
        //   - Input validation and sanitization on all parameters
        // An optional API key (set in admin) lets trusted clients skip the
        // rate limit. A key that is sent but does not match is rejected (401).
        register_rest_route('secretdrop/v1', '/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_secret'),
            'permission_callback' => array($this, 'check_create_permission'),
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
        //
        // Permission: Intentionally public (__return_true).
        // Knowledge of the token IS the authorization: tokens are 32-character
        // cryptographically random hex strings (128-bit entropy), making them
        // unguessable. No sensitive data is exposed without a valid token.
        register_rest_route('secretdrop/v1', '/retrieve/(?P<token>[a-f0-9]{32})', array(
            'methods' => 'GET',
            'callback' => array($this, 'retrieve_secret'),
            'permission_callback' => '__return_true',
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
     * This is a public endpoint by design: anyone on the page can create secrets.
     * CSRF protection is provided by the WP REST nonce sent via X-WP-Nonce header
     * from the frontend. Abuse protection is provided by IP-based rate limiting.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response|WP_Error Response with secret URL or error.
     */
    public function create_secret($request) {
        // Check rate limit (skipped for requests with a valid API key)
        if ('valid' !== self::get_api_key_status($request)) {
            $rate_limit_check = SecretDrop_DB::check_rate_limit();
            if (is_wp_error($rate_limit_check)) {
                return new WP_Error(
                    $rate_limit_check->get_error_code(),
                    $rate_limit_check->get_error_message(),
                    array('status' => 429)
                );
            }
        }

        // Get and validate secret
        $secret = $request->get_param('secret');

        // Validate secret size
        $size_check = SecretDrop_DB::validate_secret_size($secret);
        if (is_wp_error($size_check)) {
            return new WP_Error(
                $size_check->get_error_code(),
                $size_check->get_error_message(),
                array('status' => 400)
            );
        }

        // Encrypt the secret
        $encrypted_data = SecretDrop_Encryption::encrypt($secret);
        if (is_wp_error($encrypted_data)) {
            return new WP_Error(
                $encrypted_data->get_error_code(),
                $encrypted_data->get_error_message(),
                array('status' => 500)
            );
        }

        // Store in database
        $store_result = SecretDrop_DB::store_secret(
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
            'expires_in' => intval(get_option('secretdrop_expiration_time', 86400))
        ), 201);
    }

    /**
     * Permission check for the create endpoint.
     *
     * The endpoint is public, so requests without an API key are allowed
     * (and rate limited in create_secret). A request that sends an API key
     * must send the correct one.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return true|WP_Error True if allowed, WP_Error (401) for a wrong key.
     */
    public function check_create_permission($request) {
        if ('invalid' === self::get_api_key_status($request)) {
            return new WP_Error(
                'secretdrop_invalid_api_key',
                __('Invalid API key.', 'secretdrop'),
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Add Cache-Control: no-store to every create endpoint response.
     *
     * @param WP_HTTP_Response $response The response object.
     * @param WP_REST_Server   $server   The REST server.
     * @param WP_REST_Request  $request  The request.
     * @return WP_HTTP_Response The response with the header added.
     */
    public function add_no_store_header($response, $server, $request) {
        if ($response instanceof WP_HTTP_Response &&
            '/secretdrop/v1/create' === untrailingslashit($request->get_route())) {
            $response->header('Cache-Control', 'no-store');
        }

        return $response;
    }

    /**
     * Read the API key sent with a request.
     *
     * Checks "Authorization: Bearer <key>" first, then the X-SecretDrop-Key
     * header (Apache often strips Authorization before PHP sees it).
     * Other Authorization schemes (for example Basic auth on a staging site)
     * are ignored.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return string The key, or an empty string if none was sent.
     */
    public static function get_request_api_key($request) {
        $authorization = $request->get_header('authorization');
        if (is_string($authorization) && preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authorization, $matches)) {
            return $matches[1];
        }

        $header_key = $request->get_header('x_secretdrop_key');
        if (is_string($header_key)) {
            return trim($header_key);
        }

        return '';
    }

    /**
     * Check the API key sent with a request against the stored hash.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return string 'none' if no key was sent, 'valid' or 'invalid' otherwise.
     */
    public static function get_api_key_status($request) {
        $provided = self::get_request_api_key($request);
        if ('' === $provided) {
            return 'none';
        }

        $stored_hash = get_option(self::API_KEY_HASH_OPTION, '');
        if (!is_string($stored_hash) || '' === $stored_hash) {
            return 'invalid';
        }

        return hash_equals($stored_hash, self::hash_api_key($provided)) ? 'valid' : 'invalid';
    }

    /**
     * Generate a new random API key (64 hex characters, 256 bits).
     *
     * @return string The plain API key.
     */
    public static function generate_api_key() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash an API key for storage and comparison.
     *
     * @param string $key The plain API key.
     * @return string SHA-256 hex digest.
     */
    public static function hash_api_key($key) {
        return hash('sha256', $key);
    }

    /**
     * Whether an API key is currently configured.
     *
     * @return bool
     */
    public static function has_api_key() {
        $stored_hash = get_option(self::API_KEY_HASH_OPTION, '');
        return is_string($stored_hash) && '' !== $stored_hash;
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
        if (!SecretDrop_Encryption::is_valid_token($token)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'secretdrop'),
                array('status' => 400)
            );
        }

        // Retrieve from database
        $secret_data = SecretDrop_DB::retrieve_secret($token);
        if (is_wp_error($secret_data)) {
            return new WP_Error(
                $secret_data->get_error_code(),
                $secret_data->get_error_message(),
                array('status' => 404)
            );
        }

        // Decrypt the secret
        $decrypted = SecretDrop_Encryption::decrypt(
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
                __('Secret cannot be empty', 'secretdrop'),
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
        if (!SecretDrop_Encryption::is_valid_token($value)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'secretdrop'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Generate a secret URL for a given token.
     *
     * This tries to find a page with the [secretdrop] shortcode.
     * If found, appends the token as a query parameter.
     * If not found, returns a relative URL that can be used with the shortcode.
     *
     * @param string $token The secret token.
     * @return string The secret URL.
     */
    private function generate_secret_url($token) {
        // Try to find a page with the secretdrop shortcode
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            's' => '[secretdrop'
        ));

        if (!empty($pages)) {
            // Use the first page found
            $page_url = get_permalink($pages[0]->ID);
            return add_query_arg('token', $token, $page_url);
        }

        // Fallback: construct URL manually
        return home_url('?secretdrop_token=' . $token);
    }
}
