<?php
/**
 * Special Section Meta Box
 */

defined('ABSPATH') || exit;

// Get categories for the dropdown
$categories = get_terms(array(
    'taxonomy'   => 'special_section_category',
    'hide_empty' => false,
));

$selected_category = isset($category[0]) ? $category[0]->term_id : 0;

// Get existing meta values
$special_section_enabled = isset($enabled) ? $enabled : 0;
$special_section_allow_media = isset($allow_media) ? $allow_media : 0;
$special_section_image = isset($image) ? $image : '';
$special_section_image_2 = isset($image_2) ? $image_2 : '';
$special_section_image_3 = isset($image_3) ? $image_3 : '';
$special_section_image_4 = isset($image_4) ? $image_4 : '';
?>
<div class="meta-fields">
    <p class="description"><?php _e('Configure the special section with English and Indonesian descriptions.', 'coffee-shop'); ?></p>

    <h4 class="meta-section-title"><?php _e('Descriptions', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="description_en"><?php _e('Description (English)', 'coffee-shop'); ?></label>
            <textarea id="description_en" name="description_en" rows="5" class="large-text"><?php echo esc_textarea($description_en); ?></textarea>
            <p class="description"><?php _e('Enter the description in English for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="description_id"><?php _e('Description (Indonesian)', 'coffee-shop'); ?></label>
            <textarea id="description_id" name="description_id" rows="5" class="large-text"><?php echo esc_textarea($description_id); ?></textarea>
            <p class="description"><?php _e('Enter the description in Indonesian for this section.', 'coffee-shop'); ?></p>
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Category', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="special_section_category"><?php _e('Section Category', 'coffee-shop'); ?></label>
            <select id="special_section_category" name="special_section_category" class="postform">
                <option value="0"><?php _e('— Select Category —', 'coffee-shop'); ?></option>
                <?php foreach ($categories as $cat) : ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($selected_category, $cat->term_id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('Assign this section to a category.', 'coffee-shop'); ?></p>
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Section Settings', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="special_section_enabled">
                <input type="checkbox" id="special_section_enabled" name="special_section_enabled" value="1" <?php checked($special_section_enabled, 1); ?>>
                <?php _e('Enable Section', 'coffee-shop'); ?>
            </label>
            <p class="description"><?php _e('Toggle to enable or disable this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="special_section_allow_media">
                <input type="checkbox" id="special_section_allow_media" name="special_section_allow_media" value="1" <?php checked($special_section_allow_media, 1); ?>>
                <?php _e('Allow Media Upload', 'coffee-shop'); ?>
            </label>
            <p class="description"><?php _e('Enable to allow uploading an image for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="special_section_image"><?php _e('Section Image', 'coffee-shop'); ?></label>
            <div class="image-upload-wrapper">
                <?php if ($special_section_image) : ?>
                    <img src="<?php echo esc_url($special_section_image); ?>" alt="" class="preview-image" style="max-width: 150px; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="hidden" id="special_section_image" name="special_section_image" value="<?php echo esc_attr($special_section_image); ?>">
                <button type="button" class="button upload-image-button"><?php _e('Upload Image', 'coffee-shop'); ?></button>
                <button type="button" class="button remove-image-button" <?php echo !$special_section_image ? 'style="display:none;"' : ''; ?>><?php _e('Remove Image', 'coffee-shop'); ?></button>
            </div>
            <p class="description"><?php _e('Upload an image to represent this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="special_section_image_2"><?php _e('Section Image 2', 'coffee-shop'); ?></label>
            <div class="image-upload-wrapper">
                <?php if ($special_section_image_2) : ?>
                    <img src="<?php echo esc_url($special_section_image_2); ?>" alt="" class="preview-image" style="max-width: 150px; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="hidden" id="special_section_image_2" name="special_section_image_2" value="<?php echo esc_attr($special_section_image_2); ?>">
                <button type="button" class="button upload-image-button"><?php _e('Upload Image', 'coffee-shop'); ?></button>
                <button type="button" class="button remove-image-button" <?php echo !$special_section_image_2 ? 'style="display:none;"' : ''; ?>><?php _e('Remove Image', 'coffee-shop'); ?></button>
            </div>
            <p class="description"><?php _e('Upload an additional image for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="special_section_image_3"><?php _e('Section Image 3', 'coffee-shop'); ?></label>
            <div class="image-upload-wrapper">
                <?php if ($special_section_image_3) : ?>
                    <img src="<?php echo esc_url($special_section_image_3); ?>" alt="" class="preview-image" style="max-width: 150px; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="hidden" id="special_section_image_3" name="special_section_image_3" value="<?php echo esc_attr($special_section_image_3); ?>">
                <button type="button" class="button upload-image-button"><?php _e('Upload Image', 'coffee-shop'); ?></button>
                <button type="button" class="button remove-image-button" <?php echo !$special_section_image_3 ? 'style="display:none;"' : ''; ?>><?php _e('Remove Image', 'coffee-shop'); ?></button>
            </div>
            <p class="description"><?php _e('Upload an additional image for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="special_section_image_4"><?php _e('Section Image 4', 'coffee-shop'); ?></label>
            <div class="image-upload-wrapper">
                <?php if ($special_section_image_4) : ?>
                    <img src="<?php echo esc_url($special_section_image_4); ?>" alt="" class="preview-image" style="max-width: 150px; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="hidden" id="special_section_image_4" name="special_section_image_4" value="<?php echo esc_attr($special_section_image_4); ?>">
                <button type="button" class="button upload-image-button"><?php _e('Upload Image', 'coffee-shop'); ?></button>
                <button type="button" class="button remove-image-button" <?php echo !$special_section_image_4 ? 'style="display:none;"' : ''; ?>><?php _e('Remove Image', 'coffee-shop'); ?></button>
            </div>
            <p class="description"><?php _e('Upload an additional image for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="slide_title"><?php _e('Slide Title', 'coffee-shop'); ?></label>
            <input type="text" id="slide_title" name="slide_title" value="<?php echo esc_attr($slide_title); ?>" class="regular-text" />
            <p class="description"><?php _e('Enter the slide title for this section.', 'coffee-shop'); ?></p>
        </div>
        <div class="meta-field">
            <label for="color_scheme"><?php _e('Color Scheme', 'coffee-shop'); ?></label>
            <input type="color" id="color_scheme" name="color_scheme" value="<?php echo esc_attr($color_scheme); ?>" class="regular-text" />
            <p class="description"><?php _e('Select the color scheme for this section.', 'coffee-shop'); ?></p>
        </div>
    </div>
</div>