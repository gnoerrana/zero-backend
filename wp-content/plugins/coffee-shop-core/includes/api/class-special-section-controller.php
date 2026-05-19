<?php
/**
 * Special Section REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Special_Section_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'special-section';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all special sections
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => '__return_true',
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ),
        ));

        // Single section operations
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

        // Get categories for special sections
        register_rest_route($this->namespace, '/' . $this->rest_base . '/categories', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_categories'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    /**
     * Get all special sections
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'special_section',
            'posts_per_page' => $request->get_param('per_page') ?: 100,
            'paged'          => $request->get_param('page') ?: 1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        );

        // Filter by category
        if ($category = $request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'special_section_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $items[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response(array(
            'items' => $items,
            'total' => $query->found_posts,
        ));
    }

    /**
     * Get single special section
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'special_section') {
            return $this->format_error(__('Special section not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create special section
     */
    public function create_item($request) {
        $title = sanitize_text_field($request->get_param('title'));
        $description_en = wp_kses_post($request->get_param('description_en'));
        $description_id = wp_kses_post($request->get_param('description_id'));

        if (empty($title)) {
            return $this->format_error(__('Title is required', 'coffee-shop'), 'missing_title', 400);
        }

        $post_data = array(
            'post_type'    => 'special_section',
            'post_title'   => $title,
            'post_content' => '',
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save description meta fields
        if (!empty($description_en)) {
            update_post_meta($post_id, 'description_en', $description_en);
        }

        if (!empty($description_id)) {
            update_post_meta($post_id, 'description_id', $description_id);
        }

        // Handle featured image
        if ($image_id = $request->get_param('image_id')) {
            set_post_thumbnail($post_id, intval($image_id));
        }

        // Set category taxonomy
        if ($categories = $request->get_param('category')) {
            if (is_array($categories)) {
                $term_ids = array_map('intval', $categories);
                wp_set_object_terms($post_id, $term_ids, 'special_section_category');
            } else {
                wp_set_object_terms($post_id, $categories, 'special_section_category');
            }
        }

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Special section created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update special section
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'special_section') {
            return $this->format_error(__('Special section not found', 'coffee-shop'), 'not_found', 404);
        }

        $post_data = array('ID' => $post->ID);

        if ($title = $request->get_param('title')) {
            $post_data['post_title'] = sanitize_text_field($title);
        }

        if ($request->has_param('description_en')) {
            update_post_meta($post->ID, 'description_en', wp_kses_post($request->get_param('description_en')));
        }

        if ($request->has_param('description_id')) {
            update_post_meta($post->ID, 'description_id', wp_kses_post($request->get_param('description_id')));
        }

        wp_update_post($post_data);

        // Update featured image
        if ($request->has_param('image_id')) {
            set_post_thumbnail($post->ID, intval($request->get_param('image_id')));
        }

        // Update categories
        if ($request->has_param('category')) {
            $categories = $request->get_param('category');
            if (is_array($categories)) {
                $term_ids = array_map('intval', $categories);
                wp_set_object_terms($post->ID, $term_ids, 'special_section_category');
            } else {
                wp_set_object_terms($post->ID, $categories, 'special_section_category');
            }
        }

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Special section updated successfully', 'coffee-shop')
        );
    }

    /**
     * Delete special section
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'special_section') {
            return $this->format_error(__('Special section not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete special section', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Special section deleted successfully', 'coffee-shop'));
    }

    /**
     * Get special section categories
     */
    public function get_categories($request) {
        $terms = get_terms(array(
            'taxonomy'   => 'special_section_category',
            'hide_empty' => false,
        ));

        $categories = array();
        foreach ($terms as $term) {
            $categories[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
            );
        }

        return $this->format_response($categories);
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $categories = wp_get_post_terms($post->ID, 'special_section_category');
        $image_id = get_post_thumbnail_id($post->ID);

        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'description_en'  => get_post_meta($post->ID, 'description_en', true) ?: '',
            'description_id'  => get_post_meta($post->ID, 'description_id', true) ?: '',
            'image'           => $image_id ? wp_get_attachment_url($image_id) : null,
            'image_id'        => $image_id ? intval($image_id) : 0,
            'categories'      => wp_list_pluck($categories, 'term_id'),
            'category_names'  => wp_list_pluck($categories, 'name'),
            'slug'            => $post->post_name,
            'status'          => $post->post_status,
            'date'            => $post->post_date,
        );
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
                'default'           => 100,
            ),
            'category' => array(
                'description'       => __('Filter by category slug.', 'coffee-shop'),
                'type'              => 'string',
            ),
        );
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
    }
}