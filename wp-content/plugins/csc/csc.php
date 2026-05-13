<?php
/**
 * Plugin Name: CSC
 * Plugin URI: https://zerohour.local
 * Description: Manage customization options for coffee shop menu items
 * Version: 1.0.0
 * Author: Zero Hour Team
 * License: GPL v2 or later
 * Text Domain: csc
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('CSC_VERSION', '1.0.0');
define('CSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CSC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CSC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CSC_PLUGIN_DIR . 'includes/class-customizations-db.php';
require_once CSC_PLUGIN_DIR . 'includes/class-customizations-admin.php';
require_once CSC_PLUGIN_DIR . 'includes/class-customizations-api.php';
require_once CSC_PLUGIN_DIR . 'includes/class-customizations-frontend.php';

/**
 * Main Plugin Class
 */
class CSC_Customizations {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Database handler
     */
    public $db;

    /**
     * Admin handler
     */
    public $admin;

    /**
     * API handler
     */
    public $api;

    /**
     * Frontend handler
     */
    public $frontend;

    /**
     * Get single instance
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
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Initialize components
     */
    private function init_components() {
        $this->db = new CSC_Customizations_DB();
        $this->admin = new CSC_Customizations_Admin();
        $this->api = new CSC_Customizations_API();
        $this->frontend = new CSC_Customizations_Frontend();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->db->create_table();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'csc',
            false,
            dirname(CSC_PLUGIN_BASENAME) . '/languages/'
        );
    }
}

// Initialize the plugin
CSC_Customizations::get_instance();