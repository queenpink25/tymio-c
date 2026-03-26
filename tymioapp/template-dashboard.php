<?php
/*
Template Name: Tymio Mentor Dashboard
*/

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('mentor', $current_user->roles)) {
    echo '<p class="text-red-600 font-bold">Access denied. You must be a mentor to view this page.</p>';
    get_footer();
    exit;
}

global $wpdb;
$mentor_id = $current_user->ID;

// Tables (adjust names as per your DB schema)
$attendance_table = $wpdb->prefix . 'student_attendance';
$classes_table = $wpdb->prefix . 'classes';
$students_table = $wpdb->prefix . 'users'; // assuming students are WP users with role=student
$absentee_alerts_table = $wpdb->prefix . 'absentee_alerts';

// Helper function to get today's date string
$today = date('Y-m-d');

// Get Mentor’s classes scheduled today
$classes = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $classes_table WHERE mentor_id = %d AND class_date = %s ORDER BY class_start_time ASC",
        $mentor_id,
        $today
    )
);

// Handle manual check-in POST
if (isset($_POST['check_in']) && isset($_POST['class_id']) && isset($_POST['student_id'])) {
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);

    // Insert or update attendance record for this student/class/date
    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $attendance_table WHERE class_id=%d AND student_id=%d AND attendance_date=%s",
            $class_id,
            $student_id,
            $today
        )
    );

    if ($existing) {
        // Update status to present
        $wpdb->update(
            $attendance_table,
            ['status' => 'present', 'check_in_time' => current_time('mysql')],
            ['id' => $existing]
        );
    } else {
        // Insert new attendance
        $wpdb->insert(
            $attendance_table,
            [
                'class_id' => $class_id,
                'student_id' => $student_id,
                'attendance_date' => $today,
                'status' => 'present',
                'check_in_time' => current_time('mysql'),
            ]
        );
    }
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">Check-in recorded successfully.</div>';
}

// Handle mark absentee and send alert POST
if (isset($_POST['mark_absent']) && isset($_POST['class_id']) && isset($_POST['student_id'])) {
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);

    // Mark student as absent in attendance table
    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $attendance_table WHERE class_id=%d AND student_id=%d AND attendance_date=%s",
            $class_id,
            $student_id,
            $today
        )
    );

    if ($existing) {
        $wpdb->update(
            $attendance_table,
            ['status' => 'absent', 'check_in_time' => null],
            ['id' => $existing]
        );
    } else {
        $wpdb->insert(
            $attendance_table,
            [
                'class_id' => $class_id,
                'student_id' => $student_id,
                'attendance_date' => $today,
                'status' => 'absent',
                'check_in_time' => null,
            ]
        );
    }

    // Insert alert into absentee_alerts table (for admin or parent notification)
    $wpdb->insert(
        $absentee_alerts_table,
        [
            'student_id' => $student_id,
            'class_id' => $class_id,
            'alert_date' => $today,
            'alert_sent' => 0, // 0 = pending, 1 = sent
        ]
    );

    // TODO: Integrate SMS or Email API to notify admin/parents here

    echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-2 rounded mb-4">Student marked absent and alert queued.</div>';
}

// Fetch all students assigned to mentor's classes today
$student_ids = [];
foreach ($classes as $class) {
    $results = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT student_id FROM {$wpdb->prefix}class_students WHERE class_id = %d",
            $class->id
        )
    );
    $student_ids = array_merge($student_ids, $results);
}
$student_ids = array_unique($student_ids);

// Get student user objects
$students = [];
if (!empty($student_ids)) {
    $students = get_users(['include' => $student_ids, 'role' => 'student']);
}

// Handle sending messages (mentor to student)
if (isset($_POST['send_message']) && isset($_POST['recipient_id']) && isset($_POST['message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $message = sanitize_text_field($_POST['message']);

    // Store message in a custom messages table (create if doesn't exist)
    $messages_table = $wpdb->prefix . 'mentor_student_messages';

    $wpdb->insert(
        $messages_table,
        [
            'sender_id' => $mentor_id,
            'recipient_id' => $recipient_id,
            'message' => $message,
            'sent_at' => current_time('mysql'),
        ]
    );

    echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-2 rounded mb-4">Message sent.</div>';
}

// Fetch last 10 messages between mentor and students (for simplicity, combined)
$messages_table = $wpdb->prefix . 'mentor_student_messages';
$messages = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $messages_table WHERE (sender_id = %d OR recipient_id = %d) ORDER BY sent_at DESC LIMIT 10",
        $mentor_id,
        $mentor_id
    )
);

?>

<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-4">Welcome, <?php echo esc_html($current_user->display_name); ?>!</h1>
    <p class="mb-6 text-gray-600">Today is <?php echo date('l, F j, Y'); ?></p>

    <section class="mb-10">
        <h2 class="text-2xl font-semibold mb-4">Today's Classes</h2>
        <?php if (empty($classes)) : ?>
            <p>No classes scheduled for today.</p>
        <?php else : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($classes as $class) : ?>
                    <div class="bg-white shadow rounded p-4 border border-gray-200">
                        <h3 class="text-xl font-semibold mb-2"><?php echo esc_html($class->class_name); ?></h3>
                        <p><strong>Time:</strong> <?php echo esc_html($class->class_start_time . ' - ' . $class->class_end_time); ?></p>
                        <p><strong>Location:</strong> <?php echo esc_html($class->location ?? 'TBD'); ?></p>

                        <h4 class="mt-4 font-semibold">Students</h4>
                        <?php
                        // Get students in this class
                        $class_students_ids = $wpdb->get_col(
                            $wpdb->prepare(
                                "SELECT student_id FROM {$wpdb->prefix}class_students WHERE class_id = %d",
                                $class->id
                            )
                        );
                        $class_students = get_users(['include' => $class_students_ids, 'role' => 'student']);
                        ?>
                        <ul class="mb-4 max-h-40 overflow-auto border border-gray-100 p-2 rounded">
                            <?php if (empty($class_students)) : ?>
                                <li>No students assigned.</li>
                            <?php else : ?>
                                <?php foreach ($class_students as $student) : ?>
                                    <?php
                                    // Get attendance status for student/class today
                                    $status = $wpdb->get_var(
                                        $wpdb->prepare(
                                            "SELECT status FROM $attendance_table WHERE student_id=%d AND class_id=%d AND attendance_date=%s",
                                            $student->ID,
                                            $class->id,
                                            $today
                                        )
                                    ) ?? 'not marked';
                                    ?>
                                    <li class="flex items-center justify-between py-1 border-b border-gray-100">
                                        <span><?php echo esc_html($student->display_name); ?></span>
                                        <span class="text-sm font-medium <?php
                                            echo $status === 'present' ? 'text-green-600' : ($status === 'absent' ? 'text-red-600' : 'text-gray-500');
                                        ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                        <?php if (!empty($class_students)) : ?>
                            <form method="POST" class="space-y-2">
                                <input type="hidden" name="class_id" value="<?php echo intval($class->id); ?>">
                                <select name="student_id" required class="border border-gray-300 rounded p-1 w-full">
                                    <option value="" disabled selected>Select Student</option>
                                    <?php foreach ($class_students as $student) : ?>
                                        <option value="<?php echo intval($student->ID); ?>"><?php echo esc_html($student->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="flex gap-2">
                                    <button type="submit" name="check_in" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Mark Present</button>
                                    <button type="submit" name="mark_absent" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Mark Absent</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="mb-10">
        <h2 class="text-2xl font-semibold mb-4">Messages</h2>
        <div class="border border-gray-200 rounded p-4 max-w-3xl mx-auto">
            <?php if (empty($messages)) : ?>
                <p>No messages yet.</p>
            <?php else : ?>
                <ul class="mb-4 max-h-60 overflow-auto space-y-2">
                    <?php foreach ($messages as $msg) :
                        $sender = get_user_by('ID', $msg->sender_id);
                        $recipient = get_user_by('ID', $msg->recipient_id);
                        ?>
                        <li>
                            <div class="bg-gray-100 p-2 rounded">
                                <p><strong><?php echo esc_html($sender->display_name); ?></strong> to <strong><?php echo esc_html($recipient->display_name); ?></strong></p>
                                <p><?php echo esc_html($msg->message); ?></p>
                                <small class="text-gray-500"><?php echo date('M d, Y H:i', strtotime($msg->sent_at)); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" class="space-y-2">
                <label for="recipient_id" class="block font-medium">Send Message To:</label>
                <select name="recipient_id" id="recipient_id" required class="border border-gray-300 rounded p-2 w-full">
                    <option value="" disabled selected>Select Student</option>
                    <?php foreach ($students as $student) : ?>
                        <option value="<?php echo intval($student->ID); ?>"><?php echo esc_html($student->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="message" rows="3" placeholder="Type your message here..." required class="w-full border border-gray-300 rounded p-2"></textarea>
                <button type="submit" name="send_message" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Send Message</button>
            </form>
        </div>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-4">Profile & Settings</h2>
        <div class="max-w-md border border-gray-200 rounded p-4">
            <p><strong>Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p><em>Settings and profile update coming soon...</em></p>
        </div>
    </section>
</div>

<?php get_footer(); ?>
