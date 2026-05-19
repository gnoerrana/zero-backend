<?php
/**
 * Special Section Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Special_Section {

    const POST_TYPE = 'special_section';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Special Sections', 'coffee-shop'),
            'singular_name'         => __('Special Section', 'coffee-shop'),
            'menu_name'             => __('Special Section', 'coffee-shop'),
            'all_items'             => __('All Sections', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Special Section', 'coffee-shop'),
            'edit_item'             => __('Edit Special Section', 'coffee-shop'),
            'new_item'              => __('New Special Section', 'coffee-shop'),
            'view_item'             => __('View Special Section', 'coffee-shop'),
            'search_items'          => __('Search Special Sections', 'coffee-shop'),
            'not_found'             => __('No special sections found', 'coffee-shop'),
            'not_found_in_trash'    => __('No special sections found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'special-section'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-welcome-learn-more',
            'supports'            => array('title', 'editor', 'thumbnail'),
            'show_in_rest'        => true,
            'rest_base'           => 'special-sections',
        );

        register_post_type(self::POST_TYPE, $args);

        // Register meta fields
        self::register_meta_fields();
    }

    /**
     * Register meta fields for English and Indonesian descriptions
     */
    private static function register_meta_fields() {
        $meta_fields = array(
            'description_en' => array(
                'type'              => 'string',
                'description'       => __('English description for this section', 'coffee-shop'),
                'single'            => true,
                'show_in_rest'      => true,
            ),
            'description_id' => array(
                'type'              => 'string',
                'description'       => __('Indonesian description for this section', 'coffee-shop'),
                'single'            => true,
                'show_in_rest'      => true,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }
}