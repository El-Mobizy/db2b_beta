<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TypeOfType;
use FFI\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelIgnition\Http\Requests\UpdateConfigRequest;

class OrderController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/order/CreateAnOrder",
 *     tags={"Orders"},
 *  security={{"bearerAuth": {}}},
 *     summary="Create an order",
 *     description="Create an order for the authenticated user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent()
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", description="Success message", example="Order created successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Cart is empty",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Cart is empty")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Internal Server Error")
 *         )
 *     )
 * )
 */
    public function CreateAnOrder(Request $request){
        try{

            $service = new Service();
    
            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }
            
                $user = auth()->user();

                $cartItems = Cart::where('user_id', $user->id)->get();
             

                foreach ($cartItems as $cartItem) {
                    if(!$cartItem){
                        return response()->json(
                            ['message' => 'Item not found'
                        ],200);
                    }
                    $ads[] = [
                        'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                        'shop_product' =>Ad::whereId($cartItem->ad_id)->first()->shop->id,
                        'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                        'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                        'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                        'quantity_product' => $cartItem->quantity,
                        'price_product' => Ad::whereId($cartItem->ad_id)->first()->price,
                        'final_price_product' =>Ad::whereId($cartItem->ad_id)->first()->final_price,
                    ];
                }

                $total = array_sum(array_map(function ($item) {
                    return floatval($item['final_price_product']) * $item['quantity_product'];
                }, $ads));

                if ($cartItems->isEmpty()) {
                    return response()->json(['error' => 'Cart is empty'], 400);
                }

                $order = new Order();
                $order->user_id = $user->id;
                $order->amount =  $total;
                $order->status =  TypeOfType::whereLibelle('pending')->first()->id;
                $order->uid= $service->generateUid($order);
                $order->save();
        
                foreach ($ads as $item) {
                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->uid = $service->generateUid($order);
                    $order_detail->ad_id = $item['id_product'];
                    $order_detail->quantity = $item['quantity_product'];
                    $order_detail->price = $item['price_product'];
                    $order_detail->final_price = $item['final_price_product'];
                    $order_detail->shop_id = $item['shop_product'];
                    $order_detail->save();
                }

                foreach( Cart::where('user_id', $user->id)->get() as $cart){
                    $cart->delete();
                }
        
               
        
                return response()->json(
                    ['message' => 'Order created successffuly'
                ]);    
    
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Post(
 *     path="/api/order/orderSingleItem/{cartItemId}",
 *     tags={"Orders"},
 *  security={{"bearerAuth": {}}},
 *     summary="Order a single item from the cart",
 *     description="Create an order for a single item in the user's cart",
 *     @OA\Parameter(
 *         name="cartItemId",
 *         in="path",
 *         description="ID of the cart item to be ordered",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="someParameter",
 *                 type="string",
 *                 description="Some parameter that might be required for the request"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order created successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Item not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Item not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error occurred"
 *             )
 *         )
 *     )
 * )
 */

    public function orderSingleItem(Request $request, $cartItemId){
        try {
            $service = new Service();
    
            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }
            
                $user = auth()->user();

                $cartItem = Cart::where('user_id', $user->id)
                ->whereId($cartItemId)
                ->first();

                if(!$cartItem){
                    return response()->json(
                        ['message' => 'Item not found'
                    ],200);

                }

                    $ads = $this->getCartAds($cartItem);

                    $total =  $cartItem->quantity * Ad::whereId($cartItem->ad_id)->first()->final_price;

                    $orderId = $this->storeOrder($cartItem,$total);

                    $this->storeOrderDetail($ads,$orderId);

                    Cart::where('user_id', $user->id)
                    ->whereId($cartItemId)->delete();

                    return response()->json(
                        ['message' => 'Order created successffuly'
                    ],200);    


        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/order/viewOrder/{orderId}",
 *     summary="View Order Details",
 * security={{"bearerAuth": {}}},
 *     description="Retrieve the details of a specific order by its ID.",
 *     operationId="viewOrder",
 *     tags={"Orders"},
 *     @OA\Parameter(
 *         name="orderId",
 *         in="path",
 *         description="ID of the order to retrieve",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="id",
 *                     type="integer",
 *                     example=1
 *                 ),
 *                 @OA\Property(
 *                     property="user_id",
 *                     type="integer",
 *                     example=5
 *                 ),
 *                 @OA\Property(
 *                     property="amount",
 *                     type="number",
 *                     format="float",
 *                     example=150.00
 *                 ),
 *                 @OA\Property(
 *                     property="status",
 *                     type="string",
 *                     example="pending"
 *                 ),
 *                 @OA\Property(
 *                     property="uid",
 *                     type="string",
 *                     example="order_12345"
 *                 ),
 *                 @OA\Property(
 *                     property="order_details_deleted",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(
 *                             property="id",
 *                             type="integer",
 *                             example=1
 *                         ),
 *                         @OA\Property(
 *                             property="order_id",
 *                             type="integer",
 *                             example=1
 *                         ),
 *                         @OA\Property(
 *                             property="product_id",
 *                             type="integer",
 *                             example=10
 *                         ),
 *                         @OA\Property(
 *                             property="quantity",
 *                             type="integer",
 *                             example=2
 *                         ),
 *                         @OA\Property(
 *                             property="price",
 *                             type="number",
 *                             format="float",
 *                             example=75.00
 *                         ),
 *                         @OA\Property(
 *                             property="final_price",
 *                             type="number",
 *                             format="float",
 *                             example=150.00
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order not found"
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
 *                 example="An unexpected error occurred."
 *             )
 *         )
 *     )
 * )
 */

   public function viewOrder($orderId){
        try {
            $service = new Service();

            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            $order = Order::whereId($orderId)->with('order_details_deleted')->first();

            if(!$order){
                return response()->json(
                    ['message' =>'Order  not found'
                ],200);
            }

            return response()->json(
                ['data' =>$order
            ],200);
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/order/listOrders",
 *     tags={"Orders"},
 *     security={{"bearerAuth": {}}},
 *     summary="Get a list of orders",
 *     description="Get a list of orders for the authenticated user",
 *     @OA\Response(
 *         response=200,
 *         description="List of orders",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Internal Server Error")
 *         )
 *     )
 * )
 */

    public function listOrders(){
        try {
            $service = new Service();

            $checkAuth=$service->checkAuth();

            if($checkAuth){
               return $checkAuth;
            }

          $orders = Order::where('user_id',Auth::user()->id)->with('order_details_deleted')->get();

            return response()->json(
                ['data' =>$orders 
            ],200);
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Delete(
 *     path="/api/order/cancelOrder/{orderId}",
 *     tags={"Orders"},
 *     security={{"bearerAuth": {}}},
 *     summary="Cancel an order",
 *     description="Cancel an order by its ID",
 *     @OA\Parameter(
 *         name="orderId",
 *         in="path",
 *         required=true,
 *         description="ID of the order to cancel",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order canceled successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", description="Success message", example="Order canceled successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", description="Error message", example="Internal Server Error")
 *         )
 *     )
 * )
 */
    public function cancelOrder($orderId){
        try {
            $service = new Service();

            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            Order::whereId($orderId)->update(['status' =>TypeOfType::whereLibelle('rejected')->first()->id ]);

            return response()->json(
                ['message' =>'Order canceled successfuly'
            ],200);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * 
 * @OA\Post(
 *     path="/api/order/deleteOrderDetail/{orderDetailId}",
 *     tags={"Order Detail"},
 * security={{"bearerAuth": {}}},
 *     summary="Delete an order detail",
 *     description="Delete a specific order detail by ID",
 *     operationId="deleteOrderDetail",
 *     @OA\Parameter(
 *         name="orderDetailId",
 *         in="path",
 *         description="ID of the order detail to delete",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             format="int64"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order detail deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order detail deleted from Order successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order detail not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="This order detail not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Internal server error message"
 *             )
 *         )
 *     )
 * )
 */
    public function deleteOrderDetail($orderDetailId){
        try {
           $order_detail = OrderDetail::whereId($orderDetailId)->first();

           if(!$order_detail){
            return response()->json(
                ['message' =>'This order detail not found'
            ],200);
           }

           if($order_detail->deleted == true){
            return response()->json(
                ['message' =>'This order detail is already deleted'
            ],200);
           }

           OrderDetail::whereId($orderDetailId)->update(['deleted'=>true]);

           return response()->json(
            ['message' =>'Order detail updated successfuly'
        ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }



    /**
 * Update an order detail by ID.
 * @OA\Post(
 *     path="/api/order/updateOrderDetail/{orderDetailId}",
 *     tags={"Order Detail"},
 * security={{"bearerAuth": {}}},
 *     summary="Update an order detail",
 *     description="Update a specific order detail by ID",
 *     operationId="updateOrderDetail",
 *     @OA\Parameter(
 *         name="orderDetailId",
 *         in="path",
 *         description="ID of the order detail to update",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             format="int64"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="quantity",
 *                 type="integer",
 *                 example=5
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order detail updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order detail updated from Order successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order detail not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="This order detail not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Internal server error message"
 *             )
 *         )
 *     )
 * )
 */

    public function updateOrderDetail(Request $request, $orderDetailId){
        try {

           $order_detail = OrderDetail::whereId($orderDetailId)->first();

           if(!$order_detail){
            return response()->json(
                ['message' =>'This order detail not found'
            ],200);
           }

           $order_detail->quantity =  $request->quantity ?? $order_detail->quantity;
           $order_detail->save();

        return response()->json(
            ['message' =>'Order detail deteted from Order successfuly'
        ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

private function getCartAds($cartItem){
    try {

        if(!$cartItem){
            return response()->json(
                ['message' => 'Item not found'
            ],200);

        }
            $ads[] = [
                'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                'shop_product' =>Ad::whereId($cartItem->ad_id)->first()->shop->id,
                'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                'id_product' =>Ad::whereId($cartItem->ad_id)->first()->id,
                'quantity_product' => $cartItem->quantity,
                'price_product' => Ad::whereId($cartItem->ad_id)->first()->price,
                'final_price_product' =>Ad::whereId($cartItem->ad_id)->first()->final_price,
            ];
            return $ads;
    }  catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
}

    private function storeOrder($cartItem,$total){
        try {

            $service = new Service();

          
                $user = Auth::user();
                $order = new Order();
                $order->user_id = $user->id;
                $order->amount =  $total;
                $order->status =  TypeOfType::whereLibelle('pending')->first()->id;
                $order->uid= $service->generateUid($order);
                $order->save();
                return $order->id;
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function storeOrderDetail($ads,$orderId){
        try {

            $service = new Service();
            foreach ($ads as $item) {
                $order_detail = new OrderDetail();
                $order_detail->order_id = $orderId;
                $order_detail->uid = $service->generateUid($order_detail     );
                $order_detail->ad_id = $item['id_product'];
                $order_detail->quantity = $item['quantity_product'];
                $order_detail->price = $item['price_product'];
                $order_detail->final_price = $item['final_price_product'];
                $order_detail->shop_id = $item['shop_product'];
                $order_detail->save();
            }
        
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

}