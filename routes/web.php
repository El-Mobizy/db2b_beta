
<?php

use App\Http\Controllers\WebNotificationController;
use Illuminate\Support\Facades\Route;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    // URL du site externe vers lequel vous voulez rediriger
    $url = 'http://db2b.esgt-benin.com';
    
    return redirect()->away($url);
});

