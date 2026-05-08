<?php
/**
 * Order Meta Box View
 */

defined('ABSPATH') || exit;
?>

<table class="form-table">
    <tr>
        <th><label for="customer_name"><?php _e('Customer Name', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr($customer_name); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="customer_email"><?php _e('Customer Email', 'coffee-shop'); ?></label></th>
        <td>
            <input type="email" id="customer_email" name="customer_email" value="<?php echo esc_attr($customer_email); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="customer_phone"><?php _e('Customer Phone', 'coffee-shop'); ?></label></th>
        <td>
            <input type="tel" id="customer_phone" name="customer_phone" value="<?php echo esc_attr($customer_phone); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="pickup_location_name"><?php _e('Pickup Location', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="pickup_location_name" name="pickup_location_name" value="<?php echo esc_attr($pickup_location); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="pickup_time"><?php _e('Pickup Time', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="pickup_time" name="pickup_time" value="<?php echo esc_attr($pickup_time); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label><?php _e('Order Items', 'coffee-shop'); ?></label></th>
        <td>
            <?php if (is_array($order_items) && !empty($order_items)) : ?>
            <table class="widefat" style="margin-bottom: 10px;">
                <thead>
                    <tr>
                        <th><?php _e('Item', 'coffee-shop'); ?></th>
                        <th><?php _e('Qty', 'coffee-shop'); ?></th>
                        <th><?php _e('Price', 'coffee-shop'); ?></th>
                        <th><?php _e('Subtotal', 'coffee-shop'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['name'] ?? ''); ?></td>
                        <td><?php echo esc_html($item['quantity'] ?? 1); ?></td>
                        <td>Rp <?php echo number_format($item['unit_price'] ?? 0, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['subtotal'] ?? 0, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p><?php _e('No items in this order', 'coffee-shop'); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    
    <tr>
        <th><label for="subtotal"><?php _e('Subtotal', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="subtotal" name="subtotal" value="<?php echo esc_attr($subtotal); ?>" step="0.01" class="small-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="tax"><?php _e('Tax', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="tax" name="tax" value="<?php echo esc_attr($tax); ?>" step="0.01" class="small-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="discount"><?php _e('Discount', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="discount" name="discount" value="<?php echo esc_attr($discount); ?>" step="0.01" class="small-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="total"><?php _e('Total', 'coffee-shop'); ?></label></th>
        <td>
            <strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong>
            <input type="hidden" id="total" name="total" value="<?php echo esc_attr($total); ?>">
        </td>
    </tr>
    
    <tr>
        <th><label for="payment_method"><?php _e('Payment Method', 'coffee-shop'); ?></label></th>
        <td>
            <select id="payment_method" name="payment_method">
                <option value="cash" <?php selected($payment_method, 'cash'); ?>><?php _e('Cash', 'coffee-shop'); ?></option>
                <option value="card" <?php selected($payment_method, 'card'); ?>><?php _e('Card', 'coffee-shop'); ?></option>
                <option value="ewallet" <?php selected($payment_method, 'ewallet'); ?>><?php _e('E-Wallet', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="notes"><?php _e('Notes', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="notes" name="notes" rows="3" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
        </td>
    </tr>
</table>
