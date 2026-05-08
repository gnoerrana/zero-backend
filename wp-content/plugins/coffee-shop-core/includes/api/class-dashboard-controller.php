<?php
/**
 * Dashboard REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Dashboard_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'dashboard';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get dashboard stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_stats'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Get sales report
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sales', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_sales_report'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Get popular items report
        register_rest_route($this->namespace, '/' . $this->rest_base . '/popular-items', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_popular_items'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Get recent orders
        register_rest_route($this->namespace, '/' . $this->rest_base . '/recent-orders', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_recent_orders'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));
    }

    /**
     * Get dashboard stats
     */
    public function get_stats($request) {
        global $wpdb;

        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');

        // Today's orders
        $today_orders = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'order' 
            AND post_status NOT IN ('cancelled', 'trash')
            AND DATE(post_date) = %s",
            $today
        ));

        // Today's revenue
        $today_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm.meta_value) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'total'
            AND p.post_type = 'order'
            AND p.post_status NOT IN ('cancelled', 'trash')
            AND DATE(p.post_date) = %s",
            $today
        ));

        // Pending orders
        $pending_orders = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'order' 
            AND post_status = 'pending'"
        );

        // This month's revenue
        $month_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm.meta_value) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'total'
            AND p.post_type = 'order'
            AND p.post_status NOT IN ('cancelled', 'trash')
            AND DATE_FORMAT(p.post_date, '%%Y-%%m') = %s",
            $this_month
        ));

        // Total members
        $total_members = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta}
            WHERE meta_key = 'reward_points'"
        );

        // Total points in circulation
        $total_points = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->usermeta}
            WHERE meta_key = 'reward_points'"
        );

        // Orders by status
        $orders_by_status = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_type = 'order' 
            AND post_status NOT IN ('trash', 'auto-draft')
            GROUP BY post_status"
        );

        $status_counts = array();
        foreach ($orders_by_status as $row) {
            $status_counts[$row->post_status] = (int) $row->count;
        }

        return $this->format_response(array(
            'today' => array(
                'orders'  => (int) $today_orders,
                'revenue' => (float) $today_revenue,
            ),
            'pending_orders' => (int) $pending_orders,
            'month' => array(
                'revenue' => (float) $month_revenue,
            ),
            'members' => array(
                'total'  => (int) $total_members,
                'points' => (int) $total_points,
            ),
            'orders_by_status' => $status_counts,
        ));
    }

    /**
     * Get sales report
     */
    public function get_sales_report($request) {
        global $wpdb;

        $period = $request->get_param('period') ?: 'week';
        $start_date = $this->get_start_date($period);

        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(post_date) as date, 
                    COUNT(*) as orders, 
                    SUM(pm.meta_value) as revenue
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'order'
            AND p.post_status NOT IN ('cancelled', 'trash')
            AND pm.meta_key = 'total'
            AND DATE(p.post_date) >= %s
            GROUP BY DATE(post_date)
            ORDER BY date ASC",
            $start_date
        ));

        $data = array();
        foreach ($sales as $row) {
            $data[] = array(
                'date'    => $row->date,
                'orders'  => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            );
        }

        return $this->format_response($data);
    }

    /**
     * Get popular items
     */
    public function get_popular_items($request) {
        global $wpdb;

        $period = $request->get_param('period') ?: 'week';
        $start_date = $this->get_start_date($period);
        $limit = $request->get_param('limit') ?: 10;

        // Get order items from completed orders
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.meta_value as order_items
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'order_items'
            AND p.post_type = 'order'
            AND p.post_status NOT IN ('cancelled', 'trash')
            AND DATE(p.post_date) >= %s",
            $start_date
        ));

        // Aggregate item counts
        $item_counts = array();
        foreach ($results as $row) {
            $items = maybe_unserialize($row->order_items);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['menu_item_id'])) {
                        $id = $item['menu_item_id'];
                        if (!isset($item_counts[$id])) {
                            $item_counts[$id] = 0;
                        }
                        $item_counts[$id] += $item['quantity'] ?? 1;
                    }
                }
            }
        }

        // Sort by count
        arsort($item_counts);

        // Get item details
        $popular_items = array();
        $count = 0;
        foreach ($item_counts as $item_id => $quantity) {
            if ($count >= $limit) break;
            
            $post = get_post($item_id);
            if ($post && $post->post_type === 'menu_item') {
                $popular_items[] = array(
                    'id'       => $item_id,
                    'name'     => $post->post_title,
                    'quantity' => $quantity,
                    'price'    => (float) get_post_meta($item_id, 'price', true),
                    'image'    => get_the_post_thumbnail_url($item_id, 'thumbnail'),
                );
            }
            $count++;
        }

        return $this->format_response($popular_items);
    }

    /**
     * Get recent orders
     */
    public function get_recent_orders($request) {
        $limit = $request->get_param('limit') ?: 10;

        $args = array(
            'post_type'      => 'order',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);
        $orders = array();

        foreach ($query->posts as $post) {
            $orders[] = array(
                'id'            => $post->ID,
                'order_number'  => $post->post_title,
                'status'        => $post->post_status,
                'customer_name' => get_post_meta($post->ID, 'customer_name', true),
                'total'         => (float) get_post_meta($post->ID, 'total', true),
                'pickup_location'=> get_post_meta($post->ID, 'pickup_location_name', true),
                'created_at'    => $post->post_date,
            );
        }

        return $this->format_response($orders);
    }

    /**
     * Get start date based on period
     */
    private function get_start_date($period) {
        $today = current_time('Y-m-d');
        
        switch ($period) {
            case 'today':
                return $today;
            case 'week':
                return date('Y-m-d', strtotime('-7 days', strtotime($today)));
            case 'month':
                return date('Y-m-d', strtotime('-30 days', strtotime($today)));
            case 'year':
                return date('Y-m-d', strtotime('-1 year', strtotime($today)));
            default:
                return date('Y-m-d', strtotime('-7 days', strtotime($today)));
        }
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
    }
}
