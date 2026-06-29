<?php
/**
 * Order Email Handler
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Order_Emails {

    /**
     * Send order confirmation email to customer
     */
    public static function send_order_confirmation($order_id, $submission_id = null) {
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'order') {
            return false;
        }

        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submission_id ? $submissions_db->get_by_id($submission_id) : $submissions_db->get_by_post_id($order_id);

        if (!$submission) {
            return false;
        }

        $allowed_statuses = array('processing');
        if (!in_array($submission['status'], $allowed_statuses, true)) {
            return false;
        }

        $customer_email = $submission['email'];
        $customer_name = $submission['first_name'];

        if (empty($customer_email)) {
            return false;
        }

        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $items = $order_items_db->get_by_submission_id($submission['id']);

        foreach ($items as &$item) {
            if (isset($item['custom_options'])) {
                $item['custom_options'] = maybe_unserialize($item['custom_options']);
            }
        }

        $site_name = get_bloginfo('name');
        $order_number = $submission['order_id'];
        $order_date = date_i18n(get_option('date_format'), strtotime($submission['created_at']));
        $subtotal = $submission['subtotal'];
        $tax = $submission['tax'];
        $discount = $submission['discount'];
        $total = $submission['total'];
        $payment_method = $submission['payment_method'];

        $subject = sprintf(__('[%s] Order Confirmation #%s', 'coffee-shop'), $site_name, $order_number);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
        );

        $message = self::build_order_receipt_html([
            'order_number' => $order_number,
            'order_date' => $order_date,
            'customer_name' => $customer_name,
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $payment_method,
        ]);

        return wp_mail($customer_email, $subject, $message, $headers);
    }

    /**
     * Build HTML email content for order receipt
     */
    private static function build_order_receipt_html($data) {
        $site_name = get_bloginfo('name');
        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'Rp';
        $logo_url = site_url('/wp-content/uploads/2026/06/logo.png');
        $store_settings = get_option('csc_store_settings', []);
        $store_address = !empty($store_settings['address']) ? $store_settings['address'] : 'Zero Hour Coffee<br>Terminal 2 Soekarno Hatta International Airport';
        $store_name = !empty($store_settings['store_name']) ? $store_settings['store_name'] : $site_name;

        $items_html = '';
        foreach ($data['items'] as $item) {
            $custom_options = '';
            if (!empty($item['custom_options'])) {
                $options = is_array($item['custom_options']) ? $item['custom_options'] : maybe_unserialize($item['custom_options']);
                if (is_array($options)) {
                    $option_texts = [];
                    foreach ($options as $opt) {
                        if (isset($opt['selected_values']) && is_array($opt['selected_values'])) {
                            $option_texts[] = implode(', ', $opt['selected_values']);
                        }
                    }
                    if (!empty($option_texts)) {
                        $custom_options = '<br><small style="color: #858585;">' . implode('<br>', $option_texts) . '</small>';
                    }
                }
            }
            $items_html .= sprintf(
                '<tr><td style="padding: 10px 0; border-bottom: 1px solid #f1f1f1;">%s %s</td><td style="padding: 10px 0; border-bottom: 1px solid #f1f1f1; text-align: right;">%s %s</td></tr>',
                esc_html($item['name']),
                $custom_options,
                $currency_symbol,
                number_format(($item['subtotal'] ?? ($item['unit_price'] * $item['quantity'])), 0, ',', '.')
            );
        }

        $discount_row = '';
        if ($data['discount'] > 0) {
            $discount_row = sprintf(
                '<tr><td style="padding: 8px 0;">Discount</td><td style="padding: 8px 0; text-align: right;">-%s %s</td></tr>',
                $currency_symbol,
                number_format($data['discount'], 0, ',', '.')
            );
        }

        $html = sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>%s</title></head><body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f8f8;"><div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);"><div style="text-align: center; margin-bottom: 20px;"><img src="%s" alt="%s" style="max-width: 150px; margin-bottom: 10px;" /><h2 style="margin: 0;">%s</h2><div style="color: #858585; font-size: 14px; margin-top: 5px;">%s</div></div><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">#%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><h3 style="margin-bottom: 10px;">%s</h3><table style="width: 100%%; border-collapse: collapse;">%s</table><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><table style="width: 100%%; border-collapse: collapse;"><tr><td style="padding: 8px 0;">%s</td><td style="padding: 8px 0; text-align: right;">%s %s</td></tr><tr><td style="padding: 8px 0;">%s</td><td style="padding: 8px 0; text-align: right;">%s %s</td></tr>%s<tr><td style="padding: 8px 0; font-weight: bold; font-size: 18px; color: #FF6A2A;">%s</td><td style="padding: 8px 0; text-align: right; font-weight: bold; font-size: 18px; color: #FF6A2A;">%s %s</td></tr></table><div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e4e4e4; color: #585858;"><p><strong>%s</strong></p></div></div></body></html>',
            __('Order Receipt', 'coffee-shop'),
            $logo_url,
            $store_name,
            __('Order Receipt', 'coffee-shop'),
            $store_address,
            __('Order ID', 'coffee-shop'),
            esc_html($data['order_number']),
            __('Date', 'coffee-shop'),
            esc_html($data['order_date']),
            __('Payment Method', 'coffee-shop'),
            esc_html(ucfirst($data['payment_method'])),
            __('Items', 'coffee-shop'),
            $items_html,
            __('Subtotal', 'coffee-shop'),
            $currency_symbol,
            number_format($data['subtotal'], 0, ',', '.'),
            __('Tax', 'coffee-shop'),
            $currency_symbol,
            number_format($data['tax'], 0, ',', '.'),
            $discount_row,
            __('Total', 'coffee-shop'),
            $currency_symbol,
            number_format($data['total'], 0, ',', '.'),
            __('Thank you for your order!', 'coffee-shop')
        );

        return $html;
    }

    /**
     * Send payment confirmation email to customer
     */
    public static function send_payment_confirmation($order_id) {
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'order') {
            return false;
        }

        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order_id);

        if (!$submission) {
            return false;
        }

        $customer_email = $submission['email'];
        $customer_name = $submission['first_name'];

        if (empty($customer_email)) {
            return false;
        }

        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $items = $order_items_db->get_by_submission_id($submission['id']);

        foreach ($items as &$item) {
            if (isset($item['custom_options'])) {
                $item['custom_options'] = maybe_unserialize($item['custom_options']);
            }
        }

        $site_name = get_bloginfo('name');
        $order_number = $submission['order_id'];
        $order_date = date_i18n(get_option('date_format'), strtotime($submission['created_at']));
        $subtotal = $submission['subtotal'];
        $tax = $submission['tax'];
        $discount = $submission['discount'];
        $total = $submission['total'];
        $payment_method = $submission['payment_method'];

        $midtrans_db = new Coffee_Shop_Midtrans_DB();
        $transaction = $midtrans_db->get_by_order_id($order_number);
        $transaction_id = $transaction ? $transaction['transaction_id'] : '';
        $payment_type = $transaction ? $transaction['payment_type'] : $payment_method;
        $transaction_time = $transaction && !empty($transaction['transaction_time']) 
            ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction['transaction_time'])) 
            : '';

        $subject = sprintf(__('[%s] Payment Confirmation #%s', 'coffee-shop'), $site_name, $order_number);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
        );

        $message = self::build_payment_confirmation_html([
            'order_number' => $order_number,
            'order_date' => $order_date,
            'customer_name' => $customer_name,
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'transaction_id' => $transaction_id,
            'transaction_time' => $transaction_time,
        ]);

        return wp_mail($customer_email, $subject, $message, $headers);
    }

    /**
     * Build HTML email content for payment confirmation
     */
    private static function build_payment_confirmation_html($data) {
        $site_name = get_bloginfo('name');
        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'Rp';
        $logo_url = site_url('/wp-content/uploads/2026/06/logo.png');
        $store_settings = get_option('csc_store_settings', []);
        $store_address = !empty($store_settings['address']) ? $store_settings['address'] : 'Zero Hour Coffee<br>Terminal 2 Soekarno Hatta International Airport';
        $store_name = !empty($store_settings['store_name']) ? $store_settings['store_name'] : $site_name;

        $items_html = '';
        foreach ($data['items'] as $item) {
            $custom_options = '';
            if (!empty($item['custom_options'])) {
                $options = is_array($item['custom_options']) ? $item['custom_options'] : maybe_unserialize($item['custom_options']);
                if (is_array($options)) {
                    $option_texts = [];
                    foreach ($options as $opt) {
                        if (isset($opt['selected_values']) && is_array($opt['selected_values'])) {
                            $option_texts[] = implode(', ', $opt['selected_values']);
                        }
                    }
                    if (!empty($option_texts)) {
                        $custom_options = '<br><small style="color: #858585;">' . implode('<br>', $option_texts) . '</small>';
                    }
                }
            }
            $items_html .= sprintf(
                '<tr><td style="padding: 10px 0; border-bottom: 1px solid #f1f1f1;">%s %s</td><td style="padding: 10px 0; border-bottom: 1px solid #f1f1f1; text-align: right;">%s %s</td></tr>',
                esc_html($item['name']),
                $custom_options,
                $currency_symbol,
                number_format(($item['subtotal'] ?? ($item['unit_price'] * $item['quantity'])), 0, ',', '.')
            );
        }

        $discount_row = '';
        if ($data['discount'] > 0) {
            $discount_row = sprintf(
                '<tr><td style="padding: 8px 0;">Discount</td><td style="padding: 8px 0; text-align: right;">-%s %s</td></tr>',
                $currency_symbol,
                number_format($data['discount'], 0, ',', '.')
            );
        }

        $html = sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>%s</title></head><body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f8f8;"><div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);"><div style="text-align: center; margin-bottom: 20px;"><img src="%s" alt="%s" style="max-width: 150px; margin-bottom: 10px;" /><h2 style="margin: 0; color: #28a745;">%s</h2><div style="color: #858585; font-size: 14px; margin-top: 5px;">%s</div></div><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">#%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><div style="display: flex; justify-content: space-between; margin: 8px 0;"><span style="color: #858585; font-size: 14px;">%s:</span><span style="color: #000; font-weight: 500;">%s</span></div><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><h3 style="margin-bottom: 10px;">%s</h3><table style="width: 100%%; border-collapse: collapse;">%s</table><hr style="border: none; border-top: 1px solid #e4e4e4; margin: 20px 0;"><table style="width: 100%%; border-collapse: collapse;"><tr><td style="padding: 8px 0;">%s</td><td style="padding: 8px 0; text-align: right;">%s %s</td></tr><tr><td style="padding: 8px 0;">%s</td><td style="padding: 8px 0; text-align: right;">%s %s</td></tr>%s<tr><td style="padding: 8px 0; font-weight: bold; font-size: 18px; color: #FF6A2A;">%s</td><td style="padding: 8px 0; text-align: right; font-weight: bold; font-size: 18px; color: #FF6A2A;">%s %s</td></tr></table><div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e4e4e4; color: #585858;"><p><strong>%s</strong></p></div></div></body></html>',
            __('Payment Confirmation', 'coffee-shop'),
            $logo_url,
            $store_name,
            __('Payment Confirmed', 'coffee-shop'),
            $store_address,
            __('Order ID', 'coffee-shop'),
            esc_html($data['order_number']),
            __('Date', 'coffee-shop'),
            esc_html($data['order_date']),
            __('Payment Method', 'coffee-shop'),
            esc_html(ucfirst($data['payment_type'])),
            __('Transaction ID', 'coffee-shop'),
            esc_html($data['transaction_id']),
            __('Transaction Time', 'coffee-shop'),
            esc_html($data['transaction_time']),
            __('Items', 'coffee-shop'),
            $items_html,
            __('Subtotal', 'coffee-shop'),
            $currency_symbol,
            number_format($data['subtotal'], 0, ',', '.'),
            __('Tax', 'coffee-shop'),
            $currency_symbol,
            number_format($data['tax'], 0, ',', '.'),
            $discount_row,
            __('Total', 'coffee-shop'),
            $currency_symbol,
            number_format($data['total'], 0, ',', '.'),
            __('Thank you for your payment!', 'coffee-shop')
        );

        return $html;
    }
}