<?php
/**
 * Plugin Name: Coffee Shop Core
 * Plugin URI: https://coffeeshop.local
 * Description: Core functionality for Coffee Shop - Order Management, Menu, Locations, and Rewards
 * Version: 1.0.0
 * Author: Zero Hour Coffee
 * Author URI: https://coffeeshop.local
 * Text Domain: coffee-shop
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

// Plugin constants
define('COFFEE_SHOP_VERSION', '1.0.0');
define('COFFEE_SHOP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COFFEE_SHOP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Coffee Shop Core Class
 */
final class Coffee_Shop_Core {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Post Types
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-menu-item.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-order.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-location.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-reward.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-promotion.php';

        // Taxonomies
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/taxonomies/class-menu-category.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/taxonomies/class-promotion-category.php';

        // User Roles
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/user-roles/class-user-roles.php';

        // API
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-orders-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-menu-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-locations-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rewards-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-promotions-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-dashboard-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-users-controller.php';

        // Admin
        if (is_admin()) {
            require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/admin/class-admin.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'init_jwt_cors'));

        // Plugin activation/deactivation hooks
        register_activation_hook(COFFEE_SHOP_PLUGIN_DIR . 'coffee-shop-core.php', array($this, 'activate'));
        register_deactivation_hook(COFFEE_SHOP_PLUGIN_DIR . 'coffee-shop-core.php', array($this, 'deactivate'));
    }

    /**
     * Load textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'coffee-shop',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Register post types
        Coffee_Shop_Menu_Item::register();
        Coffee_Shop_Order::register();
        Coffee_Shop_Location::register();
        Coffee_Shop_Reward::register();
        Coffee_Shop_Promotion::register();

        // Force classic editor for promotion post type
        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if ($post_type === 'promotion') {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Register taxonomies
        Coffee_Shop_Menu_Category::register();
        Coffee_Shop_Promotion_Category::register();

        // Initialize user roles
        Coffee_Shop_User_Roles::init();

        // Add custom fields to user REST response
        add_filter('rest_prepare_user', array($this, 'add_user_custom_fields'), 10, 3);
    }

    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        $controllers = array(
            'Coffee_Shop_Orders_Controller',
            'Coffee_Shop_Menu_Controller',
            'Coffee_Shop_Locations_Controller',
            'Coffee_Shop_Rewards_Controller',
            'Coffee_Shop_Promotions_Controller',
            'Coffee_Shop_Dashboard_Controller',
            'Coffee_Shop_Users_Controller',
        );

        foreach ($controllers as $controller) {
            $instance = new $controller();
            $instance->register_routes();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Ensure roles are created on activation
        Coffee_Shop_User_Roles::register_custom_roles();
        Coffee_Shop_User_Roles::add_role_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize JWT CORS settings
     */
    public function init_jwt_cors() {
        // Add CORS headers for JWT Authentication
        add_filter('jwt_auth_cors_allow_headers', function($headers) {
            if (!is_array($headers)) {
                $headers = array($headers);
            }
            $headers[] = 'Authorization';
            $headers[] = 'Content-Type';
            $headers[] = 'X-Requested-With';
            return $headers;
        });

        add_filter('jwt_auth_cors_allow_methods', function($methods) {
            if (!is_array($methods)) {
                $methods = array($methods);
            }
            $methods[] = 'POST';
            $methods[] = 'PUT';
            $methods[] = 'PATCH';
            $methods[] = 'DELETE';
            $methods[] = 'OPTIONS';
            return $methods;
        });

        // Add user roles to JWT payload
        add_filter('jwt_auth_token_before_dispatch', function($data, $user) {
            $data['user']['roles'] = $user->roles;
            return $data;
        }, 10, 2);

        // Fix CORS headers filter to return string
        add_filter('jwt_auth_cors_allow_headers', function($headers) {
            if (is_array($headers)) {
                $headers = implode(', ', $headers);
            }
            $headers .= ', Authorization, Content-Type, X-Requested-With';
            return $headers;
        });

        // Add general CORS headers for all REST API requests (run early)
        add_action('init', function() {
            if (defined('REST_REQUEST') && REST_REQUEST) {
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
                $allowed_origins = ['http://localhost:3000', 'http://base.zerohour.local'];
                if (in_array($origin, $allowed_origins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
                    header('Access-Control-Allow-Credentials: true');
                }
            }
        }, 1); // Priority 1 to run early

        // Handle preflight requests early
        add_action('init', function() {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
                $allowed_origins = ['http://localhost:3000', 'http://base.zerohour.local'];
                if (in_array($origin, $allowed_origins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
                    header('Access-Control-Allow-Credentials: true');
                }
                header('Content-Type: text/plain charset=UTF-8');
                header('Content-Length: 0');
                exit(0);
            }
        }, 0); // Priority 0 to run first
    }

    /**
     * Add custom fields to user REST response
     */
    public function add_user_custom_fields($response, $user, $request) {
        $data = $response->get_data();

        // Add phone number from user meta
        $data['phone'] = get_user_meta($user->ID, 'phone', true);

        // Email should already be included if user has permission
        // Ensure email is included for authenticated requests
        if (is_user_logged_in() && (get_current_user_id() === $user->ID || current_user_can('edit_users'))) {
            $data['email'] = $user->user_email;
        }

        $response->set_data($data);
        return $response;
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize plugin
 */
function coffee_shop_core() {
    return Coffee_Shop_Core::get_instance();
}

// Fire it up!
coffee_shop_core();
