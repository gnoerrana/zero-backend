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
     * Validate JWT token
     */
    protected function validate_jwt_token($token) {
        // Simple JWT validation - in production, use a proper JWT library
        $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key';
        
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return new WP_Error(
                'invalid_token',
                __('Invalid token format', 'coffee-shop'),
                array('status' => 401)
            );
        }

        list($header, $payload, $signature) = $parts;
        
        // Verify signature - JWT uses base64url encoding
        $expected_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true)));

        if ($signature !== $expected_signature) {
            return new WP_Error(
                'invalid_signature',
                __('Invalid token signature', 'coffee-shop'),
                array('status' => 401)
            );
        }

        // Decode payload
        $payload_data = json_decode(base64_decode($payload), true);
        
        if (!$payload_data || !isset($payload_data['user_id'])) {
            return new WP_Error(
                'invalid_payload',
                __('Invalid token payload', 'coffee-shop'),
                array('status' => 401)
            );
        }

        // Check expiration
        if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
            return new WP_Error(
                'token_expired',
                __('Token has expired', 'coffee-shop'),
                array('status' => 401)
            );
        }

        return $payload_data['user_id'];
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
}
