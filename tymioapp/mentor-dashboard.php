<?php
/* Template Name: Mentor Dashboard */
get_header();

// Access control: only mentors and admins
$current_user = wp_get_current_user();
if (!in_array('mentor', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
    wp_die('Access Denied');
}

$user_id = get_current_user_id();
global $wpdb;
$attendance_table = $wpdb->prefix . 'student_attendance';
$students = get_users(['role' => 'student']);
$today = date('Y-m-d');

// Handle manual check-in POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    $student_id = intval($_POST['student_id']);
    $class = sanitize_text_field($_POST['class']);
    $wpdb->insert($attendance_table, [
        'student_id' => $student_id,
        'mentor_id' => $user_id,
        'class' => $class,
        'status' => 'present',
        'date' => $today,
        'timestamp' => current_time('mysql')
    ]);
}

// Export CSV function for attendance logs
function export_csv_logs($logs) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_logs.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student', 'Class', 'Status', 'Date']);
    foreach ($logs as $log) {
        fputcsv($output, [$log->student_name, $log->class, $log->status, $log->date]);
    }
    fclose($output);
    exit;
}

if (isset($_GET['export_csv']) && $_GET['export_csv'] === '1') {
    $logs = $wpdb->get_results("SELECT s.display_name as student_name, a.class, a.status, a.date FROM $attendance_table a JOIN $wpdb->users s ON a.student_id = s.ID WHERE a.mentor_id = $user_id");
    export_csv_logs($logs);
}

// AJAX handler for fetching weekly attendance data
add_action('wp_ajax_get_weekly_attendance', function() use ($wpdb) {
    $attendance_table = $wpdb->prefix . 'student_attendance';
    $user_id = get_current_user_id();

    // Calculate attendance for the past week
    $weeklyData = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $attendance_table WHERE date = %s AND mentor_id = %d",
            $date,
            $user_id
        ));
        $weeklyData[] = $count;
    }

    // Reverse the array to match the order of the days
    echo json_encode(array_reverse($weeklyData));
    wp_die(); // This is required to terminate immediately and return a proper response
});
?>

<style>
  :root {
    --primary-blue: #3b82f6;
    --secondary-green: #10b981;
    --neutral-light: #f9fafb;
    --neutral-dark: #1f2937;
    --text-dark: #111827;
    --text-light: #6b7280;
  }
  body {
    background-color: var(--neutral-light);
    color: var(--text-dark);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  .dark-mode {
    background-color: var(--neutral-dark);
    color: var(--neutral-light);
  }
  .dark-mode .bg-white {
    background-color: var(--neutral-dark);
  }
  .dark-mode .text-gray-600, .dark-mode .text-gray-700 {
    color: #d1d5db;
  }
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  .card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  }
  h1, h2 {
    font-weight: 700;
  }
  h1 {
    font-size: 2.25rem;
  }
  h2 {
    font-size: 1.5rem;
  }
  button, .btn {
    background-color: var(--primary-blue);
    color: white;
    padding: 0.5rem 1.2rem;
    font-size: 1rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: inline-flex;
    align-items: center;
  }
  button:hover, .btn:hover {
    filter: brightness(0.9);
  }
  .btn-green {
    background-color: var(--secondary-green);
  }
  .btn-green:hover {
    filter: brightness(0.9);
  }
  input[type="text"], input[type="search"], textarea {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    margin-top: 0.25rem;
    margin-bottom: 0.75rem;
    transition: border-color 0.3s ease;
  }
  input[type="text"]:focus, input[type="search"]:focus, textarea:focus {
    outline: none;
    border-color: var(--primary-blue);
  }
  .grid {
    display: grid;
    gap: 1.5rem;
  }
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }
  @media(min-width: 768px) {
    .md-grid-cols-2 {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }
  thead tr {
    background-color: #f3f4f6;
  }
  th, td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
  }
  tbody tr:hover {
    background-color: #f9fafb;
  }
  #chat-box {
    position: relative;
    overflow-y: auto;
    max-height: 300px;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 12px;
    background: white;
  }
  #chat-box::before,
  #chat-box::after {
    content: "";
    position: sticky;
    left: 0; right: 0; height: 15px;
    pointer-events: none;
    z-index: 10;
  }
  #chat-box::before {
    top: 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0.8), transparent);
  }
  #chat-box::after {
    bottom: 0;
    background: linear-gradient(to top, rgba(255,255,255,0.8), transparent);
  }
  .chat-message {
    margin-bottom: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    max-width: 70%;
    font-size: 0.95rem;
  }
  .chat-message.student {
    background-color: var(--primary-blue);
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 0;
  }
  .chat-message.mentor {
    background-color: #e5e7eb;
    color: var(--text-dark);
    margin-right: auto;
    border-bottom-left-radius: 0;
  }
  #chat-input {
    width: calc(100% - 50px);
    border: 1px solid #ddd;
    border-radius: 25px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }
  #send-btn {
    background-color: var(--primary-blue);
    border: none;
    color: white;
    border-radius: 25px;
    width: 40px;
    height: 40px;
    cursor: pointer;
    margin-left: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  #send-btn:hover {
    filter: brightness(0.9);
  }
  .dark-mode #chat-box {
    background: var(--neutral-dark);
    border-color: #374151;
  }
  .dark-mode #chat-box::before, 
  .dark-mode #chat-box::after {
    background: linear-gradient(to bottom, rgba(31,41,55,0.8), transparent);
  }
  .dark-mode .chat-message.mentor {
    background-color: #374151;
    color: #f9fafb;
  }
</style>

<div class="container dark-mode-toggle">
    <button onclick="toggleDarkMode()" class="btn mb-6">Toggle Dark Mode</button>

    <div class="card mb-6">
        <h1>Welcome, <?php echo esc_html($current_user->display_name); ?> 👋</h1>
        <p class="text-gray-600">Today is <?php echo date('l, F j, Y'); ?> | <span id="clock"></span></p>
    </div>

    <div class="grid grid-cols-1 md-grid-cols-2 mb-6">
        <?php foreach ($students as $student): ?>
        <div class="card">
            <h2><?php echo esc_html($student->display_name); ?></h2>
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="student_id" value="<?php echo $student->ID; ?>">
                <label>Class:</label>
                <input name="class" required placeholder="e.g. Onboarding Session" />
                <button type="submit" name="check_in" class="btn" style="display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Check In
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-6">
        <h2>Attendance Logs</h2>
        <a href="?export_csv=1" class="btn btn-green mb-4 inline-block" style="text-decoration: none;">Export to CSV</a>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logs = $wpdb->get_results("SELECT s.display_name as student_name, a.class, a.status, a.date FROM $attendance_table a JOIN $wpdb->users s ON a.student_id = s.ID WHERE a.mentor_id = $user_id ORDER BY a.date DESC LIMIT 20");
                foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->student_name); ?></td>
                    <td><?php echo esc_html($log->class); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                    <td><?php echo esc_html($log->date); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card mb-6">
        <h2>Weekly Summary</h2>
        <canvas id="weeklyChart" class="w-full h-64"></canvas>
    </div>

    <div class="card mb-6">
        <h2>Absentees Today</h2>
        <ul class="list-disc ml-6">
            <?php
            $absent_students = [];
            foreach ($students as $student) {
                $record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $attendance_table WHERE student_id = %d AND date = %s", $student->ID, $today));
                if (!$record) {
                    $absent_students[] = $student;
                    echo '<li>' . esc_html($student->display_name) . '</li>';
                }
            }
            if (empty($absent_students)) echo '<li>None 🎉</li>';
            ?>
        </ul>
    </div>

    <div class="card mb-6">
        <h2>Messaging</h2>
        <select id="student-select" aria-label="Select Student">
            <option value="">Select a student</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo esc_attr($student->ID); ?>"><?php echo esc_html($student->display_name); ?></option>
            <?php endforeach; ?>
        </select>
        <div id="chat-box" aria-live="polite" aria-atomic="true" role="log" tabindex="0">
            <!-- Chat messages appended here -->
        </div>
        <form id="chat-form" style="margin-top: 1rem; display: flex; align-items: center;">
            <input type="text" id="chat-input" placeholder="Type your message..." aria-label="Message input" required />
            <button type="submit" id="send-btn" aria-label="Send message">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9-7-9-7-2 7-7 2 7 2z" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  function updateClock() {
      const now = new Date();
      document.getElementById('clock').textContent = now.toLocaleTimeString();
  }
  setInterval(updateClock, 1000);
  updateClock();

  function toggleDarkMode() {
      document.querySelector('.dark-mode-toggle').classList.toggle('dark-mode');
  }

  const ctx = document.getElementById('weeklyChart');
  let weeklyData = [0, 0, 0, 0, 0, 0, 0]; // Initialize weekly attendance data

  function updateWeeklyChart() {
      // Fetch attendance data from the server
      fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_weekly_attendance')
          .then(response => response.json())
          .then(data => {
              weeklyData = data; // Update with real data
              myChart.update();
          });
  }

  const myChart = new Chart(ctx, {
      type: 'bar',
      data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
              label: 'Attendance',
              data: weeklyData,
              backgroundColor: 'var(--primary-blue)'
          }]
      },
      options: {
          responsive: true,
          plugins: {
              legend: { display: false },
              title: {
                  display: true,
                  text: 'Weekly Attendance Summary'
              }
          }
      }
  });

  // Initial load of the weekly attendance data
  updateWeeklyChart();

  const chatBox = document.getElementById('chat-box');
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');
  const studentSelect = document.getElementById('student-select');

  chatForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const message = chatInput.value.trim();
      const studentId = studentSelect.value;

      if (!message || !studentId) return; // Ensure both fields are filled

      const studentMsg = document.createElement('div');
      studentMsg.classList.add('chat-message', 'student');
      studentMsg.textContent = message;
      chatBox.appendChild(studentMsg);
      chatBox.scrollTop = chatBox.scrollHeight;

      setTimeout(() => {
          const aiReply = document.createElement('div');
          aiReply.classList.add('chat-message', 'mentor');
          aiReply.textContent = "[AI Rephrased]: " + message.charAt(0).toUpperCase() + message.slice(1);
          chatBox.appendChild(aiReply);
          chatBox.scrollTop = chatBox.scrollHeight;
      }, 1200);

      chatInput.value = '';
  });
</script>

<?php get_footer(); ?>





