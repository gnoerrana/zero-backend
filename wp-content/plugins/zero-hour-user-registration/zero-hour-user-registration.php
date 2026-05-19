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
            'first_name' => array(
                'required' => false,
                'validate_callback' => function($value) {
                    return is_string($value);
                }
            ),
            'last_name' => array(
                'required' => false,
                'validate_callback' => function($value) {
                    return is_string($value);
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

    // Add first_name and last_name to the user object in REST API responses
    register_rest_field('user', 'first_name', array(
        'get_callback'    => function($user_arr) {
            error_log('PLUGIN IS WORKING: first_name callback called for user ID: ' . $user_arr['id']);
            $user = get_userdata($user_arr['id']);
            $first_name = $user ? $user->first_name : '';
            error_log('PLUGIN IS WORKING: first_name value: ' . $first_name);
            return $first_name;
        },
        'update_callback' => null,
        'schema'          => array(
            'type'        => 'string',
            'context'     => array('view', 'edit'),
            'arg_options' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    register_rest_field('user', 'last_name', array(
        'get_callback'    => function($user_arr) {
            error_log('PLUGIN IS WORKING: last_name callback called for user ID: ' . $user_arr['id']);
            $user = get_userdata($user_arr['id']);
            $last_name = $user ? $user->last_name : '';
            error_log('PLUGIN IS WORKING: last_name value: ' . $last_name);
            return $last_name;
        },
        'update_callback' => null,
        'schema'          => array(
            'type'        => 'string',
            'context'     => array('view', 'edit'),
            'arg_options' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
});

// Callback function for user registration
function zero_hour_register_user($request) {
    $username = sanitize_user($request->get_param('username'));
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $first_name = sanitize_text_field($request->get_param('first_name'));
    $last_name = sanitize_text_field($request->get_param('last_name'));
    $phone = sanitize_text_field($request->get_param('phone'));

    // Check if username already exists
    if (username_exists($username)) {
        return new WP_Error('username_exists', 'Username already exists.', array('status' => 400));
    }

    // Check if email already exists
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Email already exists.', array('status' => 400));
    }

    // Create the user with first name, last name, and role
    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => 'customer',
    ));

    if (is_wp_error($user_id)) {
        return new WP_Error('registration_failed', 'User registration failed.', array('status' => 500));
    }

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