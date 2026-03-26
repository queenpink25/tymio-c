<?php
/**
 * Plugin Name:Attendance Actions
 * Description: MU plugin to handle student check-in/check-out
 */

add_action('init', function() {
    if (!is_user_logged_in()) return;

    if (isset($_POST['attendance_action'])) {
        global $wpdb;

        $user_id = get_current_user_id();
        $now = current_time('mysql');

        if ($_POST['attendance_action'] === 'check_in') {
            $wpdb->insert('wp_attendance_logs', [
                'user_id' => $user_id,
                'check_in_time' => $now
            ]);
        }

        if ($_POST['attendance_action'] === 'check_out') {
            $wpdb->update(
                'wp_attendance_logs',
                ['check_out_time' => $now],
                ['user_id' => $user_id],
                ['%s'],
                ['%d']
            );
        }
    }
});
register_activation_hook( __FILE__, 'attendance_tracker_install' );

function attendance_tracker_install() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_name = $wpdb->prefix . 'attendance_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      check_in_time DATETIME DEFAULT NULL,
      check_out_time DATETIME DEFAULT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
}

// Since MU plugins don't fire register_activation_hook, call install manually
attendance_tracker_install();

// Your attendance handling code here, e.g.
add_action('init', function () {
    // your check-in/out logic...
});