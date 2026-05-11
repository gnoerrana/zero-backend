<?php
/**
 * Promotions REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Promotions_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'promotions';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all promotions
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

        // Get single promotion
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
     * Get all promotions
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'promotion',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );

        // Include inactive promotions if requested
        if (!$request->get_param('status') || $request->get_param('status') !== 'any') {
            $args['meta_query'] = array(
                array(
                    'key'     => 'is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            );
        }

        // Filter by category
        if ($category = $request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'promotion_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query($args);
        $promotions = array();

        foreach ($query->posts as $post) {
            $promotions[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response($promotions);
    }

    /**
     * Get single promotion
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'promotion') {
            return $this->format_error(__('Promotion not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create promotion
     */
    public function create_item($request) {
        $post_data = array(
            'post_type'    => 'promotion',
            'post_title'   => $request->get_param('title'),
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        $meta_fields = array(
            'subtitle_key', 'description_en', 'description_id',
            'cta_text_en', 'cta_text_id', 'icon', 'is_active'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post_id, $field, $request->get_param($field));
            }
        }

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Promotion created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update promotion
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'promotion') {
            return $this->format_error(__('Promotion not found', 'coffee-shop'), 'not_found', 404);
        }

        // Update post data
        $post_data = array('ID' => $post->ID);
        
        if ($title = $request->get_param('title')) {
            $post_data['post_title'] = $title;
        }

        wp_update_post($post_data);

        // Update meta fields
        $meta_fields = array(
            'subtitle_key', 'description_en', 'description_id',
            'cta_text_en', 'cta_text_id', 'icon', 'is_active'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post->ID, $field, $request->get_param($field));
            }
        }

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Promotion updated successfully', 'coffee-shop')
        );
    }

    /**
     * Delete promotion
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'promotion') {
            return $this->format_error(__('Promotion not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete promotion', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Promotion deleted successfully', 'coffee-shop'));
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $categories = wp_get_post_terms($post->ID, 'promotion_category');
        $category_slugs = array_map(function($cat) {
            return $cat->slug;
        }, $categories);

        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'subtitle'        => array(
                'en' => get_post_meta($post->ID, 'subtitle_en', true),
                'id' => get_post_meta($post->ID, 'subtitle_id', true),
            ),
            'description'     => array(
                'en' => get_post_meta($post->ID, 'description_en', true),
                'id' => get_post_meta($post->ID, 'description_id', true),
            ),
            'ctaText'         => array(
                'en' => get_post_meta($post->ID, 'cta_text_en', true),
                'id' => get_post_meta($post->ID, 'cta_text_id', true),
            ),
            'icon'            => get_post_meta($post->ID, 'icon', true),
            'is_active'       => (bool) get_post_meta($post->ID, 'is_active', true),
            'categories'      => $category_slugs,
        );
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
    }
}