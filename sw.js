self.addEventListener('message', function (event) {
  if (event.data === 'show-notification') {
    self.registration.showNotification('🔔 Appointment Update!', {
      body: 'Your appointment has been accepted by the astrologer 🌟',
      icon: 'https://cdn-icons-png.flaticon.com/512/888/888879.png'
    });
  }
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('https://example.com') // change this to your actual site
  );
});
