<?php
/**
 * Database Handler for Customizations
 */

defined('ABSPATH') || exit;

class CSC_Customizations_DB {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'csc_custom_options';
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            option_name varchar(255) NOT NULL,
            options longtext NOT NULL,
            categories longtext NOT NULL,
            required tinyint(1) DEFAULT 0,
            selection_type varchar(20) DEFAULT 'multi',
            selection_type_frontend varchar(20) DEFAULT 'multi_select',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Get all custom options
     */
    public function get_all_customizations() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY id ASC", ARRAY_A);
    }

    /**
     * Get single custom option
     */
    public function get_customization($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Insert customization
     */
    public function insert_customization($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'option_name' => sanitize_text_field($data['option_name']),
                'options' => maybe_serialize($data['options']),
                'categories' => maybe_serialize($data['categories']),
                'required' => isset($data['required']) ? (bool) $data['required'] : false,
                'selection_type' => isset($data['selection_type']) ? sanitize_text_field($data['selection_type']) : 'multi',
                'selection_type_frontend' => isset($data['selection_type_frontend']) ? sanitize_text_field($data['selection_type_frontend']) : 'multi_select',
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update customization
     */
    public function update_customization($id, $data) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'option_name' => sanitize_text_field($data['option_name']),
                'options' => maybe_serialize($data['options']),
                'categories' => maybe_serialize($data['categories']),
                'required' => isset($data['required']) ? (bool) $data['required'] : false,
                'selection_type' => isset($data['selection_type']) ? sanitize_text_field($data['selection_type']) : 'multi',
                'selection_type_frontend' => isset($data['selection_type_frontend']) ? sanitize_text_field($data['selection_type_frontend']) : 'multi_select',
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%d', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Format customization for response
     */
    private function format_customization($customization) {
        return array(
            'id' => (int) $customization['id'],
            'option_name' => $customization['option_name'],
            'options' => maybe_unserialize($customization['options']),
            'categories' => maybe_unserialize($customization['categories']),
            'required' => (bool) $customization['required'],
            'selection_type' => $customization['selection_type'] ?: 'multi',
            'selection_type_frontend' => $customization['selection_type_frontend'] ?: 'multi_select',
            'created_at' => $customization['created_at'],
            'updated_at' => $customization['updated_at'],
        );
    }

    /**
     * Get menu categories
     */
    public function get_menu_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
        ));

        $options = array();
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->term_id] = $category->name;
            }
        }

        return $options;
    }
}