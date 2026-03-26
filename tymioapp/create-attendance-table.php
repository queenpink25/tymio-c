<?php
/**
 * Plugin Name: Create Attendance Table
 */

// Hook on plugin load to run once
function create_attendance_table_on_load() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'attendance_logs';
    $charset_collate = $wpdb->get_charset_collate();

    // Only create table if not exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
create_attendance_table_on_load();
