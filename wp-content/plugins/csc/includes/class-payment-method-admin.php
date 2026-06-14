<?php
/**
 * Admin Interface for Payment Methods
 */

defined('ABSPATH') || exit;

class CSC_Payment_Method_Admin {

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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_csc_save_payment_settings', array($this, 'ajax_save_payment_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'csc-store',
            'Payment Method',
            'Payment Method',
            'manage_options',
            'csc-payment-method',
            array($this, 'payment_settings_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'store_page_csc-payment-method') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'csc-payment-admin-js',
            CSC_PLUGIN_URL . 'assets/js/payment-admin.js',
            array('jquery'),
            CSC_VERSION,
            true
        );

        wp_enqueue_style(
            'csc-admin-css',
            CSC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CSC_VERSION
        );

        wp_localize_script('csc-payment-admin-js', 'csc_payment_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csc_save_payment_settings'),
        ));
    }

    /**
     * Payment settings page
     */
    public function payment_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_payment_settings();
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }

        $settings = get_option('csc_payment_settings', array());

        ?>
        <div class="wrap">
            <h1>Payment Method Settings</h1>

            <form method="post" action="">
                <?php wp_nonce_field('csc_payment_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="enable_midtrans">Enable Midtrans</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_midtrans" name="enable_midtrans" value="1" 
                                    <?php checked($settings['enable_midtrans'] ?? false); ?> />
                                Enable Midtrans Payment Gateway
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="merchant_id">Merchant ID</label></th>
                        <td>
                            <input type="text" id="merchant_id" name="merchant_id" 
                                value="<?php echo esc_attr($settings['merchant_id'] ?? ''); ?>" 
                                class="regular-text" />
                            <p class="description">Your Midtrans Merchant ID</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="client_key">Client Key</label></th>
                        <td>
                            <input type="text" id="client_key" name="client_key" 
                                value="<?php echo esc_attr($settings['client_key'] ?? ''); ?>" 
                                class="regular-text" />
                            <p class="description">Your Midtrans Client Keys (public key)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="server_key">Server Key</label></th>
                        <td>
                            <input type="password" id="server_key" name="server_key" 
                                value="<?php echo esc_attr($settings['server_key'] ?? ''); ?>" 
                                class="regular-text" />
                            <p class="description">Your Midtrans Server Key (keep this secret)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="environment">Environment</label></th>
                        <td>
                            <select id="environment" name="environment">
                                <option value="production" <?php selected($settings['environment'] ?? 'production', 'production'); ?>>Production</option>
                                <option value="sandbox" <?php selected($settings['environment'] ?? 'production', 'sandbox'); ?>>Sandbox</option>
                            </select>
                            <p class="description">Select Production for live transactions, Sandbox for testing</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label>Payment Methods</label></th>
                        <td>
                            <?php
                            $saved_methods = $settings['payment_methods_data'] ?? array();
                            $available_methods = array(
                                'credit_card' => 'Credit/Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'qris' => 'QRIS',
                                'cash_in_shop' => 'Cash in Shop',
                            );
                            foreach ($available_methods as $key => $label):
                                $method_data = $saved_methods[$key] ?? array('enabled' => false, 'icon' => '');
                            ?>
                                <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
                                    <h4 style="margin-top: 0;"><?php echo esc_html($label); ?></h4>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" name="payment_methods_data[<?php echo esc_attr($key); ?>][enabled]" value="1"
                                            <?php checked($method_data['enabled'] ?? false); ?> />
                                        Enable this payment method
                                    </label>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="url" name="payment_methods_data[<?php echo esc_attr($key); ?>][icon]" 
                                            value="<?php echo esc_attr($method_data['icon'] ?? ''); ?>" 
                                            class="regular-text" placeholder="Icon URL" />
                                        Icon URL
                                    </label>
                                    <?php if ($method_data['icon'] ?? ''): ?>
                                        <img src="<?php echo esc_url($method_data['icon']); ?>" style="width: 32px; height: 32px; display: block; margin: 5px 0;" />
                                    <?php endif; ?>
                                    <button type="button" class="button csc-select-icon" data-field="payment_methods_data[<?php echo esc_attr($key); ?>][icon]" style="display: block; margin-top: 5px;">Select Icon</button>
                                </div>
                            <?php endforeach; ?>
                            <p class="description">Select which payment methods to enable and set their icons</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="redirect_url_sandbox">Redirect URL (Sandbox)</label></th>
                        <td>
                            <input type="url" id="redirect_url_sandbox" name="redirect_url_sandbox" 
                                value="<?php echo esc_attr($settings['redirect_url_sandbox'] ?? home_url('/payment/finish')); ?>" 
                                class="regular-text" />
                            <p class="description">URL to redirect after payment (sandbox environment)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="redirect_url_production">Redirect URL (Production)</label></th>
                        <td>
                            <input type="url" id="redirect_url_production" name="redirect_url_production" 
                                value="<?php echo esc_attr($settings['redirect_url_production'] ?? home_url('/payment/finish')); ?>" 
                                class="regular-text" />
                            <p class="description">URL to redirect after payment (production environment)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="midtrans_url_sandbox">Midtrans API URL (Sandbox)</label></th>
                        <td>
                            <input type="url" id="midtrans_url_sandbox" name="midtrans_url_sandbox" 
                                value="<?php echo esc_attr($settings['midtrans_url_sandbox'] ?? 'https://app.sandbox.midtrans.com'); ?>" 
                                class="regular-text" />
                            <p class="description">Midtrans API endpoint for sandbox environment</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="midtrans_url_production">Midtrans API URL (Production)</label></th>
                        <td>
                            <input type="url" id="midtrans_url_production" name="midtrans_url_production" 
                                value="<?php echo esc_attr($settings['midtrans_url_production'] ?? 'https://app.midtrans.com'); ?>" 
                                class="regular-text" />
                            <p class="description">Midtrans API endpoint for production environment</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX save payment settings
     */
    public function ajax_save_payment_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'csc_save_payment_settings')) {
            wp_die(__('Security check failed', 'csc'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'csc'));
        }

        $enabled_methods = array();
        $payment_methods_data = array();

        if (isset($_POST['payment_methods_data']) && is_array($_POST['payment_methods_data'])) {
            foreach ($_POST['payment_methods_data'] as $key => $data) {
                $payment_methods_data[$key] = array(
                    'enabled' => isset($data['enabled']) ? (bool) $data['enabled'] : false,
                    'icon' => esc_url_raw($data['icon'] ?? ''),
                );
                if ($payment_methods_data[$key]['enabled']) {
                    $enabled_methods[] = $key;
                }
            }
        }

        $settings = array(
            'enable_midtrans' => isset($_POST['enable_midtrans']) ? (bool) $_POST['enable_midtrans'] : false,
            'merchant_id' => sanitize_text_field($_POST['merchant_id'] ?? ''),
            'client_key' => sanitize_text_field($_POST['client_key'] ?? ''),
            'server_key' => sanitize_text_field($_POST['server_key'] ?? ''),
            'environment' => sanitize_text_field($_POST['environment'] ?? 'production'),
            'payment_methods' => $enabled_methods,
            'payment_methods_data' => $payment_methods_data,
            'redirect_url_sandbox' => esc_url_raw($_POST['redirect_url_sandbox'] ?? home_url('/payment/finish')),
            'redirect_url_production' => esc_url_raw($_POST['redirect_url_production'] ?? home_url('/payment/finish')),
            'midtrans_url_sandbox' => esc_url_raw($_POST['midtrans_url_sandbox'] ?? 'https://app.sandbox.midtrans.com'),
            'midtrans_url_production' => esc_url_raw($_POST['midtrans_url_production'] ?? 'https://app.midtrans.com'),
        );

        update_option('csc_payment_settings', $settings);

        wp_send_json_success(__('Payment settings saved successfully', 'csc'));
    }

    /**
     * Save payment settings from form submission
     */
    public function save_payment_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'csc_payment_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $enabled_methods = array();
        $payment_methods_data = array();

        if (isset($_POST['payment_methods_data']) && is_array($_POST['payment_methods_data'])) {
            foreach ($_POST['payment_methods_data'] as $key => $data) {
                $payment_methods_data[$key] = array(
                    'enabled' => isset($data['enabled']) ? (bool) $data['enabled'] : false,
                    'icon' => esc_url_raw($data['icon'] ?? ''),
                );
                if ($payment_methods_data[$key]['enabled']) {
                    $enabled_methods[] = $key;
                }
            }
        }

        $settings = array(
            'enable_midtrans' => isset($_POST['enable_midtrans']) ? (bool) $_POST['enable_midtrans'] : false,
            'merchant_id' => sanitize_text_field($_POST['merchant_id'] ?? ''),
            'client_key' => sanitize_text_field($_POST['client_key'] ?? ''),
            'server_key' => sanitize_text_field($_POST['server_key'] ?? ''),
            'environment' => sanitize_text_field($_POST['environment'] ?? 'production'),
            'payment_methods' => $enabled_methods,
            'payment_methods_data' => $payment_methods_data,
            'redirect_url_sandbox' => esc_url_raw($_POST['redirect_url_sandbox'] ?? home_url('/payment/finish')),
            'redirect_url_production' => esc_url_raw($_POST['redirect_url_production'] ?? home_url('/payment/finish')),
            'midtrans_url_sandbox' => esc_url_raw($_POST['midtrans_url_sandbox'] ?? 'https://app.sandbox.midtrans.com'),
            'midtrans_url_production' => esc_url_raw($_POST['midtrans_url_production'] ?? 'https://app.midtrans.com'),
        );

        update_option('csc_payment_settings', $settings);
    }
}