<?php
/**
 * Special Section Category Taxonomy
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Special_Section_Category {

    const TAXONOMY = 'special_section_category';

    /**
     * Register taxonomy
     */
    public static function register() {
        $labels = array(
            'name'                       => __('Section Categories', 'coffee-shop'),
            'singular_name'              => __('Section Category', 'coffee-shop'),
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
            'rewrite'            => array('slug' => 'special-section-category'),
            'show_in_rest'       => true,
            'rest_base'          => 'special-section-categories',
        );

        register_taxonomy(self::TAXONOMY, array('special_section'), $args);
    }
}