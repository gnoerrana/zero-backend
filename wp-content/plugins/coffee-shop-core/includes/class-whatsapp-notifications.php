<?php
/**
 * WhatsApp Order Notifications
 *
 * Sends order-status WhatsApp messages via a companion whatsapp-web.js
 * bridge service (see /whatsapp-bridge in the repo root), since whatsapp-web.js
 * is a Node.js/Puppeteer library and cannot run inside PHP directly.
 */

defined('ABSPATH') || exit;

class Coffee_Shop_WhatsApp {

    const OPTION_KEY = 'csc_whatsapp_settings';

    /**
     * Notify customer that their order was received
     */
    public static function send_order_created($order_id) {
        return self::dispatch($order_id, 'template_created', self::default_template_created());
    }

    /**
     * Notify customer that their order is being prepared
     */
    public static function send_order_preparing($order_id) {
        return self::dispatch($order_id, 'template_preparing', self::default_template_preparing());
    }

    /**
     * Notify customer that their order is ready for pickup
     */
    public static function send_order_ready($order_id) {
        return self::dispatch($order_id, 'template_ready', self::default_template_ready());
    }

    /**
     * Notify the store admin that a new order came in
     */
    public static function notify_admin_new_order($order_id) {
        $settings = self::get_settings();

        if (empty($settings['enabled']) || empty($settings['admin_phone'])) {
            return false;
        }

        $context = self::get_order_context($order_id);
        if (!$context) {
            return false;
        }

        $template = !empty($settings['template_admin_new_order']) ? $settings['template_admin_new_order'] : self::default_template_admin_new_order();
        $message = self::render_template($template, $context);

        return self::send($settings['admin_phone'], $message, $settings);
    }

    /**
     * Build message from template and send it
     */
    private static function dispatch($order_id, $template_key, $default_template) {
        $settings = self::get_settings();

        if (empty($settings['enabled'])) {
            return false;
        }

        $context = self::get_order_context($order_id);
        if (!$context || empty($context['phone'])) {
            return false;
        }

        $template = !empty($settings[$template_key]) ? $settings[$template_key] : $default_template;
        $message = self::render_template($template, $context);

        return self::send($context['phone'], $message, $settings);
    }

    /**
     * Gather template placeholder values for an order
     */
    private static function get_order_context($order_id) {
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'order') {
            return false;
        }

        $submissions_db = new Coffee_Shop_Order_Submissions_DB();
        $submission = $submissions_db->get_by_post_id($order_id);

        $phone = $submission && !empty($submission['phone']) ? $submission['phone'] : get_post_meta($order_id, 'customer_phone', true);
        $customer_name = $submission && !empty($submission['first_name']) ? $submission['first_name'] : get_post_meta($order_id, 'customer_name', true);
        $order_number = $submission && !empty($submission['order_id']) ? $submission['order_id'] : sprintf('Order #%d', $order_id);
        $pickup_location = $submission && !empty($submission['pickup_location_name']) ? $submission['pickup_location_name'] : get_post_meta($order_id, 'pickup_location_name', true);
        $pickup_time = $submission && !empty($submission['pickup_time']) ? $submission['pickup_time'] : get_post_meta($order_id, 'pickup_time', true);
        $total = $submission && isset($submission['total']) ? (float) $submission['total'] : (float) get_post_meta($order_id, 'total', true);

        $order_items_db = new Coffee_Shop_Order_Items_DB();
        $items = $submission
            ? $order_items_db->get_by_submission_id($submission['id'])
            : $order_items_db->get_by_post_id($order_id);

        $item_lines = array();
        foreach ($items as $item) {
            $item_lines[] = sprintf('- %dx %s', (int) $item['quantity'], $item['name']);
        }

        $store_settings = get_option('csc_store_settings', array());
        $store_name = !empty($store_settings['store_name']) ? $store_settings['store_name'] : get_bloginfo('name');
        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'Rp';

        return array(
            'phone'           => $phone,
            'customer_phone'  => $phone,
            'customer_name'   => $customer_name ?: 'there',
            'order_number'    => $order_number,
            'items'           => implode("\n", $item_lines),
            'total'           => trim($currency_symbol . ' ' . number_format($total, 0, ',', '.')),
            'pickup_location' => $pickup_location,
            'pickup_time'     => $pickup_time,
            'store_name'      => $store_name,
        );
    }

    /**
     * Replace {placeholder} tokens in a template with context values
     */
    private static function render_template($template, $context) {
        $replacements = array();
        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }
        return strtr($template, $replacements);
    }

    /**
     * Send the message via the whatsapp-web.js bridge service
     */
    private static function send($phone, $message, $settings) {
        if (empty($settings['bridge_url']) || empty($settings['api_key'])) {
            error_log('[Coffee Shop WhatsApp] Bridge URL or API key not configured');
            return false;
        }

        $number = self::normalize_phone($phone, $settings['default_country_code']);
        if (empty($number)) {
            error_log('[Coffee Shop WhatsApp] Cannot send message: invalid phone number');
            return false;
        }

        // Fire-and-forget: the bridge call must never slow down the order API response.
        $response = wp_remote_post(trailingslashit($settings['bridge_url']) . 'send-message', array(
            'timeout'  => 3,
            'blocking' => false,
            'headers'  => array(
                'Content-Type' => 'application/json',
                'x-api-key'    => $settings['api_key'],
            ),
            'body' => wp_json_encode(array(
                'phone'   => $number,
                'message' => $message,
            )),
        ));

        if (is_wp_error($response)) {
            error_log('[Coffee Shop WhatsApp] Failed to reach bridge: ' . $response->get_error_message());
            return false;
        }

        return true;
    }

    /**
     * Normalize a local phone number into WhatsApp's international digits-only format
     */
    private static function normalize_phone($phone, $default_country_code = '62') {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        } elseif ($digits[0] === '0') {
            $digits = $default_country_code . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Get WhatsApp settings merged with defaults
     */
    public static function get_settings() {
        $defaults = array(
            'enabled'                  => false,
            'bridge_url'               => 'http://localhost:3001',
            'api_key'                  => '',
            'default_country_code'     => '62',
            'admin_phone'              => '',
            'template_created'         => self::default_template_created(),
            'template_preparing'       => self::default_template_preparing(),
            'template_ready'           => self::default_template_ready(),
            'template_admin_new_order' => self::default_template_admin_new_order(),
        );

        return wp_parse_args(get_option(self::OPTION_KEY, array()), $defaults);
    }

    public static function default_template_created() {
        return "Hi {customer_name}, thanks for your order at {store_name}!\n\nOrder: {order_number}\n{items}\nTotal: {total}\n\nWe'll let you know once it's being prepared.";
    }

    public static function default_template_preparing() {
        return "Hi {customer_name}, your order {order_number} at {store_name} is now being prepared. It'll be ready shortly!";
    }

    public static function default_template_ready() {
        return "Hi {customer_name}, your order {order_number} is ready for pickup at {pickup_location}! 🎉";
    }

    public static function default_template_admin_new_order() {
        return "New order {order_number} received!\n\nCustomer: {customer_name} ({customer_phone})\n{items}\nTotal: {total}\nPickup: {pickup_location} at {pickup_time}";
    }
}
