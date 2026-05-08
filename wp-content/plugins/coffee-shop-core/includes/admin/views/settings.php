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
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'coffee-shop') . '</p></div>';
}

$currency = get_option('coffee_shop_currency', 'IDR');
$tax_rate = get_option('coffee_shop_tax_rate', 10);
$points_ratio = get_option('coffee_shop_points_ratio', 100);
$min_order = get_option('coffee_shop_min_order', 0);
$pickup_time_default = get_option('coffee_shop_pickup_time_default', 15);
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
