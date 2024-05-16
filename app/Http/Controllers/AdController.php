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

class AdController extends Controller
{
 /**
 * Get All ads.
 *
 * @OA\Get(
 *      path="/api/ad/all",
 *      summary="Get all ads.",
 *   tags={"Ad"},
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

            // $db = DB::connection()->getPdo();
            // $query = "SELECT * FROM ads WHERE deleted = false";
            // $stmt = $db->prepare($query);
            // $stmt->execute();
            // $ads = $stmt->fetchAll($db::FETCH_ASSOC);
            return response()->json([
                'data' =>  Ad::with('ad_detail', 'file')
                ->orderBy('created_at', 'desc')
                ->where('deleted',false)->get()
            ]);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

 /**
 * Get recent ads.
 *
 * @OA\Get(
 *      path="/api/ad/recent/{perPage}",
 *      summary="Get recent ads.",
 *   tags={"Ad"},
 *   @OA\Parameter(
 *          in="path",
 *          name="perPage",
 *          required=true,
 *          description="number of ad per page.",
 *          @OA\Schema(type="string")
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
    public function getRecentAdd($perPage)
    {
        try {
            if($perPage > 50){
                $perPage = 50;
            }
            $products = Ad::with('ad_detail', 'file')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);


                  return response()->json([
                    'data' =>$products,
                  ]);
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

        /**
     * Get All ads.
     *
     * @OA\Get(
     *      path="/api/ad/marchand",
     *      summary="Get all seller ads.",
     * security={{"bearerAuth": {}}},
     *   tags={"Ad"},
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
    public function getMarchandAd()
    {
        try {

            // $db = DB::connection()->getPdo();
            // $query = "SELECT * FROM ads WHERE deleted = false AND owner_id=:id";
            // $stmt = $db->prepare($query);
            // $stmt->bindValue(':id',Auth::user()->id);
            // $stmt->execute();
            // $ads = $stmt->fetchAll($db::FETCH_ASSOC);
            return response()->json([
                'data' => Ad::with('ad_detail', 'file')
                ->orderBy('created_at', 'desc')
                ->where('deleted',false)->where('owner_id',Auth::user()->id)->get()
            ]);

        } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
        }
    }

//     /**
//  * @OA\Delete(
//  *     path="/api/ad/destroyAd/{uid}",
//  *     tags={"Ad"},
//  *     summary="Delete an ad",
//  *     description="Deletes an ad using its UID",
//  *     operationId="destroyAd",
//  *     security={{"bearerAuth":{}}},
//  *     @OA\Parameter(
//  *         name="uid",
//  *         in="path",
//  *         description="UID of the ad to delete",
//  *         required=true,
//  *         @OA\Schema(
//  *             type="string"
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=200,
//  *         description="Ad deleted successfully",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="message", type="string", example="Ad deleted successfully !")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=401,
//  *         description="Unauthorized - User not authenticated",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="message", type="string", example="Unauthenticated.")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=403,
//  *         description="Forbidden - User is not allowed to delete this ad",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="message", type="string", example="You can't delete an ad that you don't belong to")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=404,
//  *         description="Not Found - No ad found with the specified UID",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="message", type="string", example="No ad found with the specified UID")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Internal Server Error - An error occurred while deleting the ad",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="error", type="string", example="Internal Server Error")
//  *         )
//  *     )
//  * )
//  */


    public function destroyAd($uid)
    {
        try {
            $ad = Ad::where('uid',$uid)->first();

            if ($ad->owner_id != Auth::user()->id) {
                return response()->json([
                    'message' => "You can't delete a ad that you don't belong to"
                ]);
            }

            if($ad->deleted == true){
                return response()->json([
                    'message' => "You can't delete a ad that's already deleted"
                ]);
            }

            $db = DB::connection()->getPdo();

            $query = "UPDATE ads SET deleted = true WHERE uid = :uid";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':uid',$uid);
            $stmt->execute();

            return response()->json([
                'message' => 'ad deleted successfully !'
            ]);

        } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
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
            $db = DB::connection()->getPdo();

                $request->validate([
                    'title' => 'required',
                    'location_id' => 'required',
                    'category_id' => 'required',
                    'value_entered' => 'required|array',
                    'files' =>''
                ]);

                $help = new CategoryController();
                $randomString =  $help->generateRandomAlphaNumeric(15);
                $title =  htmlspecialchars($request->input('title'));
                $location_id =  htmlspecialchars($request->input('location_id'));
                $category_id =  htmlspecialchars($request->input('category_id'));
                $ulid = Uuid::uuid1();
                $ulidAd = $ulid->toString();
                $uid = $ulidAd;
                $owner_id = Auth::user()->id;
                $status = "pending";

                $ad = new Ad();
                $ad->title = $title;
                $ad->location_id = $location_id;
                $ad->category_id = $category_id;
                $ad->owner_id = $owner_id;
                $ad->validated_by_id = 1;
                $ad->validated_on ="2024-05-14 18:49:37";
                $ad->status = $status;
                $ad->uid = $ulidAd;
                $ad->file_code = $randomString;

                $existUserAd = Ad::where('title',$title)->where('owner_id',$owner_id)->exists();
                if($existUserAd){
                    return response()->json([
                        'message' => 'You already add this ad'
                    ]);
                }

              

                if($request->hasFile('files')){
                    foreach($request->file('files') as $index => $photo){
                        $size = filesize($photo);
                        $ulid = Uuid::uuid1();
                        $ulidPhoto = $ulid->toString();
                        $created_at = date('Y-m-d H:i:s');
                        $updated_at = date('Y-m-d H:i:s');
                        $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
                        $photoPath = $photo->move(public_path('image/photo_ad'), $photoName);
                        $photoUrl = url('/image/photo_ad/' . $photoName);
                        $type = $photo->getClientOriginalExtension();
                        $location = $photoUrl;
                        $referencecode = $randomString;
                        $filename = md5(uniqid()) . '.' . $type;
                        $uid = $ulidPhoto;
                        $q = "INSERT INTO files (filename, type, location, size, referencecode, uid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)";
                        $stmt = $db->prepare($q);
                        $stmt->bindParam(1, $filename);
                        $stmt->bindParam(2, $type);
                        $stmt->bindParam(3, $location);
                        $stmt->bindParam(4,  $size);
                        $stmt->bindParam(5,  $referencecode);
                        $stmt->bindParam(6,  $uid);
                        $stmt->bindParam(7,  $created_at);
                        $stmt->bindParam(8,  $updated_at);
                        $stmt->execute();
                    }
                }

                $category = Category::find($request->input('category_id'));
                $attributeGroups = AttributeGroup::where('group_title_id',$category->attribute_group_id)->get();
                $a = $request->input('value_entered');

                if (is_array($a) && count($a) === 1) {
                    $values = explode(",", $a[0]);
                    $c = count($values);
                    // return $values[3];

                    if ($attributeGroups->count() != $c) {

                        return response()->json([
                            'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".$c
                        ]);
                    }

                } else{

                    if ($attributeGroups->count() != count( $request->input('value_entered'))) {

                        return response()->json([
                            'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".count( $request->input('value_entered'))
                        ]);
                }
                }

                $ad->save();



                foreach ($attributeGroups as $index => $value) {
                    foreach(CategoryAttributes::where('id',$value->attribute_id)->get() as $d){
                        $ulid = Uuid::uuid1();
                        $ulidAdDetail = $ulid->toString();
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
                        $ad_detail->possible_value =$cat->possible_value ?? " a ";
                        $ad_detail->save();
                    }
                }

                return response()->json([
                    'message' => 'ad added successfully !'
                ]);

            

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
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


    public function editAd(Request $request,$uid){

         try {
            $db = DB::connection()->getPdo();

            $request->validate([
                'title' => 'nullable',
                'location_id' => 'nullable',
                'category_id' => 'nullable',
                'value_entered' => 'nullable|array',
            ]);

            $ad = Ad::where('uid',$uid)->first();
            if(!$ad){
                return response()->json([
                    'message' => ' ADd not found'
                ]);
            }
            $ad->title = $request->input('title') ?? $ad->title;
            $ad->location_id = $request->input('location_id') ?? $ad->location_id;
          

            if($request->has('category_id')){
                if($request->has('value_entered')){

                    $ad_details = AdDetail::where('ad_id',$ad->id)->get();
                    foreach($ad_details as $ad_detail){
                        $ad_detail->update(['deleted' => true]);
                    }


                $category = Category::find($request->input('category_id'));
                $attributeGroups = AttributeGroup::where('group_title_id',$category->attribute_group_id)->get();
                $a = $request->input('value_entered');

                if (is_array($a) && count($a) === 1) {
                    $values = explode(",", $a[0]);
                    $c = count($values);

                    if ($attributeGroups->count() != $c) {

                        return response()->json([
                            'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".$c
                        ]);
                    }

                } else{

                    if ($attributeGroups->count() != count( $request->input('value_entered'))) {

                        return response()->json([
                            'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".count( $request->input('value_entered'))
                        ]);
                }
                }

                $ad->save();


                    foreach ($attributeGroups as $index => $value) {
                        foreach(CategoryAttributes::where('id',$value->attribute_id)->get() as $c){
                            $ulid = Uuid::uuid1();
                            $ulidAdDetail = $ulid->toString();
                            $cat = CategoryAttributes::find($c->id);
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
                            $ad_detail->possible_value =$cat->possible_value ?? " a ";
                            $ad_detail->save();
                        }
                    }

                }else{
                    return response()->json([
                        'message' => 'Veuillez envoyer  les valeurs des attributs !'
                    ]);
                }

                    

            }

            return response()->json([
                'message' => 'ad edited successfully !'
            ]);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }



}
