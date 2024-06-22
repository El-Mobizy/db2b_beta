<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Cart;
use App\Models\CommissionWallet;
use App\Models\Escrow;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // DB::beginTransaction();
        try{

            $service = new Service();
    
            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

                $user = auth()->user();

                $cartItems = Cart::where('user_id', $user->id)->get();
                $ads= [] ;

                foreach ($cartItems as $cartItem) {
                    $ads[] = $this->getCartAds($cartItem);
                }


                    $flatAds = array_merge(...$ads);//applatir le tableau

                    $total = array_sum(array_map(function ($item) {
                        return floatval($item['final_price_product']) * $item['quantity_product'];
                        }, $flatAds));


                        if ($cartItems->isEmpty()) {
                            return response()->json(['error' => 'Cart is empty'], 400);
                         }

                        $orderId = $this->storeOrder($total);

                        foreach ($ads as $tab) {
                           $i =  $this->storeOrderDetail($tab,$orderId);
                        //    return $i;
                        }


                foreach( Cart::where('user_id', $user->id)->get() as $cart){
                    $cart->delete();
                }

                return response()->json(
                    ['message' => 'Order created successffuly'
                ],200);

                // DB::commit();
    
        }catch(Exception $e){
            // DB::rollBack();
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

            $order = Order::whereId($orderId)->with('order_details_not_deleted')->first();

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

          $orders = Order::where('user_id',Auth::user()->id)->with('order_details_not_deleted')->get();

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

    private function storeOrder($total){
        try {

                $service = new Service();

                $user = Auth::user();
                $order = new Order();
                $order->user_id = $user->id;
                $order->amount =  $total;
                $order->status =  TypeOfType::whereLibelle('pending')->first()->id;
                $order->uid= $service->generateUid($order);

                if( $order->save()){
                    return $order->id;
                }else{
                    $e = new Exception();
                    return response()->json([
                     'error' => $e->getMessage()
                    ]);
                }


        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function storeOrderDetail($ads,$orderId){
        try {
            // return $ads;
            $service = new Service();
            $trade = new TradeController();
            foreach ($ads as $item) {
                $order_detail = new OrderDetail();
                $order_detail->order_id = $orderId;
                $order_detail->uid = $service->generateUid($order_detail);
                $order_detail->ad_id = $item['id_product'];
                $order_detail->quantity = $item['quantity_product'];
                $order_detail->price = $item['price_product'];
                $order_detail->final_price = $item['final_price_product'];
                $order_detail->shop_id = $item['shop_product'];
                $order_detail->save();
                // $a[] = $order_detail;
                $trade->createTrade($order_detail->id,Order::find($orderId)->user_id,Shop::find($item['shop_product'])->client_id,'1000-10-10 10:10:10', $item['final_price_product']);
                

                // return [
                //     $order_detail->id,
                //     Order::find($orderId)->user_id,
                //     Shop::find($item['shop_product'])->client_id,
                //     '2024-06-12 12:36:25',
                //     $item['final_price_product']
                // ];
            }
            // return $ads;
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function ordersIndex(){
        return response()->json([
            'error' => Order::all()
        ]);
    }

    /**
 * @OA\Post(
 *     path="/api/order/orderManyItem",
 *     summary="Create an order from multiple cart items",
 *     tags={"Orders"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"cartItemids"},
 *             @OA\Property(
 *                 property="cartItemids",
 *                 type="array",
 *                 @OA\Items(
 *                     type="integer",
 *                     description="Array of cart item IDs"
 *                 ),
 *                 description="IDs of the cart items to order"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Order created successfully"
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
 *                 example="Cart is empty"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Unauthorized"
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
 *                 example="An error occurred"
 *             )
 *         )
 *     )
 * )
 */

    public function orderManyItem(Request $request){
        try {
            $request->validate([
                'cartItemids' => 'required|array',
            ]);
            $service = new Service();

            $checkAuth=$service->checkAuth();
    
            if($checkAuth){
               return $checkAuth;
            }

            $user = auth()->user();

            $cartItems =  Cart::where('user_id', $user->id)->whereIn('id',$request->cartItemids)->get()  ;

            $ads= [] ;

            // return $cartItems;

            foreach ($cartItems as $cartItem) {
                $ads[] = $this->getCartAds($cartItem);
            }
            // return [gettype($ads), $ads];

            $flatAds = array_merge(...$ads);//applatir le tableau (❁´◡`❁)
                    
                    $total = array_sum(array_map(function ($item) {
                        return floatval($item['final_price_product']) * $item['quantity_product'];
                        }, $flatAds));

                        
                        if ($cartItems->isEmpty()) {
                            return response()->json(['error' => 'Cart is empty'], 400);
                         }
                            
                        $orderId = $this->storeOrder($total);

                        
                        foreach ($ads as $tab) {
                            $this->storeOrderDetail($tab,$orderId);
                        }


                foreach( Cart::where('user_id', $user->id)->get() as $cart){
                    $cart->delete();
                }

                return response()->json(
                    ['message' => 'Order created successffuly'
                ],200);

                // DB::commit();
    

        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/order/payOrder/{orderId}",
 *     tags={"Orders"},
 *   security={{"bearerAuth":{}}},
 *     summary="Pay for an order",
 *     description="Pay for an order by its ID",
 *     @OA\Parameter(
 *         name="orderId",
 *         in="path",
 *         required=true,
 *         description="ID of the order",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Payment done successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Insufficient balance",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Insufficient balance")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */

    public function payOrder($orderId){
        try {

            $service = new Service();
            $personId = $service->returnPersonIdAuth();

            $checkAuth=$service->checkAuth();

            if($checkAuth){
                return $checkAuth;
            }

        $data = $this->payOrderVerification($orderId);

        if($data){
            return $data;
        }

        $diff = $this->checkSolde($orderId);

        if($diff < 0){
            return response()->json(
                ['message' => 'insufficient balance'
            ],200);
        }

        $this->updateUserWallet($personId,$diff);

        $this->createEscrow($orderId);

//Secured
            return response()->json(
                ['message' => 'Payement done Successfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function createEscrow($orderId){
        try {
            $service = new Service();
            $escrow = new Escrow();
            $order = Order::find($orderId);
            $escrow->order_id = $orderId;
            $escrow->amount =  $order->amount;
            $escrow->uid= $service->generateUid($escrow);
            $escrow->save();
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function createTransaction($orderId,$wallet){
        try {
            $user = Auth::user();
            $service = new Service();
            $order = Order::find($orderId);
            $transaction = new Transaction();
            $transaction->order_id = $orderId;
            $transaction->sender_id = $user->id;
            $transaction->receiver_id = $user->id;
            $transaction->commission_wallet_id = $wallet->id;
            $transaction->amount =  $order->amount;
            $transaction->transaction_type = 'transfer';
            $transaction->uid= $service->generateUid($transaction);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function payOrderVerification($orderId){
        try {

            $service = new Service();
            $user = Auth::user();
            $order = Order::find($orderId);

            // return [ Auth::user()->id, $order->user_id ];

            if(!$order){
                return response()->json(
                    ['message' => 'Order not found'
                ],200);
            }

            if($user->id != $order->user_id){
                return response()->json(
                    ['message' => 'This order it is not yours'
                ],200);
            }

            $personId = $service->returnPersonIdAuth();
            $wallet = CommissionWallet::where('person_id',$personId)->first();

            if(!$wallet){
                return response()->json(
                    ['message' => 'Fund your account'
                ],200);
            }
          
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkSolde($orderId){
        try{

            $service = new Service();
            $personId = $service->returnPersonIdAuth();

            $order = Order::find($orderId);
            $orderAmount = $order->amount;


            $wallet = CommissionWallet::where('person_id',$personId)->first();
            $walletAmount = $wallet->balance;

            // if($walletAmount < $orderAmount){
            //     return response()->json(
            //         ['message' => 'insufficient balance'
            //     ],200);
            // }

            $diff = $walletAmount - $orderAmount;

            return $diff;

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateUserWallet($personId,$diff){
        try{

            $wallet = CommissionWallet::where('person_id',$personId)->first();
            $walletAmount = $wallet->balance;

            CommissionWallet::where('person_id',$personId)->update([
                'prev_balance' => $walletAmount
            ]);

            CommissionWallet::where('person_id',$personId)->update([
                'balance' => $diff
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/order/addFund",
 *     tags={"Orders"},
 *   security={{"bearerAuth":{}}},
 *     summary="Add funds to a user's wallet",
 *     description="Add funds to a user's wallet",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"amount"},
 *             @OA\Property(property="amount", type="number", example=100.00)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Funds added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Successfully credited wallet")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation error")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */

    public function addFund(Request $request){
        try{
            $request->validate([
                'amount' => 'required'
            ]);
            $service = new Service();
            $personId = $service->returnPersonIdAuth();
            $wallet = CommissionWallet::where('person_id',$personId)->first();

            if(!$wallet){
                $com = new CommissionWalletController();
                $com->generateStandardWallet();
            }

            $credit =  $request->amount + CommissionWallet::where('person_id',$personId)->first()->balance;

            $this->updateUserWallet($personId,$credit);
            return response()->json(
                ['message' => 'Successfully credited wallet'
            ],200);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/order/orderTransaction/{orderId}",
 *     tags={"Trade"},
 *   security={{"bearerAuth":{}}},
 *     summary="Get order transactions",
 *     description="Get the transactions for an order by its ID",
 *     @OA\Parameter(
 *         name="orderId",
 *         in="path",
 *         required=true,
 *         description="ID of the order",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order transactions retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */

    public function orderTransaction($orderId){
        try{
            $order =  Order::find($orderId);
            return response()->json(
                ['data' =>$order->transactions->trade
            ],200);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


}