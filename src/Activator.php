<?php

namespace AboveTheFold;

/**
 * Handles plugin activation logic.
 * 
 * This class is called when the plugin is activated and performs
 * all necessary setup tasks such as database initialization and
 * cleanup scheduling.
 */
class Activator
{
        /**
         * Runs the activation routine for the plugin.
         *
         * This includes:
         * - Creating or updating database tables
         * - Scheduling periodic cleanup tasks
         */
        public static function activate()
        {
                // Set up or update database schema required for the plugin
                Database::install();

                // Schedule automatic cleanup of old tracking data
                Cleanup::init();
        }
}
