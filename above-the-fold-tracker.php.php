<?php

/**
 * Plugin Name: Above the Fold Tracker
 * Description: Tracks links visible on initial page load for UX optimization
 * Version: 1.0
 * Author: Your Name
 * Text Domain: atf-tracker
 */

defined('ABSPATH') or die;

// Composer autoload for class loading
require_once __DIR__ . '/vendor/autoload.php';

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, [AboveTheFold\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [AboveTheFold\Deactivator::class, 'deactivate']);
register_uninstall_hook(__FILE__, [AboveTheFold\Uninstaller::class, 'uninstall']);

// Plugin initialization after all plugins are loaded
add_action('plugins_loaded', [AboveTheFold\Plugin::class, 'init']);

/**
 * Debug helper: Show only ATF-specific REST routes via ?debug_atf_routes=1
 */
add_action('parse_request', function ($wp) {
        if (isset($_GET['debug_atf_routes'])) {
                if (!function_exists('rest_get_server')) {
                        wp_die('REST API not available');
                }

                // Filter only routes related to /abovefold/
                $atf_routes = [];
                foreach (rest_get_server()->get_routes() as $route => $handlers) {
                        if (strpos($route, '/abovefold/') === 0) {
                                $atf_routes[$route] = $handlers;
                        }
                }

                echo '<pre>';
                if (empty($atf_routes)) {
                        echo "No ATF routes found.\n";
                        echo "Available routes:\n";
                        echo implode("\n", array_keys(rest_get_server()->get_routes()));
                } else {
                        print_r($atf_routes);
                }
                echo '</pre>';
                exit;
        }
});

/**
 * Utility function: Convert a callback to a readable string
 */
function callback_to_string($callback)
{
        if (is_string($callback)) {
                return $callback;
        }

        if (is_array($callback)) {
                $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
                return "$class::{$callback[1]}";
        }

        if ($callback instanceof Closure) {
                return 'Closure';
        }

        return 'Unknown function';
}

/**
 * Full REST route debug: Show all registered routes via ?debug_routes=1
 */
add_action('init', function () {
        if (isset($_GET['debug_routes'])) {
                echo '<pre>';
                print_r(rest_get_server()->get_routes());
                echo '</pre>';
                exit;
        }
});

/**
 * Duplicate debug check (optional legacy fallback)
 */
add_action('init', function () {
        if (isset($_GET['debug_atf_routes'])) {
                if (function_exists('rest_get_server')) {
                        echo '<pre>Registered REST Routes:';
                        print_r(rest_get_server()->get_routes());
                        echo '</pre>';
                        exit;
                } else {
                        echo 'REST API not available';
                        exit;
                }
        }
});
