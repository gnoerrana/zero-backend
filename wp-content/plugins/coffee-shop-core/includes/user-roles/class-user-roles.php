<?php
/**
 * User Roles Management
 */

defined('ABSPATH') || exit;

class Coffee_Shop_User_Roles {

    /**
     * Initialize roles
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_custom_roles'));
        add_action('admin_init', array(__CLASS__, 'add_role_capabilities'));
        add_action('admin_init', array(__CLASS__, 'restrict_customer_admin_access'));
        add_filter('editable_roles', array(__CLASS__, 'editable_roles'));
        add_filter('map_meta_cap', array(__CLASS__, 'map_meta_cap'), 10, 4);
    }

    /**
     * Register custom user roles
     */
    public static function register_custom_roles() {
        // Check if roles already exist before creating them
        if (!get_role('store_admin')) {
            // Store Admin Role
            add_role(
                'store_admin',
                __('Store Admin', 'coffee-shop'),
                array(
                    'read'                    => true,
                    'edit_posts'              => true,
                    'delete_posts'            => true,
                    'publish_posts'           => true,
                    'upload_files'            => true,
                    'edit_others_posts'       => true,
                    'delete_others_posts'     => true,
                    'read_private_posts'      => true,
                    'edit_private_posts'      => true,
                    'delete_private_posts'    => true,
                    'manage_categories'       => true,
                    'moderate_comments'       => true,
                    'manage_links'            => true,
                    'edit_users'              => false,
                    'create_users'            => false,
                    'delete_users'            => false,
                    'list_users'              => true,
                    'promote_users'           => false,
                    'edit_theme_options'      => false,
                    'activate_plugins'        => false,
                    'edit_plugins'            => false,
                    'install_plugins'         => false,
                    'delete_plugins'          => false,
                    'manage_options'          => false,
                    'update_core'             => false,
                    'manage_woocommerce'      => true,
                    'view_woocommerce_reports' => true,
                    'export'                  => true,
                    'import'                  => true,
                    'manage_woocommerce_orders' => true,
                    'manage_woocommerce_products' => true,
                )
            );
        }

        if (!get_role('cashier')) {
            // Cashier Role
            add_role(
                'cashier',
                __('Cashier', 'coffee-shop'),
                array(
                    'read'                    => true,
                    'edit_posts'              => false,
                    'delete_posts'            => false,
                    'publish_posts'           => false,
                    'upload_files'            => false,
                    'edit_others_posts'       => false,
                    'delete_others_posts'     => false,
                    'read_private_posts'      => false,
                    'edit_private_posts'      => false,
                    'delete_private_posts'    => false,
                    'manage_categories'       => false,
                    'moderate_comments'       => false,
                    'manage_links'            => false,
                    'edit_users'              => false,
                    'create_users'            => false,
                    'delete_users'            => false,
                    'list_users'              => false,
                    'promote_users'           => false,
                    'edit_theme_options'      => false,
                    'activate_plugins'        => false,
                    'edit_plugins'            => false,
                    'install_plugins'         => false,
                    'delete_plugins'         => false,
                    'manage_options'          => false,
                    'update_core'             => false,
                    'manage_woocommerce'      => false,
                    'view_woocommerce_reports' => true,
                    'export'                  => false,
                    'import'                  => false,
                    'manage_woocommerce_orders' => true,
                    'manage_woocommerce_products' => false,
                )
            );
        }

        if (!get_role('customer')) {
            // Customer Role - Limited access for customer dashboard only
            add_role(
                'customer',
                __('Customer', 'coffee-shop'),
                array(
                    'read'                    => true,
                    'edit_posts'              => false,
                    'delete_posts'            => false,
                    'publish_posts'           => false,
                    'upload_files'            => false,
                    'edit_others_posts'       => false,
                    'delete_others_posts'     => false,
                    'read_private_posts'      => false,
                    'edit_private_posts'      => false,
                    'delete_private_posts'    => false,
                    'manage_categories'       => false,
                    'moderate_comments'       => false,
                    'manage_links'            => false,
                    'edit_users'              => false,
                    'create_users'            => false,
                    'delete_users'            => false,
                    'list_users'              => false,
                    'promote_users'           => false,
                    'edit_theme_options'      => false,
                    'activate_plugins'        => false,
                    'edit_plugins'            => false,
                    'install_plugins'         => false,
                    'delete_plugins'         => false,
                    'manage_options'          => false,
                    'update_core'             => false,
                    'manage_woocommerce'      => false,
                    'view_woocommerce_reports' => false,
                    'export'                  => false,
                    'import'                  => false,
                    'manage_woocommerce_orders' => false,
                    'manage_woocommerce_products' => false,
                )
            );
        }
    }

    /**
     * Add additional capabilities to existing roles
     */
    public static function add_role_capabilities() {
        // Add coffee shop specific capabilities
        $admin_role = get_role('administrator');
        $store_admin_role = get_role('store_admin');
        $cashier_role = get_role('cashier');

        // Define custom capabilities
        $custom_caps = array(
            'manage_coffee_shop',
            'manage_coffee_shop_orders',
            'manage_coffee_shop_products',
            'manage_coffee_shop_promotions',
            'manage_coffee_shop_locations',
            'manage_coffee_shop_rewards',
            'view_coffee_shop_reports',
        );

        // Add capabilities to administrator
        if ($admin_role) {
            foreach ($custom_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Add capabilities to store admin
        if ($store_admin_role) {
            foreach ($custom_caps as $cap) {
                $store_admin_role->add_cap($cap);
            }
        }

        // Add limited capabilities to cashier
        if ($cashier_role) {
            $cashier_role->add_cap('manage_coffee_shop_orders');
            $cashier_role->add_cap('view_coffee_shop_reports');
        }
    }

    /**
     * Filter editable roles for user management
     */
    public static function editable_roles($roles) {
        // Ensure custom roles are always available for editing
        if (!isset($roles['store_admin'])) {
            $roles['store_admin'] = array(
                'name' => __('Store Admin', 'coffee-shop'),
                'capabilities' => array()
            );
        }

        if (!isset($roles['cashier'])) {
            $roles['cashier'] = array(
                'name' => __('Cashier', 'coffee-shop'),
                'capabilities' => array()
            );
        }

        if (!isset($roles['customer'])) {
            $roles['customer'] = array(
                'name' => __('Customer', 'coffee-shop'),
                'capabilities' => array()
            );
        }

        return $roles;
    }

    /**
     * Map meta capabilities for role management
     */
    public static function map_meta_cap($caps, $cap, $user_id, $args) {
        // Allow administrators and store admins to manage users
        if ($cap === 'promote_users' || $cap === 'edit_users') {
            $user = wp_get_current_user();
            if (in_array('administrator', $user->roles) || in_array('store_admin', $user->roles)) {
                $caps = array('promote_users');
            }
        }

        return $caps;
    }

    /**
     * Restrict customer role from accessing admin dashboard
     */
    public static function restrict_customer_admin_access() {
        $user = wp_get_current_user();
        if (in_array('customer', $user->roles) && is_admin() && !wp_doing_ajax()) {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Remove custom roles (for cleanup if needed)
     */
    public static function remove_custom_roles() {
        remove_role('store_admin');
        remove_role('cashier');
        remove_role('customer');
    }
}