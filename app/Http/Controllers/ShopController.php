<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Client;
use App\Models\File;
use App\Models\Shop;
use App\Models\ShopHasCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{

 /**
 * @OA\Post(
 *     path="/api/shop/becomeMerchant",
 *     tags={"Shop"},
 *     security={{"bearerAuth": {}}},
 *     summary="Convert client to merchant",
 *     description="This endpoint allows a client to become a merchant.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="Title of the shop",
 *                     example="My Shop"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="Description of the shop",
 *                     maxLength=500,
 *                     example="This is my shop description"
 *                 ),
 *                 @OA\Property(
 *                     property="files[]",
 *                     type="array",
 *                     description="Array of images",
 *                     @OA\Items(
 *                         type="string",
 *                         format="binary"
 *                     )
 *                 ),
 *                 required={"title", "description", "files[]"}
 *             )
 *         )
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
 *                 example="You already are a merchant"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
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


    public function becomeMerchant( Request $request){
        try{

            $service = new Service();
            $checkAuth = $service->checkAuth();
    
            if ($checkAuth) {
                return $checkAuth;
            }


            $personQuery = "SELECT * FROM person WHERE user_id = :userId";
            $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);
    
            $client = Client::where('person_id',$person->id)->first();
            // $client = Client::where('id',$clientId)->first();
            if($client->is_merchant == 1){
                return response()->json([
                    'message' =>'You already is merchant'
                  ]);
            }
            $createShop = $this->createShop($client->id, $request);
            if($createShop){
                return response()->json([
                    'message' =>$createShop->original['message'],
                    'error' =>$createShop
                ]);
            }
            $client->update(['is_merchant' => 1]);

            return response()->json([
                'message' =>'Shop created successffuly'
              ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    public function createShop($clientId, Request $request){
        try{

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|unique:shops,title',
                'description' => ['required','max:500'],
                'files'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }
            // return $request;

            if(Shop::where('client_id', $clientId)->whereDeleted(0)->exists()){
                return response()->json([
                    'message' => 'You already have a shop !'
                ]);
            }

            $client = Client::find($clientId);

            $url = url("/api/shop/catalogueClient/$client->uid");

            $service = new Service();
            $shop = new Shop();
            $shop->uid = $service->generateUid($shop);
            $shop->title = $request->input('title');
            $shop->description =  $request->input('description');
            $shop->shop_url = $url;
            $shop->client_id = $clientId;
            $randomString = $service->generateRandomAlphaNumeric(7,$shop,'filecode');
            $shop->filecode = $randomString;
            if($request->hasFile('files')){
                $service->uploadFiles($request,$randomString,"shop");
            }
            $shop->save();
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

   /**
 * @OA\Post(
 *     path="/api/shop/updateShop/{uid}",
 *     summary="Update shop details",
 *     tags={"Shop"},
 *   security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Shop details to update",
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="Title of the shop",
 *                     example="New Shop Name"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="Description of the shop",
 *                     example="This is a new shop description."
 *                 ),
 *                 @OA\Property(
 *                     property="files",
 *                     type="file",
 *                     description="Optional files to upload",
 *                     example="file.txt"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         description="UID of the shop to update",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success response",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Shop updated successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Invalid data provided",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="The data provided is not valid."
 *             ),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"title": {"The title field is required."}}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="500",
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Internal Server Error"
 *             )
 *         )
 *     )
 * )
 */
    public function updateShop($uid, Request $request){
        try{

           
      $service = new Service();

      $checkAuth=$service->checkAuth();
      if($checkAuth){
         return $checkAuth;
      }

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'description' => ['required','max:500']
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }
            $request->validate([
                'title' => 'required',
                'description' => 'required'
            ]);
            $shop = Shop::whereUid($uid)->whereDeleted(0)->first();

            if(!$shop){
                return response()->json([
                    'message' => 'Shop not found!'
                ]);
            }

            $shopv = new AdController();

            $checkShop = $shopv->checkShop($request->shop_id);
            if($checkShop){
                return $checkShop;
            }
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
     *     security={{"bearerAuth": {}}},
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

    public function showShop($uid,Request $request){
        try{

            if (!Auth::user()) {
                return response()->json([
                    'message' => 'UNAUTHENFICATED'
                ]);
            }

            $service = new AdController();

            $checkShop = $service->checkShop(Shop::whereUid($uid)->first()->id);
            if($checkShop){
                return $checkShop;
            }

            $shop = Shop::whereUid($uid)
            ->with('files')
            ->with('ads')
            ->whereDeleted(0)
            ->first();
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
     *     security={{"bearerAuth": {}}},
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
                'files' => ['required','size:1024']
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
 *     security={{"bearerAuth": {}}},
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
 *     security={{"bearerAuth": {}}},
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

            if(!Shop::find($shopId)){
                return response()->json([
                    'message' =>"Shop not found"
                ],404);
            }

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
 *     path="/api/shop/AdMerchant/{shopId}/{perPage}",
 *     summary="Get ads by merchant",
 *     tags={"Shop"},
 *     description="Retrieve a paginated list of ads for a specific shop by the authenticated merchant.",
 *     @OA\Parameter(
 *         name="shopId",
 *         in="path",
 *         required=true,
 *         description="ID of the shop",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         required=false,
 *         description="Number of results per page",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shop not found",
 *         @OA\JsonContent(
 *             type="object",
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
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     security={
 *         {"bearerAuth": {}}
 *     }
 * )
 */
    public function AdMerchant($shopId,$perPage){
        try{

            if(!Shop::whereId($shopId)->first()){
                return response()->json([
                    'message' => "Shop not found"
                ]) ;
            }

            if($perPage > 50){
                $perPage = 50;
            }

            $ad = Ad::whereDeleted(0)
            ->where('owner_id',Auth::user()->id)
            ->where('shop_id',$shopId)
            ->with('file')
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            return response()->json([
                'data' => $ad
            ]) ;
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    
    /**
 * @OA\Get(
 *     path="/api/shop/userShop",
 *     summary="Get user's shop",
 *     description="Retrieve the shop details for the authenticated user",
 *     operationId="getUserShop",
 *     tags={"Shop"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error occurred while retrieving the shop details."
 *             )
 *         )
 *     ),
 *     security={
 *         {"bearerAuth": {}}
 *     }
 * )
 */

    public function userShop(){
        try {
            $service = new Service();
            $clientId = $service->returnClientIdAuth();
            $userShop = Shop::where('client_id',$clientId)
            ->whereDeleted(0)
            ->with('files')
            ->get();

            if(count(  $userShop) === 0){
                return response()->json([
                    'message'  => "No shop found",
                ]);
            }
            return response()->json([
                'data'  => $userShop,
            ]);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


}
