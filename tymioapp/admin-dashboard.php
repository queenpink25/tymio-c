<?php
/* Template Name: Admin Dashboard */
get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('administrator', $current_user->roles)) {
    wp_redirect(home_url());
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix;

// Fetch counts
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

$alerts = $wpdb->get_results("SELECT * FROM {$prefix}absentee_alerts ORDER BY sent_at DESC LIMIT 20");

$coupon_students = $wpdb->get_results("SELECT s.id, s.name,
    SUM(TIMESTAMPDIFF(SECOND, a.check_in_time, a.check_out_time))/3600 AS total_hours
    FROM {$prefix}student_attendance a
    JOIN {$prefix}students s ON a.student_id = s.id
    WHERE DATE(a.check_in_time) >= CURDATE() - INTERVAL 7 DAY
    GROUP BY s.id HAVING total_hours > 4");

$students = $wpdb->get_results("SELECT id, name FROM {$prefix}students");
$mentors = $wpdb->get_results("SELECT id, name FROM {$prefix}mentors");
$classes = $wpdb->get_results("SELECT id, name FROM {$prefix}classes");

/*// Handle redeem coupon
if (isset($_POST['redeem_coupon']) && current_user_can('administrator')) {
    $student_id = intval($_POST['coupon_student_id']);
    $wpdb->insert("{$prefix}coupons_redeemed", [
        'student_id' => $student_id,
        'redeemed_at' => current_time('mysql')
    ]);
    $notice = 'Coupon redeemed successfully!';
}*/

if (isset($_POST['redeem_coupon']) && current_user_can('administrator')) {
    $student_id = intval($_POST['coupon_student_id']);
    $redeemed_coupons = get_field('redeemed_coupons', 'user_' . $student_id) ?: [];

    // Add new redeemed coupon record (assume coupon ID is in $_POST['coupon_id'])
    $coupon_id = intval($_POST['coupon_id'] ?? 0);
    if ($coupon_id) {
        $redeemed_coupons[] = [
            'coupon_id' => $coupon_id,
            'redeemed_at' => current_time('mysql')
        ];
        update_field('redeemed_coupons', $redeemed_coupons, 'user_' . $student_id);
        $notice = 'Coupon redeemed successfully!';
    }
}

/*
// Handle preferred days
if (isset($_POST['save_days'])) {
    $student_id = intval($_POST['preferred_student']);
    $days = implode(',', array_map('sanitize_text_field', $_POST['preferred_days'] ?? []));
    $wpdb->replace("{$prefix}student_days", [
        'student_id' => $student_id,
        'preferred_days' => $days
    ]);
    $notice = 'Preferred days updated!';

}*/
    if (isset($_POST['save_days'])) {
    $student_id = intval($_POST['preferred_student']);
    $days = array_map('sanitize_text_field', $_POST['preferred_days'] ?? []);
    
    // Update ACF field for preferred days (array expected)
    update_field('preferred_days', $days, 'user_' . $student_id);

    $notice = 'Preferred days updated!';
}

/*
// Handle mentor-class assignment (example)
if (isset($_POST['assign_class'])) {
    $mentor_id = intval($_POST['assign_mentor']);
    $class_id = intval($_POST['assign_class_id']);
    // Insert or update assignment (simplified)
    $wpdb->replace("{$prefix}mentor_class_assignments", [
        'mentor_id' => $mentor_id,
        'class_id' => $class_id,
    ]);
    $notice = 'Mentor assigned to class successfully!';
}*/
if (isset($_POST['assign_class'])) {
    // Sanitize and get mentor user ID
    $mentor_id = intval($_POST['assign_mentor']); 
    
    // Sanitize and get class ID (probably a post ID or term ID)
    $class_id = intval($_POST['assign_class_id']);

    // Update the ACF user meta field for assigned class
    update_field('assigned_class', $class_id, 'user_' . $mentor_id);

    // Success notice
    $notice = 'Mentor assigned to class successfully!';
}

// Role management example - assign role to user (admin only)
if (isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = sanitize_text_field($_POST['new_role']);
    $user = new WP_User($user_id);
    if ($user) {
        $user->set_role($new_role);
        $notice = 'User role updated!';
    }
}
?>

<style>
  /* Sidebar and page layout */
  body { transition: background-color 0.3s ease; }
  #sidebar {
    width: 220px;
    background-color: #2563eb; /* blue-600 */
    color: white;
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    padding: 1.5rem;
  }
  #sidebar a {
    display: block;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    font-weight: 600;
  }
  #sidebar a:hover {
    background-color: #92400e; /* brown-700 */
  }
  #content {
    margin-left: 240px;
    padding: 2rem;
  }
  /* Toast */
  #toast {
    position: fixed;
    top: 1rem;
    right: 1rem;
    background: #2563eb;
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.375rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 9999;
  }
  #toast.show {
    opacity: 1;
    pointer-events: auto;
  }
  /* Dark mode */
  body.dark {
    background-color: #121212;
    color: #eee;
  }
  body.dark #sidebar {
    background-color: #374151; /* gray-700 */
    color: #ddd;
  }
  body.dark #sidebar a:hover {
    background-color: #a16207; /* amber-700 */
  }
  body.dark #toast {
    background: #374151;
  }
</style>

<div id="sidebar" aria-label="Sidebar Navigation">
  <h2 class="text-xl font-bold mb-6">Tymio Admin</h2>
  <a href="#overview">Overview Analytics</a>
  <a href="#alerts">Absentee Alerts</a>
  <a href="#coupons">Coupons & Redeem</a>
  <a href="#assignments">Mentor/Class Assignment</a>
  <a href="#roles">User Role Management</a>
  <a href="#" id="toggle-dark">Toggle Dark Mode</a>
  <a href="#" id="export-pdf-btn">Export PDF</a>
  <a href="<?php echo esc_url(add_query_arg('export_csv', '1')); ?>">Export CSV</a>
</div>

<div id="content">
  <h1 class="text-4xl font-bold mb-10 text-center">📊 Tymio Admin Dashboard</h1>

  <?php if (!empty($notice)): ?>
    <div id="toast" role="alert" aria-live="assertive" aria-atomic="true"><?php echo esc_html($notice); ?></div>
  <?php else: ?>
    <div id="toast"></div>
  <?php endif; ?>

  <section id="overview" class="mb-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
      <?php
        $stats = [
          ['label' => 'Mentors', 'count' => $total_mentors, 'color' => 'bg-blue-200'],
          ['label' => 'Students', 'count' => $total_students, 'color' => 'bg-green-200'],
          ['label' => 'Classes', 'count' => $total_classes, 'color' => 'bg-yellow-200'],
          ['label' => 'Attendance Logs', 'count' => $total_logs, 'color' => 'bg-purple-200'],
        ];
        foreach ($stats as $stat): ?>
          <div class="<?php echo $stat['color']; ?> p-6 rounded-lg shadow text-center">
            <div class="text-gray-700 text-sm uppercase tracking-wider"><?php echo esc_html($stat['label']); ?></div>
            <div class="text-3xl font-extrabold mt-2"><?php echo intval($stat['count']); ?></div>
          </div>
      <?php endforeach; ?>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h2 class="text-2xl font-semibold mb-4">📈 Weekly Attendance Overview</h2>
      <canvas id="attendanceChart"></canvas>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-2xl font-semibold mb-4">🔍 AI Student Performance Insights</h2>
      <ul class="divide-y divide-gray-200 max-h-64 overflow-y-auto">
        <?php foreach ($performance_patterns as $perf): ?>
          <li class="py-2 flex justify-between text-sm">
            <span class="text-gray-800 font-medium"><?php echo esc_html($perf->name); ?></span>
            <span class="text-gray-600"><?php echo intval($perf->presents); ?> / <?php echo intval($perf->total); ?> classes</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section id="alerts" class="bg-white shadow rounded-lg p-6 mb-10 max-h-96 overflow-y-auto">
    <h2 class="text-2xl font-semibold mb-4">📢 Absentee Alerts History</h2>
    <ul class="space-y-2">
      <?php foreach ($alerts as $alert): ?>
        <li class="p-3 border rounded text-sm">
          <strong><?php echo esc_html($alert->student_name); ?></strong> - <?php echo esc_html($alert->method); ?> - <?php echo esc_html($alert->status); ?>
          <span class="block text-xs text-gray-500"><?php echo esc_html($alert->sent_at); ?></span>
        </li>
      <?php endforeach; ?>
      <?php if (empty($alerts)): ?><li class="text-gray-500">No alerts found.</li><?php endif; ?>
    </ul>
  </section>

  <section id="coupons" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-2xl font-semibold mb-4">🎁 Redeem Coupons</h2>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="redeem_coupon" value="1" />
        <select name="coupon_student_id" required class="w-full p-2 border rounded">
          <option value="" disabled selected>Select Student</option>
          <?php foreach ($coupon_students as $student): ?>
            <option value="<?php echo $student->id; ?>"><?php echo esc_html($student->name); ?> (<?php echo round($student->total_hours, 2); ?> hrs)</option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-brown-700">Redeem 🎟️</button>
      </form>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-2xl font-semibold mb-4">📅 Set Preferred Days</h2>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="save_days" value="1">
        <select name="preferred_student" required class="w-full border rounded p-2">
          <option value="" disabled selected>Select Student</option>
          <?php foreach ($students as $stu): ?>
            <option value="<?php echo $stu->id; ?>"><?php echo esc_html($stu->name); ?></option>
          <?php endforeach; ?>
        </select>
        <div class="flex flex-wrap gap-2">
          <?php foreach (["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"] as $day): ?>
            <label class="flex items-center gap-1">
              <input type="checkbox" name="preferred_days[]" value="<?php echo $day; ?>"> <?php echo $day; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-brown-700">Save Days</button>
      </form>
    </div>
  </section>

  <section id="assignments" class="bg-white shadow rounded-lg p-6 mb-10 max-w-2xl">
    <h2 class="text-2xl font-semibold mb-4">🧑‍🏫 Mentor/Class Assignment</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="assign_class" value="1" />
      <div>
        <label for="assign_mentor" class="block mb-1 font-medium">Select Mentor</label>
        <select id="assign_mentor" name="assign_mentor" required class="w-full border rounded p-2">
          <option value="" disabled selected>Select Mentor</option>
          <?php foreach ($mentors as $mentor): ?>
            <option value="<?php echo $mentor->id; ?>"><?php echo esc_html($mentor->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="assign_class_id" class="block mb-1 font-medium">Select Class</label>
        <select id="assign_class_id" name="assign_class_id" required class="w-full border rounded p-2">
          <option value="" disabled selected>Select Class</option>
          <?php foreach ($classes as $class): ?>
            <option value="<?php echo $class->id; ?>"><?php echo esc_html($class->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-brown-700">Assign</button>
    </form>
  </section>

  <section id="roles" class="bg-white shadow rounded-lg p-6 max-w-2xl">
    <h2 class="text-2xl font-semibold mb-4">👥 User Role Management</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="update_role" value="1" />
      <div>
        <label for="user_id" class="block mb-1 font-medium">Select User</label>
        <select id="user_id" name="user_id" required class="w-full border rounded p-2">
          <option value="" disabled selected>Select User</option>
          <?php
          $all_users = get_users();
          foreach ($all_users as $user):
          ?>
            <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name . " (" . implode(", ", $user->roles) . ")"); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="new_role" class="block mb-1 font-medium">New Role</label>
        <select id="new_role" name="new_role" required class="w-full border rounded p-2">
          <option value="" disabled selected>Select Role</option>
          <?php
          $roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
          foreach ($roles as $role):
          ?>
            <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-brown-700">Update Role</button>
    </form>
  </section>
</div>

<!-- Toast script -->
<script>
  function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  <?php if (!empty($notice)) : ?>
    showToast("<?php echo esc_js($notice); ?>");
  <?php endif; ?>

  // Dark mode toggle
  document.getElementById('toggle-dark').addEventListener('click', function(e) {
    e.preventDefault();
    document.body.classList.toggle('dark');
  });

  // Export PDF button
  document.getElementById('export-pdf-btn').addEventListener('click', function(e) {
    e.preventDefault();
    // Simple PDF export using jsPDF library (you need to enqueue jsPDF or load it from CDN)
    // For demo: alert user
    alert('PDF export functionality coming soon!');
  });
</script>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');
fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=attendance_chart_data')
  .then(res => res.json())
  .then(data => {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: 'Presents',
            backgroundColor: '#2563eb',
            data: data.presents,
          },
          {
            label: 'Absents',
            backgroundColor: '#ef4444',
            data: data.absents,
          }
        ]
      },
      options: {
        responsive: true,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  });
</script>

