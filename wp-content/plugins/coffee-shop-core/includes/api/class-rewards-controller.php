<?php
/**
 * Rewards REST Controller
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Rewards_Controller extends Coffee_Shop_REST_Controller {

    protected $rest_base = 'rewards';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all rewards
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Get single reward
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));

        // Redeem reward
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/redeem', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'redeem_reward'),
                'permission_callback' => array($this, 'auth_permissions_check'),
            ),
        ));

        // Get user points
        register_rest_route($this->namespace, '/user/(?P<user_id>\d+)/points', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_points'),
                'permission_callback' => array($this, 'auth_permissions_check'),
            ),
        ));

        // Get user transactions
        register_rest_route($this->namespace, '/user/(?P<user_id>\d+)/transactions', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_transactions'),
                'permission_callback' => array($this, 'auth_permissions_check'),
            ),
        ));

        // Get members list (admin only)
        register_rest_route($this->namespace, '/members', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_members'),
                'permission_callback' => array($this, 'admin_permissions_check'),
            ),
        ));
    }

    /**
     * Get all rewards
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'reward',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'points_required',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );

        // Filter by tier
        if ($tier = $request->get_param('tier')) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'tier_required',
                    'value'   => $tier,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'tier_required',
                    'compare' => 'NOT EXISTS',
                ),
            );
        }

        $query = new WP_Query($args);
        $rewards = array();

        foreach ($query->posts as $post) {
            $rewards[] = $this->prepare_item_for_response($post, $request);
        }

        return $this->format_response($rewards);
    }

    /**
     * Get single reward
     */
    public function get_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'reward') {
            return $this->format_error(__('Reward not found', 'coffee-shop'), 'not_found', 404);
        }

        return $this->format_response($this->prepare_item_for_response($post, $request));
    }

    /**
     * Create reward
     */
    public function create_item($request) {
        $post_data = array(
            'post_type'    => 'reward',
            'post_title'   => $request->get_param('name'),
            'post_content' => $request->get_param('description') ?: '',
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        $meta_fields = array(
            'points_required', 'category', 'reward_type', 'discount_percentage',
            'discount_amount', 'free_item_id', 'valid_from', 'valid_until',
            'is_active', 'max_redemptions', 'tier_required', 'description_en', 'description_id'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post_id, $field, $request->get_param($field));
            }
        }

        // Set featured image
        if ($image_id = $request->get_param('image_id')) {
            set_post_thumbnail($post_id, $image_id);
        }

        $post = get_post($post_id);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Reward created successfully', 'coffee-shop'),
            201
        );
    }

    /**
     * Update reward
     */
    public function update_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'reward') {
            return $this->format_error(__('Reward not found', 'coffee-shop'), 'not_found', 404);
        }

        // Update post data
        $post_data = array('ID' => $post->ID);
        
        if ($name = $request->get_param('name')) {
            $post_data['post_title'] = $name;
        }
        if ($description = $request->get_param('description')) {
            $post_data['post_content'] = $description;
        }

        wp_update_post($post_data);

        // Update meta fields
        $meta_fields = array(
            'points_required', 'category', 'reward_type', 'discount_percentage',
            'discount_amount', 'free_item_id', 'valid_from', 'valid_until',
            'is_active', 'max_redemptions', 'tier_required', 'description_en', 'description_id'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($post->ID, $field, $request->get_param($field));
            }
        }

        $post = get_post($post->ID);
        return $this->format_response(
            $this->prepare_item_for_response($post, $request),
            __('Reward updated successfully', 'coffee-shop')
        );
    }

    /**
     * Delete reward
     */
    public function delete_item($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'reward') {
            return $this->format_error(__('Reward not found', 'coffee-shop'), 'not_found', 404);
        }

        $result = wp_delete_post($post->ID, true);

        if (!$result) {
            return $this->format_error(__('Failed to delete reward', 'coffee-shop'), 'delete_failed', 500);
        }

        return $this->format_response(null, __('Reward deleted successfully', 'coffee-shop'));
    }

    /**
     * Redeem reward
     */
    public function redeem_reward($request) {
        $user_id = $request['user_id'];
        $reward_id = $request['id'];
        
        $reward = get_post($reward_id);
        
        if (!$reward || $reward->post_type !== 'reward') {
            return $this->format_error(__('Reward not found', 'coffee-shop'), 'not_found', 404);
        }

        $points_required = (int) get_post_meta($reward_id, 'points_required', true);
        $current_points = (int) get_user_meta($user_id, 'reward_points', true);

        // Check if user has enough points
        if ($current_points < $points_required) {
            return $this->format_error(
                __('Insufficient points', 'coffee-shop'),
                'insufficient_points',
                400
            );
        }

        // Check tier requirement
        $tier_required = get_post_meta($reward_id, 'tier_required', true);
        $user_tier = get_user_meta($user_id, 'reward_tier', true) ?: 'bronze';
        
        if ($tier_required && !$this->check_tier_eligibility($user_tier, $tier_required)) {
            return $this->format_error(
                sprintf(__('This reward requires %s tier or higher', 'coffee-shop'), $tier_required),
                'tier_not_met',
                403
            );
        }

        // Check max redemptions
        $max_redemptions = (int) get_post_meta($reward_id, 'max_redemptions', true);
        $current_redemptions = (int) get_post_meta($reward_id, 'current_redemptions', true);
        
        if ($max_redemptions > 0 && $current_redemptions >= $max_redemptions) {
            return $this->format_error(
                __('This reward has reached its redemption limit', 'coffee-shop'),
                'redemption_limit',
                400
            );
        }

        // Deduct points
        update_user_meta($user_id, 'reward_points', $current_points - $points_required);
        
        // Update redemption count
        update_post_meta($reward_id, 'current_redemptions', $current_redemptions + 1);

        // Log transaction
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'coffee_points_transactions',
            array(
                'user_id'          => $user_id,
                'points'           => -$points_required,
                'transaction_type' => 'redeem',
                'reference_id'     => $reward_id,
                'reference_type'   => 'reward',
                'description'      => 'Redeemed: ' . $reward->post_title,
                'created_at'       => current_time('mysql'),
            )
        );

        return $this->format_response(array(
            'reward_id'     => $reward_id,
            'reward_name'   => $reward->post_title,
            'points_spent'  => $points_required,
            'points_remain' => $current_points - $points_required,
        ), __('Reward redeemed successfully', 'coffee-shop'));
    }

    /**
     * Get user points
     */
    public function get_user_points($request) {
        $user_id = $request['user_id'];
        
        $points = (int) get_user_meta($user_id, 'reward_points', true);
        $tier = get_user_meta($user_id, 'reward_tier', true) ?: 'bronze';
        $joined_at = get_user_meta($user_id, 'member_since', true);

        return $this->format_response(array(
            'user_id'    => $user_id,
            'points'     => $points,
            'tier'       => $tier,
            'joined_at'  => $joined_at,
            'next_tier'  => $this->get_next_tier($points),
            'points_to_next_tier' => $this->get_points_to_next_tier($points),
        ));
    }

    /**
     * Get user transactions
     */
    public function get_user_transactions($request) {
        global $wpdb;
        
        $user_id = $request['user_id'];
        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;

        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}coffee_points_transactions 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}coffee_points_transactions WHERE user_id = %d",
            $user_id
        ));

        return $this->format_response(array(
            'transactions' => $transactions,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $per_page,
        ));
    }

    /**
     * Get members list
     */
    public function get_members($request) {
        $args = array(
            'role'       => 'customer',
            'number'     => $request->get_param('per_page') ?: 50,
            'offset'     => ($request->get_param('page') - 1) * $request->get_param('per_page'),
            'orderby'    => 'registered',
            'order'      => 'DESC',
            'meta_query' => array(
                array(
                    'key'     => 'reward_points',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        $users = get_users($args);
        $members = array();

        foreach ($users as $user) {
            $members[] = array(
                'id'           => $user->ID,
                'name'         => $user->display_name,
                'email'        => $user->user_email,
                'points'       => (int) get_user_meta($user->ID, 'reward_points', true),
                'tier'         => get_user_meta($user->ID, 'reward_tier', true) ?: 'bronze',
                'member_since' => get_user_meta($user->ID, 'member_since', true) ?: $user->user_registered,
            );
        }

        return $this->format_response($members);
    }

    /**
     * Prepare item for response
     */
    public function prepare_item_for_response($post, $request) {
        return array(
            'id'                  => $post->ID,
            'name'                => $post->post_title,
            'description'         => $post->post_content,
            'points_required'     => (int) get_post_meta($post->ID, 'points_required', true),
            'category'            => get_post_meta($post->ID, 'category', true),
            'reward_type'         => get_post_meta($post->ID, 'reward_type', true),
            'discount_percentage' => (int) get_post_meta($post->ID, 'discount_percentage', true),
            'discount_amount'     => (float) get_post_meta($post->ID, 'discount_amount', true),
            'free_item_id'        => (int) get_post_meta($post->ID, 'free_item_id', true),
            'valid_from'          => get_post_meta($post->ID, 'valid_from', true),
            'valid_until'         => get_post_meta($post->ID, 'valid_until', true),
            'is_active'           => (bool) get_post_meta($post->ID, 'is_active', true),
            'max_redemptions'     => (int) get_post_meta($post->ID, 'max_redemptions', true),
            'current_redemptions' => (int) get_post_meta($post->ID, 'current_redemptions', true),
            'tier_required'       => get_post_meta($post->ID, 'tier_required', true),
            'description_en'      => get_post_meta($post->ID, 'description_en', true),
            'description_id'      => get_post_meta($post->ID, 'description_id', true),
            'image'               => get_the_post_thumbnail_url($post->ID, 'large'),
        );
    }

    /**
     * Check tier eligibility
     */
    private function check_tier_eligibility($user_tier, $required_tier) {
        $tiers = array('bronze' => 1, 'silver' => 2, 'gold' => 3, 'platinum' => 4);
        return $tiers[$user_tier] >= $tiers[$required_tier];
    }

    /**
     * Get next tier
     */
    private function get_next_tier($points) {
        if ($points >= 10000) return null;
        if ($points >= 5000) return 'platinum';
        if ($points >= 2000) return 'gold';
        if ($points >= 500) return 'silver';
        return 'bronze';
    }

    /**
     * Get points to next tier
     */
    private function get_points_to_next_tier($points) {
        if ($points >= 10000) return 0;
        if ($points >= 5000) return 10000 - $points;
        if ($points >= 2000) return 5000 - $points;
        if ($points >= 500) return 2000 - $points;
        return 500 - $points;
    }

    /**
     * Admin permissions check
     */
    public function admin_permissions_check($request) {
        return current_user_can('manage_options');
    }

    /**
     * Auth permissions check
     */
    public function auth_permissions_check($request) {
        return is_user_logged_in();
    }
}
