<?php
/**
 * Menu Item Meta Box View
 */

defined('ABSPATH') || exit;

// Get meta values
$price = get_post_meta($post->ID, 'price', true);
$category = get_post_meta($post->ID, 'category', true);
$is_available = get_post_meta($post->ID, 'is_available', true);
$preparation_time = get_post_meta($post->ID, 'preparation_time', true);
$calories = get_post_meta($post->ID, 'calories', true);
$ingredients = get_post_meta($post->ID, 'ingredients', true);
$allergens = get_post_meta($post->ID, 'allergens', true);
$points_value = get_post_meta($post->ID, 'points_value', true);

// New schema fields
$product_description = get_post_meta($post->ID, 'product_description', true) ?: array('en' => '', 'id' => '');
$image = get_post_meta($post->ID, 'image', true);
$map = get_post_meta($post->ID, 'map', true);
$color = get_post_meta($post->ID, 'color', true);
$customization_options = get_post_meta($post->ID, 'customization_options', true) ?: array();
$tags = get_post_meta($post->ID, 'tags', true) ?: array();
$enable_pattern = get_post_meta($post->ID, 'enable_pattern', true);
$ordering_num = get_post_meta($post->ID, 'ordering_num', true);
$pattern = get_post_meta($post->ID, 'pattern', true);
$image_type = get_post_meta($post->ID, 'image_type', true);
$enable_flip = get_post_meta($post->ID, 'enable_flip', true);
$flip_image = get_post_meta($post->ID, 'flip_image', true);
?>

<table class="form-table">
    <tr>
        <th><label for="price"><?php _e('Price (Rp)', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="price" name="price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="regular-text" required>
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
            <input type="number" id="preparation_time" name="preparation_time" value="<?php echo esc_attr($preparation_time ?: 5); ?>" min="1" step="1" class="small-text">
        </td>
    </tr>

    <tr>
        <th><label for="calories"><?php _e('Calories', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="calories" name="calories" value="<?php echo esc_attr($calories); ?>" min="0" step="1" class="small-text">
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
            <input type="number" id="points_value" name="points_value" value="<?php echo esc_attr($points_value ?: 10); ?>" min="0" step="1" class="small-text">
            <p class="description"><?php _e('Reward points earned when purchased', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <!-- New Schema Fields -->
    <tr>
        <th><label><?php _e('Description (English)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="product_description_en" name="product_description[en]" rows="3" class="large-text"><?php echo esc_textarea($product_description['en']); ?></textarea>
        </td>
    </tr>

    <tr>
        <th><label><?php _e('Description (Indonesian)', 'coffee-shop'); ?></label></th>
        <td>
            <textarea id="product_description_id" name="product_description[id]" rows="3" class="large-text"><?php echo esc_textarea($product_description['id']); ?></textarea>
        </td>
    </tr>

    <tr>
        <th><label><?php _e('Product Image', 'coffee-shop'); ?></label></th>
        <td>
            <div class="image-upload-section">
                <input type="url" id="image" name="image" value="<?php echo esc_attr($image); ?>" class="regular-text" placeholder="Image URL">
                <button type="button" class="button" id="select_image"><?php _e('Select from Media Library', 'coffee-shop'); ?></button>
                <input type="file" id="image_file" name="image_file" accept="image/*" class="hidden">
                <button type="button" class="button" id="upload_image"><?php _e('Upload New Image', 'coffee-shop'); ?></button>
                <p class="description"><?php _e('Upload a new image or select from media library', 'coffee-shop'); ?></p>
                <?php if ($image): ?>
                    <div class="image-preview" style="margin-top: 10px;">
                        <img src="<?php echo esc_url($image); ?>" alt="Product Image" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px;">
                    </div>
                <?php endif; ?>
            </div>
        </td>
    </tr>

    <tr>
        <th><label for="map"><?php _e('Map Selection', 'coffee-shop'); ?></label></th>
        <td>
            <select id="map" name="map">
                <option value=""><?php _e('Select Map', 'coffee-shop'); ?></option>
                <!-- Options will be populated dynamically -->
            </select>
            <p class="description"><?php _e('Map image from /public/map/pulau directory', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <tr>
        <th><label for="color"><?php _e('Color', 'coffee-shop'); ?></label></th>
        <td>
            <input type="color" id="color" name="color" value="<?php echo esc_attr($color ?: '#000000'); ?>" class="small-text">
        </td>
    </tr>

    <tr>
        <th><label><?php _e('Customization Options', 'coffee-shop'); ?></label></th>
        <td>
            <div id="customization_options_container">
                <!-- Dynamic customization options will be added here -->
            </div>
            <button type="button" class="button" id="add_customization_option"><?php _e('Add Option', 'coffee-shop'); ?></button>
            <p class="description"><?php _e('Define product customization options', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <tr>
        <th><label for="tags"><?php _e('Tags', 'coffee-shop'); ?></label></th>
        <td>
            <input type="text" id="tags" name="tags" value="<?php echo esc_attr(is_array($tags) ? implode(', ', $tags) : $tags); ?>" class="regular-text">
            <p class="description"><?php _e('Comma-separated list of tags', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <tr>
        <th><label for="ordering_num"><?php _e('Ordering Number', 'coffee-shop'); ?></label></th>
        <td>
            <input type="number" id="ordering_num" name="ordering_num" value="<?php echo esc_attr($ordering_num); ?>" class="small-text">
            <p class="description"><?php _e('Number to determine the order of the product', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <tr>
        <th><label for="enable_pattern"><?php _e('Enable Pattern', 'coffee-shop'); ?></label></th>
        <td>
            <label class="checkbox-label">
                <input type="checkbox" id="enable_pattern" name="enable_pattern" value="1" <?php checked($enable_pattern, true); ?>>
                <?php _e('Enable pattern overlay', 'coffee-shop'); ?>
            </label>
        </td>
    </tr>

    <tr>
        <th><label for="pattern"><?php _e('Pattern Selection', 'coffee-shop'); ?></label></th>
        <td>
            <select id="pattern" name="pattern">
                <option value=""><?php _e('Select Pattern', 'coffee-shop'); ?></option>
                <!-- Options will be populated dynamically -->
            </select>
            <p class="description"><?php _e('Pattern from /public/svg/patterns directory', 'coffee-shop'); ?></p>
        </td>
    </tr>

    <tr>
        <th><label for="image_type"><?php _e('Image Type', 'coffee-shop'); ?></label></th>
        <td>
            <select id="image_type" name="image_type">
                <option value="hot" <?php selected($image_type, 'hot'); ?>><?php _e('Hot', 'coffee-shop'); ?></option>
                <option value="iced" <?php selected($image_type, 'iced'); ?>><?php _e('Iced', 'coffee-shop'); ?></option>
            </select>
        </td>
    </tr>

    <tr>
        <th><label for="enable_flip"><?php _e('Enable Flip', 'coffee-shop'); ?></label></th>
        <td>
            <label class="checkbox-label">
                <input type="checkbox" id="enable_flip" name="enable_flip" value="1" <?php checked($enable_flip, true); ?>>
                <?php _e('Enable image flip functionality', 'coffee-shop'); ?>
            </label>
        </td>
    </tr>

    <tr>
        <th><label><?php _e('Flip Image', 'coffee-shop'); ?></label></th>
        <td>
            <div class="image-upload-section">
                <input type="url" id="flip_image" name="flip_image" value="<?php echo esc_attr($flip_image); ?>" class="regular-text" placeholder="Flip Image URL">
                <button type="button" class="button" id="select_flip_image"><?php _e('Select from Media Library', 'coffee-shop'); ?></button>
                <input type="file" id="flip_image_file" name="flip_image_file" accept="image/*" class="hidden">
                <button type="button" class="button" id="upload_flip_image"><?php _e('Upload New Image', 'coffee-shop'); ?></button>
                <p class="description"><?php _e('Upload a new image or select from media library', 'coffee-shop'); ?></p>
                <?php if ($flip_image): ?>
                    <div class="image-preview" style="margin-top: 10px;">
                        <img src="<?php echo esc_url($flip_image); ?>" alt="Flip Image" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px;">
                    </div>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
}
.customization-option {
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    background: #f9f9f9;
}
.customization-option input[type="text"] {
    width: 40%;
    margin-right: 10px;
}
.remove-option {
    color: #a00;
    cursor: pointer;
}
</style>

<!--
<script>
jQuery(document).ready(function($) {
    // Load customization options
    var customizationOptions = <?php echo json_encode($customization_options); ?>;
    loadCustomizationOptions(customizationOptions);

    // Add customization option
    $('#add_customization_option').on('click', function() {
        addCustomizationOption('', []);
    });

    // Media library integration
    $('#select_image, #select_flip_image').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetInput = button.attr('id') === 'select_image' ? '#image' : '#flip_image';

        var mediaUploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $(targetInput).val(attachment.url);
        });

        mediaUploader.open();
    });

    // File upload handling - Temporarily disabled
    // $('#upload_image').on('click', function(e) {
    //     e.preventDefault();
    //     $('#image_file').click();
    // });

    // $('#upload_flip_image').on('click', function(e) {
    //     e.preventDefault();
    //     $('#flip_image_file').click();
    // });

    // $('#image_file').on('change', function() {
    //     handleFileUpload(this.files[0], '#image');
    // });

    // $('#flip_image_file').on('change', function() {
    //     handleFileUpload(this.files[0], '#flip_image');
    // });

    function handleFileUpload(file, targetInput) {
        if (!file) return;

        // Validate file type
        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
            return;
        }

        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }

        var formData = new FormData();
        formData.append('file', file);

        // Show loading
        var button = $(targetInput.replace('#', '#upload_'));
        var originalText = button.text();
        button.text('Uploading...').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'coffee_shop_upload_media',
                _wpnonce: coffeeShopAdmin.nonce,
                file: file
            },
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data && response.data.url) {
                    $(targetInput).val(response.data.url);
                    // Add preview
                    var previewHtml = '<div class="image-preview" style="margin-top: 10px;">' +
                        '<img src="' + response.data.url + '" alt="Uploaded Image" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px;">' +
                        '</div>';
                    $(targetInput).closest('.image-upload-section').find('.image-preview').remove();
                    $(targetInput).closest('.image-upload-section').append(previewHtml);
                } else {
                    alert('Upload failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Upload failed: ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    // Load map options
    loadDirectoryOptions('/public/map/pulau', '#map');

    // Load pattern options
    loadDirectoryOptions('/public/svg/patterns', '#pattern');

    function loadCustomizationOptions(options) {
        $('#customization_options_container').empty();
        $.each(options, function(key, values) {
            addCustomizationOption(key, values);
        });
    }

    function addCustomizationOption(key, values) {
        var optionHtml = '<div class="customization-option">' +
            '<input type="text" name="customization_options[' + key + '][key]" value="' + key + '" placeholder="Option name (e.g., size)" />' +
            '<input type="text" name="customization_options[' + key + '][values]" value="' + values.join(', ') + '" placeholder="Values (comma-separated)" />' +
            '<span class="remove-option">Remove</span>' +
            '</div>';
        $('#customization_options_container').append(optionHtml);
    }

    // Remove customization option
    $(document).on('click', '.remove-option', function() {
        $(this).parent().remove();
    });

    function loadDirectoryOptions(path, selector) {
        // This would need AJAX call to scan directory - simplified for now
        // In real implementation, you'd make an AJAX call to get directory contents
        $(selector).append('<option value="option1">Option 1</option><option value="option2">Option 2</option>');
    }
});
</script>
-->
