<?php
/**
 * Promotion Category Taxonomy
 */

defined('ABSPATH') || exit;

class Coffee_Shop_Promotion_Category {

    const TAXONOMY = 'promotion_category';

    /**
     * Register taxonomy
     */
    public static function register() {
        $labels = array(
            'name'              => __('Promotion Categories', 'coffee-shop'),
            'singular_name'     => __('Promotion Category', 'coffee-shop'),
            'menu_name'         => __('Categories', 'coffee-shop'),
            'all_items'         => __('All Categories', 'coffee-shop'),
            'edit_item'         => __('Edit Category', 'coffee-shop'),
            'update_item'       => __('Update Category', 'coffee-shop'),
            'add_new_item'      => __('Add New Category', 'coffee-shop'),
            'new_item_name'     => __('New Category Name', 'coffee-shop'),
            'search_items'      => __('Search Categories', 'coffee-shop'),
        );

        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'rest_base'         => 'promotion-categories',
            'query_var'         => true,
            'rewrite'           => array('slug' => 'promotion-category'),
        );

        register_taxonomy(self::TAXONOMY, 'promotion', $args);
    }
}