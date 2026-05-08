<table class="form-table">
    <tr>
        <th><label for="subtitle_en"><?php _e('Subtitle (English)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" name="subtitle_en" id="subtitle_en" value="<?php echo esc_attr($subtitle_en); ?>" class="widefat" placeholder="Free Pastry with Coffee" />
        </td>
    </tr>
    <tr>
        <th><label for="subtitle_id"><?php _e('Subtitle (Indonesian)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" name="subtitle_id" id="subtitle_id" value="<?php echo esc_attr($subtitle_id); ?>" class="widefat" placeholder="Pastry Gratis dengan Kopi" />
        </td>
    </tr>
    <tr>
        <th><label for="description_en"><?php _e('Description (English)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea name="description_en" id="description_en" class="widefat" rows="3"><?php echo esc_textarea($description_en); ?></textarea>
        </td>
    </tr>
    <tr>
        <th><label for="description_id"><?php _e('Description (Indonesian)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea name="description_id" id="description_id" class="widefat" rows="3"><?php echo esc_textarea($description_id); ?></textarea>
        </td>
    </tr>
    <tr>
        <th><label for="cta_text_en"><?php _e('CTA Text (English)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" name="cta_text_en" id="cta_text_en" value="<?php echo esc_attr($cta_text_en); ?>" class="widefat" placeholder="Order Now" />
        </td>
    </tr>
    <tr>
        <th><label for="cta_text_id"><?php _e('CTA Text (Indonesian)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" name="cta_text_id" id="cta_text_id" value="<?php echo esc_attr($cta_text_id); ?>" class="widefat" placeholder="Pesan Sekarang" />
        </td>
    </tr>
    <tr>
        <th><label for="icon"><?php _e('Icon Path/URL', 'coffee-shop'); ?></label></th>
        <td>
            <select name="icon" id="icon" class="widefat">
                <option value="">Select an icon</option>
                <option value="/svg/icons/wifi.svg" <?php selected($icon, '/svg/icons/wifi.svg'); ?>>WiFi</option>
                <option value="/svg/icons/ac.svg" <?php selected($icon, '/svg/icons/ac.svg'); ?>>Air Conditioner</option>
                <option value="/svg/icons/bread.svg" <?php selected($icon, '/svg/icons/bread.svg'); ?>>Bread</option>
                <option value="/svg/icons/coffee.svg" <?php selected($icon, '/svg/icons/coffee.svg'); ?>>Coffee</option>
                <option value="/svg/icons/cup.svg" <?php selected($icon, '/svg/icons/cup.svg'); ?>>Cup</option>
                <option value="/svg/icons/oh.svg" <?php selected($icon, '/svg/icons/oh.svg'); ?>>Oh</option>
                <option value="/svg/icons/paperbag.svg" <?php selected($icon, '/svg/icons/paperbag.svg'); ?>>Paper Bag</option>
                <option value="/svg/icons/percent.svg" <?php selected($icon, '/svg/icons/percent.svg'); ?>>Percent</option>
                <option value="/svg/icons/sandwich.svg" <?php selected($icon, '/svg/icons/sandwich.svg'); ?>>Sandwich</option>
                <option value="/svg/icons/smoke.svg" <?php selected($icon, '/svg/icons/smoke.svg'); ?>>Smoke</option>
                <option value="/svg/icons/tea.svg" <?php selected($icon, '/svg/icons/tea.svg'); ?>>Tea</option>
                <option value="/svg/icons/wc.svg" <?php selected($icon, '/svg/icons/wc.svg'); ?>>WC</option>
            </select>
            <?php if ($icon): ?>
                <p class="description"><?php _e('Current icon:', 'coffee-shop'); ?> <img src="<?php echo esc_url($icon); ?>" alt="" style="height: 24px; vertical-align: middle;"></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th><label for="is_active"><?php _e('Active', 'coffee-shop'); ?></label></th>
        <td>
            <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($is_active, true); ?> />
            <label for="is_active"><?php _e('Promotion is active and visible on frontend', 'coffee-shop'); ?></label>
        </td>
    </tr>
</table>