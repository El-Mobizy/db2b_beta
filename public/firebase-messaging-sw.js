// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here. Other Firebase libraries
// are not available in the service worker.importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
*/
firebase.initializeApp({
    apiKey: 'AIzaSyDFoRj2QLaJgcFfxzzjdIabf0atCjie27A',
    authDomain: 'familiarisationb2b.firebaseapp.com',
    databaseURL: 'https://familiarisationb2b.firebaseio.com',
    projectId: 'familiarisationb2b',
    storageBucket: 'familiarisationb2b.appspot.com',
    messagingSenderId: '166200781591',
    appId: '1:166200781591:web:878e6408644ddd73a76552',
    measurementId: 'G-L3V4XXNKC0',
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    console.log("Message received.", payload);
    const title = "Hello world is awesome";
    const options = {
        body: "Your notificaiton message .",
        icon: "/firebase-logo.png",
    };
    return self.registration.showNotification(
        title,
        options,
    );
});