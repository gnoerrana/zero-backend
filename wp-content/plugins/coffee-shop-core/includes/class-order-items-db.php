<?php
/**
 * Database Handler for Order Items
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Order_Items_DB {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coffee_order_items';
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) UNSIGNED NOT NULL,
            order_id varchar(100) NOT NULL,
            menu_item_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            custom_options longtext,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY order_id (order_id),
            KEY menu_item_id (menu_item_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert order item
     */
    public function insert($data) {
        global $wpdb;

        // Check if order_id column exists, if not add it
        $this->ensure_order_id_column();

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'submission_id'  => (int) $data['submission_id'],
                'order_id'       => isset($data['order_id']) ? sanitize_text_field($data['order_id']) : '',
                'menu_item_id'   => (int) ($data['menu_item_id'] ?? 0),
                'name'           => sanitize_text_field($data['name'] ?? ''),
                'quantity'       => (int) ($data['quantity'] ?? 1),
                'unit_price'     => (float) ($data['unit_price'] ?? 0),
                'subtotal'       => (float) ($data['subtotal'] ?? 0),
                'custom_options' => maybe_serialize($data['custom_options'] ?? array()),
                'notes'          => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
            ),
            array('%d', '%s', '%d', '%s', '%d', '%f', '%f', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Ensure order_id column exists in the table
     */
    private function ensure_order_id_column() {
        global $wpdb;

        $column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'order_id'
            )
        );

        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN order_id varchar(100) NOT NULL DEFAULT ''");
        }
    }

    /**
     * Get items by submission ID
     */
    public function get_by_submission_id($submission_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE submission_id = %d ORDER BY id ASC",
                $submission_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get items by order post ID (for backward compatibility)
     */
    public function get_by_post_id($order_post_id) {
        global $wpdb;

        // Get submission record for this order using post_id
        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order_post_id);

        if (!$submission) {
            return array();
        }

        return $this->get_by_submission_id($submission['id']);
    }

    /**
     * Delete items by submission ID
     */
    public function delete_by_submission_id($submission_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('submission_id' => $submission_id),
            array('%d')
        );
    }

    /**
     * Delete items by order post ID (for backward compatibility)
     */
    public function delete_by_order_id($order_post_id) {
        global $wpdb;

        // Get submission record for this order
        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order_post_id);

        if (!$submission) {
            return 0;
        }

        return $this->delete_by_submission_id($submission['id']);
    }
}