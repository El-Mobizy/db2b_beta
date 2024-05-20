<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Service extends Controller
{
    public function generateRandomAlphaNumeric($length) {
        $bytes = random_bytes(ceil($length * 3 / 4));
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
    }

    public function checkFile(Request $request){
        try {
            if(!$request->hasFile('files')){
                return response()->json([
                    'message' =>'the field file is required'
                ],404);
            }
        } catch (\Exception $th) {

        }
        // if(!$request->hasFile('files')){
        //     return response()->json([
        //         'message' =>'the field file is required'
        //     ],404);
        // }else{

        // }
    }

     public function validateLocation($id)
    {
        if(!Country::find($id)){
            return response()->json([
                'error' => 'location not found'
            ]);
        }else{

        }

    }

    public function validateCategory($id)
    {
        if(!Category::find($id)){
            return response()->json([
                'error' => 'category not found'
            ]);
        }else{

        }
    }

    public function validateFile( $file){
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Veuillez télécharger une image (jpeg, jpg, png, gif)');
        }
    }

    public function uploadFiles(Request $request, $randomString,$location){
        foreach($request->file('files') as $photo){
            $this->validateFile($photo);
            $this->storeFile($photo, $randomString, $location);
        }
    }


    

    private function storeFile( $photo, $randomString, $location){
        $fileController = new FileController();
        $fileController->store($photo, $randomString, $location);
    }

}
