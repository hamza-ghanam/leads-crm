// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here. Other Firebase libraries
// are not available in the service worker.importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
*/
firebase.initializeApp({
    apiKey: 'AIzaSyBAjmDC4nj9quHfgqil8cSUEsAUjVk0ZKI',
    authDomain: 'wjhatna-747c4.firebaseapp.com',
    projectId: 'wjhatna-747c4',
    storageBucket: 'wjhatna-747c4.appspot.com',
    messagingSenderId: '323952113414',
    appId: '1:323952113414:web:1e1b329ffe90807d4e5087',
    measurementId: 'G-WLZCRW5X2R',
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler((payload) => {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload,
    );

    const notificationTitle = payload.data.title;
    const bb = payload.data.body.split('|');

    const notificationOptions = {
        body: bb[0],
        icon: '/dist/img/leads-logo-bg.png',
        data: bb[1]
    };

    return self.registration.showNotification(
        notificationTitle,
        notificationOptions,
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(self.clients.openWindow(event.notification.data));
});
