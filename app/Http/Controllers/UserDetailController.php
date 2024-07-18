<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function generateUserDetail($longitude,$latitude,$userId){
        try {

            if(UserDetail::whereUserId($userId)->exists()){
                UserDetail::whereUserId($userId)->update(['latitude' =>$latitude ]);
                UserDetail::whereUserId($userId)->update(['longitude' => $longitude]);
            }

            $detail = new UserDetail();
            $detail->user_id = $userId;
            $detail->latitude = $latitude
            $detail->longitude = $longitude
            $detail->save();
            return response()->json([
                         'status_code' => 200,
                         'data' =>[],
                         'message' => 'Detail saved successfully'
                     ],200);
         } catch (Exception $e) {
             return response()->json([
                 'status_code' => 500,
                 'data' =>[],
                 'message' => $e->getMessage()
             ],500);
         }
    }
}

// try {
   // return response()->json([
        //         'status_code' => 200,
        //         'data' =>[],
        //         'message' => ''
        //     ],200);
// } catch (Exception $e) {
//     return response()->json([
//         'status_code' => 500,
//         'data' =>[],
//         'message' => $e->getMessage()
//     ],500);
// }