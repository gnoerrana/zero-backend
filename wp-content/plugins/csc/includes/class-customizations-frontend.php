<?php
/**
 * Frontend Interface for Customizations
 */

defined('ABSPATH') || exit;

class CSC_Customizations_Frontend {

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('csc_customizations_dashboard', array($this, 'dashboard_shortcode'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_page() || !has_shortcode(get_post()->post_content, 'csc_customizations_dashboard')) {
            return;
        }

        wp_enqueue_script(
            'csc-frontend-js',
            CSC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CSC_VERSION,
            true
        );

        wp_enqueue_style(
            'csc-frontend-css',
            CSC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CSC_VERSION
        );

        wp_localize_script('csc-frontend-js', 'csc_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csc_frontend_actions'),
            'rest_url' => rest_url('csc/v1/'),
            'categories' => $this->db->get_menu_categories(),
        ));
    }

    /**
     * Dashboard shortcode
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return '<p>' . __('You do not have permission to access this page.', 'coffee-shop-customizations') . '</p>';
        }

        ob_start();
        ?>
        <div id="csc-frontend-dashboard" class="csc-frontend-dashboard">
            <h2><?php _e('Manage Customizations', 'coffee-shop-customizations'); ?></h2>

            <div class="csc-customizations-list">
                <div id="csc-frontend-customizations-container">
                    <!-- Customizations will be loaded here -->
                </div>

                <button type="button" class="button button-primary" id="csc-frontend-add-item">
                    <?php _e('Add New Customization', 'coffee-shop-customizations'); ?>
                </button>
            </div>

            <div class="csc-save-section">
                <button type="button" class="button button-primary" id="csc-frontend-save-changes">
                    <?php _e('Save Changes', 'coffee-shop-customizations'); ?>
                </button>
                <span id="csc-frontend-save-status"></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}