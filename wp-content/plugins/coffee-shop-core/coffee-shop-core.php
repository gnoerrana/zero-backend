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

        // API
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-orders-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-menu-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-locations-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-rewards-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-promotions-controller.php';
        require_once COFFEE_SHOP_PLUGIN_DIR . 'includes/api/class-dashboard-controller.php';

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
        );

        foreach ($controllers as $controller) {
            $instance = new $controller();
            $instance->register_routes();
        }
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
