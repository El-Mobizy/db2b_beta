
<?php

use App\Http\Controllers\WebNotificationController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    // URL du site externe vers lequel vous voulez rediriger
    $url = 'http://db2b.esgt-benin.com';
    
    return redirect()->away($url);
});




