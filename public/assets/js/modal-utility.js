/**
 * Global Modal Utility
 * Simple functions to show/hide a reusable modal across the application
 */

/**
 * Show modal with content
 * @param {string} title - Modal title
 * @param {string} content - HTML content to display
 * @param {object} options - Optional configuration
 * @param {string} options.size - Modal size: 'sm', 'lg', 'xl' (default: 'lg')
 * @param {boolean} options.scrollable - Enable scrolling (default: true)
 * @param {boolean} options.showFooter - Show footer (default: true)
 * @param {string} options.footerButtons - HTML for footer buttons
 * @param {boolean} options.staticBackdrop - Prevent closing on backdrop click (default: false)
 */
function showModal(title, content, options = {}) {
    const modalId = 'globalReusableModal';
    const modal = document.getElementById(modalId);
    
    if (!modal) {
        console.error('Global reusable modal not found. Make sure it is included in the layout.');
        return;
    }
    
    // Set title
    const titleElement = document.getElementById(modalId + 'Label');
    if (titleElement) {
        titleElement.textContent = title || '';
    }
    
    // Set content
    const bodyElement = document.getElementById(modalId + 'Body');
    if (bodyElement) {
        bodyElement.innerHTML = content || '';
    }
    
    // Set modal size
    const dialog = modal.querySelector('.modal-dialog');
    if (dialog) {
        // Remove existing size classes
        dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        
        const size = options.size || 'lg';
        if (size !== 'default') {
            dialog.classList.add('modal-' + size);
        }
        
        // Set scrollable
        if (options.scrollable !== false) {
            dialog.classList.add('modal-dialog-scrollable');
        } else {
            dialog.classList.remove('modal-dialog-scrollable');
        }
    }
    
    // Set footer
    const footerElement = document.getElementById(modalId + 'Footer');
    if (footerElement) {
        if (options.showFooter !== false) {
            footerElement.style.display = '';
            if (options.footerButtons) {
                // Insert custom buttons before Close button
                const closeBtn = footerElement.querySelector('.btn-secondary[data-bs-dismiss="modal"]');
                if (closeBtn && !closeBtn.previousElementSibling || 
                    (closeBtn.previousElementSibling && !closeBtn.previousElementSibling.classList.contains('custom-footer-btn'))) {
                    const customBtnContainer = document.createElement('div');
                    customBtnContainer.className = 'custom-footer-btn';
                    customBtnContainer.innerHTML = options.footerButtons;
                    footerElement.insertBefore(customBtnContainer, closeBtn);
                } else if (closeBtn && closeBtn.previousElementSibling) {
                    closeBtn.previousElementSibling.innerHTML = options.footerButtons;
                }
            } else {
                // Remove custom buttons if any
                const customBtn = footerElement.querySelector('.custom-footer-btn');
                if (customBtn) {
                    customBtn.remove();
                }
            }
        } else {
            footerElement.style.display = 'none';
        }
    }
    
    // Show modal using Bootstrap
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: options.staticBackdrop ? 'static' : true,
        keyboard: !options.staticBackdrop
    });
    bsModal.show();
    
    return bsModal;
}

/**
 * Clean up modal backdrop and body classes
 */
function cleanupModalBackdrop() {
    // Remove all modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(function(backdrop) {
        backdrop.remove();
    });
    
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

/**
 * Hide the global modal
 */
function hideModal() {
    const modalId = 'globalReusableModal';
    const modal = document.getElementById(modalId);
    
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        } else {
            // Fallback if instance doesn't exist
            $(modal).modal('hide');
        }
        
        // Clean up backdrop after modal hide animation
        setTimeout(cleanupModalBackdrop, 200);
    } else {
        // If modal doesn't exist, just clean up any orphaned backdrops
        cleanupModalBackdrop();
    }
}

// Ensure backdrop cleanup when modal is hidden via Bootstrap events
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('globalReusableModal');
    if (modal) {
        // Listen for hidden.bs.modal event
        modal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrop();
        });
        
        // Also listen for hide.bs.modal as a fallback
        modal.addEventListener('hide.bs.modal', function() {
            // This fires before the modal is hidden, but we can still clean up if needed
        });
    }
});

/**
 * Show modal with loading spinner
 * @param {string} title - Modal title
 */
function showModalLoading(title = 'Loading...') {
    const loadingContent = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    return showModal(title, loadingContent, { showFooter: false });
}

/**
 * Show modal with error message
 * @param {string} message - Error message
 * @param {string} title - Modal title (default: 'Error')
 */
function showModalError(message, title = 'Error') {
    const errorContent = '<div class="alert alert-danger">' + message + '</div>';
    return showModal(title, errorContent);
}

/**
 * Show modal with success message
 * @param {string} message - Success message
 * @param {string} title - Modal title (default: 'Success')
 */
function showModalSuccess(message, title = 'Success') {
    const successContent = '<div class="alert alert-success">' + message + '</div>';
    return showModal(title, successContent);
}

/**
 * Load modal content from URL via AJAX
 * @param {string} url - URL to fetch content from
 * @param {object} data - POST data to send
 * @param {string} title - Modal title
 * @param {object} options - Modal options (size, scrollable, etc.)
 * @returns {Promise}
 */
function showModalFromUrl(url, data, title, options = {}) {
    // Show loading state
    showModalLoading(title || 'Loading...');
    
    // Build full URL if needed
    var fullUrl = url.indexOf('http') === 0 ? url : (typeof baseURL !== 'undefined' ? baseURL : '/') + url;
    
    // Use ajaxRequestWithPromise but suppress automatic message display for modal content
    var postKey = 'load_modal';
    return ajaxRequestWithPromise(fullUrl, data, postKey, 0).then(function(response) {
        // Hide modal loading
        hideModal();
        
        if (typeof response === 'string') {
            // HTML response
            showModal(title, response, options);
            return response;
        } else if (response.html) {
            // JSON with html field
            showModal(response.title || title, response.html, options);
            return response;
        } else if (response.error == 0 && response.content) {
            // JSON with content field
            showModal(response.title || title, response.content, options);
            return response;
        } else if (response.error) {
            hideModal();
            showErrorAlert(response.msg || 'Failed to load content');
            throw response;
        } else {
            hideModal();
            showErrorAlert('Invalid response format');
            throw new Error('Invalid response');
        }
    }).catch(function(error) {
        hideModal();
        if (error && error.msg) {
            showErrorAlert(error.msg);
        } else {
            showErrorAlert('Failed to load content. Please try again.');
        }
        throw error;
    });
}

