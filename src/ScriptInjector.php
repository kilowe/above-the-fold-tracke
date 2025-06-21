<?php

namespace AboveTheFold;

/**
 * Handles frontend script injection for tracking visible links.
 *
 * This class enqueues the main tracking script and injects necessary
 * configuration such as AJAX URL and nonce for secure communication.
 */
class ScriptInjector
{
        const SCRIPT_HANDLE = 'atf-visible-links';

        /**
         * Initialize hooks for script injection.
         */
        public static function init()
        {
                add_action('wp_enqueue_scripts', [self::class, 'enqueue_scripts']);
                add_filter('script_loader_tag', [self::class, 'add_defer_attribute'], 10, 2);
        }

        /**
         * Enqueue the tracking script only on the homepage or front page.
         */
        public static function enqueue_scripts()
        {
                if (is_admin()) return;
                if (!is_front_page() && !is_home()) return;

                // Optionally disable for admin users
                $disable_for_admins = get_option('atf_config_disable_on_login', true);
                if ($disable_for_admins && current_user_can('manage_options')) {
                        return;
                }

                $js_url = plugin_dir_url(dirname(__FILE__)) . 'assets/js/visible-links.js';

                wp_enqueue_script(
                        self::SCRIPT_HANDLE,
                        $js_url,
                        [],
                        '1.0',
                        true
                );

                // Pass configuration variables to the script
                wp_localize_script(
                        self::SCRIPT_HANDLE,
                        'atfConfig',
                        [
                                'ajax_url'  => admin_url('admin-ajax.php'),
                                'nonce'     => wp_create_nonce('atf_tracking_nonce'),
                                'max_links' => 100,
                                'debug'     => true
                        ]
                );
        }

        /**
         * Add defer attribute to the tracking script tag for non-blocking load.
         */
        public static function add_defer_attribute($tag, $handle)
        {
                if (self::SCRIPT_HANDLE === $handle) {
                        return str_replace(' src', ' defer src', $tag);
                }
                return $tag;
        }
}
