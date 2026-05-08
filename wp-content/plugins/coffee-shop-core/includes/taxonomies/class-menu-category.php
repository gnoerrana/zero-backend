<?php
/**
 * Menu Category Taxonomy
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Menu_Category {

    const TAXONOMY = 'menu_category';

    /**
     * Register taxonomy
     */
    public static function register() {
        $labels = array(
            'name'                       => __('Menu Categories', 'coffee-shop'),
            'singular_name'              => __('Menu Category', 'coffee-shop'),
            'menu_name'                  => __('Categories', 'coffee-shop'),
            'all_items'                  => __('All Categories', 'coffee-shop'),
            'parent_item'                => __('Parent Category', 'coffee-shop'),
            'parent_item_colon'          => __('Parent Category:', 'coffee-shop'),
            'new_item_name'              => __('New Category Name', 'coffee-shop'),
            'add_new_item'               => __('Add New Category', 'coffee-shop'),
            'edit_item'                  => __('Edit Category', 'coffee-shop'),
            'update_item'                => __('Update Category', 'coffee-shop'),
            'view_item'                  => __('View Category', 'coffee-shop'),
            'separate_items_with_commas' => __('Separate categories with commas', 'coffee-shop'),
            'add_or_remove_items'        => __('Add or remove categories', 'coffee-shop'),
            'choose_from_most_used'      => __('Choose from the most used', 'coffee-shop'),
            'popular_items'              => __('Popular Categories', 'coffee-shop'),
            'search_items'               => __('Search Categories', 'coffee-shop'),
            'not_found'                  => __('Not Found', 'coffee-shop'),
            'no_terms'                   => __('No categories', 'coffee-shop'),
            'items_list'                 => __('Categories list', 'coffee-shop'),
            'items_list_navigation'      => __('Categories list navigation', 'coffee-shop'),
        );

        $args = array(
            'labels'             => $labels,
            'hierarchical'       => true,
            'public'             => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => true,
            'show_tagcloud'      => false,
            'rewrite'            => array('slug' => 'menu-category'),
            'show_in_rest'       => true,
            'rest_base'          => 'menu_categories',
        );

        register_taxonomy(self::TAXONOMY, array('menu_item'), $args);

        // Register meta fields for taxonomy
        self::register_meta_fields();
    }

    /**
     * Register meta fields for taxonomy
     */
    private static function register_meta_fields() {
        register_term_meta(self::TAXONOMY, 'icon', array(
            'type'         => 'string',
            'description'  => __('Category icon', 'coffee-shop'),
            'single'       => true,
            'show_in_rest' => true,
        ));

        register_term_meta(self::TAXONOMY, 'color', array(
            'type'         => 'string',
            'description'  => __('Category color', 'coffee-shop'),
            'single'       => true,
            'show_in_rest' => true,
        ));

        register_term_meta(self::TAXONOMY, 'display_order', array(
            'type'         => 'integer',
            'description'  => __('Display order', 'coffee-shop'),
            'single'       => true,
            'show_in_rest' => true,
        ));
    }

    /**
     * Get default categories
     */
    public static function get_defaults() {
        return array(
            'coffee' => array(
                'name'        => __('Coffee', 'coffee-shop'),
                'description' => __('Premium coffee drinks', 'coffee-shop'),
                'meta'        => array(
                    'icon'  => 'coffee',
                    'color' => '#8B4513',
                ),
            ),
            'food' => array(
                'name'        => __('Food', 'coffee-shop'),
                'description' => __('Delicious food items', 'coffee-shop'),
                'meta'        => array(
                    'icon'  => 'utensils',
                    'color' => '#228B22',
                ),
            ),
            'beverage' => array(
                'name'        => __('Beverages', 'coffee-shop'),
                'description' => __('Non-coffee beverages', 'coffee-shop'),
                'meta'        => array(
                    'icon'  => 'glass-water',
                    'color' => '#4169E1',
                ),
            ),
            'dessert' => array(
                'name'        => __('Desserts', 'coffee-shop'),
                'description' => __('Sweet treats', 'coffee-shop'),
                'meta'        => array(
                    'icon'  => 'cake',
                    'color' => '#FF69B4',
                ),
            ),
        );
    }

    /**
     * Insert default categories
     */
    public static function insert_defaults() {
        $defaults = self::get_defaults();

        foreach ($defaults as $slug => $data) {
            if (!term_exists($slug, self::TAXONOMY)) {
                $term = wp_insert_term(
                    $data['name'],
                    self::TAXONOMY,
                    array(
                        'slug'        => $slug,
                        'description' => $data['description'],
                    )
                );

                if (!is_wp_error($term)) {
                    foreach ($data['meta'] as $key => $value) {
                        update_term_meta($term['term_id'], $key, $value);
                    }
                }
            }
        }
    }
}
