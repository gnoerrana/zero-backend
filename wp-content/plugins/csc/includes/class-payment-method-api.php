<?php
/**
 * REST API Handler for Payment Methods
 */

defined('ABSPATH') || exit;

class CSC_Payment_Method_API {

    /**
     * Namespace
     */
    private $namespace = 'csc/v1';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/payment-settings', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_payment_settings'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/payment-methods', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_payment_methods'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/payment-merchant-info', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_merchant_info'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));
    }

    /**
     * Get payment settings
     */
    public function get_payment_settings($request) {
        $settings = get_option('csc_payment_settings', array());

        return new WP_REST_Response($this->format_payment_settings($settings), 200);
    }

    /**
     * Get available payment methods
     */
    public function get_payment_methods($request) {
        $settings = get_option('csc_payment_settings', array());
        $methods = array();

        $payment_methods_data = $settings['payment_methods_data'] ?? array();

        $available_methods = array(
            'credit_card' => 'Credit/Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'qris' => 'QRIS',
            'cash_in_shop' => 'Cash in Shop',
        );

        if ($settings['enable_midtrans'] ?? false) {
            foreach ($available_methods as $key => $label) {
                if (isset($payment_methods_data[$key]['enabled']) && $payment_methods_data[$key]['enabled']) {
                    $methods[] = array(
                        'id' => $key,
                        'name' => $label,
                        'icon' => $payment_methods_data[$key]['icon'] ?? '',
                    );
                }
            }
        } else {
            $methods[] = array(
                'id' => 'cash_in_shop',
                'name' => 'Cash in Shop',
                'icon' => $payment_methods_data['cash_in_shop']['icon'] ?? '💵',
            );
        }

        return new WP_REST_Response($methods, 200);
    }

    /**
     * Get merchant information
     */
    public function get_merchant_info($request) {
        $settings = get_option('csc_payment_settings', array());

        if (!$settings['enable_midtrans'] ?? false) {
            return new WP_Error('not_configured', __('Payment gateway not configured', 'csc'), array('status' => 404));
        }

        $environment = $settings['environment'] ?? 'production';

        return new WP_REST_Response(array(
            'merchant_id' => $settings['merchant_id'] ?? '',
            'client_key' => $settings['client_key'] ?? '',
            'server_key' => '***',
            'environment' => $environment,
            'redirect_url' => $this->get_redirect_url($environment),
            'midtrans_url' => $this->get_midtrans_url($environment),
        ), 200);
    }

    /**
     * Format payment settings for response
     */
    private function format_payment_settings($settings) {
        return array(
            'enable_midtrans' => (bool) ($settings['enable_midtrans'] ?? false),
            'merchant_id' => $settings['merchant_id'] ?? '',
            'environment' => $settings['environment'] ?? 'production',
            'payment_methods' => $settings['payment_methods'] ?? array(),
            'payment_methods_data' => $settings['payment_methods_data'] ?? array(),
            'redirect_url_sandbox' => $settings['redirect_url_sandbox'] ?? home_url('/payment/finish'),
            'redirect_url_production' => $settings['redirect_url_production'] ?? home_url('/payment/finish'),
            'midtrans_url_sandbox' => $settings['midtrans_url_sandbox'] ?? 'https://app.sandbox.midtrans.com',
            'midtrans_url_production' => $settings['midtrans_url_production'] ?? 'https://app.midtrans.com',
        );
    }

    /**
     * Get redirect URL based on environment
     */
    private function get_redirect_url($environment) {
        $settings = get_option('csc_payment_settings', array());

        if ($environment === 'sandbox') {
            return $settings['redirect_url_sandbox'] ?? home_url('/payment/finish');
        }

        return $settings['redirect_url_production'] ?? home_url('/payment/finish');
    }

    /**
     * Get Midtrans API URL based on environment
     */
    private function get_midtrans_url($environment) {
        $settings = get_option('csc_payment_settings', array());

        if ($environment === 'sandbox') {
            return $settings['midtrans_url_sandbox'] ?? 'https://app.sandbox.midtrans.com';
        }

        return $settings['midtrans_url_production'] ?? 'https://app.midtrans.com';
    }

    /**
     * Permission checks
     */
    public function get_permissions_check($request) {
        return true;
    }
}