<?php



// Register Custom Post Types
function create_custom_post_types()
{
    // Register Classes post type
    register_post_type(
        'class',
        array(
            'labels' => array(
                'name' => __('Class'),
                'singular_name' => __('Class'),
                'add_new' => __('Add New Class'),
                'add_new_item' => __('Add New Class'),
                'edit_item' => __('Edit Class'),
                'new_item' => __('New Class'),
                'view_item' => __('View Class'),
                'search_items' => __('Search Classes'),
                'not_found' => __('No class found'),
                'not_found_in_trash' => __('No class found in trash'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'class'),
            'menu_icon' => 'dashicons-welcome-learn-more',
        )
    );

    // Register Check In post type
    register_post_type(
        'check_in',
        array(
            'labels' => array(
                'name' => __('Check Ins'),
                'singular_name' => __('Check In')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'check-in'),
        )
    );

    // Register Check Out post type
    register_post_type(
        'check_out',
        array(
            'labels' => array(
                'name' => __('Check Outs'),
                'singular_name' => __('Check Out')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'check-out'),
        )
    );

    // Register Weekly Summary post type
    register_post_type(
        'weekly_summary',
        array(
            'labels' => array(
                'name' => __('Weekly Summaries'),
                'singular_name' => __('Weekly Summary')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'weekly-summary'),
        )
    );

    // Register Attendance History post type
    register_post_type(
        'attendance_history',
        array(
            'labels' => array(
                'name' => __('Attendance Histories'),
                'singular_name' => __('Attendance History')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'attendance-history'),
        )
    );

    // Register Reward Points post type
    register_post_type(
        'reward_points',
        array(
            'labels' => array(
                'name' => __('Reward Points'),
                'singular_name' => __('Reward Point')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'rewrite' => array('slug' => 'reward-points'),
        )
    );
}

// Hook into the 'init' action
add_action('init', 'create_custom_post_types');

/* Template Name: Student Dashboard */
get_header();
echo '<script src="https://cdn.tailwindcss.com"></script>';

$current_user = wp_get_current_user();
global $wpdb;
$table = $wpdb->prefix . 'student_attendance';
$user_id = get_current_user_id();
/*
// Handle Check-In
if (isset($_POST['check_in'])) {
    $check_in_time = current_time('mysql');
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'check_in_time' => $check_in_time,
        'check_out_time' => null,
    ]);
    wp_redirect(get_permalink());
    exit;
}

// Handle Check-Out
if (isset($_POST['check_out'])) {
    $check_out_time = current_time('mysql');
    $wpdb->query($wpdb->prepare(
        "UPDATE $table SET check_out_time = %s WHERE user_id = %d AND check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 1",
        $check_out_time,
        $user_id
    ));
    wp_redirect(get_permalink());
    exit;
}
*/
// Handle Messaging
$success_message = '';
if (isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $message = sanitize_text_field($_POST['message']);

    // Simulate saving the message to a database (you should implement this)
    // $wpdb->insert('your_message_table', [...]);
    $success_message = "Message sent!";
}

// Get today's attendance (latest)
$today = date('Y-m-d');
$attendance_today = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table WHERE user_id = %d AND DATE(check_in_time) = %s ORDER BY check_in_time DESC LIMIT 1",
    $user_id,
    $today
));

// Weekly summary
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$summary = $wpdb->get_results($wpdb->prepare(
    "SELECT check_in_time FROM $table WHERE user_id = %d AND check_in_time BETWEEN %s AND %s",
    $user_id,
    $week_start . ' 00:00:00',
    $week_end . ' 23:59:59'
));
$days = count($summary);

// Duration helper
function calc_duration($start, $end)
{
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    if (!$start_ts || !$end_ts || $end_ts < $start_ts) return '-';
    $diff = $end_ts - $start_ts;
    $h = floor($diff / 3600);
    $m = floor(($diff % 3600) / 60);
    return sprintf("%02dh %02dm", $h, $m);
}

// Get mentors and students for messaging
$mentors = get_users(['role' => 'mentor']);
$students = get_users(['role' => 'student']);
?>

<!-- Tailwind CSS CDN for styling -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 bg-white">

    <!-- Welcome -->
    <section class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
        <h2 class="text-3xl font-bold text-blue-700 mb-2 flex items-center">
            <span class="dashicons dashicons-welcome-learn-more mr-3"></span>
            Welcome, <?php echo esc_html($current_user->display_name); ?>!
        </h2>
        <p class="text-gray-600">Today is <?php echo date("l, F j, Y"); ?></p>
        <div id="real-time-clock" class="text-green-700 font-semibold mt-2"></div>
        

        <script>
            function updateClock() {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';

                hours = hours % 12 || 12; // Convert to 12-hour format

                const timeStr = `${hours}:${minutes}:${seconds} ${ampm}`;
                document.getElementById('real-time-clock').innerText = timeStr;
            }

            setInterval(updateClock, 1000);
            updateClock();
        </script>

    </section>

    <!-- Classes and Attendance Actions in responsive grid -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Today's Class -->

        <div class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="dashicons dashicons-calendar-alt mr-2"></span> Today's Class
            </h3>
  <?php
$args = array(
    'post_type' => 'class',
    'posts_per_page' => -1
);
$classes = new WP_Query($args);

if ($classes->have_posts()) : ?>
    <div class="p-6 bg-white rounded-xl shadow">
        <h2 class="text-2xl font-bold mb-4 text-blue-700">Available Classes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while ($classes->have_posts()) : $classes->the_post(); ?>
                <div class="border border-gray-200 p-4 rounded-lg shadow-sm hover:shadow-md transition relative">
                    <h3 class="text-xl font-semibold"><?php the_title(); ?></h3>
                    <p><strong>Class Mentor:</strong> <?php the_field('class_mentor'); ?></p>
                    <p><strong>Start Time:</strong> <?php the_field('start_time'); ?></p>
                    <p><strong>End Time:</strong> <?php the_field('end_time'); ?></p>


                    <a href="<?php the_permalink(); ?>" class="text-sm text-blue-500 hover:underline mt-2 inline-block">View Class</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php wp_reset_postdata(); ?>
<?php else : ?>
    <p>No classes found.</p>
<?php endif; ?>



        <!-- Attendance Actions -->
        <div class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="dashicons dashicons-location-alt mr-2"></span> Attendance Actions
            </h3>
            <div class="flex space-x-4 mb-4">
                <form method="post" class="flex-1">
    <button
        name="check_in"
        type="submit"
        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
        <?php echo ($attendance_today && !$attendance_today->check_out_time) ? 'disabled' : ''; ?>>
        <?php echo $attendance_today && !$attendance_today->check_out_time ? date("g:i A", strtotime($attendance_today->check_in_time)) : "Check In"; ?>
    </button>
</form>

<form method="post" class="flex-1">
    <button
        name="check_out"
        type="submit"
        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
        <?php echo (!$attendance_today || $attendance_today->check_out_time) ? 'disabled' : ''; ?>>
        <?php echo $attendance_today && $attendance_today->check_out_time ? date("g:i A", strtotime($attendance_today->check_out_time)) : "Check Out"; ?>
    </button>
</form>

            </div>

            <?php if ($attendance_today): ?>
                <p class="text-blue-600 font-semibold mb-1">
                    <strong>Logged In Time:</strong> <?php echo date("l, F j, Y g:i A", strtotime($attendance_today->check_in_time)); ?>
                </p>
                <?php if ($attendance_today->check_out_time): ?>
                    <p class="text-red-600 font-semibold mb-1">
                        <strong>Logged Out Time:</strong> <?php echo date("g:i A", strtotime($attendance_today->check_out_time)); ?>
                    </p>
                    <p class="text-green-700 font-medium">
                        <strong>Duration:</strong> <?php echo calc_duration($attendance_today->check_in_time, $attendance_today->check_out_time); ?>
                    </p>
                <?php else: ?>
                    <p class="text-yellow-600 flex items-center font-semibold mb-1">
                        <span class="dashicons dashicons-clock mr-1"></span> You are currently checked in.
                    </p>
                    <p class="text-green-700 font-medium">
                        <strong>Live Duration:</strong> <span id="live-duration">Loading…</span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </section>

    <!-- Weekly Summary -->
    <section class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
        <h3 class="text-xl font-semibold text-gray-800 flex items-center mb-3">
            <span class="dashicons dashicons-chart-bar mr-2"></span>
            Weekly Class Summary
        </h3>
        <p class='text-blue-600'>You attended <strong><?php echo $days; ?></strong> day(s) this week.</p>
    </section>

    <!-- Attendance History -->
    <section class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
        <h3 class="text-xl font-semibold text-gray-800 flex items-center mb-3">
            <span class="dashicons dashicons-analytics mr-2"></span>
            Attendance History
        </h3>
        <?php
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY check_in_time DESC", $user_id)
        );

        if ($results) {
            echo "<div class='overflow-x-auto'><table class='w-full mt-4 table-auto border border-gray-300 rounded-lg'>";
            echo "<thead class='bg-gray-100'><tr>
                    <th class='px-4 py-2 border border-gray-300'>Date</th>
                    <th class='px-4 py-2 border border-gray-300'>Check In</th>
                    <th class='px-4 py-2 border border-gray-300'>Check Out</th>
                    <th class='px-4 py-2 border border-gray-300'>Duration</th>
                </tr></thead><tbody>";

            foreach ($results as $row) {
                $checkin = date("g:i A", strtotime($row->check_in_time));
                $checkout = $row->check_out_time ? date("g:i A", strtotime($row->check_out_time)) : "—";
                $date = date("l, F j, Y", strtotime($row->check_in_time));
                $duration = "—";
                if ($row->check_out_time) {
                    $duration = calc_duration($row->check_in_time, $row->check_out_time);
                    $hours = floor((strtotime($row->check_out_time) - strtotime($row->check_in_time)) / 3600);
                    if ($hours >= 4) $duration .= " ⚠️";
                }
                echo "<tr class='border border-gray-300'>
                        <td class='px-4 py-2 border border-gray-300'>$date</td>
                        <td class='px-4 py-2 border border-gray-300'>$checkin</td>
                        <td class='px-4 py-2 border border-gray-300'>$checkout</td>
                        <td class='px-4 py-2 border border-gray-300'>$duration</td>
                    </tr>";
            }
            echo "</tbody></table></div>";
        } else {
            echo "<p class='text-gray-500 mt-4'>No attendance records found.</p>";
        }
        ?>
    </section>

    <!-- Points -->
    <section class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
        <h3 class="text-xl font-semibold text-gray-800 flex items-center mb-3">
            <span class="dashicons dashicons-awards mr-2"></span>
            Your Reward Points
        </h3>
        <?php
        $points = $days * 10; // Assuming 10 points per day attended
        echo "<p class='text-green-600'>You’ve earned <strong>$points points</strong> this week.</p>";
        ?>
        <p class="text-blue-600">
            To redeem your coupon, contact us via WhatsApp:
            <a href="https://wa.me/254726048368" class="text-red-500 font-bold" target="_blank">
                +254 726 048 368
            </a>
        </p>
    </section>

    <!-- Messaging Section -->
    <section class="shadow rounded-lg p-6 transition-shadow duration-300 hover:shadow-lg">
        <h3 class="text-xl font-semibold text-gray-800 flex items-center mb-3">
            <span class="dashicons dashicons-email-alt mr-2"></span>
            Message Your Mentor or Fellow Student
        </h3>
        <form method="post" class="flex flex-col">
            <label for="recipient_id" class="mb-2">Select Recipient:</label>
            <select name="recipient_id" id="recipient_id" class="mb-4 p-2 border border-gray-300 rounded">
                <option value="">Select a mentor or student</option>
                <?php foreach ($mentors as $mentor): ?>
                    <option value="<?php echo esc_attr($mentor->ID); ?>"><?php echo esc_html($mentor->display_name); ?> (Mentor)</option>
                <?php endforeach; ?>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo esc_attr($student->ID); ?>"><?php echo esc_html($student->display_name); ?> (Student)</option>
                <?php endforeach; ?>
            </select>
            <textarea id="message" name="message" rows="4" class="mb-4 p-2 border border-gray-300 rounded" placeholder="Type your message here..." required></textarea>
            <button type="submit" name="send_message" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg transition">
                Send Message
            </button>
            <?php if ($success_message): ?>
                <p class="text-green-600 mt-4"><?php echo esc_html($success_message); ?></p>
            <?php endif; ?>
        </form>
    </section>

</div>

<script>
    // Function to handle Check-In button click
    function handleCheckIn(event) {
        event.preventDefault();
        




