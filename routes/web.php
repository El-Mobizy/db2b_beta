
<?php

use App\Http\Controllers\WebNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $mail = [
        'title' => 'Test Email',
        'body' => 'hola'
    ];
    return view('notification.login',[
        'mail' => $mail
    ]);
});

Route::get('/test', function () {
    return view('test');
});


Route::get('/push-notificaiton', [WebNotificationController::class, 'index'])->name('push-notificaiton');
Route::post('/store-token', [WebNotificationController::class, 'storeToken'])->name('store.token');
Route::post('/send-web-notification', [WebNotificationController::class, 'sendWebNotification'])->name('send.web-notification');

Route::get('/create', [WebNotificationController::class, 'create'])->name('create');