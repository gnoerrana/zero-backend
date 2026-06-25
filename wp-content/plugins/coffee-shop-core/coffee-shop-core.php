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
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/post-types/class-special-section.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/class-midtrans-db.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/class-order-items-db.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/class-order-submissions-db.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/class-order-emails.php';

        // Taxonomies
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/taxonomies/class-menu-category.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/taxonomies/class-promotion-category.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/taxonomies/class-special-section-category.php';

        // User Roles
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/user-roles/class-user-roles.php';

        // API
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-orders-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-menu-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-locations-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rewards-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-promotions-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-special-section-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-dashboard-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-users-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-midtrans-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-media-controller.php';

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
        Coffee_Shop_Special_Section::register();

        // Force classic editor for menu_item and promotion post types
        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if (in_array($post_type, ['menu_item', 'promotion', 'special_section'])) {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Register taxonomies
        Coffee_Shop_Menu_Category::register();
        Coffee_Shop_Promotion_Category::register();
        Coffee_Shop_Special_Section_Category::register();

        // Initialize user roles
        Coffee_Shop_User_Roles::init();

        // Add custom fields to user REST response
        add_filter('rest_prepare_user', array($this, 'add_user_custom_fields'), 10, 3);

        // Clean up menu_item REST API response
        add_filter('rest_prepare_menu_item', array($this, 'clean_menu_item_response'), 99, 3);
        add_filter('rest_post_dispatch', array($this, 'remove_menu_item_links'), 10, 3);
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
            'Coffee_Shop_Special_Section_Controller',
            'Coffee_Shop_Dashboard_Controller',
            'Coffee_Shop_Users_Controller',
            'Coffee_Shop_Media_Controller',
            'Coffee_Shop_Midtrans_Controller',
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

        // Create Midtrans transaction table
        $midtrans_db = new Coffee_Shop_Midtrans_DB();
        $midtrans_db->create_table();

        // Create order items table
        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $order_items_db->create_table();

        // Create order submissions table
        $order_submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $order_submissions_db->create_table();

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

        // Add general CORS headers for all API requests (run very early)
        add_action('init', function() {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            // Check if this is an API request (REST API or custom endpoints)
            if (strpos($request_uri, '/wp-json/') !== false || strpos($request_uri, '/wp-admin/admin-ajax.php') !== false) {
                $allowed_origins = ['http://localhost:3000', 'http://base.zerohour.local', 'http://localhost:8080'];
                
                // Also allow any localhost or .local origin
                $allow_origin = false;
                if (empty($origin)) {
                    $allow_origin = '*';
                } elseif (in_array($origin, $allowed_origins)) {
                    $allow_origin = $origin;
                } elseif (preg_match('/^https?:\/\/([\w\-]+(\.[\w\-]+)*\.local|localhost)(:\d+)?$/', $origin)) {
                    $allow_origin = $origin;
                }
                
                if ($allow_origin) {
                    header('Access-Control-Allow-Origin: ' . $allow_origin);
                    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-WP-Nonce');
                    header('Access-Control-Allow-Credentials: true');
                    header('Vary: Origin');
                }
            }
        }, 0); // Priority 0 to run first

        // Handle preflight requests very early
        add_action('init', function() {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                $request_uri = $_SERVER['REQUEST_URI'] ?? '';
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

                // Check if this is an API request
                if (strpos($request_uri, '/wp-json/') !== false || strpos($request_uri, '/wp-admin/admin-ajax.php') !== false) {
                    $allowed_origins = ['http://localhost:3000', 'http://base.zerohour.local', 'http://localhost:8080'];
                    
                    $allow_origin = false;
                    if (empty($origin)) {
                        $allow_origin = '*';
                    } elseif (in_array($origin, $allowed_origins)) {
                        $allow_origin = $origin;
                    } elseif (preg_match('/^https?:\/\/([\w\-]+(\.[\w\-]+)*\.local|localhost)(:\d+)?$/', $origin)) {
                        $allow_origin = $origin;
                    }
                    
                    if ($allow_origin) {
                        header('Access-Control-Allow-Origin: ' . $allow_origin);
                        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-WP-Nonce');
                        header('Access-Control-Allow-Credentials: true');
                        header('Vary: Origin');
                    }
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
     * Clean up menu_item REST API response
     */
    public function clean_menu_item_response($response, $post, $request) {
        $data = $response->get_data();

        // Fields to remove from the response
        $fields_to_remove = array(
            'guid',
            'link',
            'content',
            'excerpt',
            'featured_media',
            'template',
            'class_list',
            '_embedded'
        );

        // Remove the specified fields
        foreach ($fields_to_remove as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        $response->set_data($data);
        return $response;
    }

    /**
     * Remove links from menu_item REST API response
     */
    public function remove_menu_item_links($result, $server, $request) {
        // Only modify menu_item responses
        $route = $request->get_route();
        if (strpos($route, '/wp/v2/menu_items') !== false) {
            if ($result instanceof WP_REST_Response) {
                $data = $result->get_data();
                if (is_array($data)) {
                    // Handle array of items (collection)
                    foreach ($data as &$item) {
                        if (is_array($item) && isset($item['_links'])) {
                            unset($item['_links']);
                        }
                    }
                    $result->set_data($data);
                } elseif (is_array($data) && isset($data['_links'])) {
                    // Handle single item
                    unset($data['_links']);
                    $result->set_data($data);
                }
            } elseif (is_array($result) && isset($result['_links'])) {
                // Handle raw array response
                unset($result['_links']);
            }
        }

        return $result;
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
