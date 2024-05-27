<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user): array|bool
    {
        //
    }


public function send($notifiable, Notification $notification)
{
    $message = $notification->toFcm($notifiable);

    $client = new Client();
    $response = $client->post('https://fcm.googleapis.com/fcm/send', [
                  'headers' => [
                      'Authorization' => 'key=YOUR_SERVER_KEY',
                      'Content-Type' => 'application/json',
                  ],
                  'json' => $message,
                ]);

    // Handle the response if needed

    return $response;
}
}
