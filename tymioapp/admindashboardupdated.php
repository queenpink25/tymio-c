<?php
/* Template Name: Admin Dashboard */
get_header();

if (!current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix;

$total_mentors = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}mentors");
$total_students = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}students");
$total_classes = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}classes");
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}student_attendance");

$performance_patterns = $wpdb->get_results("SELECT s.name, COUNT(*) AS total, SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS presents FROM {$prefix}student_attendance a JOIN {$prefix}students s ON a.student_id = s.id GROUP BY a.student_id ORDER BY presents DESC LIMIT 10");

$mentor_filter = $_GET['mentor'] ?? '';
$class_filter = $_GET['class'] ?? '';
$date_filter = $_GET['date'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "WHERE 1=1";
if ($mentor_filter) $where .= $wpdb->prepare(" AND m.name = %s", $mentor_filter);
if ($class_filter) $where .= $wpdb->prepare(" AND c.name = %s", $class_filter);
if ($date_filter) $where .= $wpdb->prepare(" AND DATE(a.check_in_time) = %s", $date_filter);
if ($status_filter) $where .= $wpdb->prepare(" AND a.status = %s", $status_filter);

$logs = $wpdb->get_results("SELECT s.name AS student_name, m.name AS mentor_name, c.name AS class_name,
    a.check_in_time, a.check_out_time, a.status
    FROM {$prefix}student_attendance a
    JOIN {$prefix}students s ON a.student_id = s.id
    JOIN {$prefix}mentors m ON a.mentor_id = m.id
    JOIN {$prefix}classes c ON a.class_id = c.id
    $where
    ORDER BY a.check_in_time DESC");

if (isset($_GET['export_csv']) && current_user_can('administrator')) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_logs.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student', 'Mentor', 'Class', 'Check In', 'Check Out', 'Status']);
    foreach ($logs as $log) {
        fputcsv($output, [
            $log->student_name, $log->mentor_name, $log->class_name,
            $log->check_in_time, $log->check_out_time, $log->status
        ]);
    }
    fclose($output);
    exit;
}

// More features continue below...
?>
<!-- HTML part with Tailwind styled grid/flex layout and each section in its own container -->

<?php get_footer(); ?>
