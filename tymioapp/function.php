<?php
// Child theme functions.php example
add_action( 'wp_enqueue_scripts', function() {
    // Enqueue styles or scripts here
});








function tymio_custom_cookie_lifetime( $expirein ) {
    return 60 * 60 * 24 * 14; // 14 days
}
add_filter( 'auth_cookie_expiration', 'tymio_custom_cookie_lifetime' );

function tymio_welcome_user() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        return '✅ Welcome back, <strong>' . esc_html($current_user->display_name) . '</strong>!';
    } else {
        return '✅ Welcome, Guest!';
    }
}
add_shortcode('tymio_welcome', 'tymio_welcome_user');

function tymio_create_attendance_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_attendance';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        class_date date NOT NULL,
        class_name varchar(100) NOT NULL,
        check_in_time datetime DEFAULT NULL,
        check_out_time datetime DEFAULT NULL,
        status varchar(20) NOT NULL,
        notes text DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY class_date (class_date)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

add_action('after_switch_theme', 'tymio_create_attendance_table');

add_action('wp_ajax_tymio_check_in', 'tymio_check_in');
add_action('wp_ajax_nopriv_tymio_check_in', 'tymio_check_in');
function tymio_check_in() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }
    $user_id = get_current_user_id();
    $data = json_decode(file_get_contents('php://input'), true);
    $class_name = sanitize_text_field($data['class_name']);
    $class_date = date('Y-m-d');
    $check_in_time = current_time('mysql');

    global $wpdb;
    $table = $wpdb->prefix . 'student_attendance';

    // Check if record exists today for user and class
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d AND class_date=%s AND class_name=%s", $user_id, $class_date, $class_name));

    if ($existing) {
        wp_send_json_error(['message' => 'You have already checked in for this class today.']);
    }

    // Insert new record with status 'present' by default
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'class_date' => $class_date,
        'class_name' => $class_name,
        'check_in_time' => $check_in_time,
        'status' => 'present'
    ]);

    wp_send_json_success(['message' => 'Checked in at ' . $check_in_time]);
}

add_action('wp_ajax_tymio_check_out', 'tymio_check_out');
add_action('wp_ajax_nopriv_tymio_check_out', 'tymio_check_out');
function tymio_check_out() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }
    $user_id = get_current_user_id();
    $data = json_decode(file_get_contents('php://input'), true);
    $class_name = sanitize_text_field($data['class_name']);
    $class_date = date('Y-m-d');
    $check_out_time = current_time('mysql');

    global $wpdb;
    $table = $wpdb->prefix . 'student_attendance';

    // Get record for today
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d AND class_date=%s AND class_name=%s", $user_id, $class_date, $class_name));

    if (!$existing) {
        wp_send_json_error(['message' => 'No check-in found for today. Please check in first.']);
    }

    if ($existing->check_out_time) {
        wp_send_json_error(['message' => 'You have already checked out.']);
    }

    // Update with check_out_time
    $wpdb->update($table, ['check_out_time' => $check_out_time], ['id' => $existing->id]);

    wp_send_json_success(['message' => 'Checked out at ' . $check_out_time]);
}

