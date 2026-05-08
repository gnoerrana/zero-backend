<?php
/**
 * Reward Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Reward {

    const POST_TYPE = 'reward';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Rewards', 'coffee-shop'),
            'singular_name'         => __('Reward', 'coffee-shop'),
            'menu_name'             => __('Rewards', 'coffee-shop'),
            'all_items'             => __('All Rewards', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Reward', 'coffee-shop'),
            'edit_item'             => __('Edit Reward', 'coffee-shop'),
            'new_item'              => __('New Reward', 'coffee-shop'),
            'view_item'             => __('View Reward', 'coffee-shop'),
            'search_items'          => __('Search Rewards', 'coffee-shop'),
            'not_found'             => __('No rewards found', 'coffee-shop'),
            'not_found_in_trash'    => __('No rewards found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'reward'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 8,
            'menu_icon'           => 'dashicons-awards',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true,
            'rest_base'           => 'rewards',
        );

        register_post_type(self::POST_TYPE, $args);

        // Register meta fields
        self::register_meta_fields();
    }

    /**
     * Register meta fields
     */
    private static function register_meta_fields() {
        $meta_fields = array(
            'points_required' => array(
                'type'         => 'integer',
                'description'  => __('Points required to redeem', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'category' => array(
                'type'         => 'string',
                'description'  => __('Reward category: drink, food, merchandise, discount', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'reward_type' => array(
                'type'         => 'string',
                'description'  => __('Type: free_item, discount, upgrade, special', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'discount_percentage' => array(
                'type'         => 'integer',
                'description'  => __('Discount percentage if applicable', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'discount_amount' => array(
                'type'         => 'number',
                'description'  => __('Fixed discount amount', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'free_item_id' => array(
                'type'         => 'integer',
                'description'  => __('Menu item ID for free item rewards', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'valid_from' => array(
                'type'         => 'string',
                'description'  => __('Valid from date', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'valid_until' => array(
                'type'         => 'string',
                'description'  => __('Valid until date', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'is_active' => array(
                'type'         => 'boolean',
                'description'  => __('Whether reward is active', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => true,
            ),
            'max_redemptions' => array(
                'type'         => 'integer',
                'description'  => __('Maximum number of redemptions allowed', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'current_redemptions' => array(
                'type'         => 'integer',
                'description'  => __('Current number of redemptions', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => 0,
            ),
            'tier_required' => array(
                'type'         => 'string',
                'description'  => __('Minimum tier required: bronze, silver, gold, platinum', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'description_en' => array(
                'type'         => 'string',
                'description'  => __('English description', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'description_id' => array(
                'type'         => 'string',
                'description'  => __('Indonesian description', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }
}
