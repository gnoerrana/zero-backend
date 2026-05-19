<?php
/**
 * Menu Item Post Type
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Menu_Item {

    const POST_TYPE = 'menu_item';

    /**
     * Register post type
     */
    public static function register() {
        $labels = array(
            'name'                  => __('Menu Items', 'coffee-shop'),
            'singular_name'         => __('Menu Item', 'coffee-shop'),
            'menu_name'             => __('Menu', 'coffee-shop'),
            'all_items'             => __('All Items', 'coffee-shop'),
            'add_new'               => __('Add New', 'coffee-shop'),
            'add_new_item'          => __('Add New Menu Item', 'coffee-shop'),
            'edit_item'             => __('Edit Menu Item', 'coffee-shop'),
            'new_item'              => __('New Menu Item', 'coffee-shop'),
            'view_item'             => __('View Menu Item', 'coffee-shop'),
            'search_items'          => __('Search Menu Items', 'coffee-shop'),
            'not_found'             => __('No menu items found', 'coffee-shop'),
            'not_found_in_trash'    => __('No menu items found in trash', 'coffee-shop'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'menu-item'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-coffee',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true,
            'rest_base'           => 'menu_items',
        );

        register_post_type(self::POST_TYPE, $args);

        // Register meta fields for REST API
        self::register_meta_fields();
    }

    /**
     * Register meta fields
     */
    private static function register_meta_fields() {
        $meta_fields = array(
            'price' => array(
                'type'         => 'number',
                'description'  => __('Price of the menu item', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'category' => array(
                'type'         => 'string',
                'description'  => __('Category: coffee, food, beverage', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'is_available' => array(
                'type'         => 'boolean',
                'description'  => __('Whether the item is available', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => true,
            ),
            'preparation_time' => array(
                'type'         => 'integer',
                'description'  => __('Estimated preparation time in minutes', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => 5,
            ),
            'calories' => array(
                'type'         => 'integer',
                'description'  => __('Calories count', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'ingredients' => array(
                'type'         => 'string',
                'description'  => __('List of ingredients', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'allergens' => array(
                'type'         => 'string',
                'description'  => __('Allergen information', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'points_value' => array(
                'type'         => 'integer',
                'description'  => __('Reward points earned per purchase', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => 10,
            ),
            'product_title' => array(
                'type'         => 'string',
                'description'  => __('Product title', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'product_description' => array(
                'type'         => 'object',
                'description'  => __('Product description with language variants', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'en' => array(
                                'type' => 'string',
                            ),
                            'id' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'image' => array(
                'type'         => 'string',
                'description'  => __('Product image path', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'map' => array(
                'type'         => 'string',
                'description'  => __('Map image path', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'color' => array(
                'type'         => 'string',
                'description'  => __('Product color', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'tags' => array(
                'type'         => 'array',
                'description'  => __('Product tags', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
            'customization_options' => array(
                'type'         => 'object',
                'description'  => __('Product customization options', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(),
                    ),
                ),
            ),
            'enable_pattern' => array(
                'type'         => 'boolean',
                'description'  => __('Enable pattern overlay', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => false,
            ),
            'pattern' => array(
                'type'         => 'string',
                'description'  => __('Pattern type', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'image_type' => array(
                'type'         => 'string',
                'description'  => __('Image type (hot/iced)', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'enable_flip' => array(
                'type'         => 'boolean',
                'description'  => __('Enable image flip', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => false,
            ),
            'flip_image' => array(
                'type'         => 'string',
                'description'  => __('Flip image path', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
            ),
            'manage_stock' => array(
                'type'         => 'boolean',
                'description'  => __('Whether to manage stock for this product', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => false,
            ),
            'stock_quantity' => array(
                'type'         => 'integer',
                'description'  => __('Current stock quantity', 'coffee-shop'),
                'single'       => true,
                'show_in_rest' => true,
                'default'      => 0,
            ),
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta(self::POST_TYPE, $meta_key, $args);
        }
    }
}