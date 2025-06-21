<?php

namespace AboveTheFold;

class API
{
        const ROUTE_NAMESPACE = 'abovefold/v1';
        const ENDPOINT = '/track';

        /**
         * Initialize the REST API
         */
        public static function init()
        {
                // Only route registration here
                add_action('rest_api_init', [self::class, 'register_routes']);
        }

        public static function handle_ajax_tracking()
        {
                $nonce = $_POST['nonce'] ?? '';

                if (defined('ATF_TEST_MODE') && ATF_TEST_MODE) {
                        error_log('ATF Notice: Nonce validation bypassed in test mode.');
                } else {
                        if (!wp_verify_nonce($nonce, 'atf_tracking_nonce')) {
                                wp_send_json_error('Nonce verification failed', 403);
                        }
                }

                $data = $_POST;
                $screen = sanitize_text_field($data['screen'] ?? '');
                $links = $data['links'] ?? [];

                // Fix: Decode only if it's a string
                if (is_string($links)) {
                        $links = json_decode(stripslashes($links), true);
                }

                // Validation
                if (
                        !self::validate_screen($screen) ||
                        !self::validate_links($links)
                ) {
                        // Log for debugging
                        error_log('[ATF] Validation failed during AJAX tracking');
                        error_log('Screen: ' . $screen);
                        error_log('Links: ' . print_r($links, true));

                        wp_send_json_error('Invalid data received', 400);
                }

                try {
                        $id = Database::insert_data($screen, $links);
                        wp_send_json_success(['inserted' => $id], 200);
                } catch (\Exception $e) {
                        wp_send_json_error('Server error: ' . $e->getMessage(), 500);
                }
        }

        /**
         * Register custom REST API routes
         */
        public static function register_routes()
        {
                // Main REST route
                register_rest_route(
                        self::ROUTE_NAMESPACE,
                        self::ENDPOINT,
                        [
                                'methods' => 'POST',
                                'callback' => [self::class, 'handle_tracking_request'],
                                'permission_callback' => '__return_true',
                                'args' => [
                                        'screen' => [
                                                'required' => true,
                                                'validate_callback' => [self::class, 'validate_screen'],
                                                'sanitize_callback' => 'sanitize_text_field'
                                        ],
                                        'links' => [
                                                'required' => true,
                                                'validate_callback' => [self::class, 'validate_links'],
                                                'sanitize_callback' => [self::class, 'sanitize_links']
                                        ],
                                        'nonce' => [
                                                'required' => true,
                                                'validate_callback' => [self::class, 'validate_nonce']
                                        ]
                                ]
                        ]
                );

                // Fallback in case REST server fails to auto-register
                global $wp_rest_server;
                if ($wp_rest_server) {
                        $wp_rest_server->register_route(
                                self::ROUTE_NAMESPACE,
                                self::ENDPOINT,
                                [
                                        'methods' => 'POST',
                                        'callback' => [self::class, 'handle_tracking_request'],
                                        'permission_callback' => '__return_true'
                                ]
                        );
                }
        }

        /**
         * Check user permissions (public route)
         */
        public static function permission_check()
        {
                return true;
        }

        /**
         * Validate screen format (e.g., '1920x1080')
         */
        public static function validate_screen($screen)
        {
                return preg_match('/^\d{3,5}x\d{3,5}$/', $screen);
        }

        /**
         * Validate the structure of the links array
         */
        public static function validate_links($links)
        {
                // Accept JSON strings decoded by REST
                if (is_string($links)) {
                        $links = json_decode($links, true);
                }

                if (!is_array($links) || count($links) > 100) {
                        return false;
                }

                foreach ($links as $link) {
                        if (!isset($link['url']) || !filter_var($link['url'], FILTER_VALIDATE_URL)) {
                                return false;
                        }
                }

                return true;
        }

        /**
         * Sanitize each link entry
         */
        public static function sanitize_links($links)
        {
                return array_map(function ($link) {
                        return [
                                'url' => esc_url_raw($link['url'], ['http', 'https']),
                                'text' => sanitize_text_field(substr($link['text'] ?? '', 0, 200))
                        ];
                }, $links);
        }

        /**
         * Validate nonce to prevent CSRF attacks
         */
        public static function validate_nonce($nonce)
        {
                return wp_verify_nonce($nonce, 'atf_tracking_nonce');
        }

        /**
         * Handle the tracking request for REST and fallback AJAX
         */
        public static function handle_tracking_request($request = null)
        {
                // Handle both REST and AJAX request formats
                $data = ($request instanceof \WP_REST_Request) ?
                        $request->get_params() :
                        $_POST;

                $screen = sanitize_text_field($data['screen'] ?? '');
                $links = $data['links'] ?? [];

                if (!self::validate_screen($screen) || !self::validate_links($links)) {
                        return new \WP_REST_Response([
                                'status' => 'error',
                                'message' => 'Invalid data'
                        ], 400);
                }

                try {
                        $result = Database::insert_data($screen, $links);
                        return new \WP_REST_Response([
                                'status' => 'success',
                                'inserted' => $result
                        ], 200);
                } catch (\Exception $e) {
                        error_log('[ATF] Error inserting tracking data: ' . $e->getMessage());
                        return new \WP_REST_Response([
                                'status' => 'error',
                                'message' => 'Internal server error'
                        ], 500);
                }
        }
}
