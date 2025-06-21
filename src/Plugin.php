<?php

namespace AboveTheFold;

/**
 * Main plugin class responsible for initializing all core components.
 *
 * This class bootstraps the plugin logic, including admin dashboard,
 * script injection, REST API endpoints, and AJAX handlers.
 */
class Plugin
{
        /**
         * Initialize all plugin components and hooks.
         */
        public static function init()
        {
                // Initialize scheduled cleanup, script injection, and admin interface
                Cleanup::init();
                ScriptInjector::init();
                AdminDashboard::init();

                // Register the shortcode for testing
                AdminDashboard::add_test_shortcode();

                // Register REST API routes
                add_action('rest_api_init', [API::class, 'init']);

                // Register AJAX handlers for authenticated and unauthenticated users
                add_action('wp_ajax_atf_track', [API::class, 'handle_ajax_tracking']);
                add_action('wp_ajax_nopriv_atf_track', [API::class, 'handle_ajax_tracking']);

                // Register admin script localization
                add_action('admin_init', [self::class, 'localize_admin_scripts']);

                // (Optional) Log all registered REST API routes for debugging
                add_action('rest_api_init', function () {
                        error_log('[ATF] Registered REST routes:');
                        foreach (rest_get_server()->get_routes() as $route => $details) {
                                error_log(' - ' . $route);
                        }
                }, 999);
        }

        /**
         * Localize admin JavaScript with nonce and AJAX URL.
         */
        public static function localize_admin_scripts()
        {
                wp_localize_script('atf-admin-script', 'atfAdmin', [
                        'nonce' => wp_create_nonce('atf_admin_nonce'),
                        'ajax_url' => admin_url('admin-ajax.php')
                ]);
        }
}
