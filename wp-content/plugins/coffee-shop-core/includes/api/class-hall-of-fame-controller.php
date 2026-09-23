<?php
/**
 * Hall of Fame REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Hall_Of_Fame_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'hall-of-fame';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all / create
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
            ),
        ));

        // Single item operations
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
     * Get all Hall of Fame items (dashboard sees everything, incl. inactive;
     * the public site is responsible for filtering by is_active when rendering)
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => Coffee_Shop_Hall_Of_Fame::POST_TYPE,
            'posts_per_page' => $request->get_param('per_page') ?: 100,
            'paged'          => $request->get_param('page') ?: 1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        );

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
     * Get single item
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== Coffee_Shop_Hall_Of_Fame::POST_TYPE) {
            return $this->format_error(__('Hall of Fame item not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create item
     */
    public function create_item($request) {
        $title_en = sanitize_text_field($request->get_param('title_en'));

        if (empty($title_en)) {
            return $this->format_error(__('English title is required', 'coffee-shop'), 'missing_title', 400);
        }

        $post_data = array(
            'post_type'   => Coffee_Shop_Hall_Of_Fame::POST_TYPE,
            'post_title'  => $title_en,
            'post_status' => 'publish',
            'menu_order'  => intval($request->get_param('ordering_num')),
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $this->save_meta($post_id, $request);

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Hall of Fame item created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update item
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== Coffee_Shop_Hall_Of_Fame::POST_TYPE) {
            return $this->format_error(__('Hall of Fame item not found', 'coffee-shop'), 'not_found', 404);
        }

        $post_data = array('ID' => $post->ID);

        if ($request->has_param('title_en')) {
            $post_data['post_title'] = sanitize_text_field($request->get_param('title_en'));
        }

        if ($request->has_param('ordering_num')) {
            $post_data['menu_order'] = intval($request->get_param('ordering_num'));
        }

        wp_update_post($post_data);

        $this->save_meta($post->ID, $request);

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Hall of Fame item updated successfully', 'coffee-shop')
        );
    }

    /**
     * Save meta fields shared by create/update
     */
    private function save_meta($post_id, $request) {
        if ($request->has_param('media_type')) {
            $media_type = $request->get_param('media_type');
            if (in_array($media_type, Coffee_Shop_Hall_Of_Fame::get_media_types(), true)) {
                update_post_meta($post_id, 'media_type', $media_type);
            }
        }

        if ($request->has_param('media_id')) {
            update_post_meta($post_id, 'media_id', intval($request->get_param('media_id')));
        }

        if ($request->has_param('media_url')) {
            update_post_meta($post_id, 'media_url', esc_url_raw($request->get_param('media_url')));
        }

        if ($request->has_param('text_position')) {
            $text_position = $request->get_param('text_position');
            if (in_array($text_position, Coffee_Shop_Hall_Of_Fame::get_text_positions(), true)) {
                update_post_meta($post_id, 'text_position', $text_position);
            }
        }

        if ($request->has_param('title_en')) {
            update_post_meta($post_id, 'title_en', sanitize_text_field($request->get_param('title_en')));
        }

        if ($request->has_param('title_id')) {
            update_post_meta($post_id, 'title_id', sanitize_text_field($request->get_param('title_id')));
        }

        if ($request->has_param('description_en')) {
            update_post_meta($post_id, 'description_en', sanitize_textarea_field($request->get_param('description_en')));
        }

        if ($request->has_param('description_id')) {
            update_post_meta($post_id, 'description_id', sanitize_textarea_field($request->get_param('description_id')));
        }

        if ($request->has_param('is_active')) {
            update_post_meta($post_id, 'is_active', $request->get_param('is_active') ? 1 : 0);
        }
    }

    /**
     * Delete item
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== Coffee_Shop_Hall_Of_Fame::POST_TYPE) {
            return $this->format_error(__('Hall of Fame item not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete Hall of Fame item', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Hall of Fame item deleted successfully', 'coffee-shop'));
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'media_type'      => get_post_meta($post->ID, 'media_type', true) ?: 'image',
            'media_id'        => intval(get_post_meta($post->ID, 'media_id', true)),
            'media_url'       => get_post_meta($post->ID, 'media_url', true) ?: '',
            'text_position'   => get_post_meta($post->ID, 'text_position', true) ?: 'bottom',
            'title_en'        => get_post_meta($post->ID, 'title_en', true) ?: '',
            'title_id'        => get_post_meta($post->ID, 'title_id', true) ?: '',
            'description_en'  => get_post_meta($post->ID, 'description_en', true) ?: '',
            'description_id'  => get_post_meta($post->ID, 'description_id', true) ?: '',
            'is_active'       => $this->get_boolean_meta($post->ID, 'is_active'),
            'ordering_num'    => intval($post->menu_order),
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
                'description' => __('Current page of the collection.', 'coffee-shop'),
                'type'        => 'integer',
                'default'     => 1,
            ),
            'per_page' => array(
                'description' => __('Maximum number of items to be returned.', 'coffee-shop'),
                'type'        => 'integer',
                'default'     => 100,
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
