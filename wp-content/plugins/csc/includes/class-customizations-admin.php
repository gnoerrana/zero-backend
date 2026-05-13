<?php
/**
 * Admin Interface for Customizations
 */

defined('ABSPATH') || exit;

class CSC_Customizations_Admin {

    /**
     * Database handler
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new CSC_Customizations_DB();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_csc_save_customizations', array($this, 'ajax_save_customizations'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Custom Options under Menu
        add_submenu_page(
            'edit.php?post_type=menu_item',
            'Custom Options',
            'Custom Options',
            'manage_options',
            'csc-custom-options',
            array($this, 'admin_page')
        );

        // Store menu
        add_menu_page(
            'Store',
            'Store',
            'manage_options',
            'csc-store',
            array($this, 'store_settings_page'),
            'dashicons-store',
            25
        );

        add_submenu_page(
            'csc-store',
            'Store Settings',
            'Settings',
            'manage_options',
            'csc-store-settings',
            array($this, 'store_settings_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'menu_item_page_csc-custom-options' && $hook !== 'toplevel_page_csc-store' && $hook !== 'store_page_csc-store-settings') {
            return;
        }

        wp_enqueue_script(
            'csc-admin-js',
            CSC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CSC_VERSION,
            true
        );

        wp_enqueue_style(
            'csc-admin-css',
            CSC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CSC_VERSION
        );

        wp_localize_script('csc-admin-js', 'csc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csc_save_customizations'),
            'categories' => $this->db->get_menu_categories(),
        ));
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $customizations = $this->db->get_all_customizations();
        $categories = $this->db->get_menu_categories();

        ?>
        <div class="wrap">
            <h1>Custom Options</h1>

            <div id="csc-customizations-app">
                <div class="csc-customizations-list">
                    <div id="csc-customizations-container">
                        <?php foreach ($customizations as $index => $customization): ?>
                            <div class="csc-customization-item" data-id="<?php echo esc_attr($customization['id']); ?>">
                                <div class="csc-field-group">
                                    <label>Option Name</label>
                                    <input type="text" class="csc-option-name" value="<?php echo esc_attr($customization['option_name']); ?>" />
                                </div>

                                <div class="csc-field-group">
                                    <label>Options</label>
                                    <div class="csc-options-list">
                                        <?php
                                        $options = maybe_unserialize($customization['options']);
                                        if (!is_array($options)) {
                                            $options = array();
                                        }
                                        foreach ($options as $option):
                                        ?>
                                            <div class="csc-option-item">
                                                <input type="text" class="csc-option-label" value="<?php echo esc_attr($option['label']); ?>" placeholder="Label" />
                                                <input type="number" step="0.01" class="csc-option-price" value="<?php echo esc_attr($option['price']); ?>" placeholder="Price" />
                                                <button type="button" class="button csc-remove-option">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button csc-add-option">Add Option</button>
                                </div>

                                <div class="csc-field-group">
                                    <label>Apply to Categories</label>
                                    <select multiple class="csc-categories" data-placeholder="Select categories">
                                        <?php
                                        $selected_categories = maybe_unserialize($customization['categories']);
                                        if (!is_array($selected_categories)) {
                                            $selected_categories = array();
                                        }
                                        foreach ($categories as $cat_id => $cat_name):
                                        ?>
                                            <option value="<?php echo esc_attr($cat_id); ?>" <?php selected(in_array($cat_id, $selected_categories)); ?>>
                                                <?php echo esc_html($cat_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="csc-field-group">
                                    <label>
                                        <input type="checkbox" class="csc-required" <?php checked($customization['required']); ?> />
                                        Required
                                    </label>
                                </div>

                                <div class="csc-field-group">
                                    <label>Selection Type</label>
                                    <select class="csc-selection-type">
                                        <option value="single" <?php selected($customization['selection_type'], 'single'); ?>>Single Select</option>
                                        <option value="multi" <?php selected($customization['selection_type'], 'multi'); ?>>Multi Select</option>
                                    </select>
                                </div>

                                <button type="button" class="button csc-remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="button button-primary" id="csc-add-item">Add New Custom Option</button>
                </div>

                <div class="csc-save-section">
                    <button type="button" class="button button-primary" id="csc-save-changes">Save Changes</button>
                    <span id="csc-save-status"></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX save customizations
     */
    public function ajax_save_customizations() {
        if (!wp_verify_nonce($_POST['nonce'], 'csc_save_customizations')) {
            wp_die(__('Security check failed', 'csc'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'csc'));
        }

        $customizations = isset($_POST['customizations']) ? $_POST['customizations'] : array();

        $sanitized_data = array();
        foreach ($customizations as $customization) {
            if (empty($customization['option_name'])) {
                continue;
            }
            $sanitized_data[] = array(
                'id' => isset($customization['id']) ? intval($customization['id']) : null,
                'option_name' => sanitize_text_field($customization['option_name']),
                'options' => $customization['options'],
                'categories' => $customization['categories'],
                'required' => isset($customization['required']) ? (bool) $customization['required'] : false,
                'selection_type' => isset($customization['selection_type']) ? sanitize_text_field($customization['selection_type']) : 'multi',
            );
        }

        $this->save_customizations($sanitized_data);

        wp_send_json_success(__('Customizations saved successfully', 'csc'));
    }

    /**
     * Save customizations to database
     */
    private function save_customizations($customizations) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'csc_custom_options';

        $existing_ids = $wpdb->get_col("SELECT id FROM {$table_name}");

        $updated_ids = array();

        foreach ($customizations as $customization) {
            $data = array(
                'option_name' => $customization['option_name'],
                'options' => maybe_serialize($customization['options']),
                'categories' => maybe_serialize($customization['categories']),
                'required' => $customization['required'],
                'selection_type' => $customization['selection_type'],
            );

            if ($customization['id']) {
                $wpdb->update($table_name, $data, array('id' => $customization['id']), array('%s', '%s', '%s', '%d', '%s'), array('%d'));
                $updated_ids[] = $customization['id'];
            } else {
                $result = $wpdb->insert($table_name, $data, array('%s', '%s', '%s', '%d', '%s'));
                $updated_ids[] = $wpdb->insert_id;
            }
        }

        $to_delete = array_diff($existing_ids, $updated_ids);
        if (!empty($to_delete)) {
            $wpdb->query("DELETE FROM {$table_name} WHERE id IN (" . implode(',', $to_delete) . ")");
        }
    }

    /**
     * Add meta boxes to menu item edit page
     */
    public function add_meta_boxes() {
        global $post;
        if (!$post || $post->post_type !== 'menu_item') {
            return;
        }

        $categories = wp_get_post_terms($post->ID, 'menu_category', array('fields' => 'ids'));
        $customizations = $this->db->get_all_customizations();

        foreach ($customizations as $customization) {
            $customization_categories = maybe_unserialize($customization['categories']);
            if (array_intersect($categories, $customization_categories)) {
                add_meta_box(
                    'csc_option_' . $customization['id'],
                    $customization['option_name'],
                    array($this, 'render_meta_box'),
                    'menu_item',
                    'normal',
                    'default',
                    array('customization' => $customization)
                );
            }
        }
    }

    /**
     * Render meta box
     */
    public function render_meta_box($post, $metabox) {
        $customization = $metabox['args']['customization'];
        $selected_options = get_post_meta($post->ID, '_csc_option_' . $customization['id'], true);
        if (!is_array($selected_options)) {
            $selected_options = array($selected_options);
        }

        wp_nonce_field('csc_meta_box', 'csc_meta_box_nonce');

        $options = maybe_unserialize($customization['options']);
        if (!is_array($options)) {
            $options = array();
        }

        $selection_type = $customization['selection_type'] ?: 'multi';

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label>' . esc_html($customization['option_name']) . '</label></th>';
        echo '<td>';

        if ($selection_type === 'single') {
            foreach ($options as $option) {
                $checked = in_array($option['label'], $selected_options) ? ' checked' : '';
                echo '<label>';
                echo '<input type="radio" name="csc_option_' . $customization['id'] . '" value="' . esc_attr($option['label']) . '" data-price="' . esc_attr($option['price']) . '"' . $checked . ' /> ';
                echo esc_html($option['label']) . ' (+$' . number_format($option['price'], 2) . ')';
                echo '</label><br/>';
            }
        } else {
            foreach ($options as $option) {
                $checked = in_array($option['label'], $selected_options) ? ' checked' : '';
                echo '<label>';
                echo '<input type="checkbox" name="csc_option_' . $customization['id'] . '[]" value="' . esc_attr($option['label']) . '" data-price="' . esc_attr($option['price']) . '"' . $checked . ' /> ';
                echo esc_html($option['label']) . ' (+$' . number_format($option['price'], 2) . ')';
                echo '</label><br/>';
            }
        }

        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="csc_price_' . $customization['id'] . '">Custom Price (optional)</label></th>';
        echo '<td>';
        echo '<input type="number" step="0.01" name="csc_price_' . $customization['id'] . '" id="csc_price_' . $customization['id'] . '" value="' . esc_attr(get_post_meta($post->ID, '_csc_price_' . $customization['id'], true)) . '" />';
        echo '<p class="description">Override the default price adjustment</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['csc_meta_box_nonce']) || !wp_verify_nonce($_POST['csc_meta_box_nonce'], 'csc_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $customizations = $this->db->get_all_customizations();

        foreach ($customizations as $customization) {
            $option_key = 'csc_option_' . $customization['id'];
            $price_key = 'csc_price_' . $customization['id'];

            if ($customization['selection_type'] === 'single') {
                if (isset($_POST[$option_key])) {
                    update_post_meta($post_id, '_csc_option_' . $customization['id'], sanitize_text_field($_POST[$option_key]));
                }
            } else {
                if (isset($_POST[$option_key]) && is_array($_POST[$option_key])) {
                    $sanitized = array_map('sanitize_text_field', $_POST[$option_key]);
                    update_post_meta($post_id, '_csc_option_' . $customization['id'], $sanitized);
                }
            }

            if (isset($_POST[$price_key])) {
                update_post_meta($post_id, '_csc_price_' . $customization['id'], floatval($_POST[$price_key]));
            }
        }
    }

    /**
     * Store settings page
     */
    public function store_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_store_settings();
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }

        $settings = get_option('csc_store_settings', array());

        ?>
        <div class="wrap">
            <h1>Store Settings</h1>

            <form method="post" action="">
                <?php wp_nonce_field('csc_store_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="store_name">Store Name</label></th>
                        <td>
                            <input type="text" id="store_name" name="store_name" value="<?php echo esc_attr($settings['store_name'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="address">Address</label></th>
                        <td>
                            <textarea id="address" name="address" rows="3" class="regular-text"><?php echo esc_textarea($settings['address'] ?? ''); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="currency">Currency</label></th>
                        <td>
                            <select id="currency" name="currency">
                                <option value="USD" <?php selected($settings['currency'] ?? 'USD', 'USD'); ?>>USD ($)</option>
                                <option value="EUR" <?php selected($settings['currency'] ?? 'USD', 'EUR'); ?>>EUR (€)</option>
                                <option value="GBP" <?php selected($settings['currency'] ?? 'USD', 'GBP'); ?>>GBP (£)</option>
                                <option value="JPY" <?php selected($settings['currency'] ?? 'USD', 'JPY'); ?>>JPY (¥)</option>
                                <option value="IDR" <?php selected($settings['currency'] ?? 'USD', 'IDR'); ?>>IDR (Rp)</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="latitude">Latitude</label></th>
                        <td>
                            <input type="text" id="latitude" name="latitude" value="<?php echo esc_attr($settings['latitude'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="longitude">Longitude</label></th>
                        <td>
                            <input type="text" id="longitude" name="longitude" value="<?php echo esc_attr($settings['longitude'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save store settings
     */
    private function save_store_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'csc_store_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = array(
            'store_name' => sanitize_text_field($_POST['store_name'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'USD'),
            'latitude' => sanitize_text_field($_POST['latitude'] ?? ''),
            'longitude' => sanitize_text_field($_POST['longitude'] ?? ''),
        );

        update_option('csc_store_settings', $settings);
    }
}