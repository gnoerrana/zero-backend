<?php
/**
 * Admin Dashboard View
 */

defined('ABSPATH') || exit;

global $wpdb;

$today = current_time('Y-m-d');

// Get stats
$today_orders = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} 
    WHERE post_type = 'order' 
    AND post_status NOT IN ('cancelled', 'trash')
    AND DATE(post_date) = %s",
    $today
));

$today_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(pm.meta_value) 
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = 'total'
    AND p.post_type = 'order'
    AND p.post_status NOT IN ('cancelled', 'trash')
    AND DATE(p.post_date) = %s",
    $today
));

$pending_orders = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts} 
    WHERE post_type = 'order' 
    AND post_status = 'pending'"
);

$total_members = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->usermeta}
    WHERE meta_key = 'reward_points'"
);

// Recent orders
$recent_orders = get_posts(array(
    'post_type'      => 'order',
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC',
));

// Popular items
$popular_items = get_posts(array(
    'post_type'      => 'menu_item',
    'posts_per_page' => 5,
    'meta_key'       => 'order_count',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="coffee-shop-dashboard">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orders"></div>
                <div class="stat-content">
                    <h3><?php _e("Today's Orders", 'coffee-shop'); ?></h3>
                    <p class="stat-value"><?php echo (int) $today_orders; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue"></div>
                <div class="stat-content">
                    <h3><?php _e("Today's Revenue", 'coffee-shop'); ?></h3>
                    <p class="stat-value">Rp <?php echo number_format((float) $today_revenue, 0, ',', '.'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending"></div>
                <div class="stat-content">
                    <h3><?php _e('Pending Orders', 'coffee-shop'); ?></h3>
                    <p class="stat-value"><?php echo (int) $pending_orders; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon members"></div>
                <div class="stat-content">
                    <h3><?php _e('Total Members', 'coffee-shop'); ?></h3>
                    <p class="stat-value"><?php echo (int) $total_members; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><?php _e('Quick Actions', 'coffee-shop'); ?></h2>
            <div class="actions-grid">
                <a href="<?php echo admin_url('post-new.php?post_type=menu_item'); ?>" class="action-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Menu Item', 'coffee-shop'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=order'); ?>" class="action-btn">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('View Orders', 'coffee-shop'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=order&post_status=pending'); ?>" class="action-btn pending">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Pending Orders', 'coffee-shop'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=reward'); ?>" class="action-btn">
                    <span class="dashicons dashicons-awards"></span>
                    <?php _e('Manage Rewards', 'coffee-shop'); ?>
                </a>
            </div>
        </div>
        
        <!-- Recent Orders & Popular Items -->
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <h2><?php _e('Recent Orders', 'coffee-shop'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Order', 'coffee-shop'); ?></th>
                            <th><?php _e('Customer', 'coffee-shop'); ?></th>
                            <th><?php _e('Total', 'coffee-shop'); ?></th>
                            <th><?php _e('Status', 'coffee-shop'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($order->ID); ?>">
                                    <?php echo esc_html($order->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(get_post_meta($order->ID, 'customer_name', true)); ?></td>
                            <td>Rp <?php echo number_format((float) get_post_meta($order->ID, 'total', true), 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge <?php echo esc_attr($order->post_status); ?>">
                                    <?php echo ucfirst($order->post_status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard-section">
                <h2><?php _e('Popular Items', 'coffee-shop'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Item', 'coffee-shop'); ?></th>
                            <th><?php _e('Category', 'coffee-shop'); ?></th>
                            <th><?php _e('Price', 'coffee-shop'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_items as $item) : 
                            $terms = wp_get_post_terms($item->ID, 'menu_category');
                        ?>
                        <tr>
                            <td>
                                <?php echo get_the_post_thumbnail($item->ID, array(40, 40)); ?>
                                <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                    <?php echo esc_html($item->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo !empty($terms) ? esc_html($terms[0]->name) : '-'; ?></td>
                            <td>Rp <?php echo number_format((float) get_post_meta($item->ID, 'price', true), 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.coffee-shop-dashboard {
    margin-top: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon.orders { background: #e3f2fd; }
.stat-icon.revenue { background: #e8f5e9; }
.stat-icon.pending { background: #fff3e0; }
.stat-icon.members { background: #f3e5f5; }

.stat-content h3 {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.stat-value {
    margin: 5px 0 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.quick-actions {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.actions-grid {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: #2271b1;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.2s;
}

.action-btn:hover {
    background: #135e96;
    color: #fff;
}

.action-btn.pending {
    background: #dba617;
}

.action-btn .dashicons {
    margin-right: 8px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.dashboard-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dashboard-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.pending { background: #fff3e0; color: #e65100; }
.status-badge.preparing { background: #e3f2fd; color: #1565c0; }
.status-badge.ready { background: #e8f5e9; color: #2e7d32; }
.status-badge.completed { background: #f3e5f5; color: #7b1fa2; }
.status-badge.cancelled { background: #ffebee; color: #c62828; }
</style>
