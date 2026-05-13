/**
 * Frontend JavaScript for Customizations
 */

jQuery(document).ready(function($) {
    'use strict';

    if (!$('#csc-frontend-dashboard').length) {
        return;
    }

    var CSC_Frontend = {
        init: function() {
            this.loadCustomizations();
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Add new item
            $(document).on('click', '#csc-frontend-add-item', function() {
                self.addNewItem();
            });

            // Remove item
            $(document).on('click', '.csc-remove-item', function() {
                $(this).closest('.csc-customization-item').remove();
            });

            // Save changes
            $(document).on('click', '#csc-frontend-save-changes', function() {
                self.saveChanges();
            });
        },

        loadCustomizations: function() {
            var self = this;

            $.ajax({
                url: csc_frontend.rest_url + 'customizations',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    self.renderCustomizations(response);
                },
                error: function() {
                    $('#csc-frontend-customizations-container').html('<p>Error loading customizations.</p>');
                }
            });
        },

        renderCustomizations: function(customizations) {
            var self = this;
            var $container = $('#csc-frontend-customizations-container');
            var html = '';

            customizations.forEach(function(customization) {
                html += self.getItemTemplate(customization);
            });

            $container.html(html);
            this.initSelect2();
        },

        addNewItem: function() {
            var template = this.getItemTemplate();
            $('#csc-frontend-customizations-container').append(template);
            this.initSelect2();
        },

        getItemTemplate: function(customization) {
            customization = customization || {};
            var categories = csc_frontend.categories;
            var options = '';
            var selectedCategories = customization.categories || [];

            for (var catId in categories) {
                if (categories.hasOwnProperty(catId)) {
                    var selected = selectedCategories.indexOf(parseInt(catId)) !== -1 ? ' selected' : '';
                    options += '<option value="' + catId + '"' + selected + '>' + categories[catId] + '</option>';
                }
            }

            return '<div class="csc-customization-item" data-id="' + (customization.id || '') + '">' +
                '<div class="csc-field-group">' +
                    '<label>Custom Name</label>' +
                    '<input type="text" class="csc-custom-name" value="' + (customization.custom_name || '') + '" />' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>Price</label>' +
                    '<input type="number" step="0.01" class="csc-price" value="' + (customization.price || 0) + '" />' +
                '</div>' +
                '<div class="csc-field-group">' +
                    '<label>Apply to Categories</label>' +
                    '<select multiple class="csc-categories" data-placeholder="Select categories">' +
                        options +
                    '</select>' +
                '</div>' +
                '<button type="button" class="button csc-remove-item">Remove</button>' +
            '</div>';
        },

        initSelect2: function() {
            if ($.fn.select2) {
                $('.csc-categories').select2({
                    placeholder: 'Select categories',
                    allowClear: true
                });
            }
        },

        saveChanges: function() {
            var self = this;
            var customizations = [];
            var $status = $('#csc-frontend-save-status');

            $status.text('Saving...');

            $('.csc-customization-item').each(function() {
                var $item = $(this);
                var id = $item.data('id') || '';
                var customName = $item.find('.csc-custom-name').val().trim();
                var price = parseFloat($item.find('.csc-price').val()) || 0;
                var categories = $item.find('.csc-categories').val() || [];

                if (customName) {
                    customizations.push({
                        id: id,
                        custom_name: customName,
                        price: price,
                        categories: categories.map(function(cat) { return parseInt(cat); })
                    });
                }
            });

            // For simplicity, we'll delete all and recreate
            // In a real implementation, you'd track changes
            self.saveBatch(customizations);
        },

        saveBatch: function(customizations) {
            var self = this;
            var $status = $('#csc-frontend-save-status');

            // First, get existing customizations
            $.ajax({
                url: csc_frontend.rest_url + 'customizations',
                method: 'GET',
                success: function(existing) {
                    var toDelete = existing.filter(function(ex) {
                        return !customizations.some(function(newItem) {
                            return newItem.id == ex.id;
                        });
                    });

                    var toUpdate = customizations.filter(function(item) {
                        return item.id;
                    });

                    var toCreate = customizations.filter(function(item) {
                        return !item.id;
                    });

                    self.processDeletions(toDelete, function() {
                        self.processUpdates(toUpdate, function() {
                            self.processCreations(toCreate, function() {
                                $status.text('Changes saved successfully!');
                                setTimeout(function() {
                                    $status.text('');
                                    location.reload();
                                }, 2000);
                            });
                        });
                    });
                },
                error: function() {
                    $status.text('Error saving changes.');
                }
            });
        },

        processDeletions: function(toDelete, callback) {
            if (toDelete.length === 0) {
                callback();
                return;
            }

            var self = this;
            var completed = 0;

            toDelete.forEach(function(item) {
                $.ajax({
                    url: csc_frontend.rest_url + 'customizations/' + item.id,
                    method: 'DELETE',
                    success: function() {
                        completed++;
                        if (completed === toDelete.length) {
                            callback();
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === toDelete.length) {
                            callback();
                        }
                    }
                });
            });
        },

        processUpdates: function(toUpdate, callback) {
            if (toUpdate.length === 0) {
                callback();
                return;
            }

            var self = this;
            var completed = 0;

            toUpdate.forEach(function(item) {
                $.ajax({
                    url: csc_frontend.rest_url + 'customizations/' + item.id,
                    method: 'POST',
                    data: item,
                    success: function() {
                        completed++;
                        if (completed === toUpdate.length) {
                            callback();
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === toUpdate.length) {
                            callback();
                        }
                    }
                });
            });
        },

        processCreations: function(toCreate, callback) {
            if (toCreate.length === 0) {
                callback();
                return;
            }

            var self = this;
            var completed = 0;

            toCreate.forEach(function(item) {
                $.ajax({
                    url: csc_frontend.rest_url + 'customizations',
                    method: 'POST',
                    data: item,
                    success: function() {
                        completed++;
                        if (completed === toCreate.length) {
                            callback();
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === toCreate.length) {
                            callback();
                        }
                    }
                });
            });
        }
    };

    CSC_Frontend.init();
});