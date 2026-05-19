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
</div>