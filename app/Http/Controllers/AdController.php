<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdDetail;
use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\CategoryAttributes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use App\Interfaces\Interfaces\FileRepositoryInterface;
use App\Models\Client;
use App\Models\Country;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ShopHasCategory;
use App\Models\Shop;
use App\Models\TypeOfType;
use App\Models\User;
use App\Services\Useful;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/ad/ads",
     *     tags={"Ad"},
     *     summary="Get all ads",
     *     description="Returns a list of all ads",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="")
     *         )
     *     )
     * )
     */
    public function allAds(): JsonResponse
    {
        $data = Ad::with('file')->with('ad_detail')->get();

        return(new Service())->apiResponse(200,$data,'');
    }

 /**
 * Get All ads.
 *
 * 
 * @OA\Get(
 *      path="/api/ad/all",
 *      summary="Get all ads.",
 *   tags={"Ad"},
 * security={{"bearerAuth": {}}},
 *      @OA\Response(
 *          response=200,
 *          description="Success. Return all ads.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="array",
 *                  @OA\Items(ref="")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Serveur error."
 *      )
 * )
 */
    public function getAllAd()
    {
        try {

            if(Auth::user()){
                return $this->getAllAdforAuth();
            }
            // return Auth::user();
            $data = [];
            $ads =  Ad::with('file')
            ->orderBy('created_at', 'desc')
            ->where('deleted',false) ->where('ads.statut',5)->get();

            foreach($ads as $ad){
                $ad->category_title =  Category::find($ad->category_id)->title;
                $ad->category_parent =  Category::find($ad->category_id)->parent_id;
                $ad->issynchronized = true ;

                if(File::where('referencecode',$ad->file_code)->exists()){
                    $ad->image = File::where('referencecode',$ad->file_code)->first()->location;
                }
                $data[] = $ad;
            }

            return(new Service())->apiResponse(200,$data, 'list of products');

        } catch (Exception $e) {
            // $error = 'An error occured';
            $error = $e->getMessage();
            return(new Service())->apiResponse(500,[],$error);
        }
    }

    public function getAllAdforAuth()
    {
        try {

            $validatedStatusId = TypeOfType::where('libelle', 'validated')->first()->id;
            $userId = Auth::user()->id;
            $ads = Ad::with('file')
                ->leftJoin('favorites', function ($join) use ($userId) {
                    $join->on('ads.id', '=', 'favorites.ad_id')
                        ->where('favorites.user_id', '=', $userId)
                        ->where('favorites.deleted', '=', false);
                })
                ->select('ads.*', DB::raw('CASE WHEN favorites.id IS NULL THEN false ELSE true END as is_favorite'))
                ->orderBy('created_at', 'desc')
                ->where('ads.deleted', false)
                ->where('ads.statut',$validatedStatusId)
                ->get();

                foreach($ads as $ad){
                    $ad->category_title =  Category::find($ad->category_id)->title ;
                    $ad->category_parent =  Category::find($ad->category_id)->parent_id;
                    $ad->issynchronized = true ;

                    if(File::where('referencecode',$ad->file_code)->exists() ==1){
                        $ad->image = File::where('referencecode',$ad->file_code)->first()->location;
                    }
                    $data[] = $ad;
                }

                return(new Service())->apiResponse(200,$data, 'List of all products to display for a logged-in user');

        } catch (Exception $e) {
             // $error = 'An error occured';
             $error = $e->getMessage();
             return(new Service())->apiResponse(500,[],$error);
        }
    }

    public function getAllRecentAdforAuth($perpage)
    {
        try {
            $validatedStatusId = TypeOfType::where('libelle', 'validated')->first()->id;
            $service = new Service();
    
            $checkAuth = $service->checkAuth();
    
            if ($checkAuth) {
                return $checkAuth;
            }
    
            $userId = Auth::user()->id;
    
            $ads = Ad::with(['file', 'category'])
                ->leftJoin('favorites', function ($join) use ($userId) {
                    $join->on('ads.id', '=', 'favorites.ad_id')
                        ->where('favorites.user_id', '=', $userId)
                        ->where('favorites.deleted', '=', false);
                })
                ->select('ads.*', DB::raw('CASE WHEN favorites.id IS NULL THEN false ELSE true END as is_favorite'))
                ->where('ads.deleted', false)
                ->where('ads.statut',$validatedStatusId)
                ->orderBy('ads.created_at', 'desc')
                ->paginate($perpage);

            foreach ($ads as $ad) {
                $ad->issynchronized = true ;
                $ad->category_title =  Category::find($ad->category_id)->title ;
                $ad->category_parent =  Category::find($ad->category_id)->parent_id;
                $ad->image = File::where('referencecode',$ad->file_code)->first()->location;
            }
    
            return response()->json([
                'data' => $ads,
                 'status' => 'success',
                'message' => 'A paginated list of all products to be displayed to a logged-in user'
            ],200);

        } catch (Exception $e) {
            return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
    }

 /**
 * Get recent ads.
 *
 * @OA\Get(
 *      path="/api/ad/all/{perpage}",
 *      summary="Get recent ads.",
 *  security={{"bearerAuth": {}}},
 *   tags={"Ad"},
 *   @OA\Parameter(
 *          in="path",
 *          name="perpage",
 *          required=true,
 *          description="number of ad per page.",
 *          @OA\Schema(type="integer")
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Success. Return all recent ads.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="array",
 *                  @OA\Items(ref="")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Serveur error."
 *      )
 * )
 */
public function getRecentAdd(Request $request,$perpage)
{
    try {
        if (Auth::user()) {
            return $this->getAllRecentAdforAuth($perpage);
        }

        $ads = Ad::with(['file', 'category'])
            ->where('deleted', false)
            ->where('statut', TypeOfType::where('libelle','validated')->first()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perpage);

        foreach ($ads as $ad) {
            $ad->category_title =  Category::find($ad->category_id)->title ;
            $ad->image = File::where('referencecode',$ad->file_code)->first()->location;
            $ad->category_parent =  Category::find($ad->category_id)->parent_id;
            $ad->issynchronized = true ;
        }

        return (new Service())->apiResponse(200,$ads,'A paginated list of all products to be displayed to a user');

        // return response()->json([
        //     'data' => $ads,
        //     'status' => 'success',
        //     'message' => 'A paginated list of all products to be displayed to a user'
        // ]);
    } catch (Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

        /**
     * Get All ads.
     *
     * @OA\Get(
     *      path="/api/ad/marchand/{perpage}",
     *      summary="Get all seller ads.",
     * security={{"bearerAuth": {}}},
     *   tags={"Ad"},
     * @OA\Parameter(
 *         name="perpage",
 *         in="path",
 *         description="number of element perpage",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Success. Return all seller ads.",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Serveur error."
     *      )
     * )
     */
    public function getMarchandAd($perPage)
    {
        try {

                $ads = Ad::with(['ad_detail', 'file'])
                    ->orderBy('created_at', 'desc')
                    ->where('deleted', false)
                    ->where('owner_id', Auth::user()->id)
                    ->paginate($perPage);

                    foreach ($ads as $ad) {
                    $ad->category_title = Category::find($ad->category_id)->title;

                    if (File::where('referencecode', $ad->file_code)->exists()) {
                        $ad->image = File::where('referencecode', $ad->file_code)->first()->location;
                    }
                }

                return (new Service())->apiResponse(200,$ads,'List of all products to display for a logged-in user');

                // return response()->json([
                //     'data'=> $ads,
                //     'status' => 'success',
                //     'message' => 'List of all products to display for a logged-in user'
                // ], 200);

        } catch (Exception $e) {
            return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
    }

    /**
 * @OA\Delete(
 *     path="/api/ad/destroyAd/{uid}",
 *     tags={"Ad"},
 *     summary="Delete an ad",
 *     description="Deletes an ad using its UID",
 *     operationId="destroyAd",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="UID of the ad to delete",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad deleted successfully !")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - User not authenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - User is not allowed to delete this ad",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You can't delete an ad that you don't belong to")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found - No ad found with the specified UID",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No ad found with the specified UID")
 *         )
 *     ),
 *  @OA\Response(
 *         response=203,
 *         description="You can't delete a ad because it belong to a order",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No ad found with the specified UID")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error - An error occurred while deleting the ad",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */


    public function destroyAd($uid)
    {
        try {

            $ad = Ad::where('uid',$uid)->first();

            if (!$ad) {
                // return response()->json([
                //     'message' => "Ad not found"
                // ],404);

                return (new Service())->apiResponse(404,[],'Ad not found');
            }

            if($ad->deleted == true){
                return(new Service())->apiResponse(404,[], "You can't delete a ad that's already deleted");
                // return response()->json([
                //     'message' => "You can't delete a ad that's already deleted"
                // ],202);
            }

            if ($ad->owner_id != Auth::user()->id) {
                return (new Service())->apiResponse(404,[],"You can't delete a ad that you don't belong to");
                // return response()->json([
                //     'message' => "You can't delete a ad that you don't belong to"
                // ],403);
            }

            $order_details = OrderDetail::where('ad_id',$ad->id)->whereDeleted(0)->get();

            foreach($order_details  as $order_detail){
                if(Order::find($order_detail->order_id)->status != TypeOfType::whereLibelle('validated')->first()->id){
                    return (new Service())->apiResponse(404,[],'ad list grouped by category');
                    // return response()->json([
                    //     'message' => "You can't delete a ad because it belong to a order "
                    // ],203);
                }
            }


            $db = DB::connection()->getPdo();

            $query = "UPDATE ads SET deleted = true WHERE uid = :uid";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':uid',$uid);
            $stmt->execute();

            return (new Service())->apiResponse(200,[],'ad deleted successfully !');

            // return response()->json([
            //     'message' => 'ad deleted successfully !'
            // ]);

        } catch (Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
    }


   /**
 * @OA\Post(
 *     path="/api/ad/storeAd",
 *     summary="Create a new ad",
 *     security={{"bearerAuth": {}}},
 *     tags={"Ad"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="title", type="string", example="Ad title"),
 *                 @OA\Property(property="location_id", type="integer", example=1),
 *                 @OA\Property(property="category_id", type="integer", example=1),
 *                 @OA\Property(property="shop_id", type="integer", example=1),
 *  @OA\Property(property="price", type="double", example=1),
 *               @OA\Property(
 *                     property="value_entered[]",
 *                     type="array",
 *                     @OA\Items(type="string", example="value1"),
 *                     example={"value1", "value2", "value3"}
 *                 ),
 *                 @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad added successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error or bad request",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Validation error message")
 *         )
 *     )
 * )
 */

 public function storeAd(Request $request){
    try {


      $service = new Service();

      $checkAuth=$service->checkAuth();
      if($checkAuth){
         return $checkAuth;
      }
      
        $checkIfmerchant = $this->checkMerchant();

        if($checkIfmerchant ==0){
            return (new Service())->apiResponse(200,[],'You are not merchant');
            // return response()->json([
            //     'message' => 'You are not merchant'
            //     ],200);
                }

        $checkCategoryShop = $this->checkCategoryShop($checkIfmerchant,$request);
        if($checkCategoryShop){
            return $checkCategoryShop;
        }

        $checkShop = $this->checkShop($request->shop_id);
        if($checkShop){
            return $checkShop;
        }


        $this->validateRequest($request);

        if ($request->price <= 0) {
            return (new Service())->apiResponse(200,[],'The price must be greater than 0');
            // return response()->json([
            //     'message' => 'The price must be greater than 0'
            //     ],200);
          }

     

         $validateLocation=$service->validateLocation($request->location_id);
         if($validateLocation){
            return $validateLocation;
         }

         $validateCategory = $service->validateCategory($request->category_id);
         if($validateCategory){
            return $validateCategory;
         }

         if(Category::find($request->category_id)->parent_id == null){
            return (new Service())->apiResponse(404,[],'Ad must be associated with a subcategory.');
            // return response()->json([
            //     'message' =>"The product must be associated with a subcategory."
            // ],200);
        }

        $checkFile = $service->checkFile($request);
        if($checkFile){
            return $checkFile;
         }

         

    foreach($request->file('files') as $photo){
        $errorvalidateFile = $service->validateFile($photo);
        if($errorvalidateFile){
            return $errorvalidateFile;
        }
    }

    $checkAdAttribute = $service->checkAdAttribute($request,$request->input('category_id'));
    if($checkAdAttribute){
        return $checkAdAttribute;
     }
     $checkNumberProductStore = $this->checkNumberProductStore($request);


     if($checkNumberProductStore === 0){
        return (new Service())->apiResponse(404,[],"You've reached your limit of products on the store");
        // return response()->json([
        //     'message' => "You've reached your limit of products on the store"
        // ],200);
     }


         $ad = $this->createAd($request);

        //  return $ad;



        $service->uploadFiles($request, $ad->file_code,'ad');

        $this->saveAdDetails($request, $ad);

        $title= "Confirmation of Your Product Shipment";
        $body ="Your product has been registered, please wait while an administrator analyzes it. We'll let you know what happens next. Thank you!";

        $titleAdmin = " New Product Awaiting Validation ";
        $bodyAdmin = "A new product has just been added to the system. Please log in to your account to review and validate the product. Your prompt attention is required. Thank you!";

        $service->notifyAdmin($titleAdmin,$bodyAdmin);


      $message = new MailController();
      $mes =  $message->sendNotification(Auth::user()->id,$title,$body, 'ad added successfully !');

      if($mes){
        return (new Service())->apiResponse(200,[],$mes->original['message']);
        // return response()->json([
        //       'message' =>$mes->original['message']
        // ]);
      }

    } catch (Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

public function checkShop($shop_id){
    try {
        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        $client = Client::where('person_id',$person->id)->first();

        if(!Shop::whereId($shop_id)->where('client_id',$client->id)->exists()){
            return (new Service())->apiResponse(404,[],'Check if this shop is yours');
            // return response()->json([
            //     'message' => "Check if this shop is yours"
            // ]);
        }
    } catch (\Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

public function checkMerchant(){
    try {
        // return(Auth::user());
        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        $client = Client::where('person_id',$person->id)->first();


        if($client->is_merchant == false){
          return 0;
        }

        return $client->id;
    } catch (\Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

public function checkCategoryShop($ownerId, Request $request){
    try {
        $shop = Shop::where('client_id',$ownerId)->first();

        $exist = ShopHasCategory::where('shop_id',$shop->id)->where('category_id',$request->input('category_id'))->whereDeleted(false)->exists();

        if(!$exist){
            return (new Service())->apiResponse(404,[],'You can only add the categories added to your shop');
            // return response()->json([
            //     'message' =>'You can only add the categories added to your shop'
            // ],200);
        }
    } catch (\Exception $e) {
        return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

    /**
 * @OA\Post(
 *     path="/api/ad/editAd/{uid}",
 *     summary="Edit an existing ad",
 *     security={{"bearerAuth": {}}},
 *     tags={"Ad"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="UID of the ad to edit",
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
 *                 @OA\Property(property="title", type="string", example="New ad title"),
 *                 @OA\Property(property="location_id", type="integer", example=1),
 *                 @OA\Property(property="category_id", type="integer", example=1),
 * @OA\Property(property="price", type="float", example=1),
 *                 @OA\Property(property="value_entered[]", type="array", @OA\Items(type="string"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad edited successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad edited successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error or bad request",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Validation error message")
 *         )
 *     )
 * )
 */

 public function editAd(Request $request, $uid){
    try {

        $service = new Service();

        $checkAuth=$service->checkAuth();
        if($checkAuth){
           return $checkAuth;
        }
  

        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        $client = Client::where('person_id',$person->id)->first();

        if($client->is_merchant == false){
            return(new Service())->apiResponse(404,[], 'You cannot add a ad');
            // return response()->json([
            //     'data' =>'You cannot add a ad'
            // ],200);
        }

        $shop = Shop::where('client_id',$client->id)->first();

        $exist = ShopHasCategory::where('shop_id',$shop->id)->where('category_id',$request->input('category_id'))->whereDeleted(false)->exists();

        if(!$exist){

            return (new Service())->apiResponse(404,[],'You can only add the categories added to your shop');
            // return response()->json([
            //     'message' =>'You can only add the categories added to your shop'
            // ],200);
        }
        $existAd = Ad::where('uid',$uid)->first();
        if(!$existAd){
            return (new Service())->apiResponse(404,[],'Ad not found');
            // return response()->json([
            //     'message' => ' Ad not found'
            // ]);
        }

        $this->validateRequest($request);

        if ($request->price < 0) {
            return (new Service())->apiResponse(404,[],'The price must be greater than 0');
            // return response()->json([
            //     'message' => 'The price must be greater than 0'
            //     ],200);
          }

        $service = new Service();

         $validateLocation=$service->validateLocation($request->location_id);
         if($validateLocation){
            return $validateLocation;
         }

        if($existAd->owner_id != Auth::user()->id){
            return (new Service())->apiResponse(404,[],'Your are not allowed');
            // return response()->json([
            //     'error' => 'Your are not allowed'
            // ]);
        }

       

        $ad = $this->updateAd($request,$existAd);

        if($ad->deleted == true){
            return (new Service())->apiResponse(404,[],'Your cannot edit a ad deleted');
            // return response()->json([
            //     'message' => 'Your cannot edit a ad deleted'
            // ]);
        }

        if(($request->has('category_id'))){

            if($request->has('value_entered')){

                $validateCategory = $service->validateCategory($request->category_id);
                if($validateCategory){
                   return $validateCategory;
                }

                $checkAdAttribute = $service->checkAdAttribute($request,$request->input('category_id'));
                if($checkAdAttribute){
                    return $checkAdAttribute;
                 }

                $ad_details = AdDetail::where('ad_id',$ad->id)->get();
                foreach($ad_details as $ad_detail){
                    $ad_detail->update(['deleted' => true]);
                }

            $this->saveAdDetails($request, $ad);

            }else{

                return(new Service())->apiResponse(404,[], 'Veuillez envoyer  les valeurs des attributs !');
                // return response()->json([
                //     'message' => 'Veuillez envoyer  les valeurs des attributs !'
                // ]);
            }
        }

        return(new Service())->apiResponse(200,[], 'ad edited successfully !');
        // return response()->json([
        //     'message' => 'ad edited successfully !'
        // ]);

    } catch (Exception $e) {
       return response()->json([
        'error' => $e->getMessage()
       ]);
    }
   }


    

    // try {
          
    // } catch (Exception $e) {
    //    return response()->json([
    //     'error' => $e->getMessage()
    //    ]);
    // }


   
    
    private function validateRequest(Request $request){

           if (!$request->validate([
                'title' => 'required',
                'location_id' => 'required',
                'category_id' => '',
                'value_entered' => 'required|array',
                'files' =>'',
                'price' => 'required',
            ])){
                $e = new Exception();
                return response()->json([
                    'error' => $e->getMessage()
                ]);
            }else{

            }

        }

    private function createAd(Request $request){

        $title = htmlspecialchars($request->input('title'));
        $price = htmlspecialchars($request->input('price'));
        $location_id = htmlspecialchars($request->input('location_id'));
        $category_id = htmlspecialchars($request->input('category_id'));

        $shop_id = htmlspecialchars($request->input('shop_id'));
        $shop = Shop::whereId($shop_id)->first();
        $owner_id = Auth::user()->id;
        $statut = TypeOfType::whereLibelle('pending')->first()->id;
        $percent = $shop->commission/100;
        $final_price = ($percent*$price) + $price;


        $service = new Service();

        $existUserAd = Ad::where('title',$title)->where('owner_id',$owner_id)->exists();
        if($existUserAd){
            return(new Service())->apiResponse(404,[], "You already added this ad");
            // return response()->json([
            //     'message' =>'You already added this ad'
            // ],200);
        }


        $ad = new Ad();
        $ad->title = $title;
        $ad->ad_code = $service->generateRandomAlphaNumeric(7,$ad,'ad_code');
        $ad->location_id = $location_id;
        $ad->category_id = $category_id;
        $ad->shop_id = $shop_id;
        $ad->owner_id = $owner_id;
        $ad->statut = $statut;
        $ad->price = $price;
        $ad->final_price = $final_price;
        $ad->uid = $service->generateUid($ad);

        $randomString = $service->generateRandomAlphaNumeric(7,$ad,'file_code');
        $ad->file_code = $randomString;
        $ad->save();

        return $ad;
    }

    private function checkNumberProductStore(Request $request){
        try {
            $n = Ad::where('shop_id',$request->shop_id)->where('owner_id',Auth::user()->id)->count();

            $limitOfAdInStore = TypeOfType::whereLibelle("limitOfAdInStore")->first()->codereference;

            if($n >=$limitOfAdInStore ){
                return 0;
            }

        } catch (Exception $e) {
            return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
    }

    private function saveAdDetails(Request $request, Ad $ad){
        $category = Category::find($request->input('category_id'));
        $attributeGroups = AttributeGroup::where('group_title_id',$category->attribute_group_id)->get();
        $values = $request->input('value_entered');
        $a = $request->input('value_entered');
    
        if (is_array($a) && count($a) === 1) {
            $values = explode(",", $a[0]);
            $c = count($values);
           
        }
    
        foreach ($attributeGroups as $index => $value) {
            foreach(CategoryAttributes::where('id',$value->attribute_id)->get() as $d){
                $ulidAdDetail = Uuid::uuid1()->toString();
                $cat = CategoryAttributes::find($d->id);
                $ad_detail = new AdDetail();
                $ad_detail->ad_id = $ad->id;
                $ad_detail->fieldtype = $cat->fieldtype;
                $ad_detail->label = $cat->label;
                $ad_detail->possible_value = $cat->possible_value;
                $ad_detail->description = $cat->description;
                $ad_detail->isrequired = $cat->isrequired;
                $ad_detail->order_no = $cat->order_no;
                $ad_detail->is_price_field = $cat->is_price_field;
                $ad_detail->is_crypto_price_field = $cat->is_crypto_price_field;
                $ad_detail->search_criteria = $cat->search_criteria;
                $ad_detail->is_active = $cat->is_active;
                $ad_detail->fieldtype = $cat->fieldtype;
                $ad_detail->uid = $ulidAdDetail;
                if (is_array($a) && count($a) === 1) {
                    $values = explode(",", $a[0]);
                    $ad_detail->value_entered = $values[$index];
                } else{
                    $ad_detail->value_entered = $request->input('value_entered')[$index];
                }
                $ad_detail->possible_value =$cat->possible_value ?? "null";
                $ad_detail->save();
            }
        }
    }

   public function updateAd(Request $request, $existAd){

    $title = htmlspecialchars($request->input('title'));
    $location_id = htmlspecialchars($request->input('location_id'));
    $price = htmlspecialchars($request->input('price'));
    $owner_id = Auth::user()->id;


    $existAd->title = $title ?? $existAd->title;
    $existAd->location_id = $location_id ?? $existAd->location_id;
    $existAd->price = $price ?? $existAd->price;
    // $existAd->category_id = $category_id ?? $existAd->category_id;
    $existUserAd = Ad::where('title',$title)->where('owner_id',$owner_id)->exists();
        if($existUserAd){
            // throw new \Exception('You already added this ad');
            return (new Service())->apiResponse(404,[],'You already added this ad');
        }

    $existAd->save();

    return $existAd;

   }

/**
 * @OA\Post(
 *     path="/api/ad/validateAd/{uid}",
 *     summary="Validate an ad",
 *     tags={"Ad"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="UID of the ad",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent()
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad validated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad validated successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ad not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Statut of ad must be pending. Please, check it !",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Statut of ad must be pending. Please, check it !")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error message")
 *         )
 *     )
 * )
 */

   public function validateAd(Request $request,$uid){
    try {

        //todo: check if a person who make this action is an admin

        $ad = Ad::where('uid',$uid)->first();
        // dd($ad->validated_by_id);
        if(!$ad){
            return (new Service())->apiResponse(404,[],' Ad not found');
            // return response()->json([
            //     'message' => ' Ad not found'
            // ],404);
        }

        if($ad->statut != TypeOfType::whereLibelle('pending')->first()->id){
            return (new Service())->apiResponse(404,[],'Statut of ad must be pending. Please, check it !');
            // return response()->json([
            //     'message' => 'Statut of ad must be pending. Please, check it !'
            // ]);
        }

        $ad->statut = TypeOfType::whereLibelle('validated')->first()->id;
        $ad->validated_on = now();
        $ad->validated_by_id = Auth::user()->id;
        $ad->save();

        return (new Service())->apiResponse(404,[],'Ad validated successfully!');

        // return response()->json([
        //     'message' => 'Ad validated successfully!'
        // ]);
    } catch (\Exception $e) {
         return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
   }


   /**
 * Reject an ad.
 *
 * @param Request $request The request object.
 * @param string $uid The UID of the ad.
 * @return \Illuminate\Http\JsonResponse The JSON response.
 *
 * @OA\Post(
 *     path="/api/ad/rejectAd/{uid}",
 *     tags={"Ad"},
 *     security={{"bearerAuth": {}}},
 *     summary="Reject an ad",
 *     operationId="rejectAd",
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="The UID of the ad",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="reject_reason",
 *                     type="string",
 *                     description="The reason for rejecting the ad"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Ad rejected successfully!"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Ad not found"
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
 *                 example="Internal server error"
 *             )
 *         )
 *     )
 * )
 */
   public function rejectAd(Request $request,$uid){
    try {

        //  todo: check if a person who make this action is an admin

        $request->validate([
            'reject_reason' => 'required'
        ]);
        $ad = Ad::where('uid',$uid)->first();
        if(!$ad){
            return (new Service())->apiResponse(404,[],'Ad not found');
            // return response()->json([
            //     'message' => ' Ad not found'
            // ]);
        }

        if($ad->statut != TypeOfType::whereLibelle('pending')->first()->id){
            return (new Service())->apiResponse(404,[],'Statut of ad must be pending. Please, check it !');
            // return response()->json([
            //     'message' => 'Statut of ad must be pending. Please, check it !'
            // ]);
        }

        $ad->validated_by_id = Auth::user()->id;
        $ad->validated_on = now();
        $ad->statut = TypeOfType::whereLibelle('rejected')->first()->id;
        $ad->reject_reason = $request->input('reject_reason');
        $ad->save();

        return (new Service())->apiResponse(404,[],'Ad rejected successfully!');

        // return response()->json([
        //     'message' => 'Ad rejected successfully!'
        // ]);

    } catch (\Exception $e) {
         return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
   }

   public function checkIfAdIsPending($adUid){
        try {

            $a =  Ad::where('uid',$adUid)->first()->statut ;
            $b =TypeOfType::whereLibelle('pending')->first()->id;
    
            return $a == $b?1:0;

        } catch(Exception $e){
            return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
   }

   public function checkIfAdIsRejected($adUid){
    try {

        $a =  Ad::where('uid',$adUid)->first()->statut ;
        $b =TypeOfType::whereLibelle('rejected')->first()->id;

        return $a == $b?1:0;

    } catch(Exception $e){
         return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}

public function checkIfAdIsValidated($adUid){
    try {

        $a =  Ad::where('uid',$adUid)->first()->statut ;
        $b =TypeOfType::whereLibelle('validated')->first()->id;

        return [$a,$b];

        // return $a == $b?1:0;
    } catch(Exception $e){
         return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}


    /**
     * @OA\Get(
     *     path="/api/ad/getAdDetail/{adUid}",
     *     summary="Get ad details",
     *     description="Retrieve detailed information about an advertisement by its UID.",
     *     tags={"Ad"},
     *  security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="adUid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="UID of the advertisement"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="title", type="string", example="Ad Title"),
     *                     @OA\Property(property="ad_code", type="string", example="AD123456"),
     *                     @OA\Property(property="final_price", type="number", format="float", example=99.99),
     *       @OA\Property(property="description", type="string", example="description"),
     *                     @OA\Property(property="image", type="string", example="http://example.com/image.jpg"),
     *                     @OA\Property(property="owner_uid", type="string", example="UID12345"),
     *                     @OA\Property(property="shop_uid", type="string", example="SHOP12345"),
     *                     @OA\Property(property="shop_title", type="string", example="Shop Title"),
     *                     @OA\Property(property="files", type="array", @OA\Items(ref="")),
     *                     @OA\Property(property="category_uid", type="string", example="CAT12345"),
     *                     @OA\Property(property="category_title", type="string", example="Category Title"),
     *                     @OA\Property(property="attributes", type="array", @OA\Items(ref=""))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ad not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ad not found!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */

public function getAdDetail($adUid){
    try {
        $ad =  Ad::where('uid',$adUid)->first() ;

           if(!$ad){
            return (new Service())->apiResponse(404,[],'Ad not found !');
            // return response()->json([
            //     'message' => 'Ad not found !'
            // ]);
        }

        $attributes = AdDetail::where('ad_id',$ad->id)->whereDeleted(false)->get();


        $data[] = [
            'ad_id' => $ad->id,
            'title' => $ad->title,
            'ad_code' => $ad->ad_code,
            'final_price' => $ad->final_price,
            'description' => $ad->description,
            'image' =>  File::where('referencecode',$ad->file_code)->first()->location,
            'owner_uid' =>  User::find($ad->owner_id)->uid,
            'shop_uid' => Shop::find($ad->shop_id)->uid,
            'shop_title' => Shop::find($ad->shop_id)->title,
            'files' =>  File::whereReferencecode($ad->file_code)->whereDeleted(0)->get(),
            'category_uid' =>  Category::find($ad->category_id)->uid,
            'category_title' =>Category::find($ad->category_id)->title,
            'attributes' =>  $attributes,
            'issynchronized' =>true
        ];

     

        return response()->json([
            'data' => $data
        ]);

    } catch(Exception $e){
         return  (new Service())->apiResponse(500,[],$e->getMessage());
    }
}
/**
 * @OA\Get(
 *     path="/api/ad/getAdBySubCategory/{categoryUid}",
 *     tags={"Ad"},
 *     summary="Get ads by subcategory",
 *     description="Retrieve a list of ads grouped by category",
 *     @OA\Parameter(
 *         name="categoryUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad list grouped by category",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(
 *                         property="id",
 *                         type="integer"
 *                     ),
 *                     @OA\Property(
 *                         property="title",
 *                         type="string"
 *                     ),
 *                     @OA\Property(
 *                         property="category",
 *                         type="object",
 *                         @OA\Property(
 *                             property="id",
 *                             type="integer"
 *                         ),
 *                         @OA\Property(
 *                             property="name",
 *                             type="string"
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Category not found or subcategory required",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */

    public function getAdBySubCategory($categoryUid,$perPage=5){
        try {

            $category = Category::whereUid($categoryUid)->first();
            if(!$category){
                return (new Service())->apiResponse(404,[],'Category not found');
            }

            if(is_null($category->parent_id)){
                return (new Service())->apiResponse(404,[],'You must enter a subcategory');
            }

            $ads = Ad::where('category_id',$category->id)
            ->whereDeleted(0)
            ->with(['ad_detail', 'file'])
            ->with('category')
            ->paginate($perPage);

            return (new Service())->apiResponse(200,$ads,'ad list grouped by category');

        } catch(Exception $e){
        return  (new Service())->apiResponse(500,[],$e->getMessage());
        }
    }
/**
 * @OA\Post(
 *     path="/api/ad/addInventoryToAd/{adUid}",
 *     tags={"Ad"},
 * security={{"bearerAuth": {}}},
 *     summary="Add inventory to ad",
 *     description="Add inventory to an existing ad",
 *     @OA\Parameter(
 *         name="adUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="quantity",
 *                 type="integer",
 *             ),
 *             @OA\Property(
 *                 property="threshold",
 *                 type="integer",
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Inventory added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ad not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=405,
 *         description="Quantity must be an integer",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=406,
 *         description="Threshold must be an integer",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */

    public function addInventoryToAd(Request $request, $adUid) {
        try {
            $ad = Ad::whereUid($adUid)->first();
            if (!$ad) {
                return (new Service())->apiResponse(404, [], 'Ad not found');
            }

            
            if($ad->owner_id ==! Auth::user()->id){
                return (new Service())->apiResponse(404, [], "This ad it's not yours");
            }
            
            $requiresQuantity = is_null($ad->quantity);
            $requiresThreshold = is_null($ad->threshold);

            $request->validate([
                'quantity' => $requiresQuantity ? 'required|integer' : 'nullable|integer',
                'threshold' => $requiresThreshold ? 'required|integer' : 'nullable|integer',
            ]);

            if ($request->has('quantity')) {
                if (!is_int($request->quantity)) {
                    return (new Service())->apiResponse(200, [], 'Quantity must be an integer');
                }

                if($request->quantity<=0){
                    return (new Service())->apiResponse(404, [], 'Quantity must be strictly positive');
                }
                $ad->quantity = $request->quantity;
            }

            if ($request->has('threshold')) {
                if (!is_int($request->threshold)) {
                    return (new Service())->apiResponse(404, [], 'Threshold must be an integer');
                }

                if($request->threshold<=0){
                    return (new Service())->apiResponse(404, [], 'Threshold must be strictly positive');
                }
                $ad->threshold = $request->threshold;
            }

            $ad->save();
            return (new Service())->apiResponse(200, [], 'Inventory updated successfully');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function incrementQuantity(Request $request, $adUid) {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);
    
            $ad = Ad::whereUid($adUid)->first();
            if (!$ad) {
                return (new Service())->apiResponse(200, [], 'Ad not found');
            }
    
            $ad->quantity += $request->quantity;
            $ad->save();
    
            return (new Service())->apiResponse(200, [], 'Quantity incremented successfully');
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function decrementQuantity(Request $request, $adUid) {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);
    
            $ad = Ad::whereUid($adUid)->first();
            if (!$ad) {
                return (new Service())->apiResponse(200, [], 'Ad not found');
            }
    
            if ($ad->quantity < $request->quantity) {
                return (new Service())->apiResponse(200, [], 'Insufficient quantity in stock');
            }
    
            $ad->quantity -= $request->quantity;
            $ad->save();
    
            return (new Service())->apiResponse(200, [], 'Quantity decremented successfully');
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
    


}
