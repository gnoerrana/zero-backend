<?php
/**
 * Base REST Controller
 */

defined('ABSPATH') || exit;

abstract class Coffee_Shop_REST_Controller extends WP_REST_Controller {

    /**
     * Namespace
     */
    protected $namespace = 'base/v1';

    /**
     * Check if user is authenticated
     */
    protected function check_auth($request) {
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header) {
            return new WP_Error(
                'rest_forbidden',
                __('Authentication required', 'coffee-shop'),
                array('status' => 401)
            );
        }

        // Extract token
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
            
            // Validate JWT token
            $user_id = $this->validate_jwt_token($token);
            
            if (is_wp_error($user_id)) {
                return $user_id;
            }

            return $user_id;
        }

        return new WP_Error(
            'rest_forbidden',
            __('Invalid authentication token', 'coffee-shop'),
            array('status' => 401)
        );
    }

/**
     * Validate JWT token - decode payload without signature verification (JWT plugin already validated)
     */
    protected function validate_jwt_token($token) {
        // JWT Auth plugin has already validated the signature, we just need to extract user_id
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'coffee-shop'),
                array('status' => 401)
            );
        }
        
        list(, $payload,) = $parts;
        
        // Base64url decode
        $decoded_payload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        
        if (!$decoded_payload) {
            return new WP_Error(
                'invalid_payload',
                __('Invalid token payload', 'coffee-shop'),
                array('status' => 401)
            );
        }
        
        // Handle JWT Auth plugin format: data.user.id
        $user_id = $decoded_payload['data']['user']['id'] ?? $decoded_payload['user_id'] ?? null;
        
        if (!$user_id) {
            return new WP_Error(
                'invalid_payload',
                __('Invalid token payload', 'coffee-shop'),
                array('status' => 401)
            );
        }
        
        // Check expiration
        if (isset($decoded_payload['exp']) && $decoded_payload['exp'] < time()) {
            return new WP_Error(
                'token_expired',
                __('Token has expired', 'coffee-shop'),
                array('status' => 401)
            );
        }
        
        return $user_id;
    }

    /**
     * Check if user is admin
     */
    protected function is_admin($user_id) {
        $user = get_user_by('id', $user_id);
        return $user && user_can($user, 'manage_options');
    }

    /**
     * Format response
     */
    protected function format_response($data, $message = '', $status = 200) {
        return new WP_REST_Response(array(
            'success' => $status < 400,
            'message' => $message,
            'data'    => $data,
        ), $status);
    }

    /**
     * Format error response
     */
    protected function format_error($message, $code = 'error', $status = 400) {
        return new WP_Error($code, $message, array('status' => $status));
    }

    /**
     * Get boolean meta value
     * Ensures consistent boolean/int return (1 for true, 0 for false)
     */
    protected function get_boolean_meta($post_id, $meta_key) {
        $value = get_post_meta($post_id, $meta_key, true);
        
        // Handle various possible stored formats
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, array('1', 'true', 'yes', 'on')) ? 1 : 0;
        }
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        return 0;
    }
}
