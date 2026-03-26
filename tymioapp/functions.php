<?php

// Silence is golden. Safe empty functions.php
/*function tymio_add_custom_roles() {
    // Add student role
    add_role('student', 'Student', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ]);

    // Add mentor role
    add_role('mentor', 'Mentor', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ]);

    // Add admin_assistant role
    add_role('admin_assistant', 'Admin Assistant', [
        'read' => true,
        'edit_users' => true,
    ]);
}
add_action('init', 'tymio_add_custom_roles');*/

}
add_filter('login_redirect', 'tymio_login_redirect', 10, 3);

function tymio_login_redirect($redirect_to, $request, $user) {
    if (!is_wp_error($user) && is_object($user) && isset($user->roles)) {
        $roles = (array) $user->roles;

        if (in_array('student', $roles)) {
            return site_url('/student-dashboard');
        } elseif (in_array('mentor', $roles)) {
            return site_url('/mentor-dashboard');
        } elseif (in_array('admin_assistant', $roles)) {
            return site_url('/admin-dashboard');
        }
    }

    return $redirect_to;
}
add_filter('login_redirect', 'tymio_login_redirect', 10, 3);
?>