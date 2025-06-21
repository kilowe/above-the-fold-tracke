<?php

namespace AboveTheFold;

/**
 * Handles automated cleanup operations for the Above the Fold Tracker plugin.
 *
 * This class schedules a daily cleanup task to remove outdated tracking records
 * from the database and provides logic to clear the scheduled task when the plugin is deactivated.
 */
class Cleanup
{
        /**
         * Initialize the daily cleanup schedule.
         *
         * Registers a daily cron event to purge old data, if not already scheduled.
         */
        public static function init()
        {
                // Schedule daily cleanup task if not already scheduled
                if (!wp_next_scheduled('atf_daily_cleanup')) {
                        wp_schedule_event(time(), 'daily', 'atf_daily_cleanup');
                }

                add_action('atf_daily_cleanup', [self::class, 'purge_old_data']);
        }

        /**
         * Delete old tracking data from the database.
         *
         * Removes entries older than a defined number of days (default: 7).
         *
         * @return int|false Number of rows deleted, or false on failure
         */
        public static function purge_old_data()
        {
                // Retain data for 7 days by default
                return Database::purge_old_data(7);
        }

        /**
         * Clear the scheduled cleanup task on plugin deactivation.
         *
         * Ensures no unnecessary cron jobs remain when the plugin is disabled.
         */
        public static function deactivate()
        {
                wp_clear_scheduled_hook('atf_daily_cleanup');
        }
}
