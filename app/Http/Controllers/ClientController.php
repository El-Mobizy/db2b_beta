<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



/**
 * @OA\Get(
 *     path="/api/merchant/getMerchant",
 *     summary="Retrieve a list of merchants",
 *     description="Get all users who are marked as merchants and are not deleted.",
 *     tags={"Merchant"},
 *     @OA\Response(
 *         response=200,
 *         description="List of merchants",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

class ClientController extends Controller
{
    public function getMerchant($liste=0){
        try{
            $users = User::whereDeleted(0)->get();
            $merchants = [];
            $service = new Service();

            foreach($users as $user){
                $personId = $service->returnUserPersonId($user->id);
                if(Client::where('is_merchant', true)->where('person_id',$personId)->whereDeleted(0)->exists()){
                    $merchants[] = $user;
                }
            }

            if($liste == 1){
                return $merchants;
            }
            return response()->json([
                'data' => $merchants
            ],200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
