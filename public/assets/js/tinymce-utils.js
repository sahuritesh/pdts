/**
 * TinyMCE Utility Functions
 * Reusable functions for initializing TinyMCE editors across the application
 */

/**
 * Default TinyMCE configuration
 */
var TinyMCEUtils = {
    // Base URL for TinyMCE
    baseUrl: typeof baseURL !== 'undefined' ? baseURL + 'assets/libs/tinymce' : '/assets/libs/tinymce',
    
    // Content CSS URL
    contentCssUrl: typeof baseURL !== 'undefined' ? baseURL + 'assets/css/tinymce-content.css' : '/assets/css/tinymce-content.css',
    
    /**
     * Get default configuration
     */
    getDefaultConfig: function() {
        var self = this;
        return {
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'fontselect'
            ],
            toolbar: "undo redo | formatselect | fontselect fontsizeselect |" +
                     "bold italic | alignleft aligncenter " +
                     "alignright alignjustify | bullist numlist outdent indent | " +
                     "table | tablecellprops tableprops | link image | code",
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            font_formats: 'Arial=arial,helvetica,sans-serif; Ovo=Ovo,sans-serif; Calibri=Calibri,sans-serif; Great Vibes=Great Vibes,cursive;',
            height: 500,
            // CRITICAL: Prevent TinyMCE from converting URLs to relative paths
            // This keeps URLs exactly as returned by the server (absolute URLs)
            // The server-side save logic will replace the base URL with a placeholder
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            // Image dialog configuration
            image_advtab: true,
            image_caption: true,
            image_list: false,
            image_class_list: [],
            
            // BETTER APPROACH: Use file_picker_callback instead of images_upload_handler
            // This gives us full control - upload file first, then return URL directly
            file_picker_callback: function(callback, value, meta) {
                // Only handle image files
                if (meta.filetype === 'image') {
                    // Create file input
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.style.display = 'none';
                    
                    input.onchange = function() {
                        var file = this.files[0];
                        if (!file) {
                            return;
                        }
                        
                        // Upload file first
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('filetype', 'image');
                        formData.append('datapath', 'public/uploads/blog/');
                        
                        var csrfToken = document.querySelector('meta[name="csrf-token"]');
                        if (csrfToken) {
                            formData.append('_token', csrfToken.getAttribute('content'));
                        }
                        
                        var xhr = new XMLHttpRequest();
                        var uploadUrl = (typeof baseURL !== 'undefined' ? baseURL : '') + 'ImageUpload';
                        xhr.open('POST', uploadUrl);
                        
                        if (csrfToken) {
                            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken.getAttribute('content'));
                        }
                        
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    var json = JSON.parse(xhr.responseText);
                                    if (json && json.error == 0 && json.url) {
                                        // Return URL directly to TinyMCE - no progress numbers involved!
                                        callback(json.url, { alt: file.name });
                                    } else {
                                        alert(json.msg || 'Image upload failed');
                                    }
                                } catch (e) {
                                    alert('Failed to parse server response');
                                }
                            } else {
                                alert('Image upload failed: HTTP ' + xhr.status);
                            }
                            document.body.removeChild(input);
                        };
                        
                        xhr.onerror = function() {
                            alert('Image upload failed due to network error');
                            document.body.removeChild(input);
                        };
                        
                        xhr.send(formData);
                    };
                    
                    document.body.appendChild(input);
                    input.click();
                }
            },
            // Allow inline styles for table cells (background colors)
            // Set to true to preserve all inline styles including table cell background colors
            inline_styles: true,
            // Preserve all styles when pasting
            paste_remove_styles: false,
            paste_retain_style_properties: 'all',
            // Enable table cell properties (includes background color)
            table_cell_properties: true,
            table_row_properties: true,
            table_appearance_options: true,
            table_resize_bars: true,
            table_default_attributes: {
                border: '1'
            },
            table_default_styles: {
                'border-collapse': 'collapse',
                'width': '100%'
            },
            // Allow background-color style for table cells (already allowed via extended_valid_elements)
            allow_unsafe_link_target: false,
            // Ensure dialogs are accessible
            dialog_type: 'modal',
            content_css: this.contentCssUrl,
            // Preserve formatting settings
            forced_root_block: 'p',
            forced_root_block_attrs: {},
            convert_newlines_to_brs: false,
            remove_linebreaks: false,
            preserve_br_on_enter: true,
            br_in_pre: true,
            // Allow common formatting elements
            valid_elements: 'p,br,strong/b,em/i,u,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,div,span[class],a[href|target],img[src|alt|width|height],table,thead,tbody,tr,td,th',
            extended_valid_elements: 'p[class],br,strong/b,em/i,u,ul[class],ol[class],li[class],h1[class],h2[class],h3[class],h4[class],h5[class],h6[class],blockquote[class],div[class],span[class],a[href|target|class],img[src|alt|width|height|class],table[class|style],thead[class],tbody[class],tr[class|style],td[class|colspan|rowspan|style],th[class|colspan|rowspan|style]',
            // Clean up pasted content
            paste_as_text: false,
            paste_auto_cleanup_on_paste: true,
            paste_remove_styles: true,
            paste_remove_spans: true,
            paste_strip_class_attributes: 'all',
            paste_word_valid_elements: 'b,strong,i,em,h1,h2,h3,h4,h5,h6,p,ol,ul,li,a[href],span,color,font-size,font-weight,font-style,text-decoration',
            paste_retain_style_properties: 'none',
            // Preserve HTML entities and formatting
            entity_encoding: 'named',
            verify_html: false,
            cleanup: false,
            // Style formats for consistent formatting
            style_formats: [
                {title: 'Paragraph', format: 'p'},
                {title: 'Heading 1', format: 'h1'},
                {title: 'Heading 2', format: 'h2'},
                {title: 'Heading 3', format: 'h3'},
                {title: 'Heading 4', format: 'h4'},
                {title: 'Heading 5', format: 'h5'},
                {title: 'Heading 6', format: 'h6'},
                {title: 'Preformatted', format: 'pre'},
                {title: 'Text Color - Primary', inline: 'span', classes: 'text-primary'},
                {title: 'Text Color - Secondary', inline: 'span', classes: 'text-secondary'},
                {title: 'Text Color - Muted', inline: 'span', classes: 'text-muted'},
                {title: 'Text Size - Small', inline: 'span', classes: 'text-small'},
                {title: 'Text Size - Normal', inline: 'span', classes: 'text-normal'},
                {title: 'Text Size - Large', inline: 'span', classes: 'text-large'},
                {title: 'Margin Bottom - Small', block: 'p', classes: 'mb-small'},
                {title: 'Margin Bottom - Medium', block: 'p', classes: 'mb-medium'},
                {title: 'Margin Bottom - Large', block: 'p', classes: 'mb-large'}
            ],
            // Setup callback to wrap tables in sponsorship-table container and handle image dialog
            setup: function(editor) {
                // Store uploaded URL and dialog reference to fix src.value = 100 issue
                editor.lastUploadedImageUrl = null;
                editor.currentImageDialog = null;
                
                // Wrap images_upload_handler to store the URL and update dialog directly
                var originalHandler = editor.settings.images_upload_handler;
                if (originalHandler) {
                    editor.settings.images_upload_handler = function(blobInfo, progress) {
                        return originalHandler(blobInfo, progress).then(function(url) {
                            editor.lastUploadedImageUrl = url;
                            
                            // CRITICAL: Update dialog IMMEDIATELY when URL is available
                            // This must happen before TinyMCE validates
                            if (editor.currentImageDialog) {
                                try {
                                    var dialogApi = editor.currentImageDialog;
                                    // Force update dialog data with URL
                                    var dialogData = dialogApi.getData();
                                    if (!dialogData) {
                                        dialogData = {};
                                    }
                                    if (!dialogData.src) {
                                        dialogData.src = {};
                                    }
                                    if (typeof dialogData.src === 'object') {
                                        dialogData.src.value = url;
                                    } else {
                                        dialogData.src = url;
                                    }
                                    // Set data back - this should trigger TinyMCE to update the input field
                                    dialogApi.setData(dialogData);
                                    
                                    // Also directly find and update the input field
                                    var retryCount = 0;
                                    var updateInput = function() {
                                        retryCount++;
                                        // Try all possible selectors
                                        var selectors = [
                                            '.tox-dialog input[data-name="src"]',
                                            '.tox-dialog input[name="src"]',
                                            '.tox-textfield[data-name="src"] input',
                                            '.tox-dialog .tox-textfield input',
                                            '.tox-dialog input[type="text"]',
                                            'input[placeholder*="Source"]',
                                            'input[placeholder*="src"]',
                                            '.tox-dialog input'
                                        ];
                                        
                                        for (var i = 0; i < selectors.length; i++) {
                                            var input = document.querySelector(selectors[i]);
                                            if (input && (input.value === '' || !isNaN(input.value) || input.value !== url)) {
                                                input.value = url;
                                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                                console.log('TinyMCE - Updated input field with URL (attempt ' + retryCount + ')');
                                                return true;
                                            }
                                        }
                                        
                                        // Retry if not found and haven't tried too many times
                                        if (retryCount < 10) {
                                            setTimeout(updateInput, 50);
                                        }
                                        return false;
                                    };
                                    updateInput();
                                    
                                } catch (err) {
                                    console.warn('TinyMCE - Error updating dialog:', err);
                                }
                            }
                            
                            return url;
                        });
                    };
                }
                
                // Function to wrap table in sponsorship-table div
                function wrapTableInContainer(tableNode) {
                    // Check if table is already wrapped
                    var parent = tableNode.parentNode;
                    if (parent && parent.classList && parent.classList.contains('sponsorship-table')) {
                        return; // Already wrapped
                    }
                    
                    // Create wrapper div
                    var wrapper = editor.getDoc().createElement('div');
                    wrapper.className = 'sponsorship-table';
                    
                    // Insert wrapper before table
                    if (tableNode.parentNode) {
                        tableNode.parentNode.insertBefore(wrapper, tableNode);
                    }
                    
                    // Move table into wrapper
                    wrapper.appendChild(tableNode);
                }
                
                // Process all tables when content is set
                editor.on('SetContent', function(e) {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 100);
                });
                
                // Listen for table insertion
                editor.on('ObjectResized', function(e) {
                    if (e.target && e.target.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(e.target);
                        }, 50);
                    }
                });
                
                // Listen for node changes (when table is inserted)
                editor.on('NodeChange', function(e) {
                    var node = e.element;
                    if (node && node.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(node);
                        }, 50);
                    }
                });
                
                // Process existing tables on init
                editor.on('init', function() {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 200);
                });
                
                // Fix for TinyMCE dialogs - intercept image dialog validation to fix src.value = number issue
                editor.on('OpenDialog', function(e) {
                    if (e.dialog && e.dialog.name === 'image') {
                        var dialogApi = e.dialog;
                        if (dialogApi) {
                            editor.currentImageDialog = dialogApi;
                            
                            // ULTIMATE FIX: Use Proxy to intercept ALL access to dialog data
                            // This ensures src.value is ALWAYS a URL string, never a number
                            var originalGetData = dialogApi.getData;
                            if (originalGetData) {
                                dialogApi.getData = function() {
                                    var data = originalGetData.call(this);
                                    
                                    // Create a Proxy to intercept ALL property access
                                    if (data && typeof data === 'object') {
                                        data = new Proxy(data, {
                                            get: function(target, prop) {
                                                var value = target[prop];
                                                
                                                // Intercept src property access
                                                if (prop === 'src' && value) {
                                                    var srcValue = value.value !== undefined ? value.value : value;
                                                    
                                                    // If it's a number or numeric string, return URL instead
                                                    if (typeof srcValue === 'number' || 
                                                        (typeof srcValue === 'string' && !isNaN(srcValue) && srcValue !== '' && !srcValue.startsWith('http'))) {
                                                        
                                                        if (editor.lastUploadedImageUrl) {
                                                            // Return a new object with URL
                                                            if (typeof value === 'object') {
                                                                return new Proxy(value, {
                                                                    get: function(srcTarget, srcProp) {
                                                                        if (srcProp === 'value') {
                                                                            return editor.lastUploadedImageUrl;
                                                                        }
                                                                        return srcTarget[srcProp];
                                                                    }
                                                                });
                                                            } else {
                                                                return editor.lastUploadedImageUrl;
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                return value;
                                            }
                                        });
                                    }
                                    
                                    return data;
                                };
                            }
                            
                            // Also override setData to prevent numbers
                            var originalSetData = dialogApi.setData;
                            if (originalSetData) {
                                dialogApi.setData = function(data) {
                                    if (data && data.src) {
                                        var srcValue = data.src.value !== undefined ? data.src.value : data.src;
                                        if ((typeof srcValue === 'number') || 
                                            (typeof srcValue === 'string' && !isNaN(srcValue) && srcValue !== '' && !srcValue.startsWith('http'))) {
                                            if (editor.lastUploadedImageUrl) {
                                                if (typeof data.src === 'object') {
                                                    data.src.value = editor.lastUploadedImageUrl;
                                                } else {
                                                    data.src = editor.lastUploadedImageUrl;
                                                }
                                            }
                                        }
                                    }
                                    return originalSetData.call(this, data);
                                };
                            }
                            
                            // Override submit to fix BEFORE TinyMCE validates
                            var originalSubmit = dialogApi.submit;
                            if (originalSubmit) {
                                dialogApi.submit = function() {
                                    // Get data and fix it BEFORE calling original submit
                                    var data = dialogApi.getData();
                                    if (data && data.src) {
                                        var srcValue = data.src.value !== undefined ? data.src.value : data.src;
                                        if ((typeof srcValue === 'number') || (typeof srcValue === 'string' && !isNaN(srcValue) && srcValue !== '' && !srcValue.startsWith('http'))) {
                                            if (editor.lastUploadedImageUrl) {
                                                if (typeof data.src === 'object') {
                                                    data.src.value = editor.lastUploadedImageUrl;
                                                } else {
                                                    data.src = editor.lastUploadedImageUrl;
                                                }
                                                dialogApi.setData(data);
                                            }
                                        }
                                    }
                                    return originalSubmit.call(this);
                                };
                            }
                            
                            // Aggressive polling - update input field every 50ms
                            var pollInterval = setInterval(function() {
                                if (editor.lastUploadedImageUrl) {
                                    var srcInput = document.querySelector('.tox-dialog input[data-name="src"], .tox-dialog input[name="src"], .tox-textfield[data-name="src"] input, .tox-textfield input[placeholder*="Source"]');
                                    if (srcInput) {
                                        var currentValue = srcInput.value;
                                        // If it's a number or not the URL, fix it
                                        if ((!isNaN(currentValue) && currentValue !== '') || (currentValue && currentValue !== editor.lastUploadedImageUrl && !currentValue.startsWith('http'))) {
                                            srcInput.value = editor.lastUploadedImageUrl;
                                            srcInput.dispatchEvent(new Event('input', { bubbles: true }));
                                            srcInput.dispatchEvent(new Event('change', { bubbles: true }));
                                        }
                                    }
                                }
                            }, 50);
                            
                            dialogApi.on('close', function() {
                                clearInterval(pollInterval);
                                editor.currentImageDialog = null;
                                editor.lastUploadedImageUrl = null;
                            });
                        }
                    }
                    
                    setTimeout(function() {
                        var dialog = document.querySelector('.tox-dialog-wrap');
                        if (dialog && document.querySelector('.offcanvas.show')) {
                            // Move dialog to body if it's inside offcanvas
                            var offcanvas = document.querySelector('.offcanvas.show');
                            if (offcanvas && offcanvas.contains(dialog)) {
                                document.body.appendChild(dialog);
                                // Ensure high z-index
                                dialog.style.zIndex = '999999';
                            }
                        }
                        
                        // Fix tabindex and focus on all inputs inside dialogs when offcanvas is open
                        if (document.querySelector('.offcanvas.show')) {
                            var dialogInputs = document.querySelectorAll('.tox-dialog input, .tox-dialog textarea, .tox-dialog select, .tox-textarea');
                            dialogInputs.forEach(function(input) {
                                // Remove tabindex="-1"
                                if (input.getAttribute('tabindex') === '-1') {
                                    input.removeAttribute('tabindex');
                                }
                                // Remove data-alloy-tabstop if it's preventing focus
                                if (input.hasAttribute('data-alloy-tabstop')) {
                                    input.removeAttribute('data-alloy-tabstop');
                                }
                                // Ensure it's focusable
                                input.style.pointerEvents = 'auto';
                                input.style.userSelect = 'text';
                                input.style.webkitUserSelect = 'text';
                                // Make it focusable
                                if (input.tagName.toLowerCase() === 'textarea' || input.tagName.toLowerCase() === 'input') {
                                    input.setAttribute('tabindex', '0');
                                // Try to focus it if it's a textarea (source code editor) - but don't change cursor position
                                if (input.tagName.toLowerCase() === 'textarea' && input.classList.contains('tox-textarea')) {
                                    setTimeout(function() {
                                        try {
                                            // Only focus if not already focused - preserve cursor position
                                            if (document.activeElement !== input) {
                                                input.focus();
                                            }
                                        } catch(err) {
                                            console.log('Could not focus textarea:', err);
                                        }
                                    }, 50);
                                }
                                }
                            });
                        }
                    }, 100);
                });
                
                // Also fix tabindex when dialog content changes
                editor.on('DialogOpen', function(e) {
                    setTimeout(function() {
                        if (document.querySelector('.offcanvas.show')) {
                            var dialogInputs = document.querySelectorAll('.tox-dialog input, .tox-dialog textarea, .tox-dialog select, .tox-textarea');
                            dialogInputs.forEach(function(input) {
                                // Remove tabindex="-1"
                                if (input.getAttribute('tabindex') === '-1') {
                                    input.removeAttribute('tabindex');
                                }
                                // Remove data-alloy-tabstop
                                if (input.hasAttribute('data-alloy-tabstop')) {
                                    input.removeAttribute('data-alloy-tabstop');
                                }
                                // Ensure focusable
                                input.style.pointerEvents = 'auto';
                                input.style.userSelect = 'text';
                                if (input.tagName.toLowerCase() === 'textarea' || input.tagName.toLowerCase() === 'input') {
                                    input.setAttribute('tabindex', '0');
                                    // Focus textarea for source code editor - but don't change cursor position
                                    if (input.tagName.toLowerCase() === 'textarea' && input.classList.contains('tox-textarea')) {
                                        setTimeout(function() {
                                            try {
                                                // Only focus if not already focused - preserve cursor position
                                                if (document.activeElement !== input) {
                                                    input.focus();
                                                }
                                            } catch(err) {}
                                        }, 100);
                                    }
                                }
                            });
                        }
                    }, 150);
                });
            }
            // Paste preprocessor will be added in init method
        };
    },
    
    /**
     * Get paste preprocessor function
     */
    getPastePreprocessor: function() {
        return function(plugin, args) {
            // Clean up pasted HTML - convert inline styles to CSS classes where possible
            var content = args.content;
            
            // Create a temporary div to parse and clean HTML
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            
                // Process all elements
            var allElements = tempDiv.querySelectorAll('*');
            allElements.forEach(function(el) {
                // Get inline style if exists
                var style = el.getAttribute('style');
                
                // Preserve background-color for table cells (td, th)
                var isTableCell = el.tagName === 'TD' || el.tagName === 'TH';
                var preservedStyles = '';
                
                if (style) {
                    // Preserve background-color for table cells
                    if (isTableCell) {
                        var bgColorMatch = style.match(/background-color\s*:\s*[^;]+/i);
                        if (bgColorMatch) {
                            preservedStyles = bgColorMatch[0] + ';';
                        }
                    }
                    
                    // Convert common inline styles to classes
                    // Text alignment
                    if (style.includes('text-align: center') || style.includes('text-align:center')) {
                        el.classList.add('text-center');
                    } else if (style.includes('text-align: right') || style.includes('text-align:right')) {
                        el.classList.add('text-right');
                    } else if (style.includes('text-align: left') || style.includes('text-align:left')) {
                        el.classList.add('text-left');
                    } else if (style.includes('text-align: justify') || style.includes('text-align:justify')) {
                        el.classList.add('text-justify');
                    }
                    
                    // Font size conversions (approximate)
                    if (style.includes('font-size: 12px') || style.includes('font-size:12px')) {
                        el.classList.add('text-small');
                    } else if (style.includes('font-size: 18px') || style.includes('font-size:18px') || 
                               style.includes('font-size: 1.25em') || style.includes('font-size:1.25em')) {
                        el.classList.add('text-large');
                    }
                    
                    // Color conversions
                    if (style.includes('color: rgb(29, 39, 88)') || style.includes('color:#1d2758')) {
                        el.classList.add('text-primary');
                    } else if (style.includes('color: rgb(108, 117, 125)') || style.includes('color:#6c757d')) {
                        el.classList.add('text-secondary', 'text-muted');
                    }
                    
                    // For table cells, preserve background-color in style attribute
                    if (isTableCell && preservedStyles) {
                        el.setAttribute('style', preservedStyles);
                    } else if (!isTableCell) {
                        // Remove style attribute for non-table cells (already converted to classes)
                        el.removeAttribute('style');
                    }
                    
                    // Color conversions
                    if (style.includes('color: rgb(29, 39, 88)') || style.includes('color:#1d2758')) {
                        el.classList.add('text-primary');
                    } else if (style.includes('color: rgb(108, 117, 125)') || style.includes('color:#6c757d')) {
                        el.classList.add('text-secondary', 'text-muted');
                    }
                    
                    // For table cells, we already preserved background-color above
                    // For non-table cells, remove style attribute (we've converted what we can to classes)
                    if (!isTableCell) {
                        el.removeAttribute('style');
                    }
                }
                
                // Remove class attributes that are not our custom classes
                var classList = el.classList;
                var classesToKeep = ['text-left', 'text-center', 'text-right', 'text-justify', 
                                    'text-primary', 'text-secondary', 'text-muted',
                                    'text-small', 'text-normal', 'text-large',
                                    'mb-small', 'mb-medium', 'mb-large',
                                    'mt-small', 'mt-medium', 'mt-large'];
                if (classList.length > 0) {
                    var classesToRemove = [];
                    for (var i = 0; i < classList.length; i++) {
                        var cls = classList[i];
                        var shouldKeep = false;
                        for (var j = 0; j < classesToKeep.length; j++) {
                            if (cls === classesToKeep[j]) {
                                shouldKeep = true;
                                break;
                            }
                        }
                        if (!shouldKeep) {
                            classesToRemove.push(cls);
                        }
                    }
                    classesToRemove.forEach(function(cls) {
                        el.classList.remove(cls);
                    });
                }
                
                // Remove webkit and other vendor-specific attributes
                Array.from(el.attributes).forEach(function(attr) {
                    if (attr.name.startsWith('-webkit-') || 
                        attr.name.startsWith('-moz-') || 
                        attr.name.startsWith('-ms-') ||
                        attr.name === 'box-sizing' ||
                        attr.name === 'font-optical-sizing' ||
                        attr.name === 'font-size-adjust' ||
                        attr.name === 'font-kerning' ||
                        attr.name === 'font-feature-settings' ||
                        attr.name === 'font-variation-settings' ||
                        attr.name === 'font-language-override') {
                        el.removeAttribute(attr.name);
                    }
                });
            });
            
            // Get cleaned HTML with classes instead of inline styles
            args.content = tempDiv.innerHTML;
        };
    },
    
    /**
     * Get configuration based on inline styles flag
     * @param {boolean} useInlineStyles - Whether to use inline styles (true) or CSS classes (false)
     * @param {object} customOptions - Custom options to override defaults
     */
    getConfigForInlineStyles: function(useInlineStyles, customOptions) {
        var config = this.getDefaultConfig();
        
        if (useInlineStyles) {
            // Email templates and similar need inline styles
            config.inline_styles = true;
            config.content_css = false; // Don't use external CSS
            config.paste_remove_styles = false;
            config.paste_retain_style_properties = 'all';
            config.paste_strip_class_attributes = 'none';
            // Don't use paste preprocessor for inline styles
            config.paste_preprocess = null;
        } else {
            // Use inline styles to preserve table cell background colors and all formatting
            // This ensures all styles including background-color are saved
            config.inline_styles = true;
            config.content_css = this.contentCssUrl;
            // Preserve all styles when pasting
            config.paste_remove_styles = false;
            config.paste_retain_style_properties = 'all';
            // Don't use paste preprocessor that removes styles
            config.paste_preprocess = null;
        }
        
        // Merge custom options
        if (customOptions) {
            config = Object.assign({}, config, customOptions);
        }
        
        return config;
    },
    
    /**
     * Initialize TinyMCE editor with custom options
     * @param {string|object} selector - CSS selector or configuration object
     * @param {object} customOptions - Custom options to override defaults
     * @param {function} onInitCallback - Callback function when editor is initialized
     */
    init: function(selector, customOptions, onInitCallback) {
        if (typeof tinyMCE === 'undefined') {
            console.warn('TinyMCE is not loaded. Please ensure tinymce.min.js is included.');
            return false;
        }
        
        var config;
        var useInlineStyles = false;
        
        // If selector is an object, treat it as full config
        if (typeof selector === 'object') {
            config = Object.assign({}, this.getDefaultConfig(), selector);
        } else {
            // Get default config (defaults to inline styles to preserve table cell colors)
            config = this.getConfigForInlineStyles(true, customOptions);
            config.selector = selector;
            config.base_url = this.baseUrl;
            config.suffix = '.min';
            
            // Always add setup callback to handle per-element data-inline-styles flag
            // This allows each editor instance to be configured individually
            var originalSetup = config.setup;
            var self = this;
            config.setup = function(editor) {
                // Add table wrapper functionality
                // Function to wrap table in sponsorship-table div
                function wrapTableInContainer(tableNode) {
                    // Check if table is already wrapped
                    var parent = tableNode.parentNode;
                    if (parent && parent.classList && parent.classList.contains('sponsorship-table')) {
                        return; // Already wrapped
                    }
                    
                    // Create wrapper div
                    var wrapper = editor.getDoc().createElement('div');
                    wrapper.className = 'sponsorship-table';
                    
                    // Insert wrapper before table
                    if (tableNode.parentNode) {
                        tableNode.parentNode.insertBefore(wrapper, tableNode);
                    }
                    
                    // Move table into wrapper
                    wrapper.appendChild(tableNode);
                }
                
                // Process all tables when content is set
                editor.on('SetContent', function(e) {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 100);
                });
                
                // Listen for table insertion
                editor.on('ObjectResized', function(e) {
                    if (e.target && e.target.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(e.target);
                        }, 50);
                    }
                });
                
                // Listen for node changes (when table is inserted)
                editor.on('NodeChange', function(e) {
                    var node = e.element;
                    if (node && node.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(node);
                        }, 50);
                    }
                });
                
                // Process existing tables on init
                editor.on('init', function() {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 200);
                });
                
                // Check this specific editor's element for data-inline-styles attribute
                var $editorElement = $('#' + editor.id);
                if ($editorElement.length > 0) {
                    var inlineStylesAttr = $editorElement.attr('data-inline-styles');
                    var editorUseInlineStyles = false;
                    if (inlineStylesAttr !== undefined) {
                        editorUseInlineStyles = inlineStylesAttr === 'true' || inlineStylesAttr === '1' || inlineStylesAttr === 'yes';
                    }
                    
                    // Configure this specific editor instance based on flag
                    if (editorUseInlineStyles) {
                        // Override settings for inline styles (email templates)
                        editor.settings.inline_styles = true;
                        editor.settings.content_css = false;
                        editor.settings.paste_remove_styles = false;
                        editor.settings.paste_retain_style_properties = 'all';
                        editor.settings.paste_strip_class_attributes = 'none';
                        editor.settings.paste_preprocess = null;
                    } else {
                        // Use inline styles to preserve table cell background colors
                        editor.settings.inline_styles = true;
                        editor.settings.content_css = self.contentCssUrl;
                        editor.settings.paste_remove_styles = false;
                        editor.settings.paste_retain_style_properties = 'all';
                        editor.settings.paste_preprocess = null;
                    }
                }
                
                // Call original setup if exists
                if (originalSetup) {
                    originalSetup.call(this, editor);
                }
            };
        }
        
        // Add paste preprocessor if not already set and inline styles are disabled
        // (Only if we're not using per-element setup)
        if (!config.paste_preprocess && !config.inline_styles && typeof selector !== 'object') {
            // Check if any element needs inline styles
            var $checkElements = $(selector);
            var needsInlineStyles = false;
            if ($checkElements.length > 0) {
                $checkElements.each(function() {
                    var $el = $(this);
                    var attr = $el.attr('data-inline-styles');
                    if (attr === 'true' || attr === '1' || attr === 'yes') {
                        needsInlineStyles = true;
                        return false; // break
                    }
                });
            }
            if (!needsInlineStyles) {
                config.paste_preprocess = this.getPastePreprocessor();
            }
        }
        
        // Add setup callback if provided
        if (onInitCallback) {
            var originalSetup = config.setup;
            config.setup = function(editor) {
                // Add table wrapper functionality
                // Function to wrap table in sponsorship-table div
                function wrapTableInContainer(tableNode) {
                    // Check if table is already wrapped
                    var parent = tableNode.parentNode;
                    if (parent && parent.classList && parent.classList.contains('sponsorship-table')) {
                        return; // Already wrapped
                    }
                    
                    // Create wrapper div
                    var wrapper = editor.getDoc().createElement('div');
                    wrapper.className = 'sponsorship-table';
                    
                    // Insert wrapper before table
                    if (tableNode.parentNode) {
                        tableNode.parentNode.insertBefore(wrapper, tableNode);
                    }
                    
                    // Move table into wrapper
                    wrapper.appendChild(tableNode);
                }
                
                // Process all tables when content is set
                editor.on('SetContent', function(e) {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 100);
                });
                
                // Listen for table insertion
                editor.on('ObjectResized', function(e) {
                    if (e.target && e.target.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(e.target);
                        }, 50);
                    }
                });
                
                // Listen for node changes (when table is inserted)
                editor.on('NodeChange', function(e) {
                    var node = e.element;
                    if (node && node.nodeName === 'TABLE') {
                        setTimeout(function() {
                            wrapTableInContainer(node);
                        }, 50);
                    }
                });
                
                // Process existing tables on init
                editor.on('init', function() {
                    setTimeout(function() {
                        var body = editor.getBody();
                        var tables = body.querySelectorAll('table');
                        tables.forEach(function(table) {
                            wrapTableInContainer(table);
                        });
                    }, 200);
                    // Call callback after init
                    if (onInitCallback) {
                        onInitCallback(editor);
                    }
                });
                
                if (originalSetup) {
                    originalSetup.call(this, editor);
                }
            };
        }
        
        // Initialize TinyMCE
        try {
            tinyMCE.init(config);
            return true;
        } catch (e) {
            console.error('TinyMCE initialization error:', e);
            return false;
        }
    },
    
    /**
     * Initialize TinyMCE for dynamically loaded content
     * @param {string} selector - CSS selector
     * @param {object} customOptions - Custom options
     * @param {function} onInitCallback - Callback when initialized
     * @param {number} delay - Delay in milliseconds before initialization
     */
    initDynamic: function(selector, customOptions, onInitCallback, delay) {
        delay = delay || 100;
        var self = this;
        
        setTimeout(function() {
            // Remove existing editor instance if any
            var editorId = selector.replace('#', '').replace('.', '');
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                tinyMCE.remove(editorId);
            }
            
            // Get content from textarea before initializing
            var $textarea = $(selector);
            var content = $textarea.val() || '';
            
            // Check for inline styles flag from data attribute
            var useInlineStyles = false;
            if ($textarea.length > 0) {
                var inlineStylesAttr = $textarea.attr('data-inline-styles');
                if (inlineStylesAttr !== undefined) {
                    useInlineStyles = inlineStylesAttr === 'true' || inlineStylesAttr === '1' || inlineStylesAttr === 'yes';
                }
            }
            
            // Get config based on inline styles flag
            var config = self.getConfigForInlineStyles(useInlineStyles, customOptions);
            config.selector = selector;
            config.base_url = self.baseUrl;
            config.suffix = '.min';
            
            // Create combined callback
            var combinedCallback = function(editor) {
                // Load content after initialization
                setTimeout(function() {
                    var textareaContent = $textarea.val() || content;
                    if (textareaContent) {
                        editor.setContent(textareaContent);
                    }
                }, 200);
                
                // Call user callback if provided
                if (onInitCallback) {
                    onInitCallback(editor);
                }
            };
            
            // Add setup callback
            var originalSetup = config.setup;
            config.setup = function(editor) {
                if (originalSetup) {
                    originalSetup.call(this, editor);
                }
                editor.on('init', function() {
                    combinedCallback(editor);
                });
            };
            
            // Initialize TinyMCE
            try {
                tinyMCE.init(config);
            } catch (e) {
                console.error('TinyMCE initialization error:', e);
            }
        }, delay);
    },
    
    /**
     * Save TinyMCE content to textarea before form submission
     * @param {string} selector - Editor selector (ID or selector)
     */
    saveContent: function(selector) {
        if (typeof tinyMCE === 'undefined') {
            return false;
        }
        
        var editor = tinyMCE.get(selector.replace('#', ''));
        if (editor) {
            // Get the HTML content from editor with all styles preserved
            // Use 'html' format to preserve all inline styles including background-color
            var htmlContent = editor.getContent({
                format: 'html',
                no_events: true
            });
            
            // Save to textarea using editor.save() which also saves content
            editor.save();
            
            // Also manually set it to ensure it's there with all styles
            $(selector).val(htmlContent);
            
            return true;
        } else {
            // Fallback
            tinyMCE.triggerSave();
            return true;
        }
    },
    
    /**
     * Remove TinyMCE editor instance
     * @param {string} selector - Editor selector
     */
    remove: function(selector) {
        if (typeof tinyMCE !== 'undefined') {
            var editorId = selector.replace('#', '').replace('.', '');
            if (tinyMCE.get(editorId)) {
                tinyMCE.remove(editorId);
                return true;
            }
        }
        return false;
    }
};

