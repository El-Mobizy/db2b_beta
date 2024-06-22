<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Cart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
  

/**
 * @OA\Post(
 *     path="/api/cart/addToCart",
 *     summary="Add a product to the cart",
 *     tags={"Cart"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer",
 *                     description="The ID of the product to add to the cart"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Product added to the cart successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Product add to cart successfully"
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
    public function addToCart(Request $request) {

        $item = $this->getCartItem($request);
        if($item == 0){
            return response()->json(
                [
                    'message' =>'Check if this ad is already validated'
                ]);
        }
        $cartItem = $item['cartItem'];

        // return $item['cartItem'];
    
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
 *     path="/api/cart/removeToCart",
 *     summary="Remove a product from the cart",
 *     tags={"Cart"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer",
 *                     description="The ID of the product to remove from the cart"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Product removed from the cart successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Ad remove to cart successfully!"
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
    
    public function removeToCart(Request $request){
        try {
            $item = $this->getCartItem($request);
            $cartItem = $item['cartItem'];

            if(!$cartItem){
                return response()->json([
                    'message' => ' check if this ad exist '
                ],200);
            }

            Cart::where('user_id',  $item['userId'])
            ->where('ad_id', $request->ad_id)
            ->delete();

            return response()->json([
                'message' => ' Ad remove to cart successfully!'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/cart/getUserCart",
 *     summary="Get the user's cart",
 *     tags={"Cart"},
 * security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="User's cart retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(
 *                         property="files",
 *                         type="array",
 *                         @OA\Items(
 *                             @OA\Property(
 *                                 property="file_name",
 *                                 type="string",
 *                                 description="The name of the file"
 *                             ),
 *                             @OA\Property(
 *                                 property="file_path",
 *                                 type="string",
 *                                 description="The path of the file"
 *                             )
 *                         )
 *                     )
 *                 )
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

    public function getUserCart(){
        try {
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


            foreach ($userCarts as $userCart) {
                $ad = Ad::whereId($userCart->ad_id)->with('file')->first();
                $ad->cartItemId = $userCart->id;
               
                $data[] = [
                    $ad
                ];
            }


            return response()->json([
                'data' => $data
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Post(
 *     path="/api/cart/incrementQuantity",
 *     summary="Increment the quantity of a product in the cart",
 *     tags={"Cart"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer",
 *                     description="The ID of the product to increment the quantity of"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Quantity incremented successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Quantity incremented successfully"
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
    public function incrementQuantity(Request $request){
        try {

            $item = $this->getCartItem($request);

            $cartItem = $item['cartItem'];

            if(!$cartItem){
                return response()->json([
                    'message' => ' check if this ad exist '
                ],200);
            }

           $cartItem->quantity = $cartItem->quantity + 1;
           $cartItem->save();

           return response()->json([
            'message' => ' Incremented successffuly!'
        ],200);

        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/cart/decrementQuantity",
 *     summary="Decrement the quantity of a product in the cart",
 *     tags={"Cart"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer",
 *                     description="The ID of the product to decrement the quantity of"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Quantity decremented successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Quantity decremented successfully"
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

    public function decrementQuantity(Request $request){
        try {

            $item = $this->getCartItem($request);

            $cartItem = $item['cartItem'];
            

            if(!$cartItem){
                return response()->json([
                    'message' => ' check if this ad exist '
                ],200);
            }

           if( $cartItem->quantity == 1){
            $cartItem->quantity = 1;
            $cartItem->save();

           }

           $cartItem->quantity = $cartItem->quantity - 1;
           $cartItem->save();

           return response()->json([
            'message' => ' Decremented successffuly!'
        ],200);

        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/cart/getCartItem/{ad_id}",
 *     summary="Get a cart item by ad ID",
 *     tags={"Cart"},
 *     @OA\Parameter(
 *         name="ad_id",
 *         in="query",
 *         required=true,
 *         description="The ID of the ad",
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cart item retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="catItem",
 *                 type="object",
 *                 @OA\Property(
 *                     property="id",
 *                     type="integer"
 *                 ),
 *                 @OA\Property(
 *                     property="user_id",
 *                     type="integer"
 *                 ),
 *                 @OA\Property(
 *                     property="ad_id",
 *                     type="integer"
 *                 ),
 *                 @OA\Property(
 *                     property="quantity",
 *                     type="integer"
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="userId",
 *                 type="integer"
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

    public function getCartItem(Request $request){
        try {
            $request->validate([
                'ad_id' => 'required',
            ]);

            $service = new Service();

            $checkAuth=$service->checkAuth();

            if($checkAuth){
               return $checkAuth;
            }

            // return ($request->ad_id);

            $ac = new AdController();

            $checkAd = $ac->checkIfAdIsValidated(Ad::find($request->ad_id)->uid);

            if($checkAd == 0){
                return 0;
             }

            $user = auth()->user();
    
                $cartItem = Cart::where('user_id', $user->id)
                                ->where('ad_id', $request->ad_id)
                                ->first();
            $item = [
                'cartItem' => $cartItem,
                'userId' => $user->id
            ];

                return $item;
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }
    

 
}
