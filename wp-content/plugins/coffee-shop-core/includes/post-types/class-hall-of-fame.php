<?php
/**
 * Hall of Fame Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Hall_Of_Fame {

    const POST_TYPE = 'hall_of_fame';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Hall of Fame', 'coffee-shop'),
            'singular_name'         => __('Hall of Fame Item', 'coffee-shop'),
            'menu_name'             => __('Hall of Fame', 'coffee-shop'),
            'all_items'             => __('All Items', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Hall of Fame Item', 'coffee-shop'),
            'edit_item'             => __('Edit Hall of Fame Item', 'coffee-shop'),
            'new_item'              => __('New Hall of Fame Item', 'coffee-shop'),
            'view_item'             => __('View Hall of Fame Item', 'coffee-shop'),
            'search_items'          => __('Search Hall of Fame Items', 'coffee-shop'),
            'not_found'             => __('No Hall of Fame items found', 'coffee-shop'),
            'not_found_in_trash'    => __('No Hall of Fame items found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-star-filled',
            // page-attributes gives the native "Order" field (post.menu_order) for free.
            'supports'            => array('title', 'page-attributes'),
            'show_in_rest'        => true,
            'rest_base'           => 'hall-of-fame-posts',
        );

        register_post_type(self::POST_TYPE, $args);

        self::register_meta_fields();
    }

    /**
     * Register meta fields
     */
    private static function register_meta_fields() {
        $meta_fields = array(
            'media_type' => array(
                'type'         => 'string',
                'description'  => __('Media type: image or video', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'media_id' => array(
                'type'         => 'integer',
                'description'  => __('Attachment ID for the uploaded media', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'media_url' => array(
                'type'         => 'string',
                'description'  => __('URL of the uploaded image or video', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'text_position' => array(
                'type'         => 'string',
                'description'  => __('Where the title/description render relative to the media: top or bottom', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'title_en' => array(
                'type'         => 'string',
                'description'  => __('English title', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'title_id' => array(
                'type'         => 'string',
                'description'  => __('Indonesian title', 'coffee-shop'),
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
            'is_active' => array(
                'type'         => 'boolean',
                'description'  => __('Whether this item is shown on the public Hall of Fame page', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }

    /**
     * Get valid media types
     */
    public static function get_media_types() {
        return array('image', 'video');
    }

    /**
     * Get valid text positions
     */
    public static function get_text_positions() {
        return array('top', 'bottom');
    }
}
