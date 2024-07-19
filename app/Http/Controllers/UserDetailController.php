<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Exception;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function generateUserDetail($longitude,$latitude,$userId){
        try {

            if(UserDetail::whereUserId($userId)->exists()){
                $existUserDetail = $this->updateUserDetail($longitude,$latitude,$userId);
                if($existUserDetail){
                    return $existUserDetail;
                }
            }

            $detail = new UserDetail();
            $detail->user_id = $userId;
            $detail->latitude = $latitude;
            $detail->longitude = $longitude;
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

    public function updateUserDetail($longitude,$latitude,$userId){
        UserDetail::whereUserId($userId)->update(['latitude' => $latitude ]);
        UserDetail::whereUserId($userId)->update(['longitude' => $longitude]);
        return response()->json([
            'status_code' => 200,
            'data' =>[],
            'message' => 'Detail saved successfully'
        ],200);
    }

    // public updateUserDetail($longitude,$latitude,$userId){
    //     UserDetail::whereUserId($userId)->update(['latitude' =>$latitude ]);
    //     UserDetail::whereUserId($userId)->update(['longitude' => $longitude]);
    // }

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