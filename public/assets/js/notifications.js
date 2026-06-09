/**
 * Notification Management JavaScript
 * Handles sending notifications via Firebase
 */

$(document).ready(function() {
    // Initialize Select2 for all dropdowns with search functionality
    function initializeSelect2(selector, placeholder, allowMultiple = false) {
        if ($(selector).length) {
            $(selector).select2({
                placeholder: placeholder || "Select...",
                allowClear: true,
                width: '100%',
                dropdownParent: $(selector).closest('.formCard, .card, body'),
                language: {
                    noResults: function() {
                        return "No results found";
                    },
                    searching: function() {
                        return "Searching...";
                    }
                }
            });
        }
    }

    // Initialize Select2 for all dropdowns (works even if hidden)
    initializeSelect2('#notification_type', "Select Notification Type");
    initializeSelect2('#user_ids', "Select users", true);
    initializeSelect2('#role_id', "Select role");
    initializeSelect2('#workshop_id', "Select workshop");

    // Show/hide notification options based on selected type
    $('#notification_type').on('change', function() {
        const selectedType = $(this).val();
        
        // Hide all options
        $('.notification-option').hide();
        $('.notification-option select').removeClass('required');
        $('.notification-option input').removeClass('required');
        
        // Show relevant option
        if (selectedType) {
            const optionId = selectedType + '_option';
            $('#' + optionId).show();
            
            // Make required fields mandatory
            const requiredFields = $('#' + optionId).find('select, input');
            if (requiredFields.length) {
                requiredFields.addClass('required');
            }
            
            // Select2 is already initialized for all dropdowns, no need to reinitialize
        }
    });

    // Send notification
    $('#sendNotificationBtn').on('click', function(e) {
        e.preventDefault();
        
        const form = $('#sendNotificationForm');
        const formId = form.attr('id');
        
        // Validate form
        if (!validateFormdata({
            formId: formId,
            url: '',
            postKey: 'send'
        })) {
            return false;
        }

        // Show confirmation
        if (typeof showConfirm !== 'undefined') {
            showConfirm({
                title: 'Send Notification?',
                content: 'Are you sure you want to send this notification?',
                type: 'blue',
                confirmText: 'Yes, Send',
                cancelText: 'Cancel',
                confirmBtnClass: 'btn-primary',
                onConfirm: function() {
                    sendNotification();
                }
            });
        } else {
            if (confirm('Are you sure you want to send this notification?')) {
                sendNotification();
            }
        }
    });

    function sendNotification() {
        const form = $('#sendNotificationForm');
        
        // Build data object from form
        const formData = {};
        
        // Get all form fields with safety checks
        formData.notification_type = $('#notification_type').val() || '';
        formData.title = ($('#title').val() || '').trim();
        formData.body = ($('#body').val() || '').trim();
        
        // Get notification type specific fields
        const notificationType = formData.notification_type;
        
        if (notificationType === 'selected_users') {
            formData.user_ids = $('#user_ids').val() || [];
            // Map to multiple_users for backend compatibility
            formData.notification_type = 'multiple_users';
        } else if (notificationType === 'by_role') {
            formData.role_id = $('#role_id').val() || '';
        } else if (notificationType === 'conference_users') {
            // No additional fields needed - uses active event automatically
        } else if (notificationType === 'workshop_users') {
            formData.workshop_id = $('#workshop_id').val() || '';
        } else if (notificationType === 'speaker_users') {
            // No additional fields needed
        } else if (notificationType === 'exhibitors') {
            // No additional fields needed
        } else if (notificationType === 'all_users') {
            // No additional fields needed
        }

        // Use unified loader (ajaxRequestPromise already handles loader)
        // Get URL from window variable or use fallback
        const url = (window.notificationRoutes && window.notificationRoutes.send) || '/notification/send';

        ajaxRequestPromise(url, formData).then(function(response) {
            
            if (response.error == 0) {
                // Extract stats from response.data
                const stats = response.data || {};
                const sent = parseInt(stats.sent) || 0;
                const failed = parseInt(stats.failed) || 0;
                const total = parseInt(stats.total) || 0;
                
                displaySuccessMessage(
                    response.message || 'Notification sent successfully!',
                    {
                        sent: sent,
                        failed: failed,
                        total: total
                    }
                );
                
                // Reset form
                form[0].reset();
                $('#notification_type').trigger('change');
                if ($('#user_ids').length) {
                    $('#user_ids').val(null).trigger('change');
                }
            } else {
                // Extract error message from response
                let errorMessage = 'Failed to send notification';
                if (response.msg) {
                    // If msg is an array, join it; otherwise use as string
                    if (Array.isArray(response.msg)) {
                        errorMessage = response.msg.join(', ');
                    } else {
                        errorMessage = response.msg;
                    }
                } else if (response.message) {
                    errorMessage = response.message;
                }
                displayErrorMessage(errorMessage);
            }
        }).catch(function(error) {
            // Loader handled by ajaxRequestPromise
            displayErrorMessage('An error occurred while sending notification. Please try again.');
        });
    }

    function displaySuccessMessage(message, stats) {
        let content = message;
        if (stats) {
            content += '<br><br><strong>Statistics:</strong><br>';
            content += 'Sent: <span class="text-success">' + stats.sent + '</span><br>';
            content += 'Failed: <span class="text-danger">' + stats.failed + '</span><br>';
            content += 'Total: ' + stats.total;
        }
        
        if (typeof showSuccessAlert !== 'undefined') {
            showSuccessAlert(content);
        } else if (typeof $.confirm !== 'undefined') {
            $.confirm({
                title: 'Success!',
                content: content,
                type: 'green',
                typeAnimated: true,
                buttons: {
                    ok: {
                        text: 'OK',
                        btnClass: 'btn-success'
                    }
                }
            });
        } else {
            alert(message);
        }
    }

    function displayErrorMessage(message) {
        if (typeof showErrorAlert !== 'undefined') {
            showErrorAlert(message);
        } else if (typeof $.confirm !== 'undefined') {
            $.confirm({
                title: 'Error!',
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
        } else {
            alert(message);
        }
    }
});

