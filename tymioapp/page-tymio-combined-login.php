<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tymio Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md relative">
        <div class="absolute inset-0 z-0">
            <img src="C:/Users/PC/Desktop/Tymio WebApp/image.jpg" alt="Background Image" class="w-full h-full object-cover rounded-lg" />

        </div>
        <div class="relative z-10 bg-white p-8 rounded-lg">
            <h2 class="text-2xl font-semibold text-center mb-6">Tymio Login</h2>

            <?php
            // Start session and handle form submission
            session_start(); // Start a new session or resume the existing one

            // Check if the form has been submitted
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get the username and password from the POST request
                $username = $_POST['username'];
                $password = $_POST['password'];

                $creds = array(
                    'user_login'    => $_POST['username'],
                    'user_password' => $_POST['password'],
                    'remember'      => true
                );
                $user = wp_signon($creds, false);

                // Authenticate user using WordPress's built-in function
                $user = wp_authenticate($username, $password);

                // Check if authentication failed
                if (is_wp_error($user)) {
                    // Display error message for invalid credentials
                    echo "<div class='text-red-600 text-center mb-4'>Invalid username or password.</div>";
                } else {
                    // Log the user in by setting the current user and authentication cookie
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID);

                    // Redirect users based on their roles
                    if (in_array('administrator', $user->roles)) {
                        wp_redirect(admin_url()); // Redirect to the admin dashboard
                    } elseif (in_array('mentor', $user->roles)) {
                        wp_redirect(home_url('/mentor-dashboard')); // Redirect to mentor dashboard
                    } elseif (in_array('student', $user->roles)) {
                        wp_redirect(home_url('/student-dashboard')); // Redirect to student dashboard
                    } else {
                        wp_redirect(home_url()); // Default redirect for other roles
                    }
                    exit; // Terminate the script after redirection
                }
            }
            ?>

            <!-- Login form content -->
            <form action="" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your username" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none">Log in</button>
            </form>

            <!-- Additional login options -->
            <div class="mt-4 text-center">
                <a href="/wp-login.php?action=lostpassword" class="text-blue-600 hover:underline">Forgot password?</a>
            </div>
            <div class="mt-4 text-center">
                <span class="text-gray-600">or</span>
            </div>
            <div class="flex justify-between mt-4">
                <button onclick="loginWithGoogle()" class="flex-1 bg-red-600 text-white p-2 rounded-md hover:bg-red-700 focus:outline-none">Continue with Google</button>
                <button onclick="loginWithSMS()" class="flex-1 bg-green-600 text-white p-2 rounded-md hover:bg-green-700 focus:outline-none ml-2">Login with SMS</button>
            </div>
        </div>
    </div>
    <script>
        // Function to handle Google login
        function loginWithGoogle() {
            // Redirect to Google login (Nextend Social)
            window.location.href = "/wp-login.php?action=google_oauth2";
        }

        // Function to handle SMS login (placeholder logic)
        function loginWithSMS() {
            // Logic for SMS login (currently just an alert)
            alert("SMS login clicked");
        }
    </script>
</body>

</html>


$creds = array(
'user_login' => $_POST['username'],
'user_password' => $_POST['password'],
'remember' => true
);
$user = wp_signon($creds, false);