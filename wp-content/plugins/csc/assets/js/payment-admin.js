/**
 * Admin JavaScript for Payment Methods
 */

jQuery(document).ready(function($) {
    'use strict';

    var frame;

    // Show/hide Midtrans fields based on checkbox
    $('#enable_midtrans').on('change', function() {
        if ($(this).is(':checked')) {
            $('#merchant_id, #client_key, #server_key, #environment, .payment-methods-group, #redirect_url_sandbox, #redirect_url_production, #midtrans_url_sandbox, #midtrans_url_production').closest('tr').show();
        } else {
            $('#merchant_id, #client_key, #server_key, #environment, .payment-methods-group, #redirect_url_sandbox, #redirect_url_production, #midtrans_url_sandbox, #midtrans_url_production').closest('tr').hide();
        }
    }).trigger('change');

    // Media uploader for icon selection
    $(document).on('click', '.csc-select-icon', function(e) {
        e.preventDefault();
        
        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Payment Icon',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var field = $('.csc-select-icon:focus').data('field');
            if (field) {
                $('[name="' + field + '"]').val(attachment.url).siblings('img').remove();
                $('[name="' + field + '"]').after('<img src="' + attachment.url + '" style="width: 32px; height: 32px; display: block; margin: 5px 0;" />');
            }
        });

        frame.open();
    });
});

    $(document).on('click', '.csc-select-icon', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        
        frame.open();
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('input[name="' + target + '"]').val(attachment.url);
        });
    });
});