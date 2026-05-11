<?php
/**
 * Test script to verify user roles are created
 * Access: /wp-content/plugins/coffee-shop-core/test-roles.php
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo '<h1>Coffee Shop User Roles Test</h1>';

$roles = array('store_admin', 'cashier', 'customer');
$all_good = true;

foreach ($roles as $role_slug) {
    $role = get_role($role_slug);
    if ($role) {
        echo '<p style="color: green;">✓ Role "' . $role_slug . '" exists</p>';
        echo '<ul>';
        echo '<li>Name: ' . $role->name . '</li>';
        echo '<li>Capabilities count: ' . count($role->capabilities) . '</li>';
        echo '</ul>';
    } else {
        echo '<p style="color: red;">✗ Role "' . $role_slug . '" does NOT exist</p>';
        $all_good = false;
    }
}

if ($all_good) {
    echo '<p style="color: green; font-weight: bold;">All roles are properly configured!</p>';
} else {
    echo '<p style="color: red; font-weight: bold;">Some roles are missing. Try refreshing the page or deactivating/reactivating the plugin.</p>';
}

echo '<hr>';
echo '<h2>Available Roles in WordPress</h2>';
$editable_roles = wp_roles()->roles;
echo '<ul>';
foreach ($editable_roles as $role_key => $role_info) {
    echo '<li><strong>' . $role_key . ':</strong> ' . $role_info['name'] . '</li>';
}
echo '</ul>';
?>