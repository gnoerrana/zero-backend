<?php
/**
 * Media REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Media_Controller extends WP_REST_Controller {

    protected $rest_base = 'media';

    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'base/v1';
    }

    /**
     * Check if user is authenticated
     */
    protected function check_auth($request) {
        // Manual JWT validation
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

        // Verify signature - JWT uses base64url encoding, not standard base64
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

        if (!$payload_data || !isset($payload_data['data']['user']['id'])) {
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

        return intval($payload_data['data']['user']['id']);
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
     * Register routes
     */
    public function register_routes() {
        // Get all media items
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'admin_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'upload_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
                'accept_json'         => false,
            ),
        ));

        // Get single media item
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));
    }

    /**
     * Get all media items
     */
    public function get_items($request) {
        $user_id = $this->check_auth($request);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged'          => $request->get_param('page') ?: 1,
        );

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $items[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response(array(
            'items' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }

    /**
     * Get single media item
     */
    public function get_item($request) {
        $user_id = $this->check_auth($request);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'attachment') {
            return $this->format_error(__('Media item not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Upload media item
     */
    public function upload_item($request) {
        // Get uploaded files from request
        $files = $request->get_file_params();

        // Check if file was uploaded
        if (empty($files) || !isset($files['file'])) {
            return $this->format_error(__('No file uploaded', 'coffee-shop'), 'no_file', 400);
        }

        $file = $files['file'];

        error_log('File array: ' . print_r($file, true));

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = __('File upload error', 'coffee-shop');
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message = __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'coffee-shop');
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'coffee-shop');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = __('The uploaded file was only partially uploaded', 'coffee-shop');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = __('No file was uploaded', 'coffee-shop');
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = __('Missing a temporary folder', 'coffee-shop');
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = __('Failed to write file to disk', 'coffee-shop');
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = __('File upload stopped by extension', 'coffee-shop');
                    break;
            }
            return $this->format_error($error_message, 'upload_error', 400);
        }

        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            return $this->format_error(__('Invalid file type. Only images are allowed.', 'coffee-shop'), 'invalid_type', 400);
        }

        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            return $this->format_error(__('File too large. Maximum size is 5MB.', 'coffee-shop'), 'file_too_large', 400);
        }

        // Handle upload
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $upload_overrides = array(
            'test_form' => false,
            'upload_error_handler' => function($file, $message) {
                return $message;
            }
        );

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            error_log('Upload error: ' . $uploaded_file['error']);
            return $this->format_error($uploaded_file['error'], 'upload_error', 400);
        }

        // Create attachment
        $attachment_id = wp_insert_attachment(array(
            'guid'           => $uploaded_file['url'],
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name(basename($uploaded_file['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $uploaded_file['file']);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        $post = get_post($attachment_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Media uploaded successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Delete media item
     */
    public function delete_item($request) {
        $user_id = $this->check_auth($request);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'attachment') {
            return $this->format_error(__('Media item not found', 'coffee-shop'), 'not_found', 404);
        }

        // Delete attachment
        $result = wp_delete_attachment($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete media item', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Media item deleted successfully', 'coffee-shop'));
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $sizes = get_intermediate_image_sizes();

        $data = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'filename'    => wp_basename($post->guid),
            'url'         => wp_get_attachment_url($post->ID),
            'mime_type'   => $post->post_mime_type,
            'date'        => $post->post_date,
            'modified'    => $post->post_modified,
            'sizes'       => array(),
        );

        // Add image sizes
        $metadata = wp_get_attachment_metadata($post->ID);
        if (isset($metadata['sizes'])) {
            foreach ($sizes as $size) {
                if (isset($metadata['sizes'][$size])) {
                    $data['sizes'][$size] = array(
                        'url'    => wp_get_attachment_image_url($post->ID, $size),
                        'width'  => $metadata['sizes'][$size]['width'],
                        'height' => $metadata['sizes'][$size]['height'],
                    );
                }
            }
        }

        // Add full size
        $data['sizes']['full'] = array(
            'url'    => wp_get_attachment_url($post->ID),
            'width'  => isset($metadata['width']) ? $metadata['width'] : null,
            'height' => isset($metadata['height']) ? $metadata['height'] : null,
        );

        return $data;
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        $user_id = $this->check_auth($request);
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        return current_user_can('upload_files');
    }

    /**
     * Get collection params
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description'       => __('Current page of the collection.', 'coffee-shop'),
                'type'              => 'integer',
                'default'           => 1,
            ),
            'per_page' => array(
                'description'       => __('Maximum number of items to be returned.', 'coffee-shop'),
                'type'              => 'integer',
                'default'           => 20,
                'maximum'           => 100,
            ),
        );
    }
}