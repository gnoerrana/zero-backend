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

    // Hall of Fame: image OR video media picker
    $('.hof-upload-media-button').on('click', function(e) {
        e.preventDefault();

        var wrapper = $(this).closest('.hof-media-upload-wrapper');

        var custom_uploader = wp.media({
            title: 'Choose Image or Video',
            library: { type: ['image', 'video'] },
            button: { text: 'Use this media' },
            multiple: false,
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            var isVideo = attachment.type === 'video';

            wrapper.find('#hof_media_url').val(attachment.url);
            wrapper.find('#hof_media_id').val(attachment.id);

            // Sync the media type radio to match what was actually picked.
            wrapper.closest('.meta-fields').find('input[name="hof_media_type"][value="' + (isVideo ? 'video' : 'image') + '"]').prop('checked', true);

            var preview = wrapper.find('.hof-media-preview');
            preview.empty();
            if (isVideo) {
                preview.append($('<video>', { src: attachment.url, controls: true, style: 'max-width: 240px; display: block; margin-bottom: 10px;' }));
            } else {
                preview.append($('<img>', { src: attachment.url, style: 'max-width: 240px; display: block; margin-bottom: 10px;' }));
            }

            wrapper.find('.hof-remove-media-button').show();
        }).open();
    });

    $('.hof-remove-media-button').on('click', function(e) {
        e.preventDefault();

        var wrapper = $(this).closest('.hof-media-upload-wrapper');
        wrapper.find('#hof_media_url').val('');
        wrapper.find('#hof_media_id').val('');
        wrapper.find('.hof-media-preview').empty();
        wrapper.find('.hof-remove-media-button').hide();
    });
});