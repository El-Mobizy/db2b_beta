self.addEventListener('push',(event) => {

    const notification = event.data.json();
    // { "title" : "Hi" , "body" : "check " }

   event.waitUntil( self.ServiceWorkerRegistration.showNotification(notification.title,{
        body: notification.body,
        // icon:
        data: {
            notifURL : notification.url
        }
    }));
});

self.addEventListener('notificationclick',  (event) => {
    event.waitUntil(clients.openWindow(event.notification.data.notifURL));
})