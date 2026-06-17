<?php
/**
 * Orders REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Orders_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'orders';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all orders
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ),
        ));

        // Get single order
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
        ));

        // Update order
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/update', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ),
        ));

        // Delete order
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));

        // Delete submission (dedicated table)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/submission', array(
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_submission'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));

        // Update order status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/status', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_status'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args'                => array(
                    'status' => array(
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return in_array($param, array('pending', 'preparing', 'ready', 'completed', 'cancelled'));
                        },
                    ),
                ),
            ),
        ));

        // Get orders by customer
        register_rest_route($this->namespace, '/' . $this->rest_base . '/customer/(?P<customer_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_customer_orders'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
        ));
    }

    /**
     * Get all orders
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'order',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged'          => $request->get_param('page') ?: 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Filter by status
        if ($status = $request->get_param('status')) {
            $args['post_status'] = $status;
        }

        // Filter by date
        if ($date = $request->get_param('date')) {
            $args['date_query'] = array(
                array(
                    'after' => $date,
                    'inclusive' => true,
                ),
            );
        }

        $query = new WP_Query($args);
        $orders = array();

        foreach ($query->posts as $post) {
            $orders[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response(array(
            'orders' => $orders,
            'total'  => $query->found_posts,
            'pages'  => $query->max_num_pages,
        ));
    }

    /**
     * Get single order
     */
    public function get_item($request) {
        $order = get_post($request['id']);

        if (!$order || $order->post_type !== 'order') {
            return $this->format_error(__('Order not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($order, $request));
    }

    /**
     * Create order
     */
    public function create_item($request) {
        $order_data = array(
            'customer_id'          => $request->get_param('customer_id'),
            'customer_name'        => $request->get_param('customer_name'),
            'customer_email'       => $request->get_param('customer_email'),
            'customer_phone'       => $request->get_param('customer_phone'),
            'pickup_location_id'   => $request->get_param('pickup_location_id'),
            'pickup_location_name' => $request->get_param('pickup_location_name'),
            'pickup_time'          => $request->get_param('pickup_time'),
            'order_items'          => $request->get_param('order_items'),
            'subtotal'             => $request->get_param('subtotal'),
            'tax'                  => $request->get_param('tax'),
            'discount'             => $request->get_param('discount') ?: 0,
            'total'                => $request->get_param('total'),
            'payment_method'       => $request->get_param('payment_method'),
            'payment_status'       => 'pending',
            'points_earned'        => $request->get_param('points_earned') ?: 0,
            'points_redeemed'      => $request->get_param('points_redeemed') ?: 0,
            'notes'                => $request->get_param('notes'),
        );

        $order_id = Coffee_Shop_Order::create($order_data);

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Use order_id from request or format from WordPress post ID
        $requested_order_id = $request->get_param('order_id') ?: sprintf('Order #%d', $order_id);

        // Store order submission in dedicated table
        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission_id = $submissions_db->insert(array(
            'order_id'             => $requested_order_id,
            'order_post_id'        => $order_id,
            'status'               => 'pending',
            'first_name'           => $order_data['customer_name'],
            'last_name'            => '',
            'email'                => $order_data['customer_email'],
            'phone'                => $order_data['customer_phone'],
            'pickup_location_id'   => $order_data['pickup_location_id'],
            'pickup_location_name' => $order_data['pickup_location_name'],
            'pickup_time'          => $order_data['pickup_time'],
            'subtotal'             => $order_data['subtotal'],
            'tax'                  => $order_data['tax'],
            'discount'             => $order_data['discount'],
            'total'                => $order_data['total'],
            'payment_method'       => $order_data['payment_method'],
            'payment_status'       => 'pending',
            'points_earned'        => $order_data['points_earned'],
            'points_redeemed'      => $order_data['points_redeemed'],
            'notes'                => $order_data['notes'],
        ));

        // Store order items in dedicated table
        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $order_items = $request->get_param('order_items');
        if (is_array($order_items)) {
            foreach ($order_items as $item) {
                $order_items_db->insert(array(
                    'submission_id'  => $submission_id ?: 0,
                    'order_id'       => $requested_order_id ?: sprintf('Order #%d', $order_id),
                    'menu_item_id'   => $item['menu_item_id'] ?? 0,
                    'name'           => $item['name'] ?? '',
                    'quantity'       => $item['quantity'] ?? 1,
                    'unit_price'     => $item['unit_price'] ?? 0,
                    'subtotal'       => $item['subtotal'] ?? 0,
                    'custom_options' => $item['custom_options'] ?? array(),
                    'notes'          => $item['notes'] ?? '',
                ));
            }
        }

        // Award points to customer
        if ($order_data['points_earned'] > 0 && $order_data['customer_id']) {
            $this->award_points($order_data['customer_id'], $order_data['points_earned'], $order_id);
        }

        $order = get_post($order_id);
        return $this->format_response(
            $this->prepare_item_for_response($order, $request),
            __('Order created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update order
     */
    public function update_item($request) {
        $order = get_post($request['id']);

        if (!$order || $order->post_type !== 'order') {
            return $this->format_error(__('Order not found', 'coffee-shop'), 'not_found', 404);
        }

        $updatable_fields = array(
            'customer_name', 'customer_email', 'customer_phone',
            'pickup_location_id', 'pickup_location_name', 'pickup_time',
            'subtotal', 'tax', 'discount', 'total',
            'payment_method', 'payment_status', 'notes',
        );

        foreach ($updatable_fields as $field) {
            if ($value = $request->get_param($field)) {
                update_post_meta($order->ID, $field, $value);
            }
        }

        // Handle order_items update - sync with dedicated table
        if ($order_items = $request->get_param('order_items')) {
            $submissions_db = new Coffee_Shop_Order_Submissions_DB();
            $submission = $submissions_db->get_by_post_id($order->ID);

            // Remove existing items
            $order_items_db = new Coffee_Shop_Order_Items_DB();
            if ($submission) {
                $order_items_db->delete_by_submission_id($submission['id']);
            }

            // Insert updated items
            if (is_array($order_items)) {
                $requested_order_id = $submission['order_id'] ?? '';
                foreach ($order_items as $item) {
                    $order_items_db->insert(array(
                        'submission_id'  => $submission ? $submission['id'] : 0,
                        'order_id'       => $requested_order_id,
                        'menu_item_id'   => $item['menu_item_id'] ?? 0,
                        'name'           => $item['name'] ?? '',
                        'quantity'       => $item['quantity'] ?? 1,
                        'unit_price'     => $item['unit_price'] ?? 0,
                        'subtotal'       => $item['subtotal'] ?? 0,
                        'custom_options' => $item['custom_options'] ?? array(),
                        'notes'          => $item['notes'] ?? '',
                    ));
                }
            }
        }

        $order = get_post($order->ID);
        return $this->format_response(
            $this->prepare_item_for_response($order, $request),
            __('Order updated successfully', 'coffee-shop')
        );
    }

    /**
     * Update order status
     */
    public function update_status($request) {
        $order_id = $request['id'];
        $status = $request['status'];

        $result = Coffee_Shop_Order::update_status($order_id, $status);

        if (is_wp_error($result)) {
            return $result;
        }

        // If order is completed, finalize points
        if ($status === 'completed') {
            $customer_id = get_post_meta($order_id, 'customer_id', true);
            $points_earned = get_post_meta($order_id, 'points_earned', true);
            
            if ($customer_id && $points_earned) {
                $this->finalize_points($customer_id, $points_earned, $order_id);
            }
        }

        $order = get_post($order_id);
        return $this->format_response(
            $this->prepare_item_for_response($order, $request),
            __('Order status updated', 'coffee-shop')
        );
    }

    /**
     * Get customer orders
     */
    public function get_customer_orders($request) {
        $customer_id = $request['customer_id'];

        $args = array(
            'post_type'      => 'order',
            'posts_per_page' => 50,
            'meta_query'     => array(
                array(
                    'key'   => 'customer_id',
                    'value' => $customer_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);
        $orders = array();

        foreach ($query->posts as $post) {
            $orders[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response($orders);
    }

    /**
     * Delete order
     */
    public function delete_item($request) {
        $order = get_post($request['id']);

        if (!$order || $order->post_type !== 'order') {
            return $this->format_error(__('Order not found', 'coffee-shop'), 'not_found', 404);
        }

        // Delete order items from dedicated table
        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $order_items_db->delete_by_order_id(sprintf('Order #%d', $order->ID));

        // Delete order submission from dedicated table
        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order->ID);
        if ($submission) {
            $submissions_db->delete($submission['id']);
        }

        $result = wp_delete_post($order->ID, true);

        if (!$result) {
            return $this->format_error(__('Order could not be deleted', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Order deleted successfully', 'coffee-shop'), 200);
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($post->ID);

        return array(
            'id'                  => $post->ID,
            'order_number'        => $submission ? $submission['order_id'] : $post->post_title,
            'status'              => $submission ? $submission['status'] : $post->post_status,
            'customer_id'         => $submission && !empty($submission['customer_id']) ? $submission['customer_id'] : get_post_meta($post->ID, 'customer_id', true),
            'customer_name'       => $submission && !empty($submission['first_name']) ? $submission['first_name'] : get_post_meta($post->ID, 'customer_name', true),
            'customer_email'      => $submission && !empty($submission['email']) ? $submission['email'] : get_post_meta($post->ID, 'customer_email', true),
            'customer_phone'      => $submission && !empty($submission['phone']) ? $submission['phone'] : get_post_meta($post->ID, 'customer_phone', true),
            'pickup_location_id'  => $submission && !empty($submission['pickup_location_id']) ? $submission['pickup_location_id'] : get_post_meta($post->ID, 'pickup_location_id', true),
            'pickup_location_name'=> $submission && !empty($submission['pickup_location_name']) ? $submission['pickup_location_name'] : get_post_meta($post->ID, 'pickup_location_name', true),
            'pickup_time'         => $submission && !empty($submission['pickup_time']) ? $submission['pickup_time'] : get_post_meta($post->ID, 'pickup_time', true),
            'order_items'         => $submission ? $this->get_order_items_by_submission($submission['id']) : $this->get_order_items_by_order_id($post->ID),
            'subtotal'            => $submission && isset($submission['subtotal']) ? (float) $submission['subtotal'] : (float) get_post_meta($post->ID, 'subtotal', true),
            'tax'                 => $submission && isset($submission['tax']) ? (float) $submission['tax'] : (float) get_post_meta($post->ID, 'tax', true),
            'discount'            => $submission && isset($submission['discount']) ? (float) $submission['discount'] : (float) get_post_meta($post->ID, 'discount', true),
            'total'               => $submission && isset($submission['total']) ? (float) $submission['total'] : (float) get_post_meta($post->ID, 'total', true),
            'payment_method'      => $submission && !empty($submission['payment_method']) ? $submission['payment_method'] : get_post_meta($post->ID, 'payment_method', true),
            'payment_status'      => $submission && !empty($submission['payment_status']) ? $submission['payment_status'] : get_post_meta($post->ID, 'payment_status', true),
            'points_earned'       => $submission && isset($submission['points_earned']) ? (int) $submission['points_earned'] : (int) get_post_meta($post->ID, 'points_earned', true),
            'points_redeemed'     => $submission && isset($submission['points_redeemed']) ? (int) $submission['points_redeemed'] : (int) get_post_meta($post->ID, 'points_redeemed', true),
            'notes'               => $submission && !empty($submission['notes']) ? $submission['notes'] : get_post_meta($post->ID, 'notes', true),
            'created_at'          => $submission && !empty($submission['created_at']) ? $submission['created_at'] : $post->post_date,
            'updated_at'          => $submission && !empty($submission['updated_at']) ? $submission['updated_at'] : $post->post_modified,
        );
    }

    /**
     * Get order items by submission ID
     */
    private function get_order_items_by_submission($submission_id) {
        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $items = $order_items_db->get_by_submission_id($submission_id);

        foreach ($items as &$item) {
            if (isset($item['custom_options'])) {
                $item['custom_options'] = maybe_unserialize($item['custom_options']);
            }
        }

        return $items ?: array();
    }

    /**
     * Get order items by WordPress post ID (for backward compatibility)
     */
    private function get_order_items_by_order_id($order_post_id) {
        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $items = $order_items_db->get_by_post_id($order_post_id);

        foreach ($items as &$item) {
            if (isset($item['custom_options'])) {
                $item['custom_options'] = maybe_unserialize($item['custom_options']);
            }
        }

        return $items ?: array();
    }

    /**
     * Award points to customer
     */
    private function award_points($customer_id, $points, $order_id) {
        $current_points = get_user_meta($customer_id, 'reward_points', true) ?: 0;
        update_user_meta($customer_id, 'reward_points', $current_points + $points);

        // Log transaction
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'coffee_points_transactions',
            array(
                'user_id'          => $customer_id,
                'points'           => $points,
                'transaction_type' => 'earn',
                'reference_id'     => $order_id,
                'reference_type'   => 'order',
                'description'      => 'Points earned from order #' . $order_id,
                'created_at'       => current_time('mysql'),
            )
        );
    }

    /**
     * Finalize points (called when order is completed)
     */
    private function finalize_points($customer_id, $points, $order_id) {
        // Update tier based on total points
        $total_points = get_user_meta($customer_id, 'reward_points', true) ?: 0;
        $tier = $this->calculate_tier($total_points);
        update_user_meta($customer_id, 'reward_tier', $tier);
    }

    /**
     * Calculate tier based on points
     */
    private function calculate_tier($points) {
        if ($points >= 10000) return 'platinum';
        if ($points >= 5000) return 'gold';
        if ($points >= 2000) return 'silver';
        return 'bronze';
    }

    /**
     * Permission callbacks
     */
    public function get_items_permissions_check($request) {
        return true; // Public for now, adjust as needed
    }

    public function get_item_permissions_check($request) {
        return true;
    }

    public function create_item_permissions_check($request) {
        return true;
    }

    public function update_item_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function delete_item_permissions_check($request) {
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
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __('Maximum number of items to be returned.', 'coffee-shop'),
                'type'              => 'integer',
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'description'       => __('Filter by order status.', 'coffee-shop'),
                'type'              => 'string',
                'enum'              => array('pending', 'preparing', 'ready', 'completed', 'cancelled'),
            ),
            'date' => array(
                'description'       => __('Filter by date.', 'coffee-shop'),
                'type'              => 'string',
            ),
        );
    }

    /**
     * Delete submission record
     */
    public function delete_submission($request) {
        $order = get_post($request['id']);

        if (!$order || $order->post_type !== 'order') {
            return $this->format_error(__('Order not found', 'coffee-shop'), 'not_found', 404);
        }

        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order->ID);

        if ($submission) {
            $submissions_db->delete($submission['id']);
            return $this->format_response(null, __('Submission deleted successfully', 'coffee-shop'), 200);
        }

        return $this->format_response(null, __('No submission found for this order', 'coffee-shop'), 200);
    }
}