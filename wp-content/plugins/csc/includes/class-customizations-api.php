<?php
/**
 * REST API Handler for Customizations
 */

defined('ABSPATH') || exit;

class CSC_Customizations_API {

    /**
     * Database handler
     */
    private $db;

    /**
     * Namespace
     */
    private $namespace = 'csc/v1';

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new CSC_Customizations_DB();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/customizations', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_customizations'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_customization'),
                'permission_callback' => array($this, 'create_permissions_check'),
                'args' => $this->get_customization_args(),
            ),
        ));

        register_rest_route($this->namespace, '/customizations/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_customization'),
                'permission_callback' => array($this, 'get_permissions_check'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_customization'),
                'permission_callback' => array($this, 'update_permissions_check'),
                'args' => array_merge(
                    array(
                        'id' => array(
                            'required' => true,
                            'validate_callback' => function($param) {
                                return is_numeric($param);
                            },
                        ),
                    ),
                    $this->get_customization_args()
                ),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_customization'),
                'permission_callback' => array($this, 'delete_permissions_check'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/categories', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_categories'),
                'permission_callback' => array($this, 'get_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/products/(?P<product_id>\d+)/custom-options', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_product_custom_options'),
                'permission_callback' => array($this, 'get_permissions_check'),
                'args' => array(
                    'product_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param) && get_post_type($param) === 'menu_item';
                        },
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_product_custom_options'),
                'permission_callback' => array($this, 'update_permissions_check'),
                'args' => array(
                    'product_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param) && get_post_type($param) === 'menu_item';
                        },
                    ),
                    'custom_options' => array(
                        'required' => true,
                        'type' => 'object',
                    ),
                ),
            ),
        ));
    }

    /**
     * Get customizations
     */
    public function get_customizations($request) {
        $customizations = $this->db->get_all_customizations();

        // Format response
        $formatted = array();
        foreach ($customizations as $customization) {
            $formatted[] = $this->format_customization($customization);
        }

        return new WP_REST_Response($formatted, 200);
    }

    /**
     * Get single customization
     */
    public function get_customization($request) {
        $id = $request->get_param('id');
        $customization = $this->db->get_customization($id);

        if (!$customization) {
            return new WP_Error('not_found', __('Customization not found', 'coffee-shop-customizations'), array('status' => 404));
        }

        return new WP_REST_Response($this->format_customization($customization), 200);
    }

    /**
     * Create customization
     */
    public function create_customization($request) {
        $data = $this->prepare_customization_data($request);

        $id = $this->db->insert_customization($data);

        if (!$id) {
            return new WP_Error('db_error', __('Failed to create customization', 'coffee-shop-customizations'), array('status' => 500));
        }

        $customization = $this->db->get_customization($id);

        return new WP_REST_Response($this->format_customization($customization), 201);
    }

    /**
     * Update customization
     */
    public function update_customization($request) {
        $id = $request->get_param('id');
        $customization = $this->db->get_customization($id);

        if (!$customization) {
            return new WP_Error('not_found', __('Customization not found', 'coffee-shop-customizations'), array('status' => 404));
        }

        $data = $this->prepare_customization_data($request);

        $result = $this->db->update_customization($id, $data);

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update customization', 'coffee-shop-customizations'), array('status' => 500));
        }

        $updated = $this->db->get_customization($id);

        return new WP_REST_Response($this->format_customization($updated), 200);
    }

    /**
     * Delete customization
     */
    public function delete_customization($request) {
        $id = $request->get_param('id');
        $customization = $this->db->get_customization($id);

        if (!$customization) {
            return new WP_Error('not_found', __('Customization not found', 'coffee-shop-customizations'), array('status' => 404));
        }

        $result = $this->db->delete_customization($id);

        if (!$result) {
            return new WP_Error('db_error', __('Failed to delete customization', 'coffee-shop-customizations'), array('status' => 500));
        }

        return new WP_REST_Response(null, 204);
    }

    /**
     * Get categories
     */
    public function get_categories($request) {
        $categories = $this->db->get_menu_categories();

        return new WP_REST_Response($categories, 200);
    }

    /**
     * Get custom options for a product
     */
    public function get_product_custom_options($request) {
        $product_id = $request->get_param('product_id');
        $categories = wp_get_post_terms($product_id, 'menu_category', array('fields' => 'ids'));
        $customizations = $this->db->get_all_customizations();

        $applicable = array();
        foreach ($customizations as $customization) {
            $customization_categories = maybe_unserialize($customization['categories']);
            if (array_intersect($categories, $customization_categories)) {
                $selected_options = get_post_meta($product_id, '_csc_option_' . $customization['id'], true);
                $custom_price = get_post_meta($product_id, '_csc_price_' . $customization['id'], true);

                if (!is_array($selected_options)) {
                    $selected_options = array($selected_options);
                }

                $applicable[] = array(
                    'id' => $customization['id'],
                    'option_name' => $customization['option_name'],
                    'options' => maybe_unserialize($customization['options']),
                    'selection_type' => $customization['selection_type'],
                    'required' => (bool) $customization['required'],
                    'selected_options' => $selected_options,
                    'custom_price' => $custom_price,
                );
            }
        }

        return new WP_REST_Response($applicable, 200);
    }

    /**
     * Update custom options for a product
     */
    public function update_product_custom_options($request) {
        $product_id = $request->get_param('product_id');
        $custom_options = $request->get_param('custom_options');

        // Verify user can edit this post
        if (!current_user_can('edit_post', $product_id)) {
            return new WP_Error('rest_forbidden', __('You cannot edit this product.', 'csc'), array('status' => 403));
        }

        foreach ($custom_options as $option) {
            $option_key = '_csc_option_' . $option['id'];
            $price_key = '_csc_price_' . $option['id'];

            if (isset($option['selected_options'])) {
                update_post_meta($product_id, $option_key, $option['selected_options']);
            }

            if (isset($option['custom_price'])) {
                update_post_meta($product_id, $price_key, floatval($option['custom_price']));
            }
        }

        return new WP_REST_Response(array('message' => 'Custom options updated successfully.'), 200);
    }

    /**
     * Format customization for response
     */
    private function format_customization($customization) {
        return array(
            'id' => (int) $customization['id'],
            'custom_name' => $customization['custom_name'],
            'price' => (float) $customization['price'],
            'categories' => maybe_unserialize($customization['categories']),
            'created_at' => $customization['created_at'],
            'updated_at' => $customization['updated_at'],
        );
    }

    /**
     * Prepare customization data from request
     */
    private function prepare_customization_data($request) {
        return array(
            'option_name' => $request->get_param('option_name'),
            'options' => $request->get_param('options'),
            'categories' => $request->get_param('categories'),
            'required' => $request->get_param('required'),
            'selection_type' => $request->get_param('selection_type'),
        );
    }

    /**
     * Get customization validation args
     */
    private function get_customization_args() {
        return array(
            'option_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($value) {
                    return !empty(trim($value));
                },
            ),
            'options' => array(
                'required' => true,
                'type' => 'array',
                'items' => array(
                    'type' => 'object',
                    'properties' => array(
                        'label' => array(
                            'type' => 'string',
                            'required' => true,
                        ),
                        'price' => array(
                            'type' => 'number',
                            'minimum' => 0,
                            'required' => true,
                        ),
                    ),
                ),
                'validate_callback' => function($value) {
                    return is_array($value) && !empty($value);
                },
            ),
            'categories' => array(
                'required' => false,
                'type' => 'array',
                'items' => array(
                    'type' => 'integer',
                ),
                'default' => array(),
            ),
            'required' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => false,
            ),
            'selection_type' => array(
                'required' => false,
                'type' => 'string',
                'enum' => array('single', 'multi'),
                'default' => 'multi',
            ),
        );
    }

    /**
     * Permission checks
     */
    public function get_permissions_check($request) {
        return current_user_can('read');
    }

    public function create_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function update_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function delete_permissions_check($request) {
        return current_user_can('manage_options');
    }
}