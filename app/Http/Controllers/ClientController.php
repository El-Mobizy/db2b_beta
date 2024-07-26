<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Person;
use App\Models\Preorder;
use App\Models\ShopHasCategory;
use App\Models\User;
use Exception;
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
    public function getMerchant($liste=1){
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


    public function getMerchantCOncernedByPreorder($preorderUid){
        try{
            $data= [];
        
            $merchants = $this->getMerchant(1);
            $clientLocation = Preorder::whereUid($preorderUid)->first()->location_id;
            $clientCategory = Preorder::whereUid($preorderUid)->first()->category_id;
            $shopObject = new ShopController();
            $firstStep = 0;
            $secondStep = 0;

            foreach($merchants as $merchant){
                $shops = $shopObject->anUserShop($merchant->id);
                foreach($shops as $shop){
                    $merchantHasPreorderCategory = ShopHasCategory::where('shop_id',$shop->id)->where('category_id',$clientCategory)->exists();
                    if($merchantHasPreorderCategory){
                        $firstStep = 1;
                    }
                }
                if(Person::whereUserId($merchant->id)->first()->country_id == $clientLocation){
                    $secondStep = 1;
                }

                if($firstStep == 1 && $secondStep == 1){
                    $data[] = $merchant ;
                }
            }

            return $data;
           
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
     }


     public function notifyMerchantConcernedByPreorder($title,$body,$preorderUid){
        try{
           $merchants = $this->getMerchantCOncernedByPreorder($preorderUid);
           $message = new ChatMessageController();
    
           foreach($merchants as $user){
                (new MailController)->sendNotification($user->id,$title,$body, '');
           }
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createClient($personId, Request $request){
        try{
            $service = new Service();
            $client = new Client();
            $client->uid = $service->generateUid($client);
            $client->person_id = $personId;
        
            $client->save();
           
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
}
