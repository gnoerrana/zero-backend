<?php
/**
 * Order Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Order {

    const POST_TYPE = 'order';

    // Order status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_PREPARING  = 'preparing';
    const STATUS_READY      = 'ready';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Orders', 'coffee-shop'),
            'singular_name'         => __('Order', 'coffee-shop'),
            'menu_name'             => __('Orders', 'coffee-shop'),
            'all_items'             => __('All Orders', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Order', 'coffee-shop'),
            'edit_item'             => __('Edit Order', 'coffee-shop'),
            'new_item'              => __('New Order', 'coffee-shop'),
            'view_item'             => __('View Order', 'coffee-shop'),
            'search_items'          => __('Search Orders', 'coffee-shop'),
            'not_found'             => __('No orders found', 'coffee-shop'),
            'not_found_in_trash'    => __('No orders found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-cart',
            'supports'            => array('title', 'custom-fields'),
            'show_in_rest'        => true,
            'rest_base'           => 'orders',
        );

        register_post_type(self::POST_TYPE, $args);

        // Register custom status
        self::register_statuses();

        // Register meta fields
        self::register_meta_fields();
    }

    /**
     * Register custom order statuses
     */
    private static function register_statuses() {
        $statuses = array(
            self::STATUS_PENDING   => __('Pending', 'coffee-shop'),
            self::STATUS_PREPARING => __('Preparing', 'coffee-shop'),
            self::STATUS_READY     => __('Ready for Pickup', 'coffee-shop'),
            self::STATUS_COMPLETED => __('Completed', 'coffee-shop'),
            self::STATUS_CANCELLED => __('Cancelled', 'coffee-shop'),
        );

        foreach ($statuses as $status => $label) {
            register_post_status($status, array(
                'label'                     => $label,
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'coffee-shop'),
            ));
        }
    }

    /**
     * Register meta fields
     */
    private static function register_meta_fields() {
        $meta_fields = array(
            'customer_id' => array(
                'type'         => 'integer',
                'description'  => __('Customer user ID', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'customer_name' => array(
                'type'         => 'string',
                'description'  => __('Customer name', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'customer_email' => array(
                'type'         => 'string',
                'description'  => __('Customer email', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'customer_phone' => array(
                'type'         => 'string',
                'description'  => __('Customer phone number', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'pickup_location_id' => array(
                'type'         => 'integer',
                'description'  => __('Pickup location ID', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'pickup_location_name' => array(
                'type'         => 'string',
                'description'  => __('Pickup location name', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'pickup_time' => array(
                'type'         => 'string',
                'description'  => __('Requested pickup time', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'order_items' => array(
                'type'         => 'array',
                'description'  => __('Order items', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'       => 'object',
                            'properties' => array(
                                'menu_item_id' => array('type' => 'integer'),
                                'name'         => array('type' => 'string'),
                                'quantity'     => array('type' => 'integer'),
                                'unit_price'   => array('type' => 'number'),
                                'subtotal'     => array('type' => 'number'),
                                'notes'        => array('type' => 'string'),
                            ),
                        ),
                    ),
                ),
            ),
            'subtotal' => array(
                'type'         => 'number',
                'description'  => __('Order subtotal', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'tax' => array(
                'type'         => 'number',
                'description'  => __('Tax amount', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'discount' => array(
                'type'         => 'number',
                'description'  => __('Discount amount', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'total' => array(
                'type'         => 'number',
                'description'  => __('Order total', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'payment_method' => array(
                'type'         => 'string',
                'description'  => __('Payment method: cash, card, ewallet', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'payment_status' => array(
                'type'         => 'string',
                'description'  => __('Payment status: pending, paid, failed, refunded', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'points_earned' => array(
                'type'         => 'integer',
                'description'  => __('Points earned from this order', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'points_redeemed' => array(
                'type'         => 'integer',
                'description'  => __('Points redeemed in this order', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'notes' => array(
                'type'         => 'string',
                'description'  => __('Order notes', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }

    /**
     * Get all order statuses
     */
    public static function get_statuses() {
        return array(
            self::STATUS_PENDING   => __('Pending', 'coffee-shop'),
            self::STATUS_PREPARING => __('Preparing', 'coffee-shop'),
            self::STATUS_READY     => __('Ready for Pickup', 'coffee-shop'),
            self::STATUS_COMPLETED => __('Completed', 'coffee-shop'),
            self::STATUS_CANCELLED => __('Cancelled', 'coffee-shop'),
        );
    }

    /**
     * Create new order
     */
    public static function create($data) {
        $order_data = array(
            'post_type'    => self::POST_TYPE,
            'post_title'   => sprintf('Order #%s', date('YmdHis')),
            'post_status'  => self::STATUS_PENDING,
            'post_author'  => $data['customer_id'] ?? 0,
        );

        $order_id = wp_insert_post($order_data);

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Update order title with ID
        wp_update_post(array(
            'ID'         => $order_id,
            'post_title' => sprintf('Order #%d', $order_id),
        ));

        // Save meta fields
        foreach ($data as $key => $value) {
            update_post_meta($order_id, $key, $value);
        }

        return $order_id;
    }

    /**
     * Update order status
     */
    public static function update_status($order_id, $status) {
        $valid_statuses = array_keys(self::get_statuses());

        if (!in_array($status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid order status', 'coffee-shop'));
        }

        return wp_update_post(array(
            'ID'          => $order_id,
            'post_status' => $status,
        ));
    }
}
