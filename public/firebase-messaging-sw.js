// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here. Other Firebase libraries
// are not available in the service worker.importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
*/
firebase.initializeApp({
    apiKey: "AIzaSyCJyExVqmT0cLM60nO5HF1my0dopyqRoWI",
    authDomain: "leads-crm-4553d.firebaseapp.com",
    projectId: "leads-crm-4553d",
    storageBucket: "leads-crm-4553d.appspot.com",
    messagingSenderId: "388756731314",
    appId: "1:388756731314:web:e147dfc6ec4936c67860ab",
    measurementId: 'G-measurement-id',
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function ({data: {title, body, icon}}) {
    return self.registration.showNotification(title, {body, icon});
});
