<?php
/**
 * Plugin Name: Register Class Post Type
 * Description: Registers the 'Class' custom post type safely.
 */

function register_class_post_type() {
    register_post_type('class', array(
        'labels' => array(
            'name' => __('Classes'),
            'singular_name' => __('Class')
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'rewrite' => array('slug' => 'classes'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'register_class_post_type');
