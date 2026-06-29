<?php
/**
 * Database Handler for Midtrans Transactions
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Midtrans_DB {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'midtrans_transaction_status';
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            order_post_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            status_code varchar(10) NOT NULL,
            status_message varchar(255) NOT NULL,
            transaction_id varchar(100) NOT NULL,
            gross_amount decimal(10,2) NOT NULL,
            payment_type varchar(50) NOT NULL,
            transaction_time datetime NOT NULL,
            transaction_status varchar(50) NOT NULL,
            va_numbers longtext,
            fraud_status varchar(50) DEFAULT '',
            settlement_time datetime DEFAULT NULL,
            expiry_time datetime DEFAULT NULL,
            currency varchar(10) DEFAULT 'IDR',
            raw_response longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY order_post_id (order_post_id),
            KEY transaction_id (transaction_id),
            KEY transaction_status (transaction_status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert transaction status
     */
    public function insert($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'order_id'            => sanitize_text_field($data['order_id']),
                'order_post_id'       => (int) ($data['order_post_id'] ?? 0),
                'status_code'         => sanitize_text_field($data['status_code']),
                'status_message'      => sanitize_text_field($data['status_message']),
                'transaction_id'      => sanitize_text_field($data['transaction_id']),
                'gross_amount'        => (float) $data['gross_amount'],
                'payment_type'        => sanitize_text_field($data['payment_type']),
                'transaction_time'    => sanitize_text_field($data['transaction_time']),
                'transaction_status'  => sanitize_text_field($data['transaction_status']),
                'va_numbers'          => isset($data['va_numbers']) ? maybe_serialize($data['va_numbers']) : '',
                'fraud_status'        => sanitize_text_field($data['fraud_status'] ?? ''),
                'settlement_time'     => sanitize_text_field($data['settlement_time'] ?? ''),
                'expiry_time'         => sanitize_text_field($data['expiry_time'] ?? ''),
                'currency'            => sanitize_text_field($data['currency'] ?? 'IDR'),
                'raw_response'        => maybe_serialize($data['raw_response'] ?? $data),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get transaction by order ID
     */
    public function get_by_order_id($order_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE order_id = %s ORDER BY created_at DESC",
                $order_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get transaction by transaction ID
     */
    public function get_by_transaction_id($transaction_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE transaction_id = %s",
                $transaction_id
            ),
            ARRAY_A
        );
    }

    /**
     * Update order post ID relationship
     */
    public function update_order_relation($transaction_id, $order_post_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array('order_post_id' => (int) $order_post_id),
            array('transaction_id' => $transaction_id),
            array('%d'),
            array('%s')
        );
    }

/**
      * Find order post ID by order_id string
      */
    public function find_order_post_id($order_id) {
        global $wpdb;

        // First, try to find via coffee_orders_submission table (where order_id matches Midtrans order format)
        $submission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT order_post_id FROM {$wpdb->prefix}coffee_orders_submission WHERE order_id = %s",
                $order_id
            ),
            ARRAY_A
        );
        if ($submission && !empty($submission['order_post_id'])) {
            return (int) $submission['order_post_id'];
        }

        // Fallback: try to find order by order_id pattern
        $clean_order_id = str_replace('ORD-', '', $order_id);

        // Check if clean_order_id looks like a numeric post ID
        if (is_numeric($clean_order_id)) {
            $order = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'order' 
                    AND ID = %d",
                    (int) $clean_order_id
                )
            );
        }

        // Also try to find by post_title LIKE
        if (!$order) {
            $order = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'order' 
                    AND post_title LIKE %s",
                    '%' . $wpdb->esc_like($clean_order_id) . '%'
                )
            );
        }

        return $order ?: 0;
    }
}