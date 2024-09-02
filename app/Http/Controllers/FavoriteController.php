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
 *     path="/api/favorite/addAdToFavorite/{adId}",
 *     summary="add new ad to favorite",
 *     tags={"Favorite"},
 *     security={{ "bearerAuth":{} }},
 *  @OA\Parameter(
 *         name="adId",
 *         in="path",
 *         description="ID of the product to add to the favorite",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
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

    // public function addAdToFavorite(Request $request,$adId)
    // {
    //     try {
    //         $service = new Service();

    //         $checkAuth=$service->checkAuth();
    
    //         if($checkAuth){
    //            return $checkAuth;
    //         }

    public function addAdToFavorite(Request $request, $adId) {
        try {
            $service = new Service();
            $checkAuth = $service->checkAuth();
    
            if ($checkAuth) {
                return $checkAuth;
            }
    
            $user_id = Auth::user()->id;
            $exist = Favorite::where('ad_id', $adId)->where('user_id', $user_id)->where('deleted', false)->exists();
    
            if ($exist) {
                Favorite::where('user_id', $user_id)->where('ad_id', $adId)->first()->delete();
                return $this->returnFavoritesList($user_id, 'Product removed from wishlist successfully!');
            } else {
                $ulid = Uuid::uuid1();
                $ulidFavorite = $ulid->toString();
                $uid = $ulidFavorite;
                $created_at = now();
                $updated_at = now();
    
                $db = DB::connection()->getPdo();
                $query = "INSERT INTO favorites (ad_id, user_id, uid, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
                $statement = $db->prepare($query);
    
                $statement->bindParam(1, $adId);
                $statement->bindParam(2, $user_id);
                $statement->bindParam(3, $uid);
                $statement->bindParam(4, $created_at);
                $statement->bindParam(5, $updated_at);
    
                $statement->execute();
    
                return $this->returnFavoritesList($user_id, 'Product added to wishlist successfully!');
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
    
    private function returnFavoritesList($user_id, $message) {
        $page = 1;
        $perPage = 6; 
    
        $db = DB::connection()->getPdo();
    
        $query = "
        SELECT 
            favorites.*, 
            ads.id AS ad_id, 
            ads.category_id AS ad_category_id, 
            ads.owner_id AS ad_owner_id, 
            ads.location_id AS ad_location_id, 
            (
                SELECT location 
                FROM files 
                WHERE ads.file_code = files.referencecode 
                LIMIT 1
            ) AS image,
            ads.title AS title, 
            ads.file_code AS ad_file_code, 
            ads.final_price AS price, 
            ads.uid AS ad_uid,
            categories.title AS category_title
        FROM 
            favorites 
        JOIN 
            ads ON favorites.ad_id = ads.id 
        LEFT JOIN 
            categories ON ads.category_id = categories.id
        WHERE 
            favorites.user_id = :user_id 
            AND ads.deleted = false 
        ORDER BY 
            ads.id DESC 
        LIMIT :limit OFFSET :offset
    ";
    
        $offset = $perPage * ($page - 1);
    
        $data = DB::select($query, ['user_id' => $user_id, 'limit' => $perPage, 'offset' => $offset]);
    
        $totalQuery = "
            SELECT 
                COUNT(*) AS total 
            FROM 
                favorites
            WHERE 
                favorites.user_id = :user_id
        ";
    
        $total = DB::select($totalQuery, ['user_id' => $user_id])[0]->total;
    
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($data, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    
        return response()->json(['message' => $message, 'data' => $paginator], 200);
    }

    public function returnFavoritesListUser(){
        try {

           $favs = Favorite::whereUserId();


            
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }
    

    //             // $ad_id = htmlspecialchars($request->input('ad_id'));
    //             $user_id = Auth::user()->id;
    //             $exist = Favorite::where('ad_id',$adId)->where('user_id',$user_id)->where('deleted',false)->exists();
    //             if($exist){
    //                 Favorite::where('user_id',$user_id)->where('ad_id',$adId)->first()->delete();
    //                 return response()->json([
    //                     'message' => 'Product retrieve from wishlist successfully !',
    //                     'data' => Favorite::where('user_id',Auth::user()->id)->get()
    //                 ],200);
    //             }
    //             $ulid = Uuid::uuid1();
    //             $ulidFavorite = $ulid->toString();
    //             $uid = $ulidFavorite;
    //             $created_at = date('Y-m-d H:i:s');
    //             $updated_at = date('Y-m-d H:i:s');

    //             $db = DB::connection()->getPdo();

    //             $query = " INSERT INTO favorites (ad_id, user_id, uid, created_at, updated_at) VALUES (?,?,?,?,?) ";

    //             $statement = $db->prepare($query);

    //             $statement->bindParam(1, $adId);
    //             $statement->bindParam(2, $user_id);
    //             $statement->bindParam(3, $uid);
    //             $statement->bindParam(4,  $created_at);
    //             $statement->bindParam(5,  $updated_at);

    //             $statement->execute();

    //             return response()->json([
    //                 'message' => 'product added to wishlist successfully !',
    //                 'data' => Favorite::where('user_id',Auth::user()->id)->get()
    //             ],200);

    //     } catch (Exception $e) {
    //        return response()->json([
    //         'error' => $e->getMessage()
    //        ]);
    //     }

    // }


   /**
     * @OA\Get(
     *     path="/api/favorite/GetFavoritesAd/{page}/{perPage}",
     *     summary="Get user's favorite ads",
     *     description="Retrieve a paginated list of the user's favorite ads.",
     *     operationId="getFavoritesAd",
     *     tags={"Favorite"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Page number"
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Number of items per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", ref="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

    public function GetFavoritesAd(Request $request,$page = 1,$perPage=5)
    {
        try {

            $service = new Service();

            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            if($perPage > 50){
                $perPage = 50;
            }

            $db = DB::connection()->getPdo();
            $user_id = Auth::user()->id;

            $query = "
    SELECT 
        favorites.*, 
        ads.id AS ad_id, 
        ads.category_id AS ad_category_id, 
        ads.owner_id AS ad_owner_id, 
        ads.location_id AS ad_location_id, 
        (
            SELECT location 
            FROM files 
            WHERE ads.file_code = files.referencecode 
            LIMIT 1
        ) AS image,
        ads.title AS title, 
        ads.file_code AS ad_file_code, 
        ads.final_price AS price, 
        ads.uid AS ad_uid,
        categories.title AS category_title
    FROM 
        favorites 
    JOIN 
        ads ON favorites.ad_id = ads.id 
    LEFT JOIN 
        categories ON ads.category_id = categories.id
    WHERE 
        favorites.user_id = :user_id 
        AND ads.deleted = false 
    ORDER BY 
        ads.id DESC 
    LIMIT :limit OFFSET :offset
";


        $page = max(1, intval($page));
        $perPage = intval($perPage);
        $offset = $perPage * ($page - 1);

        // Exécuter la requête
        $data = DB::select($query, [$user_id, $perPage, $offset]);

        $totalQuery = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            carts
        WHERE 
            carts.user_id = ?
            ";
            $total = DB::select($totalQuery, [$user_id])[0]->total;$paginator = new \Illuminate\Pagination\LengthAwarePaginator($data, $total, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        
            return response()->json(['data' => $paginator]);


            
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
