// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here. Other Firebase libraries
// are not available in the service worker.importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
*/
firebase.initializeApp({
    apiKey: "AIzaSyDkHR17YYFalO2XmQJ9xqrg5madLpntIuc",
    authDomain: "wrs-ae-leads.firebaseapp.com",
    projectId: "wrs-ae-leads",
    storageBucket: "wrs-ae-leads.firebasestorage.app",
    messagingSenderId: "1039605684936",
    appId: "1:1039605684936:web:7fd3e40af79c0a2c13e3fd",
    measurementId: 'G-HE4WDZ1GF0',
});

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler((payload) => {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload,
    );

    const {title, body, url, ticket_id} = payload.data;

    const notificationOptions = {
        body: body,
        icon: '/dist/img/leads-logo-bg.png',
        data: {
            url: url,
            ticket_id: ticket_id,
        },
    };

    return self.registration.showNotification(
        title,
        notificationOptions,
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const {url} = event.notification.data || {};

    event.waitUntil(
        clients.matchAll({type: 'window', includeUncontrolled: true}).then(clientList => {
            // لو في تاب مفتوحة لنفس الموقع، ركّزها
            for (const client of clientList) {
                if ('focus' in client) {
                    client.focus();
                    if (url) {
                        client.navigate(url);
                    }
                    return;
                }
            }

            // لو ما في، افتح تاب جديدة
            if (clients.openWindow) {
                return clients.openWindow(url || '/');
            }
        })
    );
});
