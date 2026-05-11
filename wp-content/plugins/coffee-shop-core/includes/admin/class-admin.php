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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_order', array($this, 'save_order_meta'));
        add_action('save_post_menu_item', array($this, 'save_menu_item_meta'));
        add_action('save_post_promotion', array($this, 'save_promotion_meta'));

        // Ensure user roles are available
        add_action('admin_init', array($this, 'ensure_user_roles'));

        // User profile fields
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('user_new_form', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        add_action('user_register', array($this, 'save_user_profile_fields'));
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
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'coffee-shop') !== false) {
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
        // Only show on user-related pages
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('users', 'user-new', 'coffee-shop-settings'))) {
            return;
        }

        // Check if roles exist
        $store_admin_role = get_role('store_admin');
        $cashier_role = get_role('cashier');

        if (!$store_admin_role || !$cashier_role) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Coffee Shop Roles:', 'coffee-shop') . '</strong> ';
            echo __('Custom user roles (Store Admin, Cashier) are being created. Please refresh this page if you don\'t see them in the role dropdown.', 'coffee-shop');
            echo '</p>';
            echo '</div>';

            // Try to create roles
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
            'calories', 'ingredients', 'allergens', 'points_value'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, $_POST[$field]);
            }
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
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        // Check if we're on the add new user page
        $is_new_user = !isset($user->ID);

        if ($is_new_user) {
            // For new user form
            $phone = '';
        } else {
            // For existing user
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
        // Check permissions
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Check if phone field is set
        if (isset($_POST['phone'])) {
            $phone = sanitize_text_field($_POST['phone']);

            // Save or delete the phone number
            if (!empty($phone)) {
                update_user_meta($user_id, 'phone', $phone);
            } else {
                delete_user_meta($user_id, 'phone');
            }
        }
    }
}

new Coffee_Shop_Admin();
