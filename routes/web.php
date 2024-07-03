
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




Route::get('/createFile', function(){
    return view('file');

 });
 Route::post('/storeFile', function(Request $request){
     $request->validate([
         'image' => 'required'
     ]);

     $photo = $request->image;

     $manager = new ImageManager(new Driver());

     $manager->read("DB2B1.jpg");

     return   $manager->read("DB2B1.jpg");
    //  dd(filesize($request->image));
     $file =new File();
     $type = $photo->getClientOriginalExtension();
     $ulid = Uuid::uuid1();
     $ulidPhoto = $ulid->toString();
     $file->filename = md5(uniqid()) . '.' . $type;
     $file->type =  $type;
     $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
     $file->location=  url("/image/test/" . $photoName);
     $file->size =  $size = filesize($photo);
     $file->referencecode = '1234567';
     $file->uid =  $ulidPhoto ;
     $file->save();

 })->name('file.store');