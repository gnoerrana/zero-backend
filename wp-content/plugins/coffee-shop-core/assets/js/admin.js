jQuery(document).ready(function($) {
    // Handle image upload for special section
    $('.upload-image-button').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var custom_uploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            
            // Update the hidden input and preview
            button.closest('.image-upload-wrapper').find('#special_section_image').val(attachment.url);
            button.closest('.image-upload-wrapper').find('.preview-image').attr('src', attachment.url).show();
            button.closest('.image-upload-wrapper').find('.remove-image-button').show();
        }).open();
    });
    
    // Handle remove image button
    $('.remove-image-button').on('click', function(e) {
        e.preventDefault();
        
        var wrapper = $(this).closest('.image-upload-wrapper');
        wrapper.find('#special_section_image').val('');
        wrapper.find('.preview-image').attr('src', '').hide();
        wrapper.find('.remove-image-button').hide();
    });
});