<?php
/**
 * Store Settings REST Controller
 *
 * Exposes the store-wide settings configured in
 * Coffee Shop > Settings (includes/admin/views/settings.php) so the frontend
 * can read values like the tax rate instead of hardcoding them.
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Store_Settings_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'settings';

    /**
     * Register routes
     */
    public function register_routes() {
        // Public, read-only - these are storefront-facing values (tax rate, currency, etc.),
        // not sensitive configuration, and the checkout flow needs them without authentication.
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_settings'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    /**
     * Get public store settings
     */
    public function get_settings($request) {
        return $this->format_response(array(
            'currency'             => get_option('coffee_shop_currency', 'IDR'),
            'tax_rate'             => (float) get_option('coffee_shop_tax_rate', 10),
            'points_ratio'         => (float) get_option('coffee_shop_points_ratio', 100),
            'min_order'            => (float) get_option('coffee_shop_min_order', 0),
            'pickup_time_default'  => (int) get_option('coffee_shop_pickup_time_default', 15),
        ));
    }
}
