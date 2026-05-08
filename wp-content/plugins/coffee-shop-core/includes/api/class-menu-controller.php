<?php
/**
 * Menu REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Menu_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'menu';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all menu items
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

        // Get single menu item
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

        // Get menu categories
        register_rest_route($this->namespace, '/' . $this->rest_base . '/categories', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_categories'),
                'permission_callback' => '__return_true',
            ),
        ));

        // Get popular items
        register_rest_route($this->namespace, '/' . $this->rest_base . '/popular', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_popular_items'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    /**
     * Get all menu items
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'menu_item',
            'posts_per_page' => $request->get_param('per_page') ?: 100,
            'paged'          => $request->get_param('page') ?: 1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        );

        // Filter by category
        if ($category = $request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'menu_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        // Filter by availability
        if ($request->get_param('available_only')) {
            $args['meta_query'] = array(
                array(
                    'key'     => 'is_available',
                    'value'   => '1',
                    'compare' => '=',
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
     * Get single menu item
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'menu_item') {
            return $this->format_error(__('Menu item not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create menu item
     */
    public function create_item($request) {
        $post_data = array(
            'post_type'    => 'menu_item',
            'post_title'   => $request->get_param('name'),
            'post_content' => $request->get_param('description') ?: '',
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        $meta_fields = array('price', 'category', 'is_available', 'preparation_time', 'calories', 'ingredients', 'allergens', 'points_value');
        foreach ($meta_fields as $field) {
            if ($value = $request->get_param($field)) {
                update_post_meta($post_id, $field, $value);
            }
        }

        // Set featured image
        if ($image_id = $request->get_param('image_id')) {
            set_post_thumbnail($post_id, $image_id);
        }

        // Set category
        if ($category = $request->get_param('category')) {
            wp_set_object_terms($post_id, $category, 'menu_category');
        }

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Menu item created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update menu item
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'menu_item') {
            return $this->format_error(__('Menu item not found', 'coffee-shop'), 'not_found', 404);
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
        $meta_fields = array('price', 'category', 'is_available', 'preparation_time', 'calories', 'ingredients', 'allergens', 'points_value');
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post->ID, $field, $request->get_param($field));
            }
        }

        // Update featured image
        if ($request->has_param('image_id')) {
            set_post_thumbnail($post->ID, $request->get_param('image_id'));
        }

        // Update category
        if ($category = $request->get_param('category')) {
            wp_set_object_terms($post->ID, $category, 'menu_category');
        }

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Menu item updated successfully', 'coffee-shop')
        );
    }

    /**
     * Delete menu item
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'menu_item') {
            return $this->format_error(__('Menu item not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete menu item', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Menu item deleted successfully', 'coffee-shop'));
    }

    /**
     * Get categories
     */
    public function get_categories($request) {
        $terms = get_terms(array(
            'taxonomy'   => 'menu_category',
            'hide_empty' => true,
        ));

        $categories = array();
        foreach ($terms as $term) {
            $categories[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
                'icon'        => get_term_meta($term->term_id, 'icon', true),
                'color'       => get_term_meta($term->term_id, 'color', true),
            );
        }

        return $this->format_response($categories);
    }

    /**
     * Get popular items
     */
    public function get_popular_items($request) {
        // Get items ordered by order count
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT pm.meta_value as menu_item_id, COUNT(*) as order_count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'order_items'
            AND p.post_type = 'order'
            AND p.post_status IN ('completed', 'ready', 'preparing')
            GROUP BY pm.meta_value
            ORDER BY order_count DESC
            LIMIT 10"
        );

        $popular_ids = array();
        foreach ($results as $row) {
            $items = maybe_unserialize($row->menu_item_id);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['menu_item_id'])) {
                        $popular_ids[] = $item['menu_item_id'];
                    }
                }
            }
        }

        $popular_ids = array_unique($popular_ids);
        $items = array();

        foreach (array_slice($popular_ids, 0, 10) as $id) {
            $post = get_post($id);
            if ($post && $post->post_type === 'menu_item') {
                $items[] = $this->prepare_item_for_response($post, $request);
            }
        }

        return $this->format_response($items);
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $categories = wp_get_post_terms($post->ID, 'menu_category');
        $category = !empty($categories) ? $categories[0]->slug : '';

        return array(
            'id'              => $post->ID,
            'name'            => $post->post_title,
            'description'     => $post->post_content,
            'price'           => (float) get_post_meta($post->ID, 'price', true),
            'category'        => get_post_meta($post->ID, 'category', true) ?: $category,
            'image'           => get_the_post_thumbnail_url($post->ID, 'large'),
            'thumbnail'       => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
            'is_available'    => (bool) get_post_meta($post->ID, 'is_available', true),
            'preparation_time'=> (int) get_post_meta($post->ID, 'preparation_time', true) ?: 5,
            'calories'        => (int) get_post_meta($post->ID, 'calories', true),
            'ingredients'     => get_post_meta($post->ID, 'ingredients', true),
            'allergens'       => get_post_meta($post->ID, 'allergens', true),
            'points_value'    => (int) get_post_meta($post->ID, 'points_value', true) ?: 10,
        );
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
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
            'available_only' => array(
                'description'       => __('Only show available items.', 'coffee-shop'),
                'type'              => 'boolean',
                'default'           => false,
            ),
        );
    }
}
