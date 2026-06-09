/**
 * Confirm & Alert Utility
 * Reusable utility functions for jquery-confirm dialogs
 * Follows project design standards
 */

(function($) {
    'use strict';

    /**
     * Show a simple confirmation dialog
     * 
     * @param {Object} options Configuration object
     * @param {string} options.title Dialog title
     * @param {string} options.content Dialog content/message
     * @param {string} options.type Alert type: 'blue', 'green', 'red', 'orange' (default: 'blue')
     * @param {string} options.confirmText Confirm button text (default: 'Confirm')
     * @param {string} options.cancelText Cancel button text (default: 'Cancel')
     * @param {string} options.confirmBtnClass Button class for confirm (default: 'btn-primary')
     * @param {Function} options.onConfirm Callback function when confirmed
     * @param {Function} options.onCancel Callback function when cancelled
     * @returns {Object} jquery-confirm instance
     */
    function showConfirm(options) {
        var defaults = {
            title: 'Confirm Action',
            content: 'Are you sure you want to proceed?',
            type: 'blue',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            confirmBtnClass: 'btn-primary',
            onConfirm: function() {},
            onCancel: function() {}
        };

        var config = $.extend({}, defaults, options);

        return $.confirm({
            title: config.title,
            content: config.content,
            type: config.type,
            typeAnimated: true,
            buttons: {
                confirm: {
                    text: config.confirmText,
                    btnClass: config.confirmBtnClass,
                    action: function() {
                        if (typeof config.onConfirm === 'function') {
                            return config.onConfirm.call(this);
                        }
                    }
                },
                cancel: {
                    text: config.cancelText,
                    btnClass: 'btn-secondary',
                    action: function() {
                        if (typeof config.onCancel === 'function') {
                            config.onCancel.call(this);
                        }
                    }
                }
            }
        });
    }

    /**
     * Show confirmation dialog for approve action
     * 
     * @param {Object} options Configuration object
     * @param {string} options.title Dialog title (default: 'Approve')
     * @param {string} options.content Dialog content/message
     * @param {Function} options.onConfirm Callback when approved
     * @param {Function} options.onCancel Callback when cancelled
     * @returns {Object} jquery-confirm instance
     */
    function showApproveConfirm(options) {
        var defaults = {
            title: 'Approve',
            content: 'Are you sure you want to approve this?',
            onConfirm: function() {},
            onCancel: function() {}
        };

        var config = $.extend({}, defaults, options);

        return showConfirm({
            title: config.title,
            content: config.content,
            type: 'green',
            confirmText: 'Yes, Approve',
            confirmBtnClass: 'btn-success',
            onConfirm: config.onConfirm,
            onCancel: config.onCancel
        });
    }

    /**
     * Show confirmation dialog for reject/delete action
     * 
     * @param {Object} options Configuration object
     * @param {string} options.title Dialog title (default: 'Reject')
     * @param {string} options.content Dialog content/message
     * @param {Function} options.onConfirm Callback when rejected
     * @param {Function} options.onCancel Callback when cancelled
     * @returns {Object} jquery-confirm instance
     */
    function showRejectConfirm(options) {
        var defaults = {
            title: 'Reject',
            content: 'Are you sure you want to reject this?',
            onConfirm: function() {},
            onCancel: function() {}
        };

        var config = $.extend({}, defaults, options);

        return showConfirm({
            title: config.title,
            content: config.content,
            type: 'red',
            confirmText: 'Reject',
            confirmBtnClass: 'btn-danger',
            onConfirm: config.onConfirm,
            onCancel: config.onCancel
        });
    }

    /**
     * Show confirmation dialog with textarea input (e.g., for rejection reason)
     * 
     * @param {Object} options Configuration object
     * @param {string} options.title Dialog title
     * @param {string} options.label Label for textarea (default: 'Please enter the reason:')
     * @param {string} options.placeholder Placeholder text (default: 'Enter reason...')
     * @param {string} options.defaultValue Default value for textarea
     * @param {string} options.type Alert type (default: 'red')
     * @param {string} options.confirmText Confirm button text (default: 'Confirm')
     * @param {string} options.confirmBtnClass Button class (default: 'btn-danger')
     * @param {boolean} options.useTinyMCE Use TinyMCE editor instead of plain textarea (default: false)
     * @param {Function} options.onConfirm Callback with textarea value: function(value) {}
     * @param {Function} options.onCancel Callback when cancelled
     * @param {Function} options.validate Optional validation function: function(value) { return true/false or error message }
     * @returns {Object} jquery-confirm instance
     */
    function showConfirmWithTextarea(options) {
        var defaults = {
            title: 'Confirm Action',
            label: 'Please enter the reason:',
            placeholder: 'Enter reason...',
            defaultValue: '',
            type: 'red',
            confirmText: 'Confirm',
            confirmBtnClass: 'btn-danger',
            useTinyMCE: false,
            onConfirm: function(value) {},
            onCancel: function() {},
            validate: null
        };

        var config = $.extend({}, defaults, options);

        // Generate unique ID for textarea
        var textareaId = 'confirm-textarea-' + Date.now();
        var textareaClass = config.useTinyMCE ? 'form-control confirm-textarea tinymceEditor' : 'form-control confirm-textarea';
        
        var content = '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<label>' + config.label + '</label>' +
            '<textarea id="' + textareaId + '" class="' + textareaClass + '" rows="' + (config.useTinyMCE ? '6' : '4') + '" placeholder="' + config.placeholder + '" required>' +
            (config.defaultValue || '') +
            '</textarea>' +
            '</div>' +
            '</form>';

        var confirmInstance = $.confirm({
            title: config.title,
            content: content,
            type: config.type,
            typeAnimated: true,
            buttons: {
                confirm: {
                    text: config.confirmText,
                    btnClass: config.confirmBtnClass,
                    action: function() {
                        var value = '';
                        
                        // Get value from TinyMCE if enabled, otherwise from textarea
                        if (config.useTinyMCE && typeof tinyMCE !== 'undefined') {
                            var editor = tinyMCE.get(textareaId);
                            if (editor) {
                                // Get HTML content from TinyMCE
                                value = editor.getContent({format: 'raw'}).trim();
                                // Also save to textarea
                                editor.save();
                            } else {
                                // Fallback to textarea value
                                value = this.$content.find('#' + textareaId).val().trim();
                            }
                        } else {
                            value = this.$content.find('#' + textareaId).val().trim();
                        }
                        
                        // Validation
                        if (!value) {
                            showErrorAlert('This field cannot be empty');
                            return false; // Prevent closing
                        }

                        // Custom validation (strip HTML tags for validation if TinyMCE is used)
                        if (typeof config.validate === 'function') {
                            var validationValue = config.useTinyMCE ? 
                                $('<div>').html(value).text().trim() : value;
                            var validationResult = config.validate(validationValue);
                            if (validationResult !== true) {
                                var errorMsg = typeof validationResult === 'string' ? validationResult : 'Validation failed';
                                showErrorAlert(errorMsg);
                                return false; // Prevent closing
                            }
                        }

                        // Call onConfirm with the value (HTML if TinyMCE, plain text otherwise)
                        if (typeof config.onConfirm === 'function') {
                            return config.onConfirm.call(this, value);
                        }
                    }
                },
                cancel: {
                    text: 'Cancel',
                    btnClass: 'btn-secondary',
                    action: function() {
                        // Remove TinyMCE editor if it was initialized
                        if (config.useTinyMCE && typeof tinyMCE !== 'undefined') {
                            var editor = tinyMCE.get(textareaId);
                            if (editor) {
                                tinyMCE.remove(textareaId);
                            }
                        }
                        if (typeof config.onCancel === 'function') {
                            config.onCancel.call(this);
                        }
                    }
                }
            },
            onContentReady: function() {
                var self = this;
                
                // Initialize TinyMCE if enabled
                if (config.useTinyMCE) {
                    console.log('TinyMCE requested for textarea:', textareaId);
                    console.log('TinyMCE available:', typeof tinyMCE !== 'undefined');
                    console.log('TinyMCEUtils available:', typeof TinyMCEUtils !== 'undefined');
                    
                    // Function to initialize TinyMCE
                    var initTinyMCE = function() {
                        var $textarea = self.$content.find('#' + textareaId);
                        console.log('Looking for textarea:', textareaId, 'Found:', $textarea.length);
                        
                        if ($textarea.length === 0) {
                            console.error('Textarea not found for TinyMCE initialization:', textareaId);
                            return false;
                        }
                        
                        // Check if TinyMCE is available
                        if (typeof tinyMCE === 'undefined') {
                            console.error('TinyMCE is not loaded. Please ensure tinymce.min.js is included.');
                            // Fallback: show warning and use plain textarea
                            if (typeof showWarningAlert === 'function') {
                                showWarningAlert('Rich text editor is not available. Using plain text editor.');
                            }
                            $textarea.focus();
                            return false;
                        }
                        
                        // Remove any existing TinyMCE instance for this ID
                        var existingEditor = tinyMCE.get(textareaId);
                        if (existingEditor) {
                            try {
                                tinyMCE.remove(textareaId);
                            } catch (e) {
                                console.warn('Error removing existing TinyMCE instance:', e);
                            }
                        }
                        
                        // Ensure textarea is visible (TinyMCE needs visible element)
                        if (!$textarea.is(':visible')) {
                            console.warn('Textarea is not visible, waiting...');
                            return false;
                        }
                        
                        // Try using TinyMCEUtils first
                        if (typeof TinyMCEUtils !== 'undefined' && typeof TinyMCEUtils.initDynamic === 'function') {
                            console.log('Initializing TinyMCE using TinyMCEUtils for:', textareaId);
                            // Use TinyMCE utility
                            TinyMCEUtils.initDynamic('#' + textareaId, {
                                height: 300,
                                menubar: false,
                                plugins: [
                                    'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                    'insertdatetime', 'table', 'help', 'wordcount'
                                ],
                                toolbar: "undo redo | formatselect | " +
                                         "bold italic | alignleft aligncenter " +
                                         "alignright alignjustify | bullist numlist outdent indent | code",
                                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                            }, function(editor) {
                                console.log('TinyMCE editor initialized successfully:', textareaId);
                                // Focus editor after initialization
                                setTimeout(function() {
                                    if (editor) {
                                        editor.focus();
                                    }
                                }, 100);
                            }, 100);
                            return true;
                        } else {
                            // Fallback to direct TinyMCE initialization
                            try {
                                console.log('Initializing TinyMCE directly for:', textareaId);
                                // Get base URL for TinyMCE
                                var baseUrl = typeof baseURL !== 'undefined' ? baseURL + 'assets/libs/tinymce' : '/assets/libs/tinymce';
                                
                                tinyMCE.init({
                                    selector: '#' + textareaId,
                                    base_url: baseUrl,
                                    suffix: '.min',
                                    height: 300,
                                    menubar: false,
                                    plugins: [
                                        'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                        'insertdatetime', 'table', 'help', 'wordcount'
                                    ],
                                    toolbar: "undo redo | formatselect | " +
                                             "bold italic | alignleft aligncenter " +
                                             "alignright alignjustify | bullist numlist outdent indent | code",
                                    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                                    setup: function(editor) {
                                        editor.on('init', function() {
                                            console.log('TinyMCE editor initialized successfully:', textareaId);
                                            setTimeout(function() {
                                                editor.focus();
                                            }, 100);
                                        });
                                    }
                                });
                                return true;
                            } catch (e) {
                                console.error('TinyMCE initialization error:', e);
                                $textarea.focus();
                                return false;
                            }
                        }
                    };
                    
                    // Wait for modal to be fully rendered before initializing
                    // Use multiple attempts to ensure initialization
                    var attempts = 0;
                    var maxAttempts = 5;
                    
                    var tryInit = function() {
                        attempts++;
                        var initialized = initTinyMCE();
                        
                        if (!initialized && attempts < maxAttempts) {
                            setTimeout(tryInit, 200);
                        } else if (!initialized) {
                            console.error('Failed to initialize TinyMCE after', maxAttempts, 'attempts');
                        }
                    };
                    
                    // Start initialization after a short delay
                    setTimeout(tryInit, 100);
                } else {
                    // Focus on textarea
                    setTimeout(function() {
                        self.$content.find('#' + textareaId).focus();
                    }, 100);
                }
            },
            onOpen: function() {
                // Additional initialization after modal is fully open
                if (config.useTinyMCE) {
                    setTimeout(function() {
                        var $textarea = this.$content.find('#' + textareaId);
                        if ($textarea.length > 0 && typeof tinyMCE !== 'undefined') {
                            var editor = tinyMCE.get(textareaId);
                            if (!editor) {
                                console.log('TinyMCE not initialized yet, retrying in onOpen callback');
                                // Try to initialize again
                                if (typeof TinyMCEUtils !== 'undefined' && typeof TinyMCEUtils.initDynamic === 'function') {
                                    TinyMCEUtils.initDynamic('#' + textareaId, {
                                        height: 300,
                                        menubar: false,
                                        plugins: [
                                            'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                                            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                            'insertdatetime', 'table', 'help', 'wordcount'
                                        ],
                                        toolbar: "undo redo | formatselect | " +
                                                 "bold italic | alignleft aligncenter " +
                                                 "alignright alignjustify | bullist numlist outdent indent | code",
                                        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                                    }, null, 100);
                                }
                            }
                        }
                    }.bind(this), 300);
                }
            },
            onDestroy: function() {
                // Clean up TinyMCE editor when dialog is destroyed
                if (config.useTinyMCE && typeof tinyMCE !== 'undefined') {
                    var editor = tinyMCE.get(textareaId);
                    if (editor) {
                        tinyMCE.remove(textareaId);
                    }
                }
            }
        });
        
        return confirmInstance;
    }

    /**
     * Show success alert
     * 
     * @param {string} message Alert message
     * @param {string} title Alert title (default: 'Success!')
     * @returns {Object} jquery-confirm instance
     */
    function showSuccessAlert(message, title) {
        title = title || 'Success!';
        return $.alert({
            title: title,
            content: message,
            type: 'green',
            typeAnimated: true,
            buttons: {
                ok: {
                    text: 'OK',
                    btnClass: 'btn-success'
                }
            }
        });
    }

    /**
     * Show error alert
     * 
     * @param {string} message Alert message
     * @param {string} title Alert title (default: 'Error!')
     * @returns {Object} jquery-confirm instance
     */
    function showErrorAlert(message, title) {
        title = title || 'Error!';
        return $.alert({
            title: title,
            content: message,
            type: 'red',
            typeAnimated: true,
            buttons: {
                ok: {
                    text: 'OK',
                    btnClass: 'btn-danger'
                }
            }
        });
    }

    /**
     * Show warning alert
     * 
     * @param {string} message Alert message
     * @param {string} title Alert title (default: 'Warning!')
     * @returns {Object} jquery-confirm instance
     */
    function showWarningAlert(message, title) {
        title = title || 'Warning!';
        return $.alert({
            title: title,
            content: message,
            type: 'orange',
            typeAnimated: true,
            buttons: {
                ok: {
                    text: 'OK',
                    btnClass: 'btn-warning'
                }
            }
        });
    }

    /**
     * Show info alert
     * 
     * @param {string} message Alert message
     * @param {string} title Alert title (default: 'Information')
     * @returns {Object} jquery-confirm instance
     */
    function showInfoAlert(message, title) {
        title = title || 'Information';
        return $.alert({
            title: title,
            content: message,
            type: 'blue',
            typeAnimated: true,
            buttons: {
                ok: {
                    text: 'OK',
                    btnClass: 'btn-info'
                }
            }
        });
    }

    /**
     * Show delete confirmation dialog
     * 
     * @param {Object} options Configuration object
     * @param {string} options.title Dialog title (default: 'Delete Confirmation')
     * @param {string} options.content Dialog content/message
     * @param {Function} options.onConfirm Callback when confirmed
     * @param {Function} options.onCancel Callback when cancelled
     * @returns {Object} jquery-confirm instance
     */
    function showDeleteConfirm(options) {
        var defaults = {
            title: 'Delete Confirmation',
            content: 'Are you sure you want to delete this? This action cannot be undone.',
            onConfirm: function() {},
            onCancel: function() {}
        };

        var config = $.extend({}, defaults, options);

        return showConfirm({
            title: config.title,
            content: config.content,
            type: 'red',
            confirmText: 'Delete',
            confirmBtnClass: 'btn-danger',
            onConfirm: config.onConfirm,
            onCancel: config.onCancel
        });
    }

    // Expose functions globally
    window.ConfirmUtility = {
        showConfirm: showConfirm,
        showApproveConfirm: showApproveConfirm,
        showRejectConfirm: showRejectConfirm,
        showConfirmWithTextarea: showConfirmWithTextarea,
        showSuccessAlert: showSuccessAlert,
        showErrorAlert: showErrorAlert,
        showWarningAlert: showWarningAlert,
        showInfoAlert: showInfoAlert,
        showDeleteConfirm: showDeleteConfirm
    };

    // Also expose as shorter aliases for convenience
    window.showConfirm = showConfirm;
    window.showApproveConfirm = showApproveConfirm;
    window.showRejectConfirm = showRejectConfirm;
    window.showConfirmWithTextarea = showConfirmWithTextarea;
    window.showSuccessAlert = showSuccessAlert;
    window.showErrorAlert = showErrorAlert;
    window.showWarningAlert = showWarningAlert;
    window.showInfoAlert = showInfoAlert;
    window.showDeleteConfirm = showDeleteConfirm;

})(jQuery);

