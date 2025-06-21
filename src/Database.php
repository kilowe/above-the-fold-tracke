<?php

namespace AboveTheFold;

/**
 * Handles all database operations related to the Above the Fold Tracker plugin.
 *
 * This includes installation, data insertion, cleanup, and retrieval of tracking records.
 */
class Database
{
        /**
         * Name of the custom table (without prefix).
         */
        public static $table_name = 'above_fold_links';

        /**
         * Create the custom database table during plugin activation.
         *
         * Uses dbDelta for safe creation and future migrations.
         */
        public static function install()
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;
                $charset_collate = $wpdb->get_charset_collate();

                $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            screen VARCHAR(20) NOT NULL,
            links TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);

                // Store DB version for future upgrades
                add_option('atf_db_version', '1.0');
        }

        /**
         * Insert a new tracking entry into the database.
         *
         * @param string $screen Screen resolution (e.g. 1920x1080)
         * @param array $links Array of link objects with 'url' and 'text'
         * @return int Inserted row ID
         */
        public static function insert_data($screen, $links)
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                // Sanitize links
                $sanitized_links = array_map(function ($link) {
                        return [
                                'url' => esc_url_raw($link['url'] ?? ''),
                                'text' => sanitize_text_field($link['text'] ?? '')
                        ];
                }, $links);

                $wpdb->insert(
                        $table_name,
                        [
                                'screen' => sanitize_text_field($screen),
                                'links' => wp_json_encode($sanitized_links)
                        ],
                        ['%s', '%s']
                );

                return $wpdb->insert_id;
        }

        /**
         * Delete tracking entries older than a specified number of days.
         *
         * @param int $days Number of days to retain data
         * @return int Number of rows deleted
         */
        public static function purge_old_data($days = 7)
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                $wpdb->query(
                        $wpdb->prepare(
                                "DELETE FROM $table_name 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                 LIMIT 1000",
                                $days
                        )
                );

                return $wpdb->rows_affected;
        }

        /**
         * Get the total number of stored tracking entries.
         *
         * @return int Total count
         */
        public static function get_total_count()
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                return (int) $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        }

        /**
         * Retrieve a single tracking entry by ID.
         *
         * @param int $id Entry ID
         * @return array|null Associative array of entry data or null if not found
         */
        public static function get_single_entry($id)
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                $result = $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
                        ARRAY_A
                );

                if ($result) {
                        $result['links'] = json_decode($result['links'], true);
                }

                return $result;
        }

        /**
         * Get a paginated list of recent tracking entries.
         *
         * @param int $limit Number of entries to return
         * @param int $offset Offset for pagination
         * @return array List of recent entries
         */
        public static function get_recent_data($limit = 20, $offset = 0)
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                $results = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT * FROM $table_name 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                                $limit,
                                $offset
                        ),
                        ARRAY_A
                );

                return array_map(function ($row) {
                        $row['links'] = json_decode($row['links'], true);
                        return $row;
                }, $results);
        }

        /**
         * Drop the custom table and delete related options during plugin uninstall.
         */
        public static function uninstall()
        {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$table_name;

                // Extra safety check before dropping the table
                if (!empty($wpdb->prefix)) {
                        $wpdb->query("DROP TABLE IF EXISTS $table_name");
                }

                delete_option('atf_db_version');
        }
}
