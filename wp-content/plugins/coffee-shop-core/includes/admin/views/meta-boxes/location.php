<?php
/**
 * Location Meta Box View
 */

defined('ABSPATH') || exit;
?>

<table class="form-table">
    <tr>
        <th><label for="floor"><?php _e('Floor', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="floor" name="floor" value="<?php echo esc_attr($floor); ?>" class="regular-text">
            <p class="description"><?php _e('e.g., Ground Floor, 2nd Floor', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="building"><?php _e('Building', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="building" name="building" value="<?php echo esc_attr($building); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="address"><?php _e('Address', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="address" name="address" rows="2" class="large-text"><?php echo esc_textarea($address); ?></textarea>
        </td>
    </tr>
    
    <tr>
        <th><label for="access_instructions"><?php _e('Access Instructions', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="access_instructions" name="access_instructions" rows="3" class="large-text"><?php echo esc_textarea($access_instructions); ?></textarea>
            <p class="description"><?php _e('How to find and access this location', 'coffee-shop'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th><label for="capacity"><?php _e('Seating Capacity', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="capacity" name="capacity" value="<?php echo esc_attr($capacity); ?>" min="0" class="small-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="phone"><?php _e('Phone', 'coffee-shop'); ?></label></th>
        <td>
            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="latitude"><?php _e('Latitude', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="latitude" name="latitude" value="<?php echo esc_attr($latitude); ?>" step="0.000001" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="longitude"><?php _e('Longitude', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="longitude" name="longitude" value="<?php echo esc_attr($longitude); ?>" step="0.000001" class="regular-text">
        </td>
    </tr>
    
    <tr>
        <th><label for="is_active"><?php _e('Active', 'coffee-shop'); ?></label></th>
        <td>
            <label class="checkbox-label">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php checked($is_active, true); ?>>
                <?php _e('Location is open and accepting orders', 'coffee-shop'); ?>
            </label>
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
