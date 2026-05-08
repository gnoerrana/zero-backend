<?php
/**
 * Location Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Location {

    const POST_TYPE = 'location';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Locations', 'coffee-shop'),
            'singular_name'         => __('Location', 'coffee-shop'),
            'menu_name'             => __('Locations', 'coffee-shop'),
            'all_items'             => __('All Locations', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Location', 'coffee-shop'),
            'edit_item'             => __('Edit Location', 'coffee-shop'),
            'new_item'              => __('New Location', 'coffee-shop'),
            'view_item'             => __('View Location', 'coffee-shop'),
            'search_items'          => __('Search Locations', 'coffee-shop'),
            'not_found'             => __('No locations found', 'coffee-shop'),
            'not_found_in_trash'    => __('No locations found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'location'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 7,
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true,
            'rest_base'           => 'locations',
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
            'floor' => array(
                'type'         => 'string',
                'description'  => __('Floor number or name', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'building' => array(
                'type'         => 'string',
                'description'  => __('Building name', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'address' => array(
                'type'         => 'string',
                'description'  => __('Full address', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'access_instructions' => array(
                'type'         => 'string',
                'description'  => __('How to access this location', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'capacity' => array(
                'type'         => 'integer',
                'description'  => __('Seating capacity', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'opening_hours' => array(
                'type'         => 'object',
                'description'  => __('Opening hours by day', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'monday'    => array('type' => 'string'),
                            'tuesday'   => array('type' => 'string'),
                            'wednesday' => array('type' => 'string'),
                            'thursday'  => array('type' => 'string'),
                            'friday'    => array('type' => 'string'),
                            'saturday'  => array('type' => 'string'),
                            'sunday'    => array('type' => 'string'),
                        ),
                    ),
                ),
            ),
            'phone' => array(
                'type'         => 'string',
                'description'  => __('Contact phone number', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'latitude' => array(
                'type'         => 'number',
                'description'  => __('Latitude coordinate', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'longitude' => array(
                'type'         => 'number',
                'description'  => __('Longitude coordinate', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'is_active' => array(
                'type'         => 'boolean',
                'description'  => __('Whether location is active', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => true,
            ),
            'features' => array(
                'type'         => 'array',
                'description'  => __('Location features', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array('type' => 'string'),
                    ),
                ),
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }
}
