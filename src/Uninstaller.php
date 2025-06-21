<?php

namespace AboveTheFold;

/**
 * Handles plugin uninstallation and cleanup of stored data and options.
 */
class Uninstaller
{
        /**
         * Execute all uninstall procedures.
         *
         * This method deletes the database table and removes all configuration options.
         */
        public static function uninstall()
        {
                // Remove plugin data from the database
                Database::uninstall();

                // Remove plugin-related options
                delete_option('atf_config_disable_on_login');
                delete_option('atf_config_data_retention');
                delete_option('atf_plugin_version');
        }
}
