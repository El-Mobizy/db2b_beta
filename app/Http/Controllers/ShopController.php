<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Category;
use App\Models\Client;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Shop;
use App\Models\ShopHasCategory;
use App\Models\TypeOfType;
use App\Models\User;
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
                return (new Service())->apiResponse(404, [], 'You already is merchant');
                // return response()->json([
                //     'message' =>'You already is merchant'
                //   ]);
            }
            $createShop = $this->createShop($request);
            if($createShop){
                return (new Service())->apiResponse(404,$createShop, $createShop->original['message']);
                // return response()->json([
                //     'message' =>$createShop->original['message'],
                //     'error' =>$createShop
                // ],200);
            }
            // $client->update(['is_merchant' => 1]);

            $client->is_merchant = 1;
            $client->save();

            return (new Service())->apiResponse(200, [], 'Shop created successfully');

            // return response()->json([
            //     'message' =>'Shop created successffuly'
            //   ],200);
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


  /**
 * @OA\Post(
 *     path="/api/shop/createShop",
 *     summary="Create a new shop for a client",
 *     description="This endpoint allows you to create a new shop for a specific client. The shop must have a unique title and a description. Optional files can be uploaded and linked to the shop.",
 *     tags={"Shop"},
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             @OA\MediaType(
 *                 mediaType="multipart/form-data",
 *                 @OA\Schema(
 *                     required={"title", "description"},
 *                     @OA\Property(
 *                         property="title",
 *                         type="string",
 *                         description="Title of the shop, must be unique."
 *                     ),
 *                     @OA\Property(
 *                         property="description",
 *                         type="string",
 *                         description="Description of the shop, maximum 500 characters."
 *                     ),
 *                     @OA\Property(
 *                         property="files[]",
 *                         type="array",
 *                         @OA\Items(
 *                             type="string",
 *                             format="binary"
 *                         ),
 *                         description="Optional files to upload for the shop."
 *                     )
 *                 )
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shop created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Shop created successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The data provided is not valid."),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error message")
 *         )
 *     ),
 *     security={{"bearerAuth": {}}},
 * )
 */
    public function createShop(Request $request){
        try{

          return (new Service())->storeImage($request);



            $validator = Validator::make($request->all(), [
                'title' => 'required|string|unique:shops,title',
                'description' => ['required','max:500'],
                'files' => ''
            ]);

            DB::beginTransaction();
            
            if ($validator->fails()) {
                return (new Service())->apiResponse(404, [], 'The data provided is not valid. '. $validator->errors());
                // return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            } 


            $personQuery = "SELECT * FROM person WHERE user_id = :userId";
            $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);
    
            $client = Client::where('person_id',$person->id)->first();


            $url = url("/api/shop/catalogueClient/$client->uid");

            $service = new Service();
            $shop = new Shop();
            $shop->uid = $service->generateUid($shop);
            $shop->title = $request->input('title');
            $shop->description =  $request->input('description');
            $shop->shop_url = $url;
            $shop->client_id = $client->id;
            $randomString = $service->generateRandomAlphaNumeric(7,$shop,'filecode');
            $shop->filecode = $randomString;
            if($request->files){
                // return (new Service())->apiResponse(200,  $request, '');
                return  $service->uploadFiles($request,$randomString,"shop");
            }
            // else{
            //     return (new Service())->apiResponse(200, [], 'no file');
            // }

            $shop->save();
            DB::commit();
            
            return (new Service())->apiResponse(200, [], 'Shop created successfully');
            // return response()->json([
            //     'message' =>'Shop created successfully'
            //   ],200);
        }catch(Exception $e){
            DB::rollBack();
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

            if((new Service())->isValidUuid($uid)){
                return (new Service())->isValidUuid($uid);
            }

           
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
                 return (new Service())->apiResponse(200, [], 'The data provided is not valid.'.$validator->errors());
                // return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }
            $request->validate([
                'title' => '',
                'description' => ''
            ]);
            $shop = Shop::whereUid($uid)->whereDeleted(0)->first();

            if(!$shop){
                return (new Service())->apiResponse(404, [], 'Shop not found!');
                // return response()->json([
                //     'message' => 'Shop not found!'
                // ]);
            }

            $shopv = new AdController();

            $checkShop = $shopv->checkShop($request->shop_id);
            if($checkShop){
                return $checkShop;
            }
            $service = new Service();
            $shop->title = $request->input('title')??$shop->title;
            $shop->description = $request->input('description')??$shop->description;
            if($request->hasFile('files')){
                $service->uploadFiles($request,$shop->filecode,"shop");
            }
            if(Shop::whereTitle($request->input('title'))->whereDeleted(0)->exists()){
                return (new Service())->apiResponse(404, [], 'This name is already takken, please change it!');
                // return response()->json([
                //     'message' => 'This name is already takken, please change it!'
                // ]);
            }
            $shop->save();

            return (new Service())->apiResponse(404, [], 'Shop updated successfully');
            // return response()->json([
            //     'message' =>'Shop updated successffuly'
            //   ]); 
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
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
                return (new Service())->apiResponse(404, [], 'UNAUTHENFICATED');
                // return response()->json([
                //     'message' => 'UNAUTHENFICATED'
                // ]);
            }

            if((new Service())->isValidUuid($uid)){
                return (new Service())->isValidUuid($uid);
            }

            $service = new AdController();

            if(!Shop::whereUid($uid)->first()){
                return (new Service())->apiResponse(404,[],'Shop not found');
            }

            $checkShop = $service->checkShop(Shop::whereUid($uid)->first()->id);
            if($checkShop){
                return $checkShop;
            }


            $shop = Shop::whereUid($uid)
            ->with('files')
            ->whereDeleted(0)
            ->first();
            if(!$shop){
                return (new Service())->apiResponse(404, [], 'Shop not found');
                // return response()->json([
                //     'message' => 'Shop not found'
                // ]);
            }

            $validatedStatusId = TypeOfType::where('libelle', 'validated')->first()->id;

            $clients = Order::where('status',$validatedStatusId)->whereHas('order_details.ad', function($query) use ($shop) {
                $query->where('shop_id', $shop->id);
            })->pluck('user_id')->unique()->values()->toArray();

            // return $clients;

            $statistique = [
                'category' => count($this->getShopCategorie($shop->id)->original['data']),
                'customer' => $clients,
                'ad' => Ad::where('owner_id',Auth::user()->id)->where('shop_id',$shop->id)->count(),
                'orders' =>Order::where('status',$validatedStatusId)->whereHas('order_details.ad', function($query) use ($shop) {
                    $query->where('shop_id', $shop->id);
                })->count(),
               
            ];

            $clientid  = (new Service())->returnClientIdUser(Auth::user()->id);

            if($shop->client_id != $clientid){
                return (new Service())->apiResponse(404,[],'You cannot view the categories of a store that does not belong to you');
            }

            $shop->statistique = $statistique;

            return (new Service())->apiResponse(404, $shop, 'Shop detail');
            // return response()->json([
            //     'data' =>$shop
            // ]);

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
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

            return (new Service())->apiResponse(404, [], 'file add successfully');

            // return response()->json([
            //     'data' =>'file add successfuly'
            // ]);
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
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
            if((new Service())->isValidUuid($uid)){
                return (new Service())->isValidUuid($uid);
            }
            $existFile = File::whereUid($uid)->first();

            if(!$existFile){
                return (new Service())->apiResponse(404, [], 'Shop file not found');
            }

            $randomString = $existFile->codereference;

            $service = new Service();

            $service->removeFile($uid);
            $service->uploadFiles($request,$randomString,'shop');

            return response()->json([
                'messge' =>'file update successfuly'
            ],200);
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
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

            // return [$request->categoryIds,'autre élément'];

            $request->validate([
                'categoryIds' => 'required|array',
                'categoryIds.*' => 'integer|exists:categories,id'
            ]);

            if(!Shop::find($shopId)){
                return (new Service())->apiResponse(404, [], "Shop not found");
                // return response()->json([
                //     'message' =>"Shop not found"
                // ],404);
            }

            $shop = Shop::whereId($shopId)->first();
            $clientid  = (new Service())->returnClientIdUser(Auth::user()->id);

            if($shop->client_id != $clientid){
                return (new Service())->apiResponse(404,[],'This shop is not yours');
            }

            $countCategory = ShopHasCategory::where('shop_id',$shopId)->whereDeleted(0)->count();

            $limit = TypeOfType::whereLibelle("limitOfCategory")->first()->codereference;

           

            foreach($request->input('categoryIds') as $categoryId){
                $categoryName =  Category::whereId($categoryId)->first()->title;
                if(ShopHasCategory::where('shop_id',$shopId)->where('category_id',$categoryId)->whereDeleted(0)->exists()){
                    return (new Service())->apiResponse(404,[],"The $categoryName category was already associated with this store");
                }
                if(Category::whereId($categoryId)->first()->attribute_group_id == null){
                    return (new Service())->apiResponse(404,[],"The $categoryName category does not have an attribute so you cannot associate it with your store");
                }
        
                if($countCategory == $limit){
                    return (new Service())->apiResponse(404,[],"You have reached the maximum number of categories which is $limit you cannot add others");
                //     return response()->json([
                //         'message' =>"You have reached the maximum number of categories which is $limit you cannot add others"
                //     ],200);
                }
            }

            foreach($request->input('categoryIds') as $categoryId){
                $service = new Service();
                    $shopHasCategory = new ShopHasCategory();
                    $shopHasCategory->uid = $service->generateUid($shopHasCategory);
                    $shopHasCategory->shop_id = $shopId;
                    $shopHasCategory->category_id = $categoryId;
                    $shopHasCategory->save();
            }

            return (new Service())->apiResponse(200, [], 'Category(ies) added successfully');

            // return response()->json([
            //     'data' =>'Category added successfully'
            // ],200);
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


     /**
 * @OA\Post(
 *     path="/api/shop/RemoveCategoryToSHop/{shopId}",
 *     summary="Remove category to shop",
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
 *                 description="Array of category IDs to Remove to the shop"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Category Removed successfully or other message",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Category Removed successfully"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="string",
 *                 example="Category Removed successfully"
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

    public function RemoveCategoryToSHop( $shopId,Request $request){
        try{

            $request->validate([
                'categoryIds' => 'required|array',
                'categoryIds.*' => 'integer|exists:categories,id'
            ]);

            if(!Shop::find($shopId)){
                return (new Service())->apiResponse(404, [], "Shop not found");
                // return response()->json([
                //     'message' =>"Shop not found"
                // ],404);
            }

            $shop = Shop::whereId($shopId)->first();
            $clientid  = (new Service())->returnClientIdUser(Auth::user()->id);

            if($shop->client_id != $clientid){
                return (new Service())->apiResponse(404,[],'This shop is not yours');
            }

            foreach($request->input('categoryIds') as $categoryId){
                $categoryName =  Category::whereId($categoryId)->first()->title;
                if(!ShopHasCategory::where('shop_id',$shopId)->where('category_id',$categoryId)->whereDeleted(0)->exists()){
                    return (new Service())->apiResponse(404,[],"The $categoryName category was not associated with this store");
                }
                if(Ad::where('shop_id',$shopId)->where('category_id',$categoryId)->whereDeleted(0)->exists())
                {
                    return (new Service())->apiResponse(404,[],"Unable to delete a category  $categoryName that is associated with products in your store");
                }
            }

            foreach($request->input('categoryIds') as $categoryId){
                ShopHasCategory::where('shop_id',$shopId)->where('category_id',$categoryId)->delete();
            }

            return (new Service())->apiResponse(200, [], 'Category(ies) removed successfuly');
            
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

/**
 * @OA\Get(
 *     path="/api/shop/AdMerchant/{shopId}",
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
    public function AdMerchant(Request $request,$shopId){
        try{

            if(!Shop::whereId($shopId)->first()){
                return response()->json([
                    'message' => "Shop not found"
                ]) ;
            }

            $perPage = $request->query('perPage');

            if($perPage> 50){
                $perPage = 50;
            }

            $clientid  = (new Service())->returnClientIdUser(Auth::user()->id);

            if(Shop::whereId($shopId)->first()->client_id != $clientid){
                return (new Service())->apiResponse(404,[],'You cannot view products that does not belong to you');
            }

            $ads = Ad::whereDeleted(0)
            ->where('owner_id',Auth::user()->id)
            ->where('shop_id',$shopId)
            ->with('file')
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            foreach($ads as $ad){
                $ad->category_title =  Category::find($ad->category_id)->title;
                $ad->category_parent =  Category::find($ad->category_id)->parent_id;
                $ad->issynchronized = true ;

                if(File::where('referencecode',$ad->file_code)->exists()){
                    $ad->image = File::where('referencecode',$ad->file_code)->first()->location;
                }
                $data[] = $ad;
            }

            return (new Service())->apiResponse(200,  $ads, 'list of ads for a specific shop by the authenticated merchant.');

            // return response()->json([
            //     'data' => $ad
            // ]) ;
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    
    /**
 * @OA\Get(
 *     path="/api/shop/userShop",
 *     summary="Get user's shop",
 *     description="Retrieve the shop for the authenticated user",
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
                return (new Service())->apiResponse(404, [], 'No shop found');
                // return response()->json([
                //     'message'  => "No shop found",
                // ]);
            }

            return (new Service())->apiResponse(200, $userShop, 'list of shop for the authenticated user');
            // return response()->json([
            //     'data'  => $userShop,
            // ],200);
        } catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function anUserShop($userId){
        try {

            if(!User::whereId($userId)->first()){
                return (new Service())->apiResponse(404,[],'User not found');
            }
            $service = new Service();
            $clientId = $service->returnClientIdUser($userId);
            $userShop = Shop::where('client_id',$clientId)
            ->whereDeleted(0)
            ->with('files')
            ->get();

            if(count(  $userShop) === 0){
                return (new Service())->apiResponse(404, [], 'No shop found');
                // return response()->json([
                //     'message'  => "No shop found",
                // ]);
            }

            return $userShop;

        } catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


      /**
 * @OA\Get(
 *     path="/api/shop/userPaginateShop/{perpage}",
 *     summary="Get user's shop",
 *     description="Retrieve the shop details for the authenticated user",
 *     tags={"Shop"},
 * @OA\Parameter(
 *         name="perpage",
 *         in="path",
 *         required=true,
 *         description="number of element perpage",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
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

 public function userPaginateShop($perpage){
    try {
        $service = new Service();
        $clientId = $service->returnClientIdAuth();
        $userShop = Shop::where('client_id',$clientId)
        ->whereDeleted(0)
        ->with('files')
        ->paginate($perpage);

        if(count( $userShop) === 0){
            return (new Service())->apiResponse(404, [], 'No shop found');
            // return response()->json([
            //     'message'  => "No shop found",
            // ]);
        }

        return (new Service())->apiResponse(200, $userShop, 'list of shop for the authenticated user');
        // return response()->json([
        //     'data'  => $userShop,
        // ]);
    } catch(Exception $e){
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


/**
 * @OA\Get(
 *     path="/api/shop/categories/{shopId}",
 *     summary="Get categories of a shop",
 *     description="Retrieve all categories associated with a specific shop.",
 *     tags={"Shop"},
 *  security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="shopId",
 *         in="path",
 *         required=true,
 *         description="ID of the shop",
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of categories",
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
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */

    public function getShopCategorie($shopId){
        try {

            $shop = Shop::whereId($shopId)->first();
            if(!$shop){
                return (new Service())->apiResponse(404,[],'Shop not found');
            }
            $clientid  = (new Service())->returnClientIdUser(Auth::user()->id);

            if($shop->client_id != $clientid){
                return (new Service())->apiResponse(404,[],'You cannot view the categories of a store that does not belong to you');
            }

            $shopCategories = ShopHasCategory::where('shop_id',$shopId)->get();
            $data = [];
            foreach($shopCategories as $shopCategorie){
                $category = Category::whereId($shopCategorie->category_id)->first();

                $data[] = [
                        'id' => $category->id,
                        'title' => $category->title,
                        'slug' => $category->slug,
                        'total_ads' => $category->total_ads,
                        'parent_id' => $category->parent_id,
                        'link_category_id' => $category->link_category_id,
                        'attribute_group_id' => $category->attribute_group_id,
                        'deleted' => $category->deleted,
                        'is_top' => $category->is_top,
                        'filecode' => $category->filecode,
                        'uid' => $category->uid,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                        'image'=> $category->file[0]->location,
                        'file' => $category->file
                ];

            }
            return (new Service())->apiResponse(404, $data,'Get categories of specific shop');
            // return response()->json([
            //     'data'  => $data,
            // ]);

        } catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    /**
 * @OA\Get(
 *     path="/api/shop/getOrdersShop/{shopUid}",
 *     tags={"Shop"},
 *     summary="Retrieve orders grouped by status for a specific shop",
 *     description="This endpoint retrieves orders grouped by status for a given shop, identified by its unique shopUid.",
 *     security={{"bearerAuth": {}}},
 * 
 *     @OA\Parameter(
 *         name="shopUid",
 *         in="path",
 *         required=true,
 *         description="Unique identifier of the shop",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="perpage",
 *         in="query",
 *         required=false,
 *         description="Number of results per page",
 *         @OA\Schema(
 *             type="integer",
 *             example=15
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=200,
 *         description="Orders grouped by status retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 example="success"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(
 *                         property="status",
 *                         type="string",
 *                         example="pending"
 *                     ),
 *                     @OA\Property(
 *                         property="orders",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(
 *                                 property="id",
 *                                 type="integer",
 *                                 example=123
 *                             ),
 *                             @OA\Property(
 *                                 property="order_details",
 *                                 type="object",
 *                                 @OA\Property(
 *                                     property="ad",
 *                                     type="object",
 *                                     @OA\Property(
 *                                         property="shop_id",
 *                                         type="integer",
 *                                         example=1
 *                                     )
 *                                 )
 *                             )
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shop not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 example="error"
 *             ),
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
 *                 property="status",
 *                 type="string",
 *                 example="error"
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="An unexpected error occurred"
 *             )
 *         )
 *     )
 * )
 */

 public function getOrdersShop(Request $request, $shopUid)
 {
     try {

        if((new Service())->isValidUuid($shopUid)){
            return (new Service())->isValidUuid($shopUid);
        }
         $shop = Shop::where('uid', $shopUid)->first();
         $perpage = $request->query('perpage') ?? 15;
 
         if (!$shop) {
             return (new Service())->apiResponse(404, [], 'Shop not found');
         }

         
         $orders = Order::whereHas('order_details.ad', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })
        ->paginate($perpage);


            foreach ($orders as $order) {
                $order->ads = $this->getShopOrderAds($order->uid, $shopUid)->original['data']['ads'];
                $order->ads_number = $this->getShopOrderAds($order->uid, $shopUid)->original['data']['number'];
                $order->ad_image = File::whereReferencecode($this->getShopOrderAds($order->uid, $shopUid)->original['data']['ads'][0]->file_code)->first()->location;
                $order->statut =  TypeOfType::whereId($order->status)->first()->libelle;
            }

         return (new Service())->apiResponse(200, $orders, 'Orders grouped by status retrieved successfully');
 
     } catch (Exception $e) {
         return (new Service())->apiResponse(500, [], $e->getMessage());
     }
 }
 

 /**
 * @OA\Get(
 *     path="/api/shop/getShopOrderAds/{orderUid}/{shopUid}",
 *     tags={"Shop"},
 *     summary="Récupère les annonces d'une commande dans un magasin",
 *     description="Cette route permet de récupérer les annonces associées à une commande spécifique dans un magasin.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="orderUid",
 *         in="path",
 *         required=true,
 *         description="UID de la commande",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="shopUid",
 *         in="path",
 *         required=true,
 *         description="UID du magasin",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des annonces trouvées dans la commande",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="ads",
 *                 type="array",
 *                 @OA\Items(type="object", ref="")
 *             ),
 *             @OA\Property(
 *                 property="number",
 *                 type="integer",
 *                 description="Nombre d'annonces trouvées"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Commande ou magasin non trouvé",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Erreur interne du serveur"
 *             )
 *         )
 *     )
 * )
 */

public function getShopOrderAds($orderUid, $shopUid) {
    try {

        if((new Service())->isValidUuid($shopUid)){
            return (new Service())->isValidUuid($shopUid);
        }

        if((new Service())->isValidUuid($orderUid)){
            return (new Service())->isValidUuid($orderUid);
        }
        $data = [];

        $order = Order::whereUid($orderUid)->first();
        $shop = Shop::whereUid($shopUid)->first();

        if (!$order) {
            return (new Service())->apiResponse(404, [], 'Order not found');
        }

        if (!$shop) {
            return (new Service())->apiResponse(404, [], 'Shop not found');
        }

        foreach ($order->order_details as $detail) {
            $ad = Ad::whereDeleted(0)
                    ->whereId($detail->ad_id)
                    ->where('shop_id', $shop->id)
                    ->first();

            if ($ad) {
                $data[] = $ad;
            }
        }

        $datas = [
            'ads' => $data,
            'number' => count($data)
        ];

        return (new Service())->apiResponse(200, $datas, 'List of ads in order');

    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Get(
 *     path="/api/shop/getMerchantStatistic",
 *     tags={"Shop"},
 *     summary="Statistiques d'un marchand",
 *     description="Cette route fournit des statistiques globales pour un marchand, y compris les commandes, les clients, et les gains.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Statistiques du marchand",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="total_orders",
 *                 type="integer",
 *                 description="Nombre total de commandes"
 *             ),
 *             @OA\Property(
 *                 property="total_customers",
 *                 type="integer",
 *                 description="Nombre total de clients"
 *             ),
 *             @OA\Property(
 *                 property="total_earning",
 *                 type="number",
 *                 format="float",
 *                 description="Revenus totaux du marchand"
 *             ),
 *             @OA\Property(
 *                 property="merchant_earning_per_order",
 *                 type="array",
 *                 description="Gains du marchand par commande",
 *                 @OA\Items(type="object", ref="")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Erreur interne du serveur"
 *             )
 *         )
 *     )
 * )
 */


    public function getMerchantStatistic(){
        try {

            $userId = Auth::user()->id;

            $clients = Order::whereHas('order_details.ad', function($query) use ($userId) {
                $query->where('owner_id', $userId);
            })->pluck('user_id')->unique()->values()->toArray();

            $merchant_earning_per_order = $this->getMerchantEarningsPerOrder();

            $totalEarning = 0;

            foreach($merchant_earning_per_order as $earning){
                $totalEarning = $totalEarning + $earning['total_earnings'];
            }

            $data = [
                'total_orders' => count((new OrderController())->getMerchantOrder()->original['data']),
                'total_customers' => count($clients),
                'total_earning' => $totalEarning,
                'total_shop' =>count( (new ShopController())->userShop()->original['data'])
                // 'merchant_earning_per_order' =>$this->getMerchantEarningsPerOrder()
            ];

            return (new Service())->apiResponse(200, $data, 'Merchant statistics');
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function getMerchantEarningsPerOrder()
    {
        try {
            $service = new Service();
            $checkAuth = $service->checkAuth();
            if ($checkAuth) {
                return $checkAuth;
            }
    
            $checkIfMerchant = (new AdController())->checkMerchant();
            if ($checkIfMerchant == 0) {
                return response()->json([
                    'message' => 'You are not merchant'
                ], 200);
            }
    
            $merchantId = Auth::user()->id;
            $userShops = (new ShopController())->anUserShop($merchantId)->pluck('id')->toArray();
            if (empty($userShops)) {
                return response()->json([
                    'data' => 0
                ]);
            }
    
            $orderDetails = OrderDetail::whereIn('shop_id', $userShops)
                ->whereDeleted(false)
                ->with('ad') 
                ->get();
    
            $ordersEarnings = [];
    
            foreach ($orderDetails as $orderDetail) {
                if ($orderDetail->ad && $orderDetail->ad->owner_id == $merchantId) {
                    $orderId = $orderDetail->order_id;
    
                    if (!isset($ordersEarnings[$orderId])) {
                        $ordersEarnings[$orderId] = [
                            'order_id' => $orderId,
                            'total_earnings' => 0,
                            // 'order_details' => []
                        ];
                    }
    
                    $totalPrice = $orderDetail->price * $orderDetail->quantity; 
                    $ordersEarnings[$orderId]['total_earnings'] += $totalPrice;
                    // $ordersEarnings[$orderId]['order_details'][] = $orderDetail;
                }
            }
            return array_values($ordersEarnings);

        } catch (\Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
    





}
