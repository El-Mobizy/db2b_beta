<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Ad;
use App\Models\Category;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FavoriteController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/favorite/addAdToFavorite",
 *     summary="add new ad to favorite",
 *     tags={"Favorite"},
 *     security={{ "bearerAuth":{} }},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer",
 *                     description="Get specific ID of ad"
 *                 ),
 *                 example={"ad_id": 123}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="add added successfully"
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Requête invalide"
 *     ),
 *     @OA\Response(
 *         response="401",
 *         description="unauthorized"
 *     ),
 *     @OA\Response(
 *         response="500",
 *         description="error server"
 *     )
 * )
 */

    public function addAdToFavorite(Request $request)
    {
        try {
                $request->validate([
                    'ad_id' => 'required',
                ]);

                $ad_id = htmlspecialchars($request->input('ad_id'));
                $user_id = Auth::user()->id;
                $exist = Favorite::where('ad_id',$ad_id)->where('user_id',$user_id)->where('deleted',false)->exists();
                if($exist){
                    return response()->json([
                        'message' => 'This add already exist in your favorites'
                    ]);
                }
                $ulid = Uuid::uuid1();
                $ulidFavorite = $ulid->toString();
                $uid = $ulidFavorite;
                $created_at = date('Y-m-d H:i:s');
                $updated_at = date('Y-m-d H:i:s');

                $db = DB::connection()->getPdo();

                $query = " INSERT INTO favorites (ad_id, user_id, uid, created_at, updated_at) VALUES (?,?,?,?,?) ";

                $statement = $db->prepare($query);

                $statement->bindParam(1, $ad_id);
                $statement->bindParam(2, $user_id);
                $statement->bindParam(3, $uid);
                $statement->bindParam(4,  $created_at);
                $statement->bindParam(5,  $updated_at);

                $statement->execute();

                return response()->json([
                    'message' => 'Ad added successfully !'
                ]);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }


    /**
     * @OA\Get(
     *     path="/api/favorite/GetFavoritesAd",
     *     summary="Retrieve favorite ads for the current user",
     *     description="Retrieve a list of favorite ads for the currently authenticated user",
     *     tags={"Favorite"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of favorite ads",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="")
     *         )
     *     )
     * )
     */

   

    public function GetFavoritesAd()
    {
        try {

            $service = new Service();

            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            $db = DB::connection()->getPdo();
            $user_id = Auth::user()->id;

            $query = "SELECT favorites.*,
            ads.id AS ad_id, 
            ads.category_id AS ad_category_id,
            ads.owner_id AS ad_owner_id,
            ads.location_id AS ad_location_id,
            -- ads.validated_by_id AS ad_validated_by_id,
            ads.title AS ad_title, 
            ads.statut AS ad_status, 
            ads.file_code AS ad_file_code, 
            -- ads.reject_reason AS ad_reject_reason, 
            -- ads.validated_on AS ad_validated_on, 
            -- ads.created_at AS ad_created_at,
            -- ads.updated_at AS ad_updated_at,
            ads.uid AS ad_uid
            FROM favorites
            INNER JOIN ads ON favorites.ad_id = ads.id 
            WHERE favorites.user_id = :user_id 
            AND ads.deleted = false";


            $statement = $db->prepare($query);
            $statement->bindParam(':user_id', $user_id);
            $statement->execute();
            $favorites = $statement->fetchAll($db::FETCH_ASSOC);

            foreach($favorites as $favorite){
                $favorite['category_title'] = Category::whereId($favorite['ad_category_id'])->first()->title;
                $favorite['image'] = File::where('referencecode',$favorite['ad_file_code'])->first()->location;
                // return $favorite;

                $data[] = $favorite;
            }

            return response()->json([
                'data' => $data
            ]);
            
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }


    /**
 * @OA\Delete(
 *     path="/api/favorite/RemoveAdFromFavoriteList/{id}",
 *     summary="Remove an ad from the favorite list",
 *     tags={"Favorite"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the ad to remove from the favorite list",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ad removed from favorite successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="ad remove from favorite successfully !")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ad not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized to remove the ad",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You can't remove an ad from another favorite list")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     ),
 *     security={
 *         {"bearerAuth": {}}
 *     }
 * )
 */

    public function RemoveAdFromFavoriteList($id)
    {
        try {
            $db = DB::connection()->getPdo();
            $user_id = Auth::user()->id;
            $blur = Ad::find($id);

            // return [Auth::user()->id, ]

            if(!$blur){
                return response()->json([
                    'message' => "Not found"
                ]);
            }

            if( Favorite::where('user_id',$user_id)->where('ad_id',$blur->id)->first()->user_id != $user_id){
                return response()->json([
                    'message' => "You can't remove an ad from another favorite list"
                ]);
            }

         Favorite::where('user_id',$user_id)->where('ad_id',$blur->id)->first()->delete();
            return response()->json([
                'message' => 'ad remove from favorite successfully !'
            ]);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

   
}
