<?php
/**
 * Locations REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Locations_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'locations';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all locations
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Get single location
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
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
     * Get all locations
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'location',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);
        $locations = array();

        foreach ($query->posts as $post) {
            $locations[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response($locations);
    }

    /**
     * Get single location
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'location') {
            return $this->format_error(__('Location not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create location
     */
    public function create_item($request) {
        $post_data = array(
            'post_type'    => 'location',
            'post_title'   => $request->get_param('name'),
            'post_content' => $request->get_param('description') ?: '',
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        $meta_fields = array(
            'floor', 'building', 'address', 'access_instructions', 'capacity',
            'opening_hours', 'phone', 'latitude', 'longitude', 'is_active', 'features'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post_id, $field, $request->get_param($field));
            }
        }

        // Set featured image
        if ($image_id = $request->get_param('image_id')) {
            set_post_thumbnail($post_id, $image_id);
        }

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Location created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update location
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'location') {
            return $this->format_error(__('Location not found', 'coffee-shop'), 'not_found', 404);
        }

        // Update post data
        $post_data = array('ID' => $post->ID);
        
        if ($name = $request->get_param('name')) {
            $post_data['post_title'] = $name;
        }
        if ($description = $request->get_param('description')) {
            $post_data['post_content'] = $description;
        }

        wp_update_post($post_data);

        // Update meta fields
        $meta_fields = array(
            'floor', 'building', 'address', 'access_instructions', 'capacity',
            'opening_hours', 'phone', 'latitude', 'longitude', 'is_active', 'features'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post->ID, $field, $request->get_param($field));
            }
        }

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Location updated successfully', 'coffee-shop')
        );
    }

    /**
     * Delete location
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'location') {
            return $this->format_error(__('Location not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete location', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Location deleted successfully', 'coffee-shop'));
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $opening_hours = get_post_meta($post->ID, 'opening_hours', true);
        
        return array(
            'id'                 => $post->ID,
            'name'               => $post->post_title,
            'description'        => $post->post_content,
            'floor'              => get_post_meta($post->ID, 'floor', true),
            'building'           => get_post_meta($post->ID, 'building', true),
            'address'            => get_post_meta($post->ID, 'address', true),
            'access_instructions'=> get_post_meta($post->ID, 'access_instructions', true),
            'capacity'           => (int) get_post_meta($post->ID, 'capacity', true),
            'opening_hours'      => $opening_hours ? json_decode($opening_hours, true) : null,
            'phone'              => get_post_meta($post->ID, 'phone', true),
            'latitude'           => (float) get_post_meta($post->ID, 'latitude', true),
            'longitude'          => (float) get_post_meta($post->ID, 'longitude', true),
            'is_active'          => (bool) get_post_meta($post->ID, 'is_active', true),
            'features'           => get_post_meta($post->ID, 'features', true) ?: array(),
            'image'              => get_the_post_thumbnail_url($post->ID, 'large'),
        );
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
    }
}
