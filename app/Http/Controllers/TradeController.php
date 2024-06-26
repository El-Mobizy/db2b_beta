<?php

namespace App\Http\Controllers;

use App\Models\OngingTradeStage;
use App\Models\OrderDetail;
use App\Models\Trade;
use App\Models\TradeChat;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TradeController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/trade/createTrade",
 *     summary="Create a new trade",
 *  security={{"bearerAuth": {}} },
 * tags={"Trade"},
 *     @OA\Parameter(
 *         name="orderDetailId",
 *         in="query",
 *         description="Order detail ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="buyerId",
 *         in="query",
 *         description="Buyer ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="sellerId",
 *         in="query",
 *         description="Seller ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="enddate",
 *         in="query",
 *         description="End date of the trade",
 *         required=true,
 *         @OA\Schema(type="string", format="date-time")
 *     ),
 *     @OA\Parameter(
 *         name="amount",
 *         in="query",
 *         description="Trade amount",
 *         required=true,
 *         @OA\Schema(type="number", format="float")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade created successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     )
 * )
 */
    public function createTrade($orderDetailId, $buyerId, $sellerId,$enddate, $amount){
        try{

            $trade = new Trade();
            $service = new Service();
            $trade->order_detail_id = $orderDetailId;
            $trade->ad_id = OrderDetail::find($orderDetailId)->ad_id;
            $trade->buyer_id = $buyerId;
            $trade->seller_id = $sellerId;
            $trade->status_id = TypeOfType::whereLibelle('pending')->first()->id;
            $trade->ref = 'Trade-'.$service->generateRandomAlphaNumeric(7,$trade,'ref') ;
            $trade->uid = $service->generateUid($trade);
            $trade->amount = $amount;
            $trade->enddate = $enddate;
            if($trade->save()){
                $OngingTradeStage = new OngingTradeStageController();
                $OngingTradeStage->ongingCreateTradeStage($trade->id);
                return response()->json([
                    'message' => 'Trade created sucessfully'
                ],200);
            }else{
                return response()->json([
                    'message' => 'No'
                ],200);
            }
           
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function updateTradeStatusCompleted($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('completed')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Post(
 *     path="/api/trade/updateTradeStatusCanceled{tradeId}",
 *     summary="Update trade status to canceled",
 *  security={{"bearerAuth": {}} },    
 * tags={"Trade"},
 *     @OA\Parameter(
 *         name="tradeId",
 *         in="path",
 *         description="Trade ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     )
 * )
 */
    public function updateTradeStatusCanceled($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('canceled')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Post(
 *     path="/api/trade/updateTradeStatusDisputed/{tradeId}",
 *     summary="Update trade status to disputed",
 *  security={{"bearerAuth": {}} },    
 * tags={"Trade"},
 *     @OA\Parameter(
 *         name="tradeId",
 *         in="path",
 *         description="Trade ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     )
 * )
 */

    public function updateTradeStatusDisputed($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('disputed')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/trade/displayTrades",
 *     summary="Display all trades for the authenticated user",
 *  security={{"bearerAuth": {}} },    
 * tags={"Trade"},
 *     @OA\Response(
 *         response=200,
 *         description="Trades retrieved successfully",
 *         @OA\JsonContent(
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
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     ),
 *    
 * )
 */

    public function displayTrades(){
        try {
            $userId = Auth::user()->id;

            $trades = Trade::where('buyer_id', $userId)->orWhere('seller_id', $userId)->get();

            foreach($trades as $trade){
                if($trade->buyer_id == Auth::user()->id){
                    $trade->type = 'Buyer';
                }else if($trade->seller_id == Auth::user()->id){
                    $trade->type = 'Seller';
                }

                $data[] = $trade;
            }

            return response()->json([
                'data' => $data,
                'number' => count($data)
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/trade/displayTradesWithoutEndDate",
 *     summary="Display trades without end date",
 *  security={{"bearerAuth": {}} },    
 * tags={"Trade"},
 *     @OA\Response(
 *         response=200,
 *         description="List of trades without end date",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     )
 * )
 */
        public function displayTradesWithoutEndDate(){
            try {

                $userId = Auth::user()->id;
                $trades = Trade::where('enddate','1000-10-10 10:10:10')->where('buyer_id', $userId)->orWhere('seller_id', $userId)->get();

                foreach($trades as $trade){
                    if($trade->buyer_id == Auth::user()->id){
                        $trade->type = 'Buyer';
                    }else if($trade->seller_id == Auth::user()->id){
                        $trade->type = 'Seller';
                    }
    
                    $data[] = $trade;
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
 *     path="/api/trade/updateEndDate/{tradeId}",
 *     summary="Update end date of a trade",
 *  security={{"bearerAuth": {}} },    
 * tags={"Trade"},
 *     @OA\Parameter(
 *         name="tradeId",
 *         in="path",
 *         description="Trade ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="enddate", type="string", format="date-time", example="2024-06-24 10:00:00")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="End date updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="End date updated successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The end date field is required.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     )
 * )
 */

    public function updateEndDate($tradeId,Request $request){
        try {

                $request->validate([
                    'enddate' => 'required'
                ]);

                $trade = Trade::find($tradeId);

                if(
                    ($trade->buyer_id != Auth::user()->id)  &&
                     ($trade->seller_id != Auth::user()->id) ){
                        return response()->json([
                            'message' => 'YOU ARE NOT ALLOWED !'
                        ],200);
                }

               $trade->update([
                    'enddate' => $request->input('enddate')
                ]);

                return response()->json([
                    'message' => 'end date updated successfully !'
                ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/trade/getTradeStage/{tradeId}",
     *     summary="Get stages of a trade",
     *  security={{"bearerAuth": {}} },    
     * tags={"Trade"},
     *     @OA\Parameter(
     *         name="tradeId",
     *         in="path",
     *         description="Trade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of trade stages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="trade_id", type="integer", example=123),
     *                 @OA\Property(property="stage", type="string", example="Stage name"),
     *                 @OA\Property(property="buyer", type="boolean", example=true),
     *                 @OA\Property(property="seller", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */

    public function getTradeStage($tradeId){
        try {
            $stages = OngingTradeStage::where('trade_id', $tradeId)
            ->where('deleted', false)
            ->orderBy('steporder', 'asc')
            ->get();


            if(count($stages) == 0){
                return response()->json([
                    'message' => 'No stage available for this trade'
                ],200);
            }
            
         $data = $this->tradeStage($tradeId,$stages);

            return response()->json([
                'data' => $data
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    public function tradeStage($tradeId,$stages){
        try {

            if(count($stages) == 0){
                return response()->json([
                    'message' => 'No stage available for this trade'
                ],200);
            }

            foreach($stages as $stage){

                $checkBuyerTradeStage = $this->checkBuyerTradeStage($tradeId,$stage);
                $checkSellerTradeStage = $this->checkSellerTradeStage($tradeId,$stage);

                if( $checkBuyerTradeStage){
                    $stage->buyer = true;
                    $stage->seller = false;
                }
               else if($checkSellerTradeStage){
                    $stage->buyer = false;
                    $stage->seller = true;
                }else{
                    $stage->buyer = false;
                    $stage->seller = false;
                }
                $data[] = $stage ;
            }

            return $data;

            // return response()->json([
            //     'data' => $stages
            // ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/trade/getTradeStageDone/{tradeId}",
     * security={{"bearerAuth": {}} },
     *     summary="Get completed stages of a trade",
     *     tags={"Trade"},
     *     @OA\Parameter(
     *         name="tradeId",
     *         in="path",
     *         description="Trade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of completed trade stages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="trade_id", type="integer", example=123),
     *                 @OA\Property(property="stage", type="string", example="Stage name"),
     *                 @OA\Property(property="complete", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */

    public function getTradeStageDone($tradeId){
        try {

            $stages =  OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->whereComplete(true)->get();

            $data = $this->tradeStage($tradeId,$stages);

            return response()->json([
                'data' =>$data
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/trade/getTradeStageNotDoneYet/{tradeId}",
     *  security={{"bearerAuth": {}} },
     *     summary="Get stages not completed yet of a trade",
     *     tags={"Trade"},
     *     @OA\Parameter(
     *         name="tradeId",
     *         in="path",
     *         description="Trade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of not completed trade stages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="trade_id", type="integer", example=123),
     *                 @OA\Property(property="stage", type="string", example="Stage name"),
     *                 @OA\Property(property="complete", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */

    public function getTradeStageNotDoneYet($tradeId){
        try {
            $stages = OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->whereComplete(false)->get();

            $data = $this->tradeStage($tradeId,$stages);

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
     * @OA\Get(
     *     path="/api/trade/getTradeChat/{tradeId}",
     *  security={{"bearerAuth": {}} },
     *     summary="Get chat messages of a trade",
     *     tags={"Trade"},
     *     @OA\Parameter(
     *         name="tradeId",
     *         in="path",
     *         description="Trade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of chat messages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="trade_id", type="integer", example=123),
     *                 @OA\Property(property="message", type="string", example="Hello"),
     *                 @OA\Property(property="sender_id", type="integer", example=456),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */

    public function getTradeChat($tradeId){
        try {
            $chats = TradeChat::where('trade_id',$tradeId)->whereDeleted(false)->get();

            return response()->json([
                'data' => $chats
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/trade/getAuthTradeStage/{tradeId}",
     *  security={{"bearerAuth": {}} },
     *     summary="Get authenticated user's trade stage details",
     *     tags={"Trade"},
     *     @OA\Parameter(
     *         name="tradeId",
     *         in="path",
     *         description="Trade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade stage details for the authenticated user",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="trade_id", type="integer", example=123),
     *                 @OA\Property(property="stage", type="string", example="Stage name"),
     *                 @OA\Property(property="buyer", type="boolean", example=true),
     *                 @OA\Property(property="seller", type="boolean", example=false),
     *                 @OA\Property(property="action_done_by", type="string", example="BUYER"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 10:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="No data found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */

    public function getAuthTradeStage($tradeId){
        try {
            $stages = OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->get();

            foreach($stages as $stage){

                $checkBuyerTradeStage = $this->checkBuyerTradeStage($tradeId,$stage);
                $checkSellerTradeStage = $this->checkSellerTradeStage($tradeId,$stage);

                if( $checkBuyerTradeStage){
                    $stage->buyer = true;
                    $stage->seller = false;

                    return response()->json([
                        'data' => $stage->where('action_done_by','BUYER')->where('trade_id',$tradeId)->get()
                    ],200);

                }
               else if($checkSellerTradeStage){

                    $stage->buyer = false;
                    $stage->seller = true;
                    return response()->json([
                        'data' => $stage->where('action_done_by','SELLER')->where('trade_id',$tradeId)->get()
                    ],200);

                }else{
                    $stage->buyer = false;
                    $stage->seller = false;

                    return response()->json([
                        'data' => 'No data found'
                    ],200);
                }
                // $data[] = $stage ;
            }


        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function checkBuyerTradeStage($tradeId,$stage){
        try {

            if(Auth::user()->id == Trade::find($tradeId)->buyer_id && $stage->action_done_by =='BUYER'){
               return true;
            }
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function checkSellerTradeStage($tradeId,$stage){
        try {

            if(Auth::user()->id == Trade::find($tradeId)->seller_id && $stage->action_done_by =='SELLER'){
               return true;
            }
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function getEndTrade(){
        try {
            $statut_trade_id = TypeOfType::whereLibelle('endtrade')->first()->id;
            $trades = Trade::where('status_id',$statut_trade_id)->whereDeleted(0)->get();
        } catch(Exception $e){
            return response()->json([
                'data' => $trades
            ],500);
        }
    }

    public function getCanceledTrade(){
        try {
            $statut_trade_id = TypeOfType::whereLibelle('canceled')->first()->id;
            $trades = Trade::where('status_id',$statut_trade_id)->whereDeleted(0)->get();
        } catch(Exception $e){
            return response()->json([
                'data' => $trades
            ],500);
        }
    }
  
}

// try {
//     //code...
// } catch(Exception $e){
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }