// Firebase Service Worker for Web Push Notifications
// This file must be in the public root directory

importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Get base URL from the service worker's scope
// Handle subdirectory installations (e.g., /efi/)
const baseURL = self.location.origin;
const basePath = self.location.pathname.split('/').slice(0, -1).join('/') || '';
const fullBaseURL = baseURL + basePath;

// Fetch Firebase configuration from backend
let firebaseConfig = null;
let messaging = null;

// Fetch config and initialize Firebase
fetch(fullBaseURL + '/web-push/firebase-config')
    .then(response => response.json())
    .then(data => {
        if (data.error === 0 && data.data) {
            firebaseConfig = data.data;
            // Initialize Firebase
            firebase.initializeApp(firebaseConfig);
            messaging = firebase.messaging();
            setupMessageHandlers();
        } else {
            console.error('[SW] Failed to load Firebase config');
        }
    })
    .catch(error => {
        console.error('[SW] Error loading Firebase config:', error);
    });

function setupMessageHandlers() {
    if (!messaging) return;

    // Handle background messages
    messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    
    const notificationTitle = payload.notification?.title || payload.data?.title || 'New Notification';
    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || '',
        icon: payload.notification?.icon || (fullBaseURL + '/assets/images/favicon.ico'),
        badge: fullBaseURL + '/assets/images/favicon.ico',
        tag: payload.data?.tag || 'notification',
        data: payload.data || {},
        requireInteraction: false,
        silent: false
    };

        // Show notification
        return self.registration.showNotification(notificationTitle, notificationOptions);
    });
}
self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw.js] Notification click received.');
    
    event.notification.close();

    // Handle click action - open URL from data payload
    const clickAction = event.notification.data?.click_action || event.notification.data?.url;
    
    if (clickAction) {
        event.waitUntil(
            clients.openWindow(clickAction)
        );
    } else {
        // Default: open the app
        event.waitUntil(
            clients.openWindow(fullBaseURL || '/')
        );
    }
});

