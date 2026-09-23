<?php
/**
 * Hall of Fame Meta Box
 */

defined('ABSPATH') || exit;

$media_type     = isset($media_type) ? $media_type : 'image';
$media_id       = isset($media_id) ? $media_id : 0;
$media_url      = isset($media_url) ? $media_url : '';
$text_position  = isset($text_position) ? $text_position : 'bottom';
$title_en       = isset($title_en) ? $title_en : '';
$title_id       = isset($title_id) ? $title_id : '';
$description_en = isset($description_en) ? $description_en : '';
$description_id = isset($description_id) ? $description_id : '';
$is_active      = isset($is_active) ? $is_active : 1;
?>
<div class="meta-fields">
    <p class="description"><?php _e('Configure the media, text placement, and bilingual copy for this Hall of Fame item.', 'coffee-shop'); ?></p>

    <h4 class="meta-section-title"><?php _e('Status', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="hof_is_active">
                <input type="checkbox" id="hof_is_active" name="hof_is_active" value="1" <?php checked($is_active, 1); ?>>
                <?php _e('Show on the public Hall of Fame page', 'coffee-shop'); ?>
            </label>
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Media', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label><?php _e('Media Type', 'coffee-shop'); ?></label>
            <label class="hof-radio-inline">
                <input type="radio" name="hof_media_type" value="image" <?php checked($media_type, 'image'); ?>>
                <?php _e('Image', 'coffee-shop'); ?>
            </label>
            <label class="hof-radio-inline">
                <input type="radio" name="hof_media_type" value="video" <?php checked($media_type, 'video'); ?>>
                <?php _e('Video', 'coffee-shop'); ?>
            </label>
        </div>
        <div class="meta-field">
            <label for="hof_media_url"><?php _e('Upload', 'coffee-shop'); ?></label>
            <div class="hof-media-upload-wrapper">
                <div class="hof-media-preview">
                    <?php if ($media_url && $media_type === 'video') : ?>
                        <video src="<?php echo esc_url($media_url); ?>" controls style="max-width: 240px; display: block; margin-bottom: 10px;"></video>
                    <?php elseif ($media_url) : ?>
                        <img src="<?php echo esc_url($media_url); ?>" alt="" style="max-width: 240px; display: block; margin-bottom: 10px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" id="hof_media_url" name="hof_media_url" value="<?php echo esc_attr($media_url); ?>">
                <input type="hidden" id="hof_media_id" name="hof_media_id" value="<?php echo esc_attr($media_id); ?>">
                <button type="button" class="button button-primary button-hero hof-upload-media-button">
                    <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 6px;"></span>
                    <?php _e('Choose Image or Video', 'coffee-shop'); ?>
                </button>
                <button type="button" class="button hof-remove-media-button" <?php echo !$media_url ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'coffee-shop'); ?></button>
            </div>
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Text Placement', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="hof_text_position"><?php _e('Title/Description Position', 'coffee-shop'); ?></label>
            <select id="hof_text_position" name="hof_text_position">
                <option value="top" <?php selected($text_position, 'top'); ?>><?php _e('Top (above media)', 'coffee-shop'); ?></option>
                <option value="bottom" <?php selected($text_position, 'bottom'); ?>><?php _e('Bottom (below media)', 'coffee-shop'); ?></option>
            </select>
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Title', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="hof_title_en"><?php _e('Title (English)', 'coffee-shop'); ?></label>
            <input type="text" id="hof_title_en" name="hof_title_en" value="<?php echo esc_attr($title_en); ?>" class="large-text">
        </div>
        <div class="meta-field">
            <label for="hof_title_id"><?php _e('Title (Indonesian)', 'coffee-shop'); ?></label>
            <input type="text" id="hof_title_id" name="hof_title_id" value="<?php echo esc_attr($title_id); ?>" class="large-text">
        </div>
    </div>

    <h4 class="meta-section-title"><?php _e('Description', 'coffee-shop'); ?></h4>
    <div class="meta-fields-group">
        <div class="meta-field">
            <label for="hof_description_en"><?php _e('Description (English)', 'coffee-shop'); ?></label>
            <textarea id="hof_description_en" name="hof_description_en" rows="4" class="large-text"><?php echo esc_textarea($description_en); ?></textarea>
        </div>
        <div class="meta-field">
            <label for="hof_description_id"><?php _e('Description (Indonesian)', 'coffee-shop'); ?></label>
            <textarea id="hof_description_id" name="hof_description_id" rows="4" class="large-text"><?php echo esc_textarea($description_id); ?></textarea>
        </div>
    </div>

    <p class="description">
        <?php _e('Display order is controlled by the "Order" field in the Page Attributes box (lower numbers show first).', 'coffee-shop'); ?>
    </p>
</div>
