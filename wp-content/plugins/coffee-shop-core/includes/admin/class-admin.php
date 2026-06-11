<?php
/**
 * Admin Class
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('manage_order_posts_columns', array($this, 'order_columns'));
        add_action('manage_order_posts_custom_column', array($this, 'order_column_content'), 10, 2);
        add_filter('manage_menu_item_posts_columns', array($this, 'menu_item_columns'));
        add_action('manage_menu_item_posts_custom_column', array($this, 'menu_item_column_content'), 10, 2);
        add_filter('manage_special_section_posts_columns', array($this, 'special_section_columns'));
        add_action('manage_special_section_posts_custom_column', array($this, 'special_section_column_content'), 10, 2);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_order', array($this, 'save_order_meta'));
        add_action('save_post_menu_item', array($this, 'save_menu_item_meta'));
        add_action('save_post_promotion', array($this, 'save_promotion_meta'));
        add_action('save_post_special_section', array($this, 'save_special_section_meta'));

        // Ensure user roles are available
        add_action('admin_init', array($this, 'ensure_user_roles'));

        // User profile fields
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('user_new_form', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        add_action('user_register', array($this, 'save_user_profile_fields'));

        // Allow SVG uploads
        add_filter('upload_mimes', array($this, 'allow_svg_upload'));

        // AJAX handlers for media uploads
        add_action('wp_ajax_coffee_shop_upload_media', array($this, 'ajax_upload_media'));
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        // Dashboard
        add_menu_page(
            __('Coffee Shop Dashboard', 'coffee-shop'),
            __('Coffee Shop', 'coffee-shop'),
            'manage_options',
            'coffee-shop-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-coffee',
            30
        );

        // Settings
        add_submenu_page(
            'coffee-shop-dashboard',
            __('Settings', 'coffee-shop'),
            __('Settings', 'coffee-shop'),
            'manage_options',
            'coffee-shop-settings',
            array($this, 'render_settings')
        );

        // Special Section
        add_submenu_page(
            'coffee-shop-dashboard',
            __('Special Sections', 'coffee-shop'),
            __('Special Sections', 'coffee-shop'),
            'manage_options',
            'edit.php?post_type=special_section'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'coffee-shop') !== false || strpos($hook, 'special_section') !== false) {
            wp_enqueue_style(
                'coffee-shop-admin',
                COFFEE_SHOP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                COFFEE_SHOP_VERSION
            );

            wp_enqueue_script(
                'coffee-shop-admin',
                COFFEE_SHOP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                COFFEE_SHOP_VERSION,
                true
            );

            wp_localize_script('coffee-shop-admin', 'coffeeShopAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('coffee_shop_admin'),
            ));
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    /**
     * Custom columns for orders
     */
    public function order_columns($columns) {
        $new_columns = array(
            'cb'            => $columns['cb'],
            'order_number'  => __('Order', 'coffee-shop'),
            'customer'      => __('Customer', 'coffee-shop'),
            'items'         => __('Items', 'coffee-shop'),
            'total'         => __('Total', 'coffee-shop'),
            'pickup'        => __('Pickup Location', 'coffee-shop'),
            'status'        => __('Status', 'coffee-shop'),
            'date'          => __('Date', 'coffee-shop'),
        );
        return $new_columns;
    }

    /**
     * Order column content
     */
    public function order_column_content($column, $post_id) {
        switch ($column) {
            case 'order_number':
                $order = get_post($post_id);
                echo '<a href="' . get_edit_post_link($post_id) . '">' . esc_html($order->post_title) . '</a>';
                break;
            case 'customer':
                echo esc_html(get_post_meta($post_id, 'customer_name', true));
                break;
            case 'items':
                $items = get_post_meta($post_id, 'order_items', true);
                echo is_array($items) ? count($items) : 0;
                break;
            case 'total':
                echo 'Rp ' . number_format((float) get_post_meta($post_id, 'total', true), 0, ',', '.');
                break;
            case 'pickup':
                echo esc_html(get_post_meta($post_id, 'pickup_location_name', true));
                break;
            case 'status':
                $status = get_post_status($post_id);
                $status_labels = array(
                    'pending'   => '<span class="status-badge pending">Pending</span>',
                    'preparing' => '<span class="status-badge preparing">Preparing</span>',
                    'ready'     => '<span class="status-badge ready">Ready</span>',
                    'completed' => '<span class="status-badge completed">Completed</span>',
                    'cancelled' => '<span class="status-badge cancelled">Cancelled</span>',
                );
                echo $status_labels[$status] ?? $status;
                break;
        }
    }

    /**
     * Custom columns for menu items
     */
    public function menu_item_columns($columns) {
        $new_columns = array(
            'cb'            => $columns['cb'],
            'thumbnail'     => __('Image', 'coffee-shop'),
            'title'         => __('Name', 'coffee-shop'),
            'category'      => __('Category', 'coffee-shop'),
            'price'         => __('Price', 'coffee-shop'),
            'availability'  => __('Available', 'coffee-shop'),
            'points'        => __('Points', 'coffee-shop'),
        );
        return $new_columns;
    }

    /**
     * Menu item column content
     */
    public function menu_item_column_content($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                echo get_the_post_thumbnail($post_id, array(50, 50));
                break;
            case 'category':
                $terms = wp_get_post_terms($post_id, 'menu_category');
                if (!empty($terms)) {
                    echo esc_html($terms[0]->name);
                }
                break;
            case 'price':
                echo 'Rp ' . number_format((float) get_post_meta($post_id, 'price', true), 0, ',', '.');
                break;
            case 'availability':
                $available = get_post_meta($post_id, 'is_available', true);
                echo $available ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
                break;
            case 'points':
                echo (int) get_post_meta($post_id, 'points_value', true);
                break;
        }
    }

    /**
     * Custom columns for special sections
     */
    public function special_section_columns($columns) {
        $new_columns = array(
            'cb'            => $columns['cb'],
            'title'         => __('Title', 'coffee-shop'),
            'category'      => __('Category', 'coffee-shop'),
            'description_en' => __('Description (EN)', 'coffee-shop'),
            'date'          => __('Date', 'coffee-shop'),
        );
        return $new_columns;
    }

    /**
     * Special section column content
     */
    public function special_section_column_content($column, $post_id) {
        switch ($column) {
            case 'description_en':
                $desc = get_post_meta($post_id, 'description_en', true);
                echo esc_html(wp_trim_words($desc, 10, '...'));
                break;
            case 'category':
                $terms = wp_get_post_terms($post_id, 'special_section_category');
                if (!empty($terms)) {
                    $names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $names));
                }
                break;
        }
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Order details
        add_meta_box(
            'order_details',
            __('Order Details', 'coffee-shop'),
            array($this, 'render_order_meta_box'),
            'order',
            'normal',
            'high'
        );

        // Menu item details
        add_meta_box(
            'menu_item_details',
            __('Item Details', 'coffee-shop'),
            array($this, 'render_menu_item_meta_box'),
            'menu_item',
            'normal',
            'high'
        );

        // Location details
        add_meta_box(
            'location_details',
            __('Location Details', 'coffee-shop'),
            array($this, 'render_location_meta_box'),
            'location',
            'normal',
            'high'
        );

        // Reward details
        add_meta_box(
            'reward_details',
            __('Reward Details', 'coffee-shop'),
            array($this, 'render_reward_meta_box'),
            'reward',
            'normal',
            'high'
        );

        // Promotion details
        add_meta_box(
            'promotion_details',
            __('Promotion Details', 'coffee-shop'),
            array($this, 'render_promotion_meta_box'),
            'promotion',
            'normal',
            'high'
        );

        // Special section details
        add_meta_box(
            'special_section_details',
            __('Section Details', 'coffee-shop'),
            array($this, 'render_special_section_meta_box'),
            'special_section',
            'normal',
            'high'
        );

        // Add user role verification on admin pages
        add_action('admin_notices', array($this, 'check_user_roles'));
    }

    /**
     * Render order meta box
     */
    public function render_order_meta_box($post) {
        wp_nonce_field('coffee_shop_order_meta', 'coffee_shop_order_meta_nonce');

        $customer_name = get_post_meta($post->ID, 'customer_name', true);
        $customer_email = get_post_meta($post->ID, 'customer_email', true);
        $customer_phone = get_post_meta($post->ID, 'customer_phone', true);
        $pickup_location = get_post_meta($post->ID, 'pickup_location_name', true);
        $pickup_time = get_post_meta($post->ID, 'pickup_time', true);
        $order_items = get_post_meta($post->ID, 'order_items', true);
        $subtotal = get_post_meta($post->ID, 'subtotal', true);
        $tax = get_post_meta($post->ID, 'tax', true);
        $discount = get_post_meta($post->ID, 'discount', true);
        $total = get_post_meta($post->ID, 'total', true);
        $payment_method = get_post_meta($post->ID, 'payment_method', true);
        $notes = get_post_meta($post->ID, 'notes', true);

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/order.php';
    }

    /**
     * Render menu item meta box
     */
    public function render_menu_item_meta_box($post) {
        wp_nonce_field('coffee_shop_menu_item_meta', 'coffee_shop_menu_item_meta_nonce');

        $price = get_post_meta($post->ID, 'price', true);
        $category = get_post_meta($post->ID, 'category', true);
        $is_available = get_post_meta($post->ID, 'is_available', true);
        $ordering_num = get_post_meta($post->ID, 'ordering_num', true);
        $preparation_time = get_post_meta($post->ID, 'preparation_time', true);
        $calories = get_post_meta($post->ID, 'calories', true);
        $ingredients = get_post_meta($post->ID, 'ingredients', true);
        $allergens = get_post_meta($post->ID, 'allergens', true);
        $points_value = get_post_meta($post->ID, 'points_value', true);

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/menu-item.php';
    }

    /**
     * Render location meta box
     */
    public function render_location_meta_box($post) {
        wp_nonce_field('coffee_shop_location_meta', 'coffee_shop_location_meta_nonce');

        $floor = get_post_meta($post->ID, 'floor', true);
        $building = get_post_meta($post->ID, 'building', true);
        $address = get_post_meta($post->ID, 'address', true);
        $access_instructions = get_post_meta($post->ID, 'access_instructions', true);
        $capacity = get_post_meta($post->ID, 'capacity', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $latitude = get_post_meta($post->ID, 'latitude', true);
        $longitude = get_post_meta($post->ID, 'longitude', true);
        $is_active = get_post_meta($post->ID, 'is_active', true);

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/location.php';
    }

    /**
     * Render reward meta box
     */
    public function render_reward_meta_box($post) {
        wp_nonce_field('coffee_shop_reward_meta', 'coffee_shop_reward_meta_nonce');

        $points_required = get_post_meta($post->ID, 'points_required', true);
        $category = get_post_meta($post->ID, 'category', true);
        $reward_type = get_post_meta($post->ID, 'reward_type', true);
        $discount_percentage = get_post_meta($post->ID, 'discount_percentage', true);
        $discount_amount = get_post_meta($post->ID, 'discount_amount', true);
        $tier_required = get_post_meta($post->ID, 'tier_required', true);
        $is_active = get_post_meta($post->ID, 'is_active', true);
        $max_redemptions = get_post_meta($post->ID, 'max_redemptions', true);
        $description_en = get_post_meta($post->ID, 'description_en', true);
        $description_id = get_post_meta($post->ID, 'description_id', true);

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/reward.php';
    }

    /**
     * Render promotion meta box
     */
    public function render_promotion_meta_box($post) {
        wp_nonce_field('coffee_shop_promotion_meta', 'coffee_shop_promotion_meta_nonce');

        $subtitle_en = get_post_meta($post->ID, 'subtitle_en', true);
        $subtitle_id = get_post_meta($post->ID, 'subtitle_id', true);
        $description_en = get_post_meta($post->ID, 'description_en', true);
        $description_id = get_post_meta($post->ID, 'description_id', true);
        $cta_text_en = get_post_meta($post->ID, 'cta_text_en', true);
        $cta_text_id = get_post_meta($post->ID, 'cta_text_id', true);
        $icon = get_post_meta($post->ID, 'icon', true);
        $is_active = get_post_meta($post->ID, 'is_active', true);

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/promotion.php';
    }

    /**
     * Render special section meta box
     */
    public function render_special_section_meta_box($post) {
        wp_nonce_field('coffee_shop_special_section_meta', 'coffee_shop_special_section_meta_nonce');

        $description_en = get_post_meta($post->ID, 'description_en', true);
        $description_id = get_post_meta($post->ID, 'description_id', true);
        $enabled = get_post_meta($post->ID, 'special_section_enabled', true);
        $allow_media = get_post_meta($post->ID, 'special_section_allow_media', true);
        $image = get_post_meta($post->ID, 'special_section_image', true);
        $image_2 = get_post_meta($post->ID, 'special_section_image_2', true);
        $image_3 = get_post_meta($post->ID, 'special_section_image_3', true);
        $image_4 = get_post_meta($post->ID, 'special_section_image_4', true);
        $slide_title = get_post_meta($post->ID, 'slide_title', true);
        $color_scheme = get_post_meta($post->ID, 'color_scheme', true);
        $category = wp_get_post_terms($post->ID, 'special_section_category');
        $selected_category = !empty($category) ? $category[0]->term_id : 0;

        include COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/views/meta-boxes/special-section.php';
    }

    /**
     * Ensure user roles exist on admin init
     */
    public function ensure_user_roles() {
        Coffee_Shop_User_Roles::register_custom_roles();
        Coffee_Shop_User_Roles::add_role_capabilities();
    }

    /**
     * Check if custom user roles exist and show notice if needed
     */
    public function check_user_roles() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('users', 'user-new', 'coffee-shop-settings'))) {
            return;
        }

        $store_admin_role = get_role('store_admin');
        $cashier_role = get_role('cashier');

        if (!$store_admin_role || !$cashier_role) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Coffee Shop Roles:', 'coffee-shop') . '</strong> ';
            echo __('Custom user roles (Store Admin, Cashier) are being created. Please refresh this page if you don\'t see them in the role dropdown.', 'coffee-shop');
            echo '</p>';
            echo '</div>';

            Coffee_Shop_User_Roles::register_custom_roles();
        }
    }

    /**
     * Save order meta
     */
    public function save_order_meta($post_id) {
        if (!isset($_POST['coffee_shop_order_meta_nonce']) ||
            !wp_verify_nonce($_POST['coffee_shop_order_meta_nonce'], 'coffee_shop_order_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'customer_name', 'customer_email', 'customer_phone',
            'pickup_location_id', 'pickup_location_name', 'pickup_time',
            'order_items', 'subtotal', 'tax', 'discount', 'total',
            'payment_method', 'payment_status', 'notes'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, $_POST[$field]);
            }
        }
    }

    /**
     * Save menu item meta
     */
    public function save_menu_item_meta($post_id) {
        if (!isset($_POST['coffee_shop_menu_item_meta_nonce']) ||
            !wp_verify_nonce($_POST['coffee_shop_menu_item_meta_nonce'], 'coffee_shop_menu_item_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'price', 'category', 'is_available', 'preparation_time',
            'calories', 'ingredients', 'allergens', 'points_value',
            'image', 'map', 'color', 'enable_pattern', 'pattern',
            'image_type', 'enable_flip', 'flip_image', 'ordering_num'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if (in_array($field, array('is_available', 'enable_pattern', 'ordering_num','enable_flip'))) {
                    update_post_meta($post_id, $field, $_POST[$field] ? 1 : 0);
                } else {
                    update_post_meta($post_id, $field, $_POST[$field]);
                }
            }
        }

        if (isset($_POST['product_description'])) {
            update_post_meta($post_id, 'product_description', $_POST['product_description']);
        }

        if (isset($_POST['tags'])) {
            $tags = array_map('trim', explode(',', $_POST['tags']));
            update_post_meta($post_id, 'tags', $tags);
        }

        if (isset($_POST['customization_options'])) {
            $options = array();
            foreach ($_POST['customization_options'] as $option) {
                if (!empty($option['key'])) {
                    $values = array_map('trim', explode(',', $option['values']));
                    $options[$option['key']] = array_filter($values);
                }
            }
            update_post_meta($post_id, 'customization_options', $options);
        }
    }

    /**
     * Save promotion meta
     */
    public function save_promotion_meta($post_id) {
        if (!isset($_POST['coffee_shop_promotion_meta_nonce']) ||
            !wp_verify_nonce($_POST['coffee_shop_promotion_meta_nonce'], 'coffee_shop_promotion_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'subtitle_en', 'subtitle_id', 'description_en', 'description_id',
            'cta_text_en', 'cta_text_id', 'icon', 'is_active'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, $_POST[$field]);
            }
        }
    }

    /**
     * Save special section meta
     */
    public function save_special_section_meta($post_id) {
        if (!isset($_POST['coffee_shop_special_section_meta_nonce']) ||
            !wp_verify_nonce($_POST['coffee_shop_special_section_meta_nonce'], 'coffee_shop_special_section_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $description_en = isset($_POST['description_en']) ? wp_kses_post($_POST['description_en']) : '';
        $description_id = isset($_POST['description_id']) ? wp_kses_post($_POST['description_id']) : '';
        $special_section_enabled = isset($_POST['special_section_enabled']) ? 1 : 0;
        $special_section_allow_media = isset($_POST['special_section_allow_media']) ? 1 : 0;
        $special_section_image = isset($_POST['special_section_image']) ? esc_url_raw($_POST['special_section_image']) : '';
        $special_section_image_2 = isset($_POST['special_section_image_2']) ? esc_url_raw($_POST['special_section_image_2']) : '';
        $special_section_image_3 = isset($_POST['special_section_image_3']) ? esc_url_raw($_POST['special_section_image_3']) : '';
        $special_section_image_4 = isset($_POST['special_section_image_4']) ? esc_url_raw($_POST['special_section_image_4']) : '';
        $slide_title = isset($_POST['slide_title']) ? sanitize_text_field($_POST['slide_title']) : '';

        update_post_meta($post_id, 'description_en', $description_en);
        update_post_meta($post_id, 'description_id', $description_id);
        update_post_meta($post_id, 'special_section_enabled', $special_section_enabled);
        update_post_meta($post_id, 'special_section_allow_media', $special_section_allow_media);
        update_post_meta($post_id, 'special_section_image', $special_section_image);
        update_post_meta($post_id, 'special_section_image_2', $special_section_image_2);
        update_post_meta($post_id, 'special_section_image_3', $special_section_image_3);
        update_post_meta($post_id, 'special_section_image_4', $special_section_image_4);
        update_post_meta($post_id, 'slide_title', $slide_title);
        update_post_meta($post_id, 'color_scheme', $color_scheme);

        // Handle category taxonomy
        if (isset($_POST['special_section_category'])) {
            $category_id = intval($_POST['special_section_category']);
            if ($category_id > 0) {
                wp_set_object_terms($post_id, $category_id, 'special_section_category');
            }
        }
    }

    /**
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        $is_new_user = !isset($user->ID);

        if ($is_new_user) {
            $phone = '';
        } else {
            $phone = get_user_meta($user->ID, 'phone', true);
        }
        ?>
        <h3><?php _e('Contact Information', 'coffee-shop'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone"><?php _e('Phone Number', 'coffee-shop'); ?></label></th>
                <td>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           value="<?php echo esc_attr($phone); ?>"
                           class="regular-text"
                           placeholder="<?php _e('Enter phone number', 'coffee-shop'); ?>" />
                    <p class="description"><?php _e('User\'s phone number for contact purposes.', 'coffee-shop'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (isset($_POST['phone'])) {
            $phone = sanitize_text_field($_POST['phone']);

            if (!empty($phone)) {
                update_user_meta($user_id, 'phone', $phone);
            } else {
                delete_user_meta($user_id, 'phone');
            }
        }
    }

    /**
     * AJAX handler for media uploads
     */
    public function ajax_upload_media() {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'coffee_shop_admin') && !current_user_can('upload_files')) {
            wp_die(__('Unauthorized', 'coffee-shop'));
        }

        if (empty($_FILES) || !isset($_FILES['file'])) {
            wp_send_json_error(__('No file uploaded', 'coffee-shop'));
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('File upload error', 'coffee-shop'));
        }

        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Only images are allowed.', 'coffee-shop'));
        }

        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            wp_send_json_error(__('File too large. Maximum size is 5MB.', 'coffee-shop'));
        }

        $upload_overrides = array(
            'test_form' => false,
            'upload_error_handler' => function($file, $message) {
                wp_send_json_error($message);
            }
        );

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_send_json_error($uploaded_file['error']);
        }

        $attachment_id = wp_insert_attachment(array(
            'guid'           => $uploaded_file['url'],
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name(basename($uploaded_file['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $uploaded_file['file']);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        $attachment_url = wp_get_attachment_url($attachment_id);

        wp_send_json_success(array(
            'id' => $attachment_id,
            'url' => $attachment_url,
            'filename' => basename($uploaded_file['file']),
        ));
    }

    /**
     * Allow SVG uploads
     * 
     * @param array $mimes
     * @return array
     */
    public function allow_svg_upload($mimes) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }
}

new Coffee_Shop_Admin();