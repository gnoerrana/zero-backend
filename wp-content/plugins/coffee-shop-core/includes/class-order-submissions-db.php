<?php
/**
 * Database Handler for Order Submissions
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Order_Submissions_DB {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coffee_orders_submission';
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Order submissions table
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            order_post_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'pending',
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            pickup_location_id bigint(20) UNSIGNED DEFAULT 0,
            pickup_location_name varchar(100) NOT NULL,
            pickup_time datetime NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0,
            tax decimal(10,2) NOT NULL DEFAULT 0,
            discount decimal(10,2) NOT NULL DEFAULT 0,
            total decimal(10,2) NOT NULL DEFAULT 0,
            payment_method varchar(50) NOT NULL,
            payment_status varchar(50) NOT NULL DEFAULT 'pending',
            points_earned int(11) DEFAULT 0,
            points_redeemed int(11) DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY order_post_id (order_post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert order submission
     */
    public function insert($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'order_id'              => sanitize_text_field($data['order_id'] ?? ''),
                'order_post_id'         => (int) ($data['order_post_id'] ?? 0),
                'status'                => sanitize_text_field($data['status'] ?? 'pending'),
                'first_name'            => sanitize_text_field($data['first_name'] ?? ''),
                'last_name'             => sanitize_text_field($data['last_name'] ?? ''),
                'email'                 => sanitize_email($data['email'] ?? ''),
                'phone'                 => sanitize_text_field($data['phone'] ?? ''),
                'pickup_location_id'    => (int) ($data['pickup_location_id'] ?? 0),
                'pickup_location_name'  => sanitize_text_field($data['pickup_location_name'] ?? ''),
                'pickup_time'           => sanitize_text_field($data['pickup_time'] ?? ''),
                'subtotal'              => (float) ($data['subtotal'] ?? 0),
                'tax'                   => (float) ($data['tax'] ?? 0),
                'discount'              => (float) ($data['discount'] ?? 0),
                'total'                 => (float) ($data['total'] ?? 0),
                'payment_method'        => sanitize_text_field($data['payment_method'] ?? ''),
                'payment_status'        => sanitize_text_field($data['payment_status'] ?? 'pending'),
                'points_earned'         => (int) ($data['points_earned'] ?? 0),
                'points_redeemed'       => (int) ($data['points_redeemed'] ?? 0),
                'notes'                 => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%d', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get order submission by ID
     */
    public function get_by_id($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }

    /**
     * Get order submission by order_id (Midtrans order ID)
     */
    public function get_by_order_id($order_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE order_id = %s",
                $order_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get order submission by WordPress post ID
     */
    public function get_by_post_id($post_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE order_post_id = %d",
                $post_id
            ),
            ARRAY_A
        );
    }

    /**
     * Update order_post_id
     */
    public function update_post_id($id, $post_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array('order_post_id' => (int) $post_id),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Get all order submissions
     */
    public function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $where = '';
        if (!empty($args['status'])) {
            $where = $wpdb->prepare(" WHERE status = %s", $args['status']);
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name}{$where} ORDER BY created_at DESC LIMIT {$args['offset']}, {$args['limit']}",
            ARRAY_A
        );
    }

    /**
     * Update order submission
     */
    public function update($id, $data) {
        global $wpdb;

        $update_data = array();
        $format = array();

        $allowed_fields = array(
            'status' => '%s',
            'payment_status' => '%s',
            'notes' => '%s',
            'first_name' => '%s',
            'last_name' => '%s',
            'email' => '%s',
            'phone' => '%s',
        );

        foreach ($allowed_fields as $field => $format_str) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = $format_str;
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete order submission
     */
    public function delete($id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
}