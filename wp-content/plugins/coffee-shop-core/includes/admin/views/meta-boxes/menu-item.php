<?php
/**
 * Menu Item Meta Box View
 */

defined('ABSPATH') || exit;
?>

<table class="form-table">
    <tr>
        <th><label for="price"><?php _e('Price (Rp)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="price" name="price" value="<?php echo esc_attr($price); ?>" step="1000" min="0" class="regular-text" required>
        </td>
    </tr>
    
    <tr>
        <th><label for="category"><?php _e('Category', 'coffee-shop'); ?></label></th>
        <td>
            <select id="category" name="category">
                <option value="coffee" <?php selected($category, 'coffee'); ?>><?php _e('Coffee', 'coffee-shop'); ?></option>
                <option value="food" <?php selected($category, 'food'); ?>><?php _e('Food', 'coffee-shop'); ?></option>
                <option value="beverage" <?php selected($category, 'beverage'); ?>><?php _e('Beverage', 'coffee-shop'); ?></option>
                <option value="dessert" <?php selected($category, 'dessert'); ?>><?php _e('Dessert', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>
    
    <tr>
        <th><label for="is_available"><?php _e('Available', 'coffee-shop'); ?></label></th>
        <td>
            <label class="checkbox-label">
                <input type="checkbox" id="is_available" name="is_available" value="1" <?php checked($is_available, true); ?>>
                <?php _e('Item is available for ordering', 'coffee-shop'); ?>
            </label>
        </td>
    </tr>
    
    <tr>
        <th><label for="preparation_time"><?php _e('Preparation Time (minutes)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="preparation_time" name="preparation_time" value="<?php echo esc_attr($preparation_time ?: 5); ?>" min="1" class="small-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="calories"><?php _e('Calories', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="calories" name="calories" value="<?php echo esc_attr($calories); ?>" min="0" class="small-text">
            <p class="description"><?php _e('Calories per serving', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="ingredients"><?php _e('Ingredients', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="ingredients" name="ingredients" rows="3" class="large-text"><?php echo esc_textarea($ingredients); ?></textarea>
            <p class="description"><?php _e('List main ingredients', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="allergens"><?php _e('Allergens', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="allergens" name="allergens" value="<?php echo esc_attr($allergens); ?>" class="regular-text">
            <p class="description"><?php _e('e.g., dairy, nuts, gluten', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="points_value"><?php _e('Points Value', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="points_value" name="points_value" value="<?php echo esc_attr($points_value ?: 10); ?>" min="0" class="small-text">
            <p class="description"><?php _e('Reward points earned when purchased', 'coffee-shop'); ?></p>
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
