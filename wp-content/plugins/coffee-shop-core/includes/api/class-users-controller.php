<?php
/**
 * Users REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Users_Controller extends Coffee_Shop_REST_Controller {

    /**
     * Rest base
     */
    protected $rest_base = 'users';

    /**
     * Register routes
     */
    public function register_routes() {
        // Register user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/register', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'register_user'),
                'permission_callback' => '__return_true',
                'args'                => $this->get_register_args(),
            ),
        ));

        // Update user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user'),
                'permission_callback' => array($this, 'check_update_permission'),
                'args'                => $this->get_update_args(),
            ),
        ));
    }

    /**
     * Register user
     */
    public function register_user($request) {
        $username = sanitize_user($request->get_param('username'));
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $phone = sanitize_text_field($request->get_param('phone'));

        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            return $this->format_error('Username, email, and password are required', 'missing_required_fields', 400);
        }

        // Check if user exists
        if (username_exists($username) || email_exists($email)) {
            return $this->format_error('User already exists', 'user_exists', 400);
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $this->format_error($user_id->get_error_message(), 'user_creation_failed', 400);
        }

        // Set user meta
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone', $phone);
        }

        // Set default role
        $user = new WP_User($user_id);
        $user->set_role('subscriber');

        return $this->format_response(array(
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
        ), 'User registered successfully', 201);
    }

    /**
     * Update user
     */
    public function update_user($request) {
        $user_id = (int) $request->get_param('id');
        $phone = sanitize_text_field($request->get_param('phone'));

        // Check if user exists
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return $this->format_error('User not found', 'user_not_found', 404);
        }

        // Update phone number
        if (isset($phone)) {
            update_user_meta($user_id, 'phone', $phone);
        }

        return $this->format_response(array(
            'id' => $user_id,
            'phone' => $phone,
        ), 'User updated successfully', 200);
    }

    /**
     * Check update permission
     */
    public function check_update_permission($request) {
        $user_id = (int) $request->get_param('id');

        // Allow if user is updating themselves or is admin
        $current_user_id = get_current_user_id();
        if ($current_user_id === $user_id || current_user_can('manage_options')) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            __('You do not have permission to update this user', 'coffee-shop'),
            array('status' => 403)
        );
    }

    /**
     * Get register args
     */
    private function get_register_args() {
        return array(
            'username' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_user',
            ),
            'email' => array(
                'required'          => true,
                'type'              => 'string',
                'format'            => 'email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'password' => array(
                'required' => true,
                'type'     => 'string',
                'minLength' => 6,
            ),
            'phone' => array(
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get update args
     */
    private function get_update_args() {
        return array(
            'phone' => array(
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}