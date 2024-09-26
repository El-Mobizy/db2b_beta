<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Models\Ad;
use App\Models\AllowTransaction;
use App\Models\Cart;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\Escrow;
use App\Models\EscrowDelivery;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\Shop;
use App\Models\Trade;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TypeOfType;
use App\Models\UserDetail;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function CreateAnOrder($l = 0){

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


                    $flatAds = array_merge(...$ads);

                    $total = array_sum(array_map(function ($item) {
                        return floatval($item['final_price_product']) * $item['quantity_product'];
                        }, $flatAds));


                        if ($cartItems->isEmpty()) {
                            return response()->json(['error' => 'Cart is empty'], 400);
                         }
                         $request = new Request();
                        $orderId = $this->storeOrder($total);

                        foreach ($ads as $tab) {
                           $i =  $this->storeOrderDetail($tab,$orderId);
                        //    return $i;
                        }


                foreach( Cart::where('user_id', $user->id)->get() as $cart){
                    $cart->delete();
                }

                if($l==1){
                    return $orderId;
                }

                return response()->json(
                    [
                        'message' => 'Order created successfully',
                        'orderId' =>  $orderId
                ],200);

                // DB::commit();
    
        }catch(Exception $e){
            // DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


//     /**
//  * @OA\Post(
//  *     path="/api/order/orderSingleItem/{cartItemId}",
//  *     tags={"Orders"},
//  *  security={{"bearerAuth": {}}},
//  *     summary="Order a single item from the cart",
//  *     description="Create an order for a single item in the user's cart",
//  *     @OA\Parameter(
//  *         name="cartItemId",
//  *         in="path",
//  *         description="ID of the cart item to be ordered",
//  *         required=true,
//  *         @OA\Schema(type="integer")
//  *     ),
//  *     @OA\RequestBody(
//  *         required=true,
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(
//  *                 property="someParameter",
//  *                 type="string",
//  *                 description="Some parameter that might be required for the request"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=200,
//  *         description="Order created successfully",
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(
//  *                 property="message",
//  *                 type="string",
//  *                 example="Order created successfully"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=404,
//  *         description="Item not found",
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(
//  *                 property="message",
//  *                 type="string",
//  *                 example="Item not found"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Server error",
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(
//  *                 property="error",
//  *                 type="string",
//  *                 example="An error occurred"
//  *             )
//  *         )
//  *     )
//  * )
//  */

//  public function orderSingleItem(Request $request, $cartItemId){
//     try {
//         $service = new Service();

//         $checkAuth=$service->checkAuth();

//         if($checkAuth){
//            return $checkAuth;
//         }
        
//             $user = auth()->user();

//             $cartItem = Cart::where('user_id', $user->id)
//             ->whereId($cartItemId)
//             ->first();

//             if(!$cartItem){
//                 return response()->json(
//                     ['message' => 'Item not found'
//                 ],200);

//             }

//                 $ads = $this->getCartAds($cartItem);

//                 $total =  $cartItem->quantity * Ad::whereId($cartItem->ad_id)->first()->final_price;

//                 $orderId = $this->storeOrder($cartItem,$total);

//                 $this->storeOrderDetail($ads,$orderId);

//                 Cart::where('user_id', $user->id)
//                 ->whereId($cartItemId)->delete();

//                 return response()->json(
//                     ['message' => 'Order created successffuly'
//                 ],200);    


//     } catch(Exception $e){
//         return response()->json([
//             'error' => $e->getMessage()
//         ]);
//     }
// }

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
 *     path="/api/order/listOrders/{perpage}",
 *     tags={"Orders"},
 *     security={{"bearerAuth": {}}},
 * @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
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

    public function listOrders($perpage){
        try {
            $service = new Service();

            $checkAuth=$service->checkAuth();

            if($checkAuth){
               return $checkAuth;
            }

            $orders = Order::with('order_details_not_deleted')
            ->orderBy('created_at', 'desc')
            ->paginate($perpage);

            return response()->json(
                ['data' =>$orders 
            ],200);
        }  catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


//     /**
//  * @OA\Delete(
//  *     path="/api/order/cancelOrder/{orderId}",
//  *     tags={"Orders"},
//  *     security={{"bearerAuth": {}}},
//  *     summary="Cancel an order",
//  *     description="Cancel an order by its ID",
//  *     @OA\Parameter(
//  *         name="orderId",
//  *         in="path",
//  *         required=true,
//  *         description="ID of the order to cancel",
//  *         @OA\Schema(type="integer")
//  *     ),
//  *     @OA\Response(
//  *         response=200,
//  *         description="Order canceled successfully",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="message", type="string", description="Success message", example="Order canceled successfully")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=401,
//  *         description="Unauthorized",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="error", type="string", description="Error message", example="Unauthorized")
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Internal Server Error",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="error", type="string", description="Error message", example="Internal Server Error")
//  *         )
//  *     )
//  * )
//  */
//     public function cancelOrder($orderId){
//         try {
//             $service = new Service();

//             $checkAuth=$service->checkAuth();
    
//             if($checkAuth){
//                return $checkAuth;
//             }

//             Order::whereId($orderId)->update(['status' =>TypeOfType::whereLibelle('rejected')->first()->id ]);

//             return response()->json(
//                 ['message' =>'Order canceled successfuly'
//             ],200);

//         }catch(Exception $e){
//             return response()->json([
//                 'error' => $e->getMessage()
//             ]);
//         }
//     }


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

           $paidStatusId = TypeOfType::whereLibelle('paid')->first()->id;
           $validatedStatusId = TypeOfType::whereLibelle('validated')->first()->id;
           $startedStatusId = TypeOfType::whereLibelle('started')->first()->id;

           
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
            
            if(Order::find($order_detail->order_id)->status == $paidStatusId || Order::find($order_detail->order_id)->status == $validatedStatusId || Order::find($order_detail->order_id)->status == $startedStatusId){
                 return response()->json(
                     ['message' =>'You cannot delete this order detail when the status of order it belong to is already change'
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
           $paidStatusId = TypeOfType::whereLibelle('paid')->first()->id;
           $validatedStatusId = TypeOfType::whereLibelle('validated')->first()->id;
           $startedStatusId = TypeOfType::whereLibelle('started')->first()->id;

           if(!$order_detail){
            return response()->json(
                ['message' =>'This order detail not found'
            ],200);
           }

           if(Order::find($order_detail->order_id)->status == $paidStatusId || Order::find($order_detail->order_id)->status == $validatedStatusId || Order::find($order_detail->order_id)->status == $startedStatusId){
            return response()->json(
                ['message' =>'You cannot edit this order detail when the status of order it belong to is already change'
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
                $order_detail->amount = $item['final_price_product'] *  $item['quantity_product'];
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
            $cartItems=[];

            // $cartItems =  Cart::where('user_id', $user->id)->whereIn('id',$request->cartItemids)->get()  ;

            // return Cart::where('user_id', $user->id)->get();
            $cartIds = [];

            foreach(Cart::where('user_id', $user->id)->get() as $cartItem){
                foreach($request->cartItemids as $adId){
                    if($cartItem->ad_id == $adId){
                        $cartItems[] = Cart::where('user_id', $user->id)->where('ad_id',$adId)->first();
                        $cartIds[] = Cart::where('user_id', $user->id)->where('ad_id',$adId)->first()->id;
                    }
                }
            }



            $ads=[]  ;

            foreach ($cartItems as $cartItem) {
                $ads[] =  [
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

            $flatAds = array_merge($ads);

                    $total = array_sum(array_map(function ($item) {
                        return floatval($item['final_price_product']) * $item['quantity_product'];
                        }, $flatAds));

                        $cartitemsnumber = count($cartItems);
                        
                        if ($cartitemsnumber== 0) {
                            return response()->json(['error' => 'Cart is empty'], 400);
                         }

                         $request = new Request();

                        $orderId = $this->storeOrder($total,$request);

                        foreach ($ads as $tab) {
                            $this->storeOrderDetail($tab,$orderId);
                        }

               foreach($cartIds as $cartId){
                    Cart::whereId($cartId)->first()->delete();
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
 *      path="/api/order/payOrder/{orderId}",
 *     summary="Pay for an order",
 *     tags={"Orders"},
 *  security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="orderId",
 *         in="path",
 *         description="ID of the order to be paid",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="longitude",
 *                 type="number",
 *                 description="Longitude of the user's location"
 *             ),
 *             @OA\Property(
 *                 property="latitude",
 *                 type="number",
 *                 description="Latitude of the user's location"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment done successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Payment done Successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Validation error message"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
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
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Error message"
 *             )
 *         )
 *     )
 * )
 */

    public function payOrder($orderId, Request $request){
        try {
            // return 1;

            $request->validate([
                'longitude' => 'required',
                'latitude' => 'required'
            ]);

            $service = new Service();
            $personId = $service->returnPersonIdAuth();

            $order = Order::find($orderId);

            if(!$order){
                return response()->json(
                    ['message' => 'Order not found'
                ],200);
            }

            $checkIfOrderIsPaid = $this->checkIfOrderIsPending($orderId);
            
            if($checkIfOrderIsPaid){
                return $checkIfOrderIsPaid;
            }
            
            // return 1;

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

        (new WalletService())->updateUserWallet($personId,$diff);

          (new EscrowController)->createEscrow($orderId);

        $order->status = TypeOfType::whereLibelle('paid')->first()->id;

       $order->save();

    //    return 1;

       $this->notifyParty($orderId,$request->longitude,$request->latitude);

        (new UserDetailController())->generateUserDetail($request->longitude,$request->latitude,$order->user_id);


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

    public function notifyParty($orderId,$longitude,$latitude){
        $order = Order::where('id',$orderId)->first();

        $this->notifyBuyer(Order::where('id',$orderId)->first()->uid);


        $orderDetails = OrderDetail::where('order_id',$order->id)->get();


        foreach($orderDetails as $orderDetail){
            $ad = Ad::whereId($orderDetail->ad_id)->first();

            $seller = User::whereId($ad->owner_id)->first();
             $this->notifySeller($seller->id);
        }

        // $notification = new DeliveryAgencyController();
        // $notification->notifyDeliveryAgents($orderUid);

        (new ZoneController())->isWithinDeliveryZone($longitude, $latitude, Order::where('id',$orderId)->first()->uid);

        return 'sent';

    }

  

    public function checkIfOrderIsPending($orderId){
        $statusId = TypeOfType::whereLibelle('pending')->first()->id;
        if(!$statusId){
            return response()->json([
                'message' => 'Status not found'
            ]);
        }
        $order = Order::find($orderId);
        if($order->status != $statusId){
            return response()->json([
                'message' => 'Order status must be pending'
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
            $typeId = Commission::whereShort('STD')->first()->id;
            $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();

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

            $typeId = Commission::whereShort('STD')->first()->id;
            $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();
            $walletAmount = $wallet->balance;

            $diff = $walletAmount - $orderAmount;

            return $diff;

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    

 

    /**
 * @OA\Get(
 *     path="/api/order/orderTrade/{orderId}",
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

    public function orderTrade($orderId){
        try{
            $order =  Order::find($orderId);
            $data = [];
            foreach(OrderDetail::where('order_id',$order->id)->get() as $od){
                $trades = Trade::where('order_detail_id',$od->id)->first();
                if($trades != null){
                $data[] =$trades;}
            }
            return response()->json(
                ['data' =>$data
            ],200);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


     /**
 * @OA\Get(
 *     path="/api/order/orderValidatedTrade/{orderId}",
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


    public function orderValidatedTrade($orderId){
        try{
            $order =  Order::find($orderId);
            $data = [];
            foreach(OrderDetail::where('order_id',$order->id)->get() as $od){
                $trades = Trade::where('order_detail_id',$od->id)->where('admin_validate',true)->first();
                if($trades != null){
                $data[] =$trades;}
            }
            return response()->json(
                ['data' =>$data
            ],200);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getOrderEndTrade($orderId) {
        try {
            $order = Order::find($orderId);
    
            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }
    
            $statut_trade_id = TypeOfType::whereLibelle('endtrade')->first()->id;
    
            if (!$statut_trade_id) {
                return response()->json([
                    'error' => 'End trade status not found'
                ], 404);
            }
    
            $data = [];
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
    
            foreach ($orderDetails as $od) {
                $trade = Trade::where('order_detail_id', $od->id)
                              ->where('status_id', $statut_trade_id)
                              ->first();
    
                if ($trade) {
                    $trade->state = $trade->status_id == $statut_trade_id;
                    if ($trade->state) {
                        $data[] = $trade;
                    }
                }
            }
    
            if (empty($data)) {
                $data = 'No trades found';
            }
    
            return response()->json([
                'data' => $data,
                'dataCount' => count($data)
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function getOrderCanceledTrade($orderId) {
        try {
            $order = Order::find($orderId);
    
            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }
    
            $statut_trade_id = TypeOfType::whereLibelle('canceltrade')->first()->id;
    
            if (!$statut_trade_id) {
                return response()->json([
                    'error' => 'Cancel trade status not found'
                ], 404);
            }
    
            $data = [];
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
    
            foreach ($orderDetails as $od) {
                $trade = Trade::where('order_detail_id', $od->id)
                              ->where('status_id', $statut_trade_id)
                              ->first();
    
                if ($trade) {
                    $trade->state = $trade->status_id == $statut_trade_id;
                    if ($trade->state) {
                        $data[] = $trade;
                    }
                }
            }
    
            if (empty($data)) {
                $data = 'No trades found';
            }
    
            return response()->json([
                'data' => $data
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/order/getAllFinalizedOrders",
 *     summary="Get all finalized orders",
 *     description="Retrieve all orders that have OrderDetails with Trades having the status 'endtrade' or 'canceltrade'.",
 *     tags={"Orders"},
 *  security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of finalized orders",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             ),
 *             @OA\Property(
 *                 property="count",
 *                 type="integer",
 *                 example=10
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Trade statuses not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Trade statuses not found"
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
    public function getAllFinalizedOrders(){
        try {
            $endTradeStatusId = TypeOfType::whereLibelle('endtrade')->first()->id;
            $cancelTradeStatusId = TypeOfType::whereLibelle('canceltrade')->first()->id;
    
            if (!$endTradeStatusId || !$cancelTradeStatusId) {
                return response()->json([
                    'error' => 'Trade statuses not found'
                ], 404);
            }
    
            $finalizedOrders = [];
    
            // Retrieve all orders
            $orders = Order::all();
    
            foreach ($orders as $order) {
                $orderDetails = OrderDetail::where('order_id', $order->id)->get();
                $isFinalized = false;
    
                foreach ($orderDetails as $od) {
                    $trade = Trade::where('order_detail_id', $od->id)
                                  ->whereIn('status_id', [$endTradeStatusId, $cancelTradeStatusId])
                                  ->first();
    
                    if ($trade) {
                        $isFinalized = true;
                        break;
                    }
                }
    
                if ($isFinalized) {
                    $finalizedOrders[] = $order;
                }
            }
    
            if (empty($finalizedOrders)) {
                return response()->json([
                    'message' => 'No finalized orders found'
                ], 200);
            }
    
            return response()->json([
                'data' => $finalizedOrders,
                'count' => count($finalizedOrders)
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/order/userOrders/{perpage}",
     *     summary="Get all orders of the authenticated user",
     *     description="Returns a list of all orders placed by the authenticated user",
     *     tags={"Orders"},
     *  @OA\Parameter(
 *         name="perpage",
 *         in="path",
 *         required=true,
 *         description="number of elements per page",
 *         @OA\Schema(type="integer")
 *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function userOrders($perpage)
    {
        try {

            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }
            
            $user = Auth::user();

            $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')->paginate($perpage);

            return response()->json([
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
        
    }


     /**
     * @OA\Get(
     *     path="/api/order/getMerchantOrder",
     *     summary="Get all orders of the merchant",
     *     description="Returns a list of all orders placed by the merchant",
     *     tags={"Orders"},

     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function getMerchantOrder()
{
    try {
        $service = new Service();
        $checkAuth = $service->checkAuth();
        if ($checkAuth) {
            return $checkAuth;
        }

        $checkIfmerchant = (new AdController())->checkMerchant();
        if ($checkIfmerchant == 0) {
            return response()->json([
                'message' => 'You are not merchant'
            ], 200);
        }

        $userShops = (new ShopController())->anUserShop(Auth::user()->id)->pluck('id')->toArray();
        if (empty($userShops)) {
            return response()->json([
                'data' => 0
            ]);
        }

        $orderDetails = OrderDetail::whereIn('shop_id', $userShops)
            ->whereDeleted(false)
            ->get();

        $orderIds = $orderDetails->pluck('order_id')->unique();

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            $shopUid = Shop::find($order->order_details->first()->shop_id)->uid;
            $order->ads = (new ShopController())->getShopOrderAds($order->uid, $shopUid)->original['data']['ads'];
            $order->ads_number = (new ShopController())->getShopOrderAds($order->uid, $shopUid)->original['data']['number'];
            $order->ad_image = File::whereReferencecode((new ShopController())->getShopOrderAds($order->uid, $shopUid)->original['data']['ads'][0]->file_code)->first()->location;
            $order->statut =  TypeOfType::whereId($order->status)->first()->libelle;
        }

        return response()->json([
            'data' => $orders,
            'number' => count($orders)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}




        /**
 * @OA\Post(
 *     path="/api/order/CreateAndPayOrder",
 *     summary="Create and pay for an order",
 *     tags={"Orders"},
 *      security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="longitude",
 *                 type="number",
 *                 description="Longitude of the user's location"
 *             ),
 *             @OA\Property(
 *                 property="latitude",
 *                 type="number",
 *                 description="Latitude of the user's location"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order created and payment done successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Payment done Successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Validation error message"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
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
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Error message"
 *             )
 *         )
 *     )
 * )
 */
    public function CreateAndPayOrder(Request $request){
        try{

            $request->validate([
                'longitude' => 'required',
                'latitude' => 'required'
            ]);

            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }

            
            $orderId= $this->CreateAnOrder(1);
            

            if(!is_numeric($orderId)){
                return response()->json([
                    'message' => 'It may be that you have already created the order because your cart is empty, consult the list of your orders to pay for it there'
              ],200);
            }

            $PayOrder = $this->PayOrder($orderId, $request);

            $PayOrder;
            if ($PayOrder) {
                $response = [];

                if (isset($PayOrder->original['message'])) {
                    $response['message'] = $PayOrder->original['message'];
                }

                if (isset($PayOrder->original['error'])) {
                    $response['error'] = $PayOrder->original['error'];
                }

                return response()->json([
                    'message' => $PayOrder->original['message']
                    // 'message' => $response['message']->original['message']
                ]);
            }


        }catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function notifyBuyer($orderUid) {
        try {


            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            $user = User::whereId(Order::whereUid($orderUid)->first()->user_id)
            ->first();

           $service = new Service();
           $personId = $service->returnUserPersonId($user->id);
           $balance = $service->returnSTDPersonWalletBalance($personId);

            $title = "Payment Successful: Wallet Debited";
            $body = "Your order has been placed successfully. Your wallet has been debited, and your new balance is $balance XOF. Thank you for your purchase!";



            dispatch(new SendEmail($user->id,$title,$body,2));

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function notifySeller($userId) {
        try {

            $title = "New Order Placed: Action Required";
            $body = "One of your products has just been ordered. Please start the necessary steps to complete the transaction. Thank you!";

            dispatch(new SendEmail($userId,$title,$body,2));

    
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

/**
 * @OA\Get(
 *     path="/api/order/getOrderAds/{orderUid}",
 *     tags={"Orders"},
 *     summary="Récupère les produits d'une commande",
 *     description="Cette route permet de récupérer la liste des produits associées à une commande spécifique.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="orderUid",
 *         in="path",
 *         required=true,
 *         description="UID de la commande",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des produits dans la commande",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Commande non trouvée",
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


    public function getOrderAds($orderUid){
        try {

            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            $order = Order::whereUid($orderUid)->first();
            if(!$order){
                return (new Service())->apiResponse(404 , [], 'Order not found');
            }

            // return $order->order_details;

            $ad = [];

            foreach($order->order_details as $detail){
                // return $detail;
                $adItem= Ad::whereDeleted(0)->whereId($detail->ad_id)->first();
                $adItem->quantity_sale = $detail->quantity;

                $ad[] = $adItem;
            }
            $order->ad = $ad;
            $order->order_statut =  TypeOfType::whereId($order->status)->first()->libelle;

            unset($order->order_details);

            return (new Service())->apiResponse(200, $order, 'list of ad in order');
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    /**
 * @OA\Get(
 *     path="/api/order/getMerchantOrderAds/{orderUid}",
 *     tags={"Orders"},
 *     summary="Récupère les produits d'une commande d'un marchand",
 *     description="Cette route permet de récupérer la liste des produits d'un marchand associées à une commande spécifique.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="orderUid",
 *         in="path",
 *         required=true,
 *         description="UID de la commande",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des produits dans la commande",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Commande non trouvée",
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
    public function getMerchantOrderAds($orderUid){
        try {

            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            $order = Order::whereUid($orderUid)->first();
            if(!$order){
                return (new Service())->apiResponse(404 , [], 'Order not found');
            }

            // return $order->order_details;

            $ad = [];
            $order->order_statut =  TypeOfType::whereId($order->status)->first()->libelle;

            foreach($order->order_details as $detail){
                // return $detail;
                
                $adElement= Ad::whereDeleted(0)->whereId($detail->ad_id)->whereOwnerId(Auth::user()->id)->first();
                $adItem = $adElement;
                $adItem->quantity_sale = $detail->quantity;

                if(File::where('referencecode',$adElement->file_code)->exists()){
                    $adItem->image = File::where('referencecode',$adElement->file_code)->first()->location;
                }

                $ad[] = $adItem;
            }
            $order->adNumber = count($ad);
            $order->adItems = $ad;
           

            unset($order->order_details);

            return (new Service())->apiResponse(200, $order, 'list of ad in order');
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    public function getMerchantOrderWithDelivery()
{
    try {
        $service = new Service();
        $checkAuth = $service->checkAuth();
        if ($checkAuth) {
            return $checkAuth;
        }

        $checkIfmerchant = (new AdController())->checkMerchant();
        if ($checkIfmerchant == 0) {
            return response()->json([
                'message' => 'You are not merchant'
            ], 200);
        }

        $userShops = (new ShopController())->anUserShop(Auth::user()->id)->pluck('id')->toArray();
        if (empty($userShops)) {
            return (new Service())->apiResponse(404, 0, 'detail');
        }

        $orderDetails = OrderDetail::whereIn('shop_id', $userShops)
            ->whereDeleted(false)
            ->get();

        $orderIds = $orderDetails->pluck('order_id')->unique();

        $orderswithdelivery = [];

        foreach (Order::whereIn('id', $orderIds)->get() as $order) {
            $escrowDelivery = EscrowDelivery::where('order_uid', $order->uid)->first();
            if ($escrowDelivery) {
                $orderswithdelivery[] = $order->id;
            }
        }

        $orders = [];

        foreach ($orderswithdelivery as $orderId) {

            $order = Order::whereId($orderId)->first();
            if(!$order){
                return (new Service())->apiResponse(404, [], 'Order not found');
            }

            $escrowDelivery = EscrowDelivery::where('order_uid', $order->uid)->first();
            
            if ($escrowDelivery) {
                // Commande affectée à un livreur, ajouter les informations du livreur et de livraison
                $deliveryPerson = Person::where('uid', $escrowDelivery->person_uid)->first();

                $order->Adimage = File::whereReferencecode((new OrderController())->getMerchantOrderAds($order->uid)->original['data'][0]['file_code'])->first()->location;
                $order->Adnumber =  count((new OrderController())->getOrderAds($order->uid)->original['data']);
                // $order->ad= (new OrderController())->getOrderAds($order->uid)->original['data'];

                $order->delivery_person = [
                    'name' => $deliveryPerson->first_name ?? 'N/A',
                    'email' => User::whereId($deliveryPerson->user_id)->first()->email ,
                    'phone' =>User::whereId($deliveryPerson->user_id)->first()->phone ?? 'N/A',
                    'image' => User::whereId($deliveryPerson->user_id)->first()->person->file!=null?  User::whereId($deliveryPerson->user_id)->first()->person->file->location:null ?? 'N/A',
                ];
                $order->delivery_info = [
                    // 'pickup_date' => $escrowDelivery->pickup_date,
                    // 'delivery_date' => $escrowDelivery->delivery_date,
                    'delivery_agent_amount' => $escrowDelivery->delivery_agent_amount,
                  
                ];
            }

            $orders[] = $order;
        }

        return response()->json([
            'data' => $orders
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


}