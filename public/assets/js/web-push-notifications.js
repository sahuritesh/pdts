/**
 * Web Push Notifications using Firebase Cloud Messaging
 * Handles registration, permission requests, and token management for web users
 */

(function() {
    'use strict';

    // Check if Firebase is available
    if (typeof firebase === 'undefined') {
       
        return;
    }

    // Firebase configuration - should be set from backend
    // This will be initialized from a config endpoint or inline script
    let firebaseConfig = null;
    let messaging = null;
    let currentToken = null;

    // Store service worker registration globally
    let serviceWorkerRegistration = null;

    /**
     * Register service worker
     * Returns a Promise that resolves with the registration
     */
    function registerServiceWorker() {
        return new Promise(function(resolve, reject) {
            if (!('serviceWorker' in navigator)) {
                reject(new Error('Service workers are not supported'));
                return;
            }

            // Construct service worker path
            // baseURL is set in template_v1.blade.php as {{env('APP_URL')}}
            // which should be like "http://localhost/efi"
            let swPath = '/firebase-messaging-sw.js';
            
            if (typeof baseURL !== 'undefined' && baseURL) {
                try {
                    // Parse baseURL to get the path
                    const url = new URL(baseURL);
                    const basePath = url.pathname.replace(/\/$/, ''); // Remove trailing slash
                    swPath = basePath + '/firebase-messaging-sw.js';
                } catch (e) {
                    // If baseURL is not a full URL, treat it as a path
                    const basePath = baseURL.replace(/\/$/, '');
                    swPath = basePath + '/firebase-messaging-sw.js';
                }
            } else {
                // Fallback: extract base path from current location
                const currentPath = window.location.pathname;
                const pathParts = currentPath.split('/').filter(p => p);
                if (pathParts.length > 0) {
                    // Remove last part (current page) and join
                    pathParts.pop();
                    const basePath = '/' + pathParts.join('/');
                    swPath = basePath + '/firebase-messaging-sw.js';
                }
            }
            
          
            
            navigator.serviceWorker.register(swPath)
                .then(function(registration) {
                  
                    serviceWorkerRegistration = registration;
                    resolve(registration);
                })
                .catch(function(error) {
                 
                    reject(error);
                });
        });
    }

    /**
     * Initialize Firebase for web push notifications
     * @param {Object} config Firebase configuration object
     */
    function initializeFirebase(config) {
        if (!config || !config.apiKey) {
          
            return false;
        }

        try {
            firebaseConfig = config;
            firebase.initializeApp(config);
            messaging = firebase.messaging();
            
            // Register service worker first, then request permission
            registerServiceWorker()
                .then(function(registration) {
                    // Service worker registered, now request permission
                    requestNotificationPermission();
                })
                .catch(function(error) {
                    console.error('[Web Push] Service worker registration failed:', error);
                    // Still try to request permission (Firebase might handle it)
                    requestNotificationPermission();
                });
            
            return true;
        } catch (error) {
          
            return false;
        }
    }

    /**
     * Request notification permission from user
     */
    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.warn('[Web Push] Notifications not supported in this browser');
            return;
        }

        if (Notification.permission === 'granted') {
            // Permission already granted, get token
            getFCMToken();
        } else if (Notification.permission !== 'denied') {
            // Request permission
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    getFCMToken();
                }
            });
        } else {
            console.warn('[Web Push] Notification permission was previously denied');
            console.warn('[Web Push] To enable notifications:');
            console.warn('[Web Push] 1. Click the lock icon in your browser address bar');
            console.warn('[Web Push] 2. Go to Site Settings → Notifications');
            console.warn('[Web Push] 3. Change to "Ask" or "Allow"');
            console.warn('[Web Push] 4. Refresh this page');
            
            // Optionally show a user-friendly message (you can customize this)
            if (typeof displayResponseMessage !== 'undefined') {
                displayResponseMessage({
                    error: 0,
                    msg: 'Notification permission was denied. Please enable notifications in your browser settings to receive push notifications.'
                });
            }
        }
    }

    /**
     * Get FCM token for current device
     */
    function getFCMToken() {
        if (!messaging) {
            console.error('[Web Push] Messaging not initialized');
            return;
        }

        if (!firebaseConfig) {
            console.error('[Web Push] Firebase config not available');
            return;
        }
        
        if (!firebaseConfig.vapidKey || firebaseConfig.vapidKey === '') {
            console.error('[Web Push] VAPID key is missing in config');
            return;
        }

       
        
        // Prepare token options with service worker registration if available
        const tokenOptions = {
            vapidKey: firebaseConfig.vapidKey
        };

        // If we have a service worker registration, pass it to Firebase
        // This prevents Firebase from trying to register its own service worker
        if (serviceWorkerRegistration) {
            tokenOptions.serviceWorkerRegistration = serviceWorkerRegistration;
          
        } else {
           
        }
        
        messaging.getToken(tokenOptions)
            .then(function(token) {
                if (token) {
                    currentToken = token;
                    
                    // Register token with backend
                    registerDeviceToken(token);
                    
                    // If user is logged in, also update the token to link with user_id
                    const userId = getUserId();
                    if (userId) {
                        updateDeviceTokenForUser(token, userId);
                    }
                }
            })
            .catch(function(error) {
                console.error('[Web Push] Error getting FCM token:', error);
            });
    }

    /**
     * Register device token with backend
     * @param {string} token FCM device token
     */
    function registerDeviceToken(token) {
       
        
        // Check if user is logged in
        const userId = getUserId();
       
        
        // Get device ID (generate if not exists)
        let deviceId = localStorage.getItem('web_device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('web_device_id', deviceId);
        }
       

        // For web users, use anonymous registration first
        // If user is logged in, we'll update the token to link with user_id
      
        registerAnonymousToken(token, userId);
    }
    
    /**
     * Update device token to link with user_id after login
     * This is called after successful login to link anonymous tokens
     * @param {string} token FCM device token (optional, will use current token if not provided)
     * @param {number} userId User ID
     */
    function updateDeviceTokenForUser(token, userId) {
        if (!token) {
            token = currentToken;
        }
        
        if (!token || !userId) {
            return;
        }
        
        // Get device ID
        const deviceId = localStorage.getItem('web_device_id');
        if (!deviceId) {
            return;
        }
        
        // Ensure baseURL doesn't have trailing slash
        const cleanBaseURL = (typeof baseURL !== 'undefined' ? baseURL : '').replace(/\/$/, '');
        const url = cleanBaseURL + '/web-push/update-device-token';
        const data = {
            device_token: token,
            device_id: deviceId
        };
        
      
        
        // Use fetch with CSRF token for web route
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(responseData => {
            if (responseData.error != 0) {
                console.error('[Web Push] Failed to update device token:', responseData.msg || responseData.message);
            }
        })
        .catch(error => {
            console.error('[Web Push] Error updating device token:', error);
        });
    }

    /**
     * Get a concise app version string for web (max 50 chars)
     * @returns {string} Short browser/version identifier
     */
    function getWebAppVersion() {
        const ua = navigator.userAgent;
        // Try to extract browser name and version
        let browser = 'Unknown';
        let version = '';
        
        if (ua.indexOf('Chrome') > -1) {
            browser = 'Chrome';
            const match = ua.match(/Chrome\/(\d+)/);
            version = match ? match[1] : '';
        } else if (ua.indexOf('Firefox') > -1) {
            browser = 'Firefox';
            const match = ua.match(/Firefox\/(\d+)/);
            version = match ? match[1] : '';
        } else if (ua.indexOf('Safari') > -1 && ua.indexOf('Chrome') === -1) {
            browser = 'Safari';
            const match = ua.match(/Version\/(\d+)/);
            version = match ? match[1] : '';
        } else if (ua.indexOf('Edge') > -1) {
            browser = 'Edge';
            const match = ua.match(/Edge\/(\d+)/);
            version = match ? match[1] : '';
        }
        
        const appVersion = version ? `${browser} ${version}` : browser;
        // Truncate to 50 characters max
        return appVersion.length > 50 ? appVersion.substring(0, 47) + '...' : appVersion;
    }

    /**
     * Register anonymous device token
     * @param {string} token FCM device token
     * @param {number|null} userId Optional user ID (if user is logged in)
     */
    function registerAnonymousToken(token, userId = null) {
       
        
        let deviceId = localStorage.getItem('web_device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('web_device_id', deviceId);
        }

        // Ensure baseURL doesn't have trailing slash, then add API path
        const cleanBaseURL = (typeof baseURL !== 'undefined' ? baseURL : '').replace(/\/$/, '');
        const url = cleanBaseURL + '/api/v1/register-device-token-anonymous';
        const data = {
            device_token: token,
            device_id: deviceId,
            platform: 'web',
            app_version: getWebAppVersion()
        };

       

        // Use static token for anonymous registration
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (window.STATIC_APP_TOKEN || '')
            },
            body: JSON.stringify(data)
        })
        .then(response => {
           
            return response.json();
        })
        .then(data => {
          
          
        })
        .catch(error => {
          
        });
    }

    /**
     * Get current user ID (if logged in)
     * @returns {number|null} User ID or null
     */
    function getUserId() {
        // Try to get user ID from various sources
        if (typeof window.currentUserId !== 'undefined') {
            return window.currentUserId;
        }
        
        // Check if there's a user ID in the page
        const userIdElement = document.querySelector('[data-user-id]');
        if (userIdElement) {
            return parseInt(userIdElement.getAttribute('data-user-id'));
        }
        
        return null;
    }

    /**
     * Handle token refresh
     * Note: Firebase v9 compat may not have onTokenRefresh, so we'll handle it differently
     */
    function setupTokenRefresh() {
        if (!messaging) return;

        // Firebase v9 compat uses onTokenRefresh differently
        // Check if the method exists before calling
        try {
            if (messaging && typeof messaging.onTokenRefresh === 'function') {
                messaging.onTokenRefresh(function() {
                   
                    messaging.getToken({ vapidKey: firebaseConfig.vapidKey })
                        .then(function(token) {
                            currentToken = token;
                         
                            registerDeviceToken(token);
                            
                            // If user is logged in, also update the token to link with user_id
                            const userId = getUserId();
                            if (userId) {
                                updateDeviceTokenForUser(token, userId);
                            }
                        })
                        .catch(function(error) {
                          
                        });
                });
            } else {
                // Firebase v9 compat doesn't have onTokenRefresh
                // Token will be refreshed automatically when getToken() is called
              
            }
        } catch (error) {
           
            // Not critical - token refresh will happen automatically
        }
    }

    /**
     * Handle foreground messages
     */
    function setupForegroundMessages() {
        if (!messaging) return;

        messaging.onMessage(function(payload) {
          
            
            // Extract notification data
            const notificationTitle = payload.notification?.title || payload.data?.title || 'New Notification';
            const notificationBody = payload.notification?.body || payload.data?.body || '';
            const notificationType = payload.data?.type || payload.data?.notification_type || 'info'; // info, success, warning, error
            const notificationIcon = payload.notification?.icon || payload.data?.icon || '/assets/images/favicon.ico';
            
            // Show browser notification (if permission granted)
            if (Notification.permission === 'granted') {
                const notificationOptions = {
                    body: notificationBody,
                    icon: notificationIcon,
                    badge: '/assets/images/favicon.ico',
                    tag: payload.data?.tag || 'notification',
                    data: payload.data || {},
                    requireInteraction: false
                };
                new Notification(notificationTitle, notificationOptions);
            }

            // Show toast notification using toastr
            if (typeof toastr !== 'undefined') {
                // Configure toastr options for better visibility
                const toastOptions = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    timeOut: 5000, // 5 seconds
                    extendedTimeOut: 2000,
                    preventDuplicates: false,
                    onclick: function() {
                        // Handle click action if provided
                        const clickAction = payload.data?.click_action || payload.data?.url;
                        if (clickAction) {
                            window.open(clickAction, '_blank');
                        }
                    }
                };

                // Show toast based on notification type
                switch(notificationType.toLowerCase()) {
                    case 'success':
                        toastr.success(notificationBody, notificationTitle, toastOptions);
                        break;
                    case 'error':
                        toastr.error(notificationBody, notificationTitle, toastOptions);
                        break;
                    case 'warning':
                        toastr.warning(notificationBody, notificationTitle, toastOptions);
                        break;
                    case 'info':
                    default:
                        toastr.info(notificationBody, notificationTitle, toastOptions);
                        break;
                }
            } else {
               
            }
        });
    }

    /**
     * Public API
     */
    window.WebPushNotifications = {
        /**
         * Initialize web push notifications
         * @param {Object} config Firebase configuration
         */
        init: function(config) {
            if (initializeFirebase(config)) {
                setupTokenRefresh();
                setupForegroundMessages();
                
                // Check if user is logged in and update token if needed
                // Retry multiple times since token generation is async
                let retryCount = 0;
                const maxRetries = 10; // Try for up to 10 seconds
                const checkInterval = setInterval(function() {
                    const userId = getUserId();
                    if (userId && currentToken) {
                        clearInterval(checkInterval);
                        updateDeviceTokenForUser(currentToken, userId);
                    } else if (retryCount >= maxRetries) {
                        clearInterval(checkInterval);
                    }
                    retryCount++;
                }, 1000); // Check every second
            }
        },

        /**
         * Request notification permission manually
         */
        requestPermission: requestNotificationPermission,

        /**
         * Get current FCM token
         * @returns {string|null} FCM token or null
         */
        getToken: function() {
            return currentToken;
        },

        /**
         * Check if notifications are supported
         * @returns {boolean}
         */
        isSupported: function() {
            return 'Notification' in window && 'serviceWorker' in navigator;
        },

        /**
         * Check notification permission status
         * @returns {string} 'granted', 'denied', or 'default'
         */
        getPermission: function() {
            return Notification.permission;
        }
    };

    // Auto-initialize if config is available
    if (typeof window.firebaseConfig !== 'undefined' && window.firebaseConfig) {
        window.WebPushNotifications.init(window.firebaseConfig);
    }

})();

