<?php
/**
 * Midtrans Transaction REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Midtrans_Controller extends Coffee_Shop_REST_Controller {

    /**
     * DB handler
     */
    private $midtrans_db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->midtrans_db = new Coffee_Shop_Midtrans_DB();
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes
     */
    public function register_routes() {
        // Store transaction status
        register_rest_route('base/v1', '/midtrans/transaction', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'store_transaction'),
                'permission_callback' => '__return_true',
            ),
        ));

        // Get transaction by order ID
        register_rest_route('base/v1', '/midtrans/transaction/(?P<order_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_transaction'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

/**
      * Store transaction status from Midtrans
      */
    public function store_transaction($request) {
        $transaction_data = array(
            'order_id'           => $request->get_param('order_id'),
            'status_code'        => $request->get_param('status_code'),
            'status_message'     => $request->get_param('status_message'),
            'transaction_id'   => $request->get_param('transaction_id'),
            'gross_amount'       => $request->get_param('gross_amount'),
            'payment_type'       => $request->get_param('payment_type'),
            'transaction_time'   => $request->get_param('transaction_time'),
            'transaction_status' => $request->get_param('transaction_status'),
            'raw_response'       => $request->get_json_params(),
        );

        // Validate required fields - transaction_id can be optional for pending/error states
        if (empty($transaction_data['order_id'])) {
            return $this->format_error(__('Missing required parameter: order_id', 'coffee-shop'), 'missing_params', 400);
        }

        // Generate transaction_id if not provided (for pending/error states)
        if (empty($transaction_data['transaction_id'])) {
            $transaction_data['transaction_id'] = $transaction_data['order_id'] . '-' . time();
        }

        // Use provided order_post_id or try to find it
        $order_post_id = (int) $request->get_param('order_post_id');
        if ($order_post_id <= 0) {
            $order_post_id = $this->midtrans_db->find_order_post_id($transaction_data['order_id']);
        }
        $transaction_data['order_post_id'] = $order_post_id;

        $id = $this->midtrans_db->insert($transaction_data);

        if (!$id) {
            return $this->format_error(__('Failed to store transaction', 'coffee-shop'), 'insert_failed', 500);
        }

        // Update order payment status if order exists
        if ($order_post_id > 0) {
            // Captured before overwriting below, so the email guard can tell whether this
            // request is the one that actually transitioned payment_status to 'paid'
            // (the frontend can call this endpoint more than once for the same transaction -
            // e.g. a re-run of a React effect - and without this check every duplicate call
            // would resend the payment confirmation email).
            $previous_payment_status = get_post_meta($order_post_id, 'payment_status', true);

            $payment_status = $this->map_transaction_status($transaction_data['transaction_status']);
            update_post_meta($order_post_id, 'payment_status', $payment_status);
            update_post_meta($order_post_id, 'midtrans_transaction_id', $transaction_data['transaction_id']);

            // Map transaction status to order status
            $order_status = $this->map_transaction_to_order_status($transaction_data['transaction_status']);

            // Get previous submission status before update
            $submissions_db = new Coffee_Shop_Order_Submissions_DB();
            $submission = $submissions_db->get_by_post_id($order_post_id);
            $previous_status = $submission ? $submission['status'] : '';

            if ($order_status) {
                wp_update_post(array(
                    'ID' => $order_post_id,
                    'post_status' => $order_status,
                ));
            }

            // Also update status in order_submissions table
            if ($submission) {
                // Map to submission status (pending_payment instead of pending for pending state)
                $submission_status = ($order_status === 'pending_payment') ? 'pending_payment' : $order_status;
                $submissions_db->update($submission['id'], array('status' => $submission_status));
            }

            // Send order confirmation email when status changes to processing (payment settled)
            if ($order_status === 'processing' && $previous_status !== 'processing' && $submission) {
                Coffee_Shop_Order_Emails::send_order_confirmation($order_post_id, $submission['id']);
            }

            // Send payment confirmation email only the first time this order transitions to paid
            if ($payment_status === 'paid' && $previous_payment_status !== 'paid' && in_array($transaction_data['transaction_status'], ['capture', 'settlement'])) {
                Coffee_Shop_Order_Emails::send_payment_confirmation($order_post_id);
            }
        }

        return $this->format_response(
            array(
                'id' => $id,
                'order_post_id' => $order_post_id,
                'transaction_id' => $transaction_data['transaction_id'],
            ),
            __('Transaction status stored successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Get transaction by order ID
     */
    public function get_transaction($request) {
        $transaction = $this->midtrans_db->get_by_order_id($request['order_id']);

        if (!$transaction) {
            return $this->format_error(__('Transaction not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item($transaction));
    }

    /**
     * Prepare item for response
     */
    private function prepare_item($transaction) {
        return array(
            'id'              => (int) $transaction['id'],
            'order_id'        => $transaction['order_id'],
            'order_post_id'   => (int) $transaction['order_post_id'],
            'status_code'     => $transaction['status_code'],
            'status_message'  => $transaction['status_message'],
            'transaction_id'  => $transaction['transaction_id'],
            'gross_amount'    => (float) $transaction['gross_amount'],
            'payment_type'    => $transaction['payment_type'],
            'transaction_time' => $transaction['transaction_time'],
            'transaction_status' => $transaction['transaction_status'],
            'created_at'      => $transaction['created_at'],
            'updated_at'      => $transaction['updated_at'],
        );
    }

    /**
     * Map Midtrans transaction status to payment status
     */
    private function map_transaction_status($transaction_status) {
        $map = array(
            'capture'       => 'paid',
            'settlement'    => 'paid',
            'pending'       => 'pending',
            'deny'          => 'failed',
            'cancel'        => 'cancelled',
            'expired'       => 'expired',
            'expire'        => 'expired',
            'refund'        => 'refunded',
            'partial_refund'=> 'refunded',
        );

        return $map[$transaction_status] ?? 'pending';
    }

    /**
      * Map Midtrans transaction status to order status
      */
    private function map_transaction_to_order_status($transaction_status) {
        $map = array(
            'pending' => 'pending_payment',
            'settlement' => 'processing',
            'capture' => 'processing',
            'expire' => 'cancelled',
            'expired' => 'cancelled',
            'cancel' => 'cancelled',
            'deny' => 'cancelled',
        );

        return $map[$transaction_status] ?? null;
    }
}