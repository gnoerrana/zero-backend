<?php
/**
 * Reward Meta Box View
 */

defined('ABSPATH') || exit;
?>

<table class="form-table">
    <tr>
        <th><label for="points_required"><?php _e('Points Required', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="points_required" name="points_required" value="<?php echo esc_attr($points_required); ?>" min="1" class="small-text" required>
        </td>
    </tr>
    
    <tr>
        <th><label for="category"><?php _e('Category', 'coffee-shop'); ?></label></th>
        <td>
            <select id="category" name="category">
                <option value="drink" <?php selected($category, 'drink'); ?>><?php _e('Drink', 'coffee-shop'); ?></option>
                <option value="food" <?php selected($category, 'food'); ?>><?php _e('Food', 'coffee-shop'); ?></option>
                <option value="merchandise" <?php selected($category, 'merchandise'); ?>><?php _e('Merchandise', 'coffee-shop'); ?></option>
                <option value="discount" <?php selected($category, 'discount'); ?>><?php _e('Discount', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="reward_type"><?php _e('Reward Type', 'coffee-shop'); ?></label></th>
        <td>
            <select id="reward_type" name="reward_type">
                <option value="free_item" <?php selected($reward_type, 'free_item'); ?>><?php _e('Free Item', 'coffee-shop'); ?></option>
                <option value="discount" <?php selected($reward_type, 'discount'); ?>><?php _e('Discount', 'coffee-shop'); ?></option>
                <option value="upgrade" <?php selected($reward_type, 'upgrade'); ?>><?php _e('Size Upgrade', 'coffee-shop'); ?></option>
                <option value="special" <?php selected($reward_type, 'special'); ?>><?php _e('Special Offer', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="discount_percentage"><?php _e('Discount Percentage', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="discount_percentage" name="discount_percentage" value="<?php echo esc_attr($discount_percentage); ?>" min="0" max="100" class="small-text">
            <span>%</span>
            <p class="description"><?php _e('For discount type rewards', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="discount_amount"><?php _e('Discount Amount (Rp)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="discount_amount" name="discount_amount" value="<?php echo esc_attr($discount_amount); ?>" min="0" step="1000" class="regular-text">
            <p class="description"><?php _e('Fixed discount amount', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="tier_required"><?php _e('Minimum Tier Required', 'coffee-shop'); ?></label></th>
        <td>
            <select id="tier_required" name="tier_required">
                <option value="" <?php selected($tier_required, ''); ?>><?php _e('No minimum', 'coffee-shop'); ?></option>
                <option value="bronze" <?php selected($tier_required, 'bronze'); ?>><?php _e('Bronze', 'coffee-shop'); ?></option>
                <option value="silver" <?php selected($tier_required, 'silver'); ?>><?php _e('Silver', 'coffee-shop'); ?></option>
                <option value="gold" <?php selected($tier_required, 'gold'); ?>><?php _e('Gold', 'coffee-shop'); ?></option>
                <option value="platinum" <?php selected($tier_required, 'platinum'); ?>><?php _e('Platinum', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="max_redemptions"><?php _e('Maximum Redemptions', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="max_redemptions" name="max_redemptions" value="<?php echo esc_attr($max_redemptions); ?>" min="0" class="small-text">
            <p class="description"><?php _e('0 for unlimited', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="is_active"><?php _e('Active', 'coffee-shop'); ?></label></th>
        <td>
            <label class="checkbox-label">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php checked($is_active, true); ?>>
                <?php _e('Reward is available for redemption', 'coffee-shop'); ?>
            </label>
        </td>
    </tr>
    
    <tr>
        <th><label for="description_en"><?php _e('Description (English)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="description_en" name="description_en" rows="2" class="large-text"><?php echo esc_textarea($description_en); ?></textarea>
        </td>
    </tr>
    
    <tr>
        <th><label for="description_id"><?php _e('Description (Indonesian)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="description_id" name="description_id" rows="2" class="large-text"><?php echo esc_textarea($description_id); ?></textarea>
        </td>
    </tr>
</table>

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
