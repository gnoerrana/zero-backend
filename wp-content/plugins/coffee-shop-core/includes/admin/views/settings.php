<?php
/**
 * Admin Settings View
 */

defined('ABSPATH') || exit;

// Save settings
if (isset($_POST['coffee_shop_settings_nonce']) && wp_verify_nonce($_POST['coffee_shop_settings_nonce'], 'coffee_shop_settings')) {
    update_option('coffee_shop_currency', sanitize_text_field($_POST['currency']));
    update_option('coffee_shop_tax_rate', floatval($_POST['tax_rate']));
    update_option('coffee_shop_points_ratio', floatval($_POST['points_ratio']));
    update_option('coffee_shop_min_order', floatval($_POST['min_order']));
    update_option('coffee_shop_pickup_time_default', intval($_POST['pickup_time_default']));

    update_option(Coffee_Shop_WhatsApp::OPTION_KEY, array(
        'enabled'                  => isset($_POST['whatsapp_enabled']) ? 1 : 0,
        'bridge_url'               => esc_url_raw(trim(wp_unslash($_POST['whatsapp_bridge_url']))),
        'api_key'                  => sanitize_text_field(wp_unslash($_POST['whatsapp_api_key'])),
        'default_country_code'     => preg_replace('/\D+/', '', $_POST['whatsapp_country_code']),
        'admin_phone'              => sanitize_text_field(wp_unslash($_POST['whatsapp_admin_phone'])),
        'template_created'         => sanitize_textarea_field(wp_unslash($_POST['whatsapp_template_created'])),
        'template_preparing'       => sanitize_textarea_field(wp_unslash($_POST['whatsapp_template_preparing'])),
        'template_ready'           => sanitize_textarea_field(wp_unslash($_POST['whatsapp_template_ready'])),
        'template_admin_new_order' => sanitize_textarea_field(wp_unslash($_POST['whatsapp_template_admin_new_order'])),
    ));

    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'coffee-shop') . '</p></div>';
}

$currency = get_option('coffee_shop_currency', 'IDR');
$tax_rate = get_option('coffee_shop_tax_rate', 10);
$points_ratio = get_option('coffee_shop_points_ratio', 100);
$min_order = get_option('coffee_shop_min_order', 0);
$pickup_time_default = get_option('coffee_shop_pickup_time_default', 15);
$whatsapp = Coffee_Shop_WhatsApp::get_settings();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('coffee_shop_settings', 'coffee_shop_settings_nonce'); ?>
        
        <div class="coffee-shop-settings">
            <!-- General Settings -->
            <div class="settings-section">
                <h2><?php _e('General Settings', 'coffee-shop'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Currency', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <select name="currency" id="currency">
                                <option value="IDR" <?php selected($currency, 'IDR'); ?>>IDR - Indonesian Rupiah</option>
                                <option value="USD" <?php selected($currency, 'USD'); ?>>USD - US Dollar</option>
                                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR - Euro</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax_rate"><?php _e('Tax Rate (%)', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="tax_rate" id="tax_rate" value="<?php echo esc_attr($tax_rate); ?>" step="0.1" min="0" max="100">
                            <p class="description"><?php _e('Tax percentage applied to orders', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="min_order"><?php _e('Minimum Order Amount', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="min_order" id="min_order" value="<?php echo esc_attr($min_order); ?>" step="1000" min="0">
                            <p class="description"><?php _e('Minimum order amount required (0 for no minimum)', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Order Settings -->
            <div class="settings-section">
                <h2><?php _e('Order Settings', 'coffee-shop'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pickup_time_default"><?php _e('Default Pickup Time (minutes)', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="pickup_time_default" id="pickup_time_default" value="<?php echo esc_attr($pickup_time_default); ?>" min="5" step="5">
                            <p class="description"><?php _e('Default time for order pickup', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Rewards Settings -->
            <div class="settings-section">
                <h2><?php _e('Rewards Settings', 'coffee-shop'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="points_ratio"><?php _e('Points Ratio', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="points_ratio" id="points_ratio" value="<?php echo esc_attr($points_ratio); ?>" step="1" min="1">
                            <p class="description"><?php _e('Points earned per currency unit spent (e.g., 100 = 1 point per Rp 100)', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Tier Thresholds', 'coffee-shop'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Bronze', 'coffee-shop'); ?></th>
                        <td>0 - 499 <?php _e('points', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Silver', 'coffee-shop'); ?></th>
                        <td>500 - 1,999 <?php _e('points', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gold', 'coffee-shop'); ?></th>
                        <td>2,000 - 4,999 <?php _e('points', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Platinum', 'coffee-shop'); ?></th>
                        <td>5,000+ <?php _e('points', 'coffee-shop'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- WhatsApp Notifications -->
            <div class="settings-section">
                <h2><?php _e('WhatsApp Notifications', 'coffee-shop'); ?></h2>
                <p class="description">
                    <?php _e('Sends order-status messages via a companion whatsapp-web.js bridge service. Run the bridge (see /whatsapp-bridge in the project), scan the QR code once at its /qr URL, then configure it below.', 'coffee-shop'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable WhatsApp Notifications', 'coffee-shop'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="whatsapp_enabled" value="1" <?php checked($whatsapp['enabled'], 1); ?>>
                                <?php _e('Send WhatsApp messages when an order is created, starts preparing, or is ready', 'coffee-shop'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_bridge_url"><?php _e('Bridge Service URL', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="whatsapp_bridge_url" id="whatsapp_bridge_url" value="<?php echo esc_attr($whatsapp['bridge_url']); ?>" class="regular-text" placeholder="http://localhost:3001">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_api_key"><?php _e('Bridge API Key', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="whatsapp_api_key" id="whatsapp_api_key" value="<?php echo esc_attr($whatsapp['api_key']); ?>" class="regular-text">
                            <p class="description"><?php _e('Must match BRIDGE_API_KEY configured on the bridge service.', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_country_code"><?php _e('Default Country Code', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="whatsapp_country_code" id="whatsapp_country_code" value="<?php echo esc_attr($whatsapp['default_country_code']); ?>" class="small-text">
                            <p class="description"><?php _e('Used to convert local numbers starting with 0 (e.g. 0812...) into international format.', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="button" class="button" id="whatsapp-check-status"><?php _e('Check Bridge Status', 'coffee-shop'); ?></button>
                    <span id="whatsapp-status-result" style="margin-left: 10px;"></span>
                </p>

                <h3><?php _e('Store Admin Alerts', 'coffee-shop'); ?></h3>
                <p class="description"><?php _e('Optionally notify the store on WhatsApp whenever a new order comes in.', 'coffee-shop'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_admin_phone"><?php _e('Store Admin WhatsApp Number', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <input type="tel" name="whatsapp_admin_phone" id="whatsapp_admin_phone" value="<?php echo esc_attr($whatsapp['admin_phone']); ?>" class="regular-text" placeholder="0812xxxxxxx">
                            <p class="description"><?php _e('Leave blank to disable admin new-order alerts. Customer notifications are unaffected.', 'coffee-shop'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_template_admin_new_order"><?php _e('New Order Alert', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <textarea name="whatsapp_template_admin_new_order" id="whatsapp_template_admin_new_order" rows="4" class="large-text"><?php echo esc_textarea($whatsapp['template_admin_new_order']); ?></textarea>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Customer Message Templates', 'coffee-shop'); ?></h3>
                <p class="description">
                    <?php _e('Available placeholders:', 'coffee-shop'); ?>
                    <code>{customer_name} {customer_phone} {order_number} {items} {total} {pickup_location} {pickup_time} {store_name}</code>
                </p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_template_created"><?php _e('Order Created', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <textarea name="whatsapp_template_created" id="whatsapp_template_created" rows="4" class="large-text"><?php echo esc_textarea($whatsapp['template_created']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_template_preparing"><?php _e('Order Preparing', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <textarea name="whatsapp_template_preparing" id="whatsapp_template_preparing" rows="4" class="large-text"><?php echo esc_textarea($whatsapp['template_preparing']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_template_ready"><?php _e('Order Ready', 'coffee-shop'); ?></label>
                        </th>
                        <td>
                            <textarea name="whatsapp_template_ready" id="whatsapp_template_ready" rows="4" class="large-text"><?php echo esc_textarea($whatsapp['template_ready']); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- User Roles Information -->
            <div class="settings-section">
                <h2><?php _e('User Roles', 'coffee-shop'); ?></h2>
                <p><?php _e('The Coffee Shop plugin automatically creates custom user roles for store management.', 'coffee-shop'); ?></p>

                <h3><?php _e('Available Roles', 'coffee-shop'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Administrator', 'coffee-shop'); ?></th>
                        <td><?php _e('Full access to all WordPress features plus coffee shop management.', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Store Admin', 'coffee-shop'); ?></th>
                        <td><?php _e('Can manage products, promotions, orders, and view reports. Cannot manage users or WordPress settings.', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cashier', 'coffee-shop'); ?></th>
                        <td><?php _e('Limited access to manage orders and view basic reports only.', 'coffee-shop'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Customer', 'coffee-shop'); ?></th>
                        <td><?php _e('Regular customers with access to place orders and view their account.', 'coffee-shop'); ?></td>
                    </tr>
                </table>

                <p>
                    <strong><?php _e('Note:', 'coffee-shop'); ?></strong>
                    <?php _e('These roles are automatically created when you activate the plugin. You can assign them when creating new users in WordPress admin.', 'coffee-shop'); ?>
                </p>
            </div>
        </div>

        <?php submit_button(__('Save Settings', 'coffee-shop')); ?>
    </form>
</div>

<style>
.coffee-shop-settings {
    max-width: 800px;
    margin-top: 20px;
}

.settings-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.settings-section h3 {
    margin-top: 20px;
}
</style>

<script>
jQuery(function($) {
    $('#whatsapp-check-status').on('click', function() {
        var $button = $(this);
        var $result = $('#whatsapp-status-result');

        $button.prop('disabled', true);
        $result.text('<?php echo esc_js(__('Checking...', 'coffee-shop')); ?>');

        $.post(coffeeShopAdmin.ajaxUrl, {
            action: 'coffee_shop_whatsapp_status',
            nonce: coffeeShopAdmin.nonce
        }).done(function(response) {
            if (response.success && response.data && response.data.ready) {
                $result.html('<span style="color: green;">&#10003; <?php echo esc_js(__('Connected', 'coffee-shop')); ?></span>');
            } else if (response.success && response.data && response.data.hasQr) {
                $result.html('<span style="color: #b26a00;"><?php echo esc_js(__('Waiting for QR scan - open the bridge\'s /qr URL', 'coffee-shop')); ?></span>');
            } else {
                var message = (response.data && response.data.error) ? response.data.error : '<?php echo esc_js(__('Not connected', 'coffee-shop')); ?>';
                $result.html('<span style="color: #b32d2e;">' + message + '</span>');
            }
        }).fail(function() {
            $result.html('<span style="color: #b32d2e;"><?php echo esc_js(__('Could not reach WordPress admin-ajax', 'coffee-shop')); ?></span>');
        }).always(function() {
            $button.prop('disabled', false);
        });
    });
});
</script>
