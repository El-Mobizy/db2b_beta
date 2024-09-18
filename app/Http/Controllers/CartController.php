<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Cart;
use App\Models\File;
use App\Models\OngingTradeStage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
/**
 * @OA\Post(
 *     path="/api/cart/addToCart/{adId}",
 *     summary="Add to Cart",
 * security={{"bearerAuth": {}}},
 *     description="Add an advertisement to the authenticated user's cart.",
 *     operationId="addToCart",  
 *     tags={"Cart"},
 *     @OA\Parameter(
 *         name="adId",
 *         in="path",
 *         description="ID of the advertisement to add to the cart",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Product added to cart successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Product added to cart successfully"
 *             )
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
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Error message"
 *             )
 *         )
 *     )
 * )
 */
    public function addToCart(Request $request,$adId) {

        

        $service = new Service();

        $checkAuth=$service->checkAuth();

        if($checkAuth){
           return $checkAuth;
        }

        $user = auth()->user();

        $ac = new AdController();

        $checkAd = $ac->checkIfAdIsValidated(Ad::find($adId)->uid);


        if($checkAd == 0){
            return (new Service())->apiResponse(404,[],'Check if this ad is validated !');
            // return response()->json([
            //     'message' => 'Check if this ad is validated !'
            // ]);
         }
    
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('ad_id', $adId)
                        ->first();
    $item = [
        'cartItem' => $cartItem,
        'userId' => $user->id
    ];

        // $cartItem = $item['cartItem'];

    
        if ($cartItem) {
            $cartItem->quantity += 1;
            $cartItem->save();
        } else {
            $cartItem = new Cart();
            $cartItem->user_id = $item['userId'];
            $cartItem->ad_id =$request->ad_id;
            $cartItem->quantity = 1;
            $cartItem->save();

        }
        return response()->json(
            [
                'message' =>'product add to cart sucessfully'
            ]);
    }
    

    /**
     * @OA\Delete(
     *     path="/api/cart/removeToCart/{adId}",
     *     summary="Remove a product from the cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="adId",
     *         in="path",
     *         required=true,
     *         description="The ID of the ad",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed from the cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Ad removed from cart successfully!"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    
    public function removeToCart($adId){
        try {
           $cartItem = Cart::where('user_id',Auth::user()->id)
           ->where('ad_id',intval($adId))
           ->first();
            if(!$cartItem){
                return response()->json([
                    'message' => ' check if this ad exist in your cart'
                ],400);
            }

           $cartItem->delete();

            return response()->json([
                'message' => 'Ad remove from cart successfully !'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


 /**
 * @OA\Get(
 *     path="/api/cart/getUserCart/{page}/{perPage}",
 * security={{"bearerAuth": {}}},
 *     summary="Get User Cart",
 *     description="Retrieve the cart items for the authenticated user with pagination.",
 *     operationId="getUserCart",
 *     tags={"Cart"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination",
 *         required=false,
 *         @OA\Schema(
 *             type="integer",
 *             default=1
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Number of items per page",
 *         required=false,
 *         @OA\Schema(
 *             type="integer",
 *             default=5,
 *             maximum=50
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful retrieval of cart items",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="ad_id", type="integer", example=1),
 *                     @OA\Property(property="ad_uid", type="string", example="abc123"),
 *                     @OA\Property(property="ad_title", type="string", example="Sample Ad Title"),
 *                     @OA\Property(property="final_price", type="number", format="float", example=99.99),
 *                     @OA\Property(property="cart_id", type="integer", example=1),
 *                     @OA\Property(property="quantity", type="integer", example=2),
 *                     @OA\Property(property="image", type="string", example="http://example.com/image.jpg")
 *                 )
 *             ),
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="last_page", type="integer", example=2),
 *             @OA\Property(property="per_page", type="integer", example=5),
 *             @OA\Property(property="total", type="integer", example=10),
 *             @OA\Property(property="path", type="string", example="http://example.com/user/cart"),
 *             @OA\Property(property="query", type="object")
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
 *             @OA\Property(property="error", type="string", example="Error message")
 *         )
 *     )
 * )
 */

    public function getUserCart(Request $request,$page = 1,$perPage=5){
        try {
            // $page = $request->input('page', 1);
            // $perPage = $request->input('per_page',5);

            if($perPage > 50){
                $perPage = 50;
            }

            $service = new Service();
    
            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            $user = Auth::user();

            $userCarts = Cart::where('user_id',$user->id)->get();

            if(count($userCarts) === 0){
                return response()->json([
                    'message' => 'No data found'
                ],200);
            }

            $query = "
            SELECT 
                ads.id AS ad_id, 
                ads.uid AS ad_uid, 
                ads.title AS ad_title, 
                ads.final_price, 
                carts.id AS cart_id, 
                carts.quantity, 
                (SELECT files.location FROM files WHERE files.referencecode = ads.file_code LIMIT 1) AS image,
                true AS isSynchronized
            FROM 
                carts
            JOIN 
                ads ON carts.ad_id = ads.id
            WHERE 
                carts.user_id = ?
                AND ads.deleted = false
            ORDER BY 
                carts.id DESC
            LIMIT ? OFFSET ?
        ";
        

    // Calculer l'offset
    
    $page = max(1, intval($page));
    $perPage = intval($perPage);
    $offset = $perPage * ($page - 1);
    // Exécuter la requête
    $data = DB::select($query, [$user->id, $perPage, $offset]);

    // Compter le total des résultats pour la pagination
    $totalQuery = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            carts
        WHERE 
            carts.user_id = ?
    ";
    $total = DB::select($totalQuery, [$user->id])[0]->total;



    $paginator = new \Illuminate\Pagination\LengthAwarePaginator($data, $total, $perPage, $page, [
        'path' => request()->url(),
        'query' => request()->query(),
    ]);

    return response()->json(['data' => $paginator]);


        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

       // foreach ($userCarts as $userCart) {
            //     $ad = Ad::whereId($userCart->ad_id)->with('file')->first();

            //     $data[] = [
            //         'ad_id' =>Ad::whereId($userCart->ad_id)->first()->id,
            //         'ad_uid' =>Ad::whereId($userCart->ad_id)->first()->uid,
            //         'ad_title' => Ad::whereId($userCart->ad_id)->first()->title,
            //         'final_price' => Ad::whereId($userCart->ad_id)->first()->final_price,
            //         'cart_id' => $userCart->id,
            //         'quantity' => $userCart->quantity,
            //         'image' => File::where('referencecode',$ad->file_code)->first()->location

            //     ];
            // }

//     /**
//  * @OA\Get(
//  *     path="/api/cart/getCartItem/{ad_id}",
//  *     summary="Get a cart item by ad ID",
//  *     tags={"Cart"},
//  *     @OA\Parameter(
//  *         name="ad_id",
//  *         in="query",
//  *         required=true,
//  *         description="The ID of the ad",
//  *         @OA\Schema(
//  *             type="integer"
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=200,
//  *         description="Cart item retrieved successfully",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="catItem",
//  *                 type="object",
//  *                 @OA\Property(
//  *                     property="id",
//  *                     type="integer"
//  *                 ),
//  *                 @OA\Property(
//  *                     property="user_id",
//  *                     type="integer"
//  *                 ),
//  *                 @OA\Property(
//  *                     property="ad_id",
//  *                     type="integer"
//  *                 ),
//  *                 @OA\Property(
//  *                     property="quantity",
//  *                     type="integer"
//  *                 )
//  *             ),
//  *             @OA\Property(
//  *                 property="userId",
//  *                 type="integer"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=400,
//  *         description="Invalid input"
//  *     ),
//  *     @OA\Response(
//  *         response=401,
//  *         description="Unauthorized"
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Server error"
//  *     )
//  * )
//  */


/**
 * @OA\Post(
 *     path="/api/cart/actualiseAuthCart",
 *     summary="Update or add items to the user's cart",
 *     description="Updates the quantity of items in the user's cart. If an item is not present in the cart, it is added.",
 *   security={{"bearerAuth":{}}},
 *     tags={"Cart"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"carts"},
 *                 @OA\Property(
 *                     property="carts",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         required={"ad_id", "quantity"},
 *                         @OA\Property(
 *                             property="ad_id",
 *                             type="integer",
 *                             example=1
 *                         ),
 *                         @OA\Property(
 *                             property="quantity",
 *                             type="integer",
 *                             example=2
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cart updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 example=200
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 additionalProperties=true
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Actualisation done"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ad not found",
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
 *                 example="An error occurred"
 *             )
 *         )
 *     ),
 *     @OA\Tag(
 *         name="Cart",
 *         description="Operations related to the user's shopping cart"
 *     )
 * )
 */
public function actualiseAuthCart(Request $request) {
    try {
        $request->validate([
            'carts' => 'required|array',
            'carts.*.ad_id' => 'required|exists:ads,id',
            'carts.*.quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::user()->id;
        $carts = $request->carts;

        foreach ($carts as $cart) {
            $adId = $cart['ad_id'];
            $quantity = $cart['quantity'];

            Cart::updateOrCreate(
                ['user_id' => $userId, 'ad_id' => $adId],
                ['quantity' => $quantity]
            );
        }

        $AuthCarts =  Cart::where('user_id',Auth::user()->id)->get();

        return (new Service())->apiResponse(200,$AuthCarts, 'Actualisation done');

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


}
