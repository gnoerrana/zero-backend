<?php
/**
 * Promotion Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Promotion {

    const POST_TYPE = 'promotion';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Promotions', 'coffee-shop'),
            'singular_name'         => __('Promotion', 'coffee-shop'),
            'menu_name'             => __('Promotions', 'coffee-shop'),
            'all_items'             => __('All Promotions', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Promotion', 'coffee-shop'),
            'edit_item'             => __('Edit Promotion', 'coffee-shop'),
            'new_item'              => __('New Promotion', 'coffee-shop'),
            'view_item'             => __('View Promotion', 'coffee-shop'),
            'search_items'          => __('Search Promotions', 'coffee-shop'),
            'not_found'             => __('No promotions found', 'coffee-shop'),
            'not_found_in_trash'    => __('No promotions found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'promotion'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 9,
            'menu_icon'           => 'dashicons-megaphone',
            'supports'            => array('title', 'editor', 'thumbnail'),
            'show_in_rest'        => true,
            'rest_base'           => 'promotions',
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
            'subtitle_en' => array(
                'type'         => 'string',
                'description'  => __('Subtitle (English)', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'subtitle_id' => array(
                'type'         => 'string',
                'description'  => __('Subtitle (Indonesian)', 'coffee-shop'),
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
            'cta_text_en' => array(
                'type'         => 'string',
                'description'  => __('English CTA text', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'cta_text_id' => array(
                'type'         => 'string',
                'description'  => __('Indonesian CTA text', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'icon' => array(
                'type'         => 'string',
                'description'  => __('Icon path or URL', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'is_active' => array(
                'type'         => 'boolean',
                'description'  => __('Whether promotion is active', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => true,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }
}