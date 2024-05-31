<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Client;
use App\Models\File;
use App\Models\Shop;
use App\Models\ShopHasCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{

        /**
     * @OA\Post(
     *     path="/api/shop/becomeMerchant/{clientId}",
     *     tags={"Shop"},
     *     summary="Convert client to merchant",
     *     description="This endpoint allows a client to become a merchant.",
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         description="ID of the client",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shop created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Shop created successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Client is already a merchant",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="You already is merchant"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error message"
     *             )
     *         )
     *     )
     * )
     */

    public function becomeMerchant($clientId, Request $request){
        try{
            $client = Client::where('id',$clientId)->first();
            if($client->is_merchant == 1){
                return response()->json([
                    'message' =>'You already is merchant'
                  ]);
            }
            $shop = new UserController();
            $createShop = $shop->createShop($client->id, $request);
            $client->update(['is_merchant' => 1]);
            if($createShop){
                return response()->json([
                    'message' =>$createShop->original['message']
                  ]);
            }

            return response()->json([
                'message' =>'Shop created successffuly'
              ]); 
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/shop/updateShop/{uid}",
     *     tags={"Shop"},
     *     summary="Update shop details",
     *     description="This endpoint allows updating the details of an existing shop.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="UID of the shop",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"title", "description"},
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 description="Title of the shop"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Description of the shop"
     *             ),
     *             @OA\Property(
     *                 property="files",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                 description="Files to upload"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shop updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Shop updated successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The data provided is not valid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error message"
     *             )
     *         )
     *     )
     * )
     */
    public function updateShop($uid, Request $request){
        try{

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'description' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }
            $request->validate([
                'title' => 'required',
                'description' => 'required'
            ]);
            $shop = Shop::whereUid($uid)->whereDeleted(0)->first();
            $service = new Service();
            $shop->title = $request->input('title');
            $shop->description = $request->input('description');
            if($request->hasFile('files')){
                $service->uploadFiles($request,$shop->filecode,"shop");
            }
            if(Shop::whereTitle($request->input('title'))->whereDeleted(0)->exists()){
                return response()->json([
                    'message' => 'This name is already takken, please change it!'
                ]);
            }
            $shop->save();
            return response()->json([
                'message' =>'Shop updated successffuly'
              ]); 
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

       /**
     * @OA\Get(
     *     path="/api/shop/showShop/{uid}",
     *     tags={"Shop"},
     *     summary="Show shop details",
     *     description="This endpoint returns the details of a shop by its UID.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="UID of the shop",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="" 
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shop not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Shop not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error message"
     *             )
     *         )
     *     )
     * )
     */

    public function showShop($uid){
        try{
            $shop = Shop::whereUid($uid)->whereDeleted(0)->first();
            if(!$shop){
                return response()->json([
                    'message' => 'Shop not found'
                ]);
            }

            return response()->json([
                'data' =>$shop
            ]);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/shop/addShopFile/{filecodeShop}",
     *     tags={"Shop"},
     *     summary="Add a file to the shop",
     *     description="This endpoint allows you to add a file to a shop using the shop's file code.",
     *     @OA\Parameter(
     *         name="filecodeShop",
     *         in="path",
     *         description="File code of the shop",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="files[]",
     *                     type="array",
     *                     @OA\Items(type="file"),
     *                     description="Files to be uploaded"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="string",
     *                 example="file add successfuly"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error message"
     *             )
     *         )
     *     )
     * )
     */

    public function addShopFile(Request $request, $filecodeShop){
        try{

            $request->validate([
                'files' => 'required'
            ]);

            $service = new Service();

            $service->uploadFiles($request,$filecodeShop,'shop');


            return response()->json([
                'data' =>'file add successfuly'
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/shop/updateShopFile/{uid}",
 *     summary="Update shop file",
 *     tags={"Shop"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="Unique identifier of the shop",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="files",
 *                     type="array",
 *                     @OA\Items(
 *                         type="string",
 *                         format="binary"
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="file update successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Validation error message"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Server error message"
 *             )
 *         )
 *     )
 * )
 */

    public function updateShopFile($uid, Request $request){
        try{

            $request->validate([
                'files' => 'required'
            ]);
            $existFile = File::whereUid($uid)->first();
            $randomString = $existFile->codereference;

            $service = new Service();

            $service->removeFile($uid);
            $service->uploadFiles($request,$randomString,'shop');

            return response()->json([
                'messge' =>'file update successfuly'
            ],200);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/shop/addCategoryToSHop/{shopId}",
 *     summary="Add category to shop",
 *     tags={"Shop"},
 *     @OA\Parameter(
 *         name="shopId",
 *         in="path",
 *         description="Unique identifier of the shop",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="categoryIds",
 *                 type="array",
 *                 @OA\Items(
 *                     type="integer"
 *                 ),
 *                 description="Array of category IDs to add to the shop"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Category added successfully or other message",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Category added successfully"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="string",
 *                 example="Category added successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error or other client error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Validation error message"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Server error message"
 *             )
 *         )
 *     )
 * )
 */

    public function addCategoryToSHop( $shopId,Request $request){
        try{

            $request->validate([
                'categoryIds' => 'required'
            ]);

            $countCategory = ShopHasCategory::where('shop_id',$shopId)->whereDeleted(0)->count();

            $limit = 3;

            if($countCategory == $limit){
                return response()->json([
                    'message' =>"You have reached the maximum number of categories which is $limit you cannot add others"
                ],200);
            }

            foreach($request->input('categoryIds') as $categoryId){
                if(ShopHasCategory::where('shop_id',$shopId)->where('category_id',$categoryId)->whereDeleted(0)->exists()){
                    return response()->json([
                        'message' =>"You already add this category in your shop"
                    ],200);
                }

                $service = new Service();

                $shopHasCategory = new ShopHasCategory();
                $shopHasCategory->uid = $service->generateUid($shopHasCategory);
                $shopHasCategory->shop_id = $shopId;
                $shopHasCategory->category_id = $categoryId;
                $shopHasCategory->save();
            }

            return response()->json([
                'data' =>'Category added successfuly'
            ],200);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/shop/catalogueClient/{clientUid}",
 *     summary="Get catalogue for a client",
 *     tags={"Shop"},
 *     @OA\Parameter(
 *         name="clientUid",
 *         in="path",
 *         description="Unique identifier of the client",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Client's catalogue retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Schema(ref="")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Client not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Client not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Server error message"
 *             )
 *         )
 *     )
 * )
 */
    public function catalogueClient($clientUid){
        try{
            $client = Client::whereUid($clientUid)->first();
            // $clientId = $client->id;

            $personQuery = "SELECT * FROM person WHERE id = :clientId";
            $person = DB::selectOne($personQuery, ['clientId' => $client->person_id]);

            $userQuery = "SELECT * FROM users WHERE id = :clientId";
            $user = DB::selectOne($userQuery, ['clientId' => $person->user_id]);

            $ad = Ad::whereDeleted(0)->where('owner_id',$user->id)->with('file')->with('category')->get();
   
            return response()->json([
                'data' => $ad
            ]) ;
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }
}
