/**
 * Admin JavaScript for Customizations
 */

jQuery(document).ready(function($) {
    'use strict';

    var CSC_Admin = {
        init: function() {
            this.bindEvents();
            this.initSelect2();
        },

    bindEvents: function() {
            var self = this;

            // Add new item
            $('#csc-add-item').on('click', function() {
                self.addNewItem();
            });

            // Remove item
            $(document).on('click', '.csc-remove-item', function() {
                $(this).closest('.csc-customization-item').remove();
            });

            // Add option
            $(document).on('click', '.csc-add-option', function() {
                self.addOption($(this).siblings('.csc-options-list'));
            });

            // Remove option
            $(document).on('click', '.csc-remove-option', function() {
                $(this).closest('.csc-option-item').remove();
            });

            // Save changes
            $('#csc-save-changes').on('click', function() {
                self.saveChanges();
            });
        },

        initSelect2: function() {
            if ($.fn.select2) {
                $('.csc-categories').select2({
                    placeholder: csc_ajax.categories.placeholder || 'Select categories',
                    allowClear: true
                });
            }
        },

        addNewItem: function() {
            var template = this.getItemTemplate();
            $('#csc-customizations-container').append(template);
            this.initSelect2();
        },

        getItemTemplate: function(customization) {
            customization = customization || {};
            var categories = csc_ajax.categories;
            var optionsHtml = '';
            var selectedCategories = customization.categories || [];

            for (var catId in categories) {
                if (categories.hasOwnProperty(catId)) {
                    var selected = selectedCategories.indexOf(parseInt(catId)) !== -1 ? ' selected' : '';
                    optionsHtml += '<option value="' + catId + '"' + selected + '>' + categories[catId] + '</option>';
                }
            }

            var optionItems = '';
            var options = customization.options || [];
            for (var i = 0; i < options.length; i++) {
                optionItems += '<div class="csc-option-item">' +
                    '<input type="text" class="csc-option-label" value="' + (options[i].label || '') + '" placeholder="Label" />' +
                    '<input type="number" step="0.01" class="csc-option-price" value="' + (options[i].price || 0) + '" placeholder="Price" />' +
                    '<button type="button" class="button csc-remove-option">Remove</button>' +
                '</div>';
            }

            return '<div class="csc-customization-item" data-id="' + (customization.id || '') + '">' +
                '<div class="csc-field-group">' +
                    '<label>Option Name</label>' +
                    '<input type="text" class="csc-option-name" value="' + (customization.option_name || '') + '" />' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>Options</label>' +
                    '<div class="csc-options-list">' + optionItems + '</div>' +
                    '<button type="button" class="button csc-add-option">Add Option</button>' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>Apply to Categories</label>' +
                    '<select multiple class="csc-categories" data-placeholder="Select categories">' +
                        optionsHtml +
                    '</select>' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>' +
                        '<input type="checkbox" class="csc-required"' + (customization.required ? ' checked' : '') + ' />' +
                        ' Required' +
                    '</label>' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>Selection Type</label>' +
                    '<select class="csc-selection-type">' +
                        '<option value="single"' + (customization.selection_type === 'single' ? ' selected' : '') + '>Single Select</option>' +
                        '<option value="multi"' + (customization.selection_type !== 'single' ? ' selected' : '') + '>Multi Select</option>' +
                    '</select>' +
                '</div>' +
                '<button type="button" class="button csc-remove-item">Remove</button>' +
            '</div>';
        },

        addOption: function($optionsList) {
            var optionTemplate = '<div class="csc-option-item">' +
                '<input type="text" class="csc-option-label" placeholder="Label" />' +
                '<input type="number" step="0.01" class="csc-option-price" placeholder="Price" />' +
                '<button type="button" class="button csc-remove-option">Remove</button>' +
            '</div>';
            $optionsList.append(optionTemplate);
        },

        saveChanges: function() {
            var self = this;
            var customizations = [];
            var $status = $('#csc-save-status');

            $status.text('Saving...');

            $('.csc-customization-item').each(function() {
                var $item = $(this);
                var id = $item.data('id') || '';
                var optionName = $item.find('.csc-option-name').val().trim();
                var options = [];
                $item.find('.csc-option-item').each(function() {
                    var label = $(this).find('.csc-option-label').val().trim();
                    var price = parseFloat($(this).find('.csc-option-price').val()) || 0;
                    if (label) options.push({label: label, price: price});
                });
                var categories = $item.find('.csc-categories').val() || [];
                var required = $item.find('.csc-required').is(':checked');
                var selectionType = $item.find('.csc-selection-type').val() || 'multi';

                if (optionName && options.length > 0) {
                    customizations.push({
                        id: id,
                        option_name: optionName,
                        options: options,
                        categories: categories.map(function(cat) { return parseInt(cat); }),
                        required: required,
                        selection_type: selectionType
                    });
                }
            });

            $.ajax({
                url: csc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'csc_save_customizations',
                    nonce: csc_ajax.nonce,
                    customizations: customizations
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('Changes saved successfully!');
                        setTimeout(function() {
                            $status.text('');
                        }, 3000);
                        // Reload page to get new IDs
                        location.reload();
                    } else {
                        $status.text('Error: ' + response.data);
                    }
                },
                error: function() {
                    $status.text('Error saving changes.');
                }
            });
        }
    };

    CSC_Admin.init();
});