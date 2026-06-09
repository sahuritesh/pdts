// this file consists of complete package to handle the ajax request from calling method to error handling


var isResolved = false;

/**
 * Unified Loader Overlay Utility - Works for both Admin and Frontend
 * This function creates and manages a consistent loader overlay across the entire project
 * 
 * Usage:
 *   showGlobalLoader(true);   // or showGlobalLoader('show') - Shows the loader
 *   showGlobalLoader(false);  // or showGlobalLoader('hide') - Hides the loader
 * 
 * The loader is automatically created on first use and reused for subsequent calls.
 * It works consistently across both admin panel and frontend.
 * 
 * @param {boolean|string} show - true/'show' to show loader, false/'hide' to hide loader
 * @returns {void}
 */
function showGlobalLoader(show) {
    // Normalize input - accept boolean or string
    var shouldShow = show === true || show === 'show';
    
    // Get or create the loader overlay
    var $loader = $('#global-loader-overlay');
    
    // Remove any duplicate loaders first
    if ($('#global-loader-overlay').length > 1) {
        $('#global-loader-overlay').not(':first').remove();
    }
    
    if ($loader.length === 0) {
        // Determine loader image path based on context
        var loaderPath = '/assets/images/loader.svg';
        if (typeof baseURL !== 'undefined' && baseURL) {
            var normalizedBase = (baseURL.charAt(baseURL.length - 1) === '/') ? baseURL : baseURL + '/';
            loaderPath = normalizedBase + loaderPath.replace(/^\//, '');
        }
        
        // Create loader overlay if it doesn't exist
        var loaderHtml = '<div id="global-loader-overlay" class="global-loader-overlay" style="display: none;">' +
            '<div class="loader-content">' +
            '<div class="loader-spinner">' +
            '<img src="' + loaderPath + '" alt="Loading..." />' +
            '</div>' +
            '</div>' +
            '</div>';
        $('body').append(loaderHtml);
        $loader = $('#global-loader-overlay');
    }
    
    // Avoid stacking duplicate "show" while animating. "Hide" must never be dropped:
    // fast AJAX can call hide during fadeIn; ignoring it left the overlay stuck (production dashboard).
    if ($loader.data('animating')) {
        if (shouldShow) {
            return;
        }
        $loader.stop(true, true);
        $loader.data('animating', false);
    }
    
    if (shouldShow) {
        if ($loader.is(':visible')) {
            return; // Already visible, don't animate again
        }
        $loader.data('animating', true);
        $loader.stop(true, true).fadeIn(200, function() {
            $loader.data('animating', false);
        });
    } else {
        if (!$loader.is(':visible')) {
            $loader.data('animating', false);
            return; // Already hidden, don't animate again
        }
        $loader.data('animating', true);
        $loader.stop(true, true).fadeOut(200, function() {
            $loader.data('animating', false);
        });
    }
}

/**
 * preloaderOverlay function - uses unified loader
 * Kept for backward compatibility - all calls to this function use the unified loader
 * 
 * @param {string} action - 'show' or 'hide'
 * @returns {void}
 */
function preloaderOverlay(action) {
    // Use unified loader only
    showGlobalLoader(action === 'show');
}

// Utility function to find submit button from form or active element
function findSubmitButton(formSelector) {
    // First, try to find button from the active element (clicked button)
    var $activeBtn = $(document.activeElement);
    if ($activeBtn.is('button') && !$activeBtn.prop('disabled')) {
        return $activeBtn;
    }
    
    // Try to find button from form
    if (formSelector) {
        var $form = $(formSelector);
        if ($form.length > 0) {
            // Try common button patterns - look for buttons with .btn-primary class
            var $btn = $form.find('button[type="button"].btn-primary').first();
            if ($btn.length === 0) {
                $btn = $form.find('button.btn-primary').first();
            }
            if ($btn.length === 0) {
                $btn = $form.find('button[type="button"]').not('[type="reset"]').first();
            }
            if ($btn.length === 0) {
                $btn = $form.find('button').not('[type="reset"]').first();
            }
            if ($btn.length > 0) return $btn;
        }
    }
    
    // Try to find by common button IDs near the form
    if (formSelector) {
        var $form = $(formSelector);
        var formId = $form.attr('id');
        if (formId) {
            // Map form IDs to button IDs
            var formButtonMap = {
                'addemailtempform': 'addemail',
                'update_settingsform': 'update_settings',
                'updatesmtp_settingsform': 'updatesmtp_settings',
                'submitticketfrm': 'submitticket',
                'submitregistrationfrm': 'submitregistration'
            };
            
            if (formButtonMap[formId]) {
                var $btn = $('#' + formButtonMap[formId]);
                if ($btn.length > 0) return $btn;
            }
            
            // Try common button IDs
            var commonIds = ['addemail', 'adduser', 'submitticket', 'add-template-button', 'update_settings', 'updatesmtp_settings', 'submitmember', 'submitregistration'];
            for (var i = 0; i < commonIds.length; i++) {
                var $btn = $('#' + commonIds[i]);
                if ($btn.length > 0) {
                    // Check if button is related to the form (inside or nearby)
                    if ($btn.closest('form').length > 0 || $btn.closest('.formCard, .card').length > 0) {
                        return $btn;
                    }
                }
            }
        }
    }
    
    return null;
}

// Utility function to clean up waves-ripple elements
function cleanupWavesRipple($btn) {
    if (!$btn || $btn.length === 0) return;
    
    // Remove all waves-ripple elements from the button
    $btn.find('.waves-ripple').remove();
    // Also check if button itself has waves-ripple as direct child (sometimes Waves.js adds it directly)
    $btn.children('.waves-ripple').remove();
    
    // Remove any ripples that are positioned outside or are stale
    $btn.siblings('.waves-ripple').remove();
}

// Utility function to clean up all orphaned waves-ripple elements
function cleanupAllWavesRipples() {
    // Remove all waves-ripple elements that are:
    // 1. Not inside a button
    // 2. Have opacity 0 or very low
    // 3. Are older than 2 seconds (stale)
    $('.waves-ripple').each(function() {
        var $ripple = $(this);
        var $parentBtn = $ripple.closest('button');
        
        // Remove if not in a button, or if opacity is 0/low, or if it's stale
        if ($parentBtn.length === 0 || 
            parseFloat($ripple.css('opacity')) < 0.1 || 
            $ripple.css('opacity') === '0') {
            $ripple.remove();
        }
    });
}

// Utility function to set button loading state (reusable across project)
function setButtonLoadingState(buttonSelector, isLoading) {
    var $btn = typeof buttonSelector === 'string' ? $(buttonSelector) : buttonSelector;
    if ($btn.length === 0 || !$btn.is('button')) {
        return;
    }
    
    if (isLoading) {
        // Clean up any existing waves-ripple elements before changing state
        cleanupWavesRipple($btn);
        
        // Save original classes if not already saved (to preserve button styling)
        if (!$btn.data('original-classes')) {
            $btn.data('original-classes', $btn.attr('class') || '');
        }
        
        $btn.prop('disabled', true);
        var $btnText = $btn.find('.btn-text');
        var $btnSpinner = $btn.find('.btn-spinner');
        
        // If button has proper structure (.btn-text and .btn-spinner)
        if ($btnText.length > 0 && $btnSpinner.length > 0) {
            // Hide text and show spinner
            $btnText.addClass('d-none');
            $btnSpinner.removeClass('d-none');
            // Force display in case d-none removal doesn't work
            $btnSpinner.css('display', 'inline-flex');
        } else {
            // Fallback: Save original HTML and replace with spinner
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }
            var originalText = $btnText.length > 0 ? $btnText.text().trim() : $btn.text().trim();
            if (originalText && originalText !== 'Processing...') {
                $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + originalText);
            } else {
                $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');
            }
        }
    } else {
        // Clean up any existing waves-ripple elements
        cleanupWavesRipple($btn);
        
        $btn.prop('disabled', false);
        var $btnText = $btn.find('.btn-text');
        var $btnSpinner = $btn.find('.btn-spinner');
        var originalHtml = $btn.data('original-html');
        var originalClasses = $btn.data('original-classes');
        
        // If button has proper structure
        if ($btnText.length > 0 && $btnSpinner.length > 0) {
            // Show text and hide spinner
            $btnText.removeClass('d-none');
            $btnSpinner.addClass('d-none');
            // Remove inline style if set
            $btnSpinner.css('display', '');
        } else if (originalHtml) {
            // Restore original HTML
            $btn.html(originalHtml);
            $btn.removeData('original-html');
        }
        
        // Restore original classes to ensure button styling is preserved
        if (originalClasses) {
            $btn.attr('class', originalClasses);
            $btn.removeData('original-classes');
        }
        
        // Clean up any remaining waves-ripple elements after a short delay
        setTimeout(function() {
            cleanupWavesRipple($btn);
        }, 500);
    }
}

// Utility function to reset all button loading states (call on page load)
function resetAllButtonStates() {
    // Reset ALL buttons (not just disabled ones) that might have loading states
    $('button').each(function() {
        var $btn = $(this);
        var $btnText = $btn.find('.btn-text');
        var $btnSpinner = $btn.find('.btn-spinner');
        var originalHtml = $btn.data('original-html');
        var originalText = $btn.data('original-text');
        
        // Check if button has visible spinner or is disabled
        var hasVisibleSpinner = $btnSpinner.length > 0 && !$btnSpinner.hasClass('d-none');
        var hasSpinnerInHtml = $btn.html().indexOf('spinner-border') !== -1;
        var isDisabled = $btn.prop('disabled');
        
        if (hasVisibleSpinner || hasSpinnerInHtml || isDisabled) {
            // Clean up waves-ripple elements
            cleanupWavesRipple($btn);
            
            // Reset disabled state
            $btn.prop('disabled', false);
            
            // Remove inline display style if set
            $btnSpinner.css('display', '');
            
            if ($btnText.length > 0 && $btnSpinner.length > 0) {
                // Button has .btn-text and .btn-spinner structure
                $btnText.removeClass('d-none');
                $btnSpinner.addClass('d-none');
            } else if (originalHtml) {
                // Button HTML was replaced with spinner, restore original
                $btn.html(originalHtml);
                $btn.removeData('original-html');
            } else if (originalText) {
                // Button text was replaced, restore original
                $btn.text(originalText);
                $btn.removeData('original-text');
            } else if (hasSpinnerInHtml) {
                // Button has spinner in HTML but no saved data, try to restore from structure
                if ($btnText.length > 0) {
                    $btnText.removeClass('d-none');
                }
                if ($btnSpinner.length > 0) {
                    $btnSpinner.addClass('d-none');
                    $btnSpinner.css('display', '');
                }
            }
        }
    });
    
    // Clean up all orphaned waves-ripple elements
    cleanupAllWavesRipples();
    
    // Hide any visible loaders using unified utility
    showGlobalLoader(false);
}

// Periodic cleanup of waves-ripple elements (every 2 seconds)
setInterval(function() {
    cleanupAllWavesRipples();
}, 2000);

function ajaxRequestWithPromise(url, parameterData, postKey, isFormData = '', callback = '', buttonSelector = null, method = 'POST', timeoutMs = 0) {
    // Auto-detect button if not provided
    var $submitButton = null;
    if (!buttonSelector) {
        // Try to find button from active element or form
        var $activeBtn = $(document.activeElement);
        if ($activeBtn.is('button') && !$activeBtn.prop('disabled')) {
            $submitButton = $activeBtn;
        } else if (isFormData && parameterData instanceof FormData) {
            // Try to find form and button
            var $forms = $('form');
            $forms.each(function() {
                var $btn = findSubmitButton(this);
                if ($btn && $btn.length > 0) {
                    $submitButton = $btn;
                    return false; // break loop
                }
            });
        }
    } else {
        // Accept both jQuery object and selector string
        if (buttonSelector instanceof jQuery) {
            $submitButton = buttonSelector;
        } else {
            $submitButton = $(buttonSelector);
        }
    }
    
    // Set button loading state if button found
    if ($submitButton && $submitButton.length > 0) {
        setButtonLoadingState($submitButton, true);
    }
    
    // Normalize method to uppercase
    method = (method || 'POST').toUpperCase();
    
    // Handle GET vs POST data preparation
    var dataToSend, processData, contentType;
    if (method === 'GET') {
        // For GET requests, append data as query string to URL
        var queryString = '';
        if (parameterData && typeof parameterData === 'object' && !(parameterData instanceof FormData)) {
            var params = [];
            for (var key in parameterData) {
                if (parameterData.hasOwnProperty(key)) {
                    params.push(encodeURIComponent(key) + '=' + encodeURIComponent(parameterData[key]));
                }
            }
            queryString = params.join('&');
        }
        if (queryString) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + queryString;
        }
        dataToSend = null;
        processData = true;
        contentType = 'application/x-www-form-urlencoded';
    } else {
        // POST request handling
        if (isFormData) {
            dataToSend = parameterData;
            processData = false;
            contentType = false;
        } else {
            var jsonData = JSON.stringify(parameterData);
            processData = true;
            contentType = 'application/x-www-form-urlencoded';
            dataToSend = {
                'postKey': postKey,
                'data': jsonData,
            }
        }
    }
    
    // Show unified loader overlay
    showGlobalLoader(true);
    
    const promise = new Promise(function (resolve, reject) {
        var ajaxOptions = {
            url: url,
            type: method,
            processData: processData,
            contentType: contentType,
            data: dataToSend,
            success: function (data) {
                // Hide loader overlay
                showGlobalLoader(false);
                
                // Reset button state
                if ($submitButton && $submitButton.length > 0) {
                    setButtonLoadingState($submitButton, false);
                }
                
                // Only show response message for POST requests (operations), not GET (data fetches)
                if (method === 'POST') {
                    displayResponseMessage(data);
                }
                
                // Check if data is already an object or needs parsing
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (e) {
                        // If parsing fails, return as string
                    }
                }
                resolve(data);
            },
            error: function (err) {
                // Hide loader overlay on error
                showGlobalLoader(false);
                
                // Reset button state on error
                if ($submitButton && $submitButton.length > 0) {
                    setButtonLoadingState($submitButton, false);
                }
                // console.log('Error+++'+err);
                reject(err);
            }
        };
        if (timeoutMs > 0) {
            ajaxOptions.timeout = timeoutMs;
        }
        $.ajax(ajaxOptions);
    });

    return promise;
}



function ajaxRequestPromiseHtml(url, parameterData, postKey) {
      
      return new Promise(function (resolve, reject) {
            $.ajax({
                  url: url,
                  type: 'POST',
                  data: {
                        'postKey': postKey,
                        data: JSON.stringify(parameterData),
                  },
                  success: function (data) {
                        displayResponseMessage(data);
                        resolve(data) // Resolve promise and go to then()                                            
                  },
                  error: function (err) {
                        reject(err) // Reject the promise and go to catch() 
                  }
            });
            
      });
      
}



function removeErrorClass() {    
      $('input, select,textarea').each(function (index) {
            var input = $(this);
            var name = input.attr('name');
            input.removeClass("errorBorder");
      });
}



function showValidationMsg(msg, id, error) {
      console.log(id);
      
      if (error == '1') {
            notify('top', 'right', '', 'success', 'animated fadeInDown', 'animated fadeOutDown', msg);
      } else {
            notify('top', 'right', '', 'danger', 'animated fadeInDown', 'animated fadeOutDown', msg);
            $('#' + id).addClass('errorBorder').focus();
            
      }
      
}



function displayResponseMessage(response, response_type) {
      var datatype = typeof response;
      try {
            if(datatype == 'string')
            {
                  data = JSON.parse(response);
                  toastr.options = {
                        "closeButton": true,
                        "progressBar": false
                  }
                  if (response_type == 'array') {
                        $.each(data, function () {                  
                              if (this.error == '1') {
                                    var msgText = typeof this.msg === 'string' ? this.msg.replace(/<[^>]*>/g, '') : this.msg;
                                    toastr.error(msgText);
                                    return false;
                              } else {
                                    var msgText = typeof this.msg === 'string' ? this.msg.replace(/<[^>]*>/g, '') : this.msg;
                                    toastr.success(msgText);
                                    return true;
                              }
                        });
                  } else {
                        if ((data.error == '0' || data.error == 0) && data.msg) {
                              var msgText = typeof data.msg === 'string' ? data.msg.replace(/<[^>]*>/g, '') : data.msg;
                              toastr.success(msgText);
                              if( typeof(data.redirect) != 'undefined' && data.redirect != null && data.redirect != '' ){
                                    window.setTimeout( function(){
                                          window.location.href = data.redirect;
                                    }, 1500 );
                              }
                        } else if(data.msg) {
                              toastr.options = {
                                    "closeButton": true,
                                    "progressBar": false
                              }
                              // Strip HTML tags from error message
                              var msgText = typeof data.msg === 'string' ? data.msg.replace(/<[^>]*>/g, '') : data.msg;
                              toastr.error(msgText);
                        }
                  }    
            }
      }catch(e){
            return false;
      }

}



function notify(from, align, icon, type, animIn, animOut, message) {
      
      $.growl({
            
            icon: icon,
            
            title: "",
            
            message: message,
            
            url: "",
            
            
      }, {
            
            element: "body",
            
            type: type,
            
            allow_dismiss: true,
            
            placement: {
                  
                  from: from,
                  
                  align: align,
                  
            },
            
            offset: {
                  
                  x: 30,
                  
                  y: 30,
                  
            },
            
            spacing: 10,
            
            z_index: 999999,
            
            delay: 2500,
            
            timer: 1000,
            
            url_target: "_blank",
            
            mouse_over: false,
            
            animate: {
                  
                  enter: animIn,
                  
                  exit: animOut,
                  
            },
            
            
            
            icon_type: "class",
            
            template: '<div data-growl="container" class="alert" role="alert">' +
            
            '<button type="button" class="close growlCloseButton" data-growl="dismiss">' +
            
            '<span aria-hidden="true">&times;</span>' +
            
            '<span class="sr-only">Close</span>' +
            
            "</button>" +
            
            '<span data-growl="icon"></span>' +
            
            '<span data-growl="title"></span>' +
            
            '<span data-growl="message"></span>' +
            
            '<a href="#" data-growl="url"></a>' +
            
            "</div>",
            
      });      
      
}



// preloaderOverlay function moved above - this is just a placeholder comment



$('input').keyup(function () {      
      $(this).removeClass('errorBorder');      
});

// Handle browser back/forward navigation (bfcache)
window.addEventListener('pageshow', function(event) {
    // Reset button states when page is loaded from cache (back button)
    if (event.persisted) {
        if (typeof resetAllButtonStates === 'function') {
            resetAllButtonStates();
        }
    }
});