<?php
/**
 * Plugin Name: Attendance actions
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
