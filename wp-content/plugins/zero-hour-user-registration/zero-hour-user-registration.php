<?php
/**
 * Plugin Name: Zero Hour User Registration
 * Description: Adds a REST API endpoint for user registration
 * Version: 1.0.0
 * Author: Zero Hour Team
 */

// Enable user registration if not already enabled
add_action('init', function() {
    if (!get_option('users_can_register')) {
        update_option('users_can_register', 1);
    }
});

// Register the REST API endpoint
add_action('rest_api_init', function() {
    register_rest_route('wp/v2', '/users/register', array(
        'methods' => 'POST',
        'callback' => 'zero_hour_register_user',
        'permission_callback' => '__return_true',
        'args' => array(
            'username' => array(
                'required' => true,
                'validate_callback' => function($value) {
                    return !empty($value) && is_string($value);
                }
            ),
            'email' => array(
                'required' => true,
                'validate_callback' => function($value) {
                    return is_email($value);
                }
            ),
            'password' => array(
                'required' => true,
                'validate_callback' => function($value) {
                    return !empty($value) && strlen($value) >= 6;
                }
            ),
            'phone' => array(
                'required' => false,
                'validate_callback' => function($value) {
                    return empty($value) || is_string($value);
                }
            ),
        ),
    ));
});

// Callback function for user registration
function zero_hour_register_user($request) {
    $username = sanitize_user($request->get_param('username'));
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $phone = sanitize_text_field($request->get_param('phone'));

    // Check if username already exists
    if (username_exists($username)) {
        return new WP_Error('username_exists', 'Username already exists.', array('status' => 400));
    }

    // Check if email already exists
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Email already exists.', array('status' => 400));
    }

    // Create the user
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_Error('registration_failed', 'User registration failed.', array('status' => 500));
    }

    // Set user role to customer
    wp_update_user(array('ID' => $user_id, 'role' => 'customer'));

    // Store phone number if provided
    if (!empty($phone)) {
        update_user_meta($user_id, 'phone', $phone);
    }

    // Optionally, send a welcome email or perform additional actions
    // wp_new_user_notification($user_id, null, 'user');

    return array(
        'message' => 'User registered successfully.',
        'user_id' => $user_id,
    );
}