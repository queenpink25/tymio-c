<?php
/**
 * Plugin Name: Custom Login Redirect
 * Description: Redirect users after login based on their roles.
 * Version: 1.0
 * Author: Your Name
 */

function custom_login_redirect($redirect_to, $request, $user) {
    // Check if the user is logged in
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect based on user role
        if (in_array('administrator', $user->roles)) {
            return admin_url(); // Redirect to the admin dashboard
        } elseif (in_array('mentor', $user->roles)) {
            return home_url('/mentor-dashboard'); // Redirect to mentor dashboard
        } elseif (in_array('student', $user->roles)) {
            return home_url('/student-dashboard'); // Redirect to student dashboard
        } else {
            return home_url(); // Default redirect for other roles
        }
    }
    return $redirect_to; // Return the original redirect URL if no roles match
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);
