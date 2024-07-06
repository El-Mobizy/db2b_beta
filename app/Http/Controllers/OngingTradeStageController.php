<?php

namespace App\Http\Controllers;

use App\Models\CommissionWallet;
use App\Models\Escrow;
use App\Models\OngingTradeStage;
// use App\Services\OngingStage;
use App\Models\Order;
use App\Models\Trade;
use App\Models\TradeStage;
use App\Models\TypeOfType;
use App\Services\OngingTradeStageService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;

class OngingTradeStageController extends Controller
{
    function ongingCreateTradeStage($tradeId){
        try {

            $service = new Service();
                $tradeStages = TradeStage::whereDeleted(0)->get();
                foreach($tradeStages as $tradeStage){
                    $OngingTradeStage = new OngingTradeStage();
                    $OngingTradeStage->trade_id = $tradeId;
                    $OngingTradeStage->stage_title = $tradeStage->stage_title;
                    $OngingTradeStage->steporder = $tradeStage->steporder;
                    $OngingTradeStage->yes_action = $tradeStage->yes_action;
                    $OngingTradeStage->no_action = $tradeStage->no_action;
                    $OngingTradeStage->action_done_by = $tradeStage->action_done_by;
                    $OngingTradeStage->uid = $service->generateUid($OngingTradeStage);
                    $OngingTradeStage->save();
                }
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/ongingtradeStage/makeCompleteTradeStage/{ongingtradeStageId}",
 *     summary="Mark a trade stage as complete",
 *     tags={"OnGoingTradeStage"},
 * security={{"bearerAuth": {}} },    
 *     @OA\Parameter(
 *         name="ongingtradeStageId",
 *         in="path",
 *         description="Ongoing trade stage ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade stage marked as complete successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade stage complete successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Trade stage already complete",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade stage already complete")
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

    public function makeCompleteTradeStage($ongingtradeStageId){
        $tradeStage = OngingTradeStage::find($ongingtradeStageId);

        if ($tradeStage->complete == true) {
            return response()->json([
                'message' => 'Trade stage already complete'
            ],200);
        }
        $a = $this->checkAuthAction($ongingtradeStageId,true);

        if($a){
            return $a;
        }

        return response()->json([
            'message' =>'trade stage complete successfully'
        ],200);
    }

    //makeInCompleteTradeStage

    /**
 * @OA\Post(
 *     path="/api/ongingtradeStage/makeInCompleteTradeStage/{ongingtradeStageId}",
 *     summary="Mark a trade stage as incomplete",
 *     tags={"OnGoingTradeStage"},
 * security={{"bearerAuth": {}} },    
 *     @OA\Parameter(
 *         name="ongingtradeStageId",
 *         in="path",
 *         description="Ongoing trade stage ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade stage marked as incomplete successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade stage marked as incomplete successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Trade stage already incomplete",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade stage already incomplete")
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

    public function makeInCompleteTradeStage($ongingtradeStageId){

        $tradeStage = OngingTradeStage::find($ongingtradeStageId);

        if ($tradeStage->complete == false) {
            return response()->json([
                'message' => 'Trade stage already uncomplete'
            ],200);
        }
        $a = $this->checkAuthAction($ongingtradeStageId,true);

        if($a){
            return $a;
        }

        return response()->json([
            'message' =>'trade stage uncomplete successfully'
        ],200);

    }

    public function checkAuthAction($ongingtradeStageId,$response=false){
        try {

            $tradeStage = OngingTradeStage::find($ongingtradeStageId);
            $tradeId = Trade::find($tradeStage->trade_id)->id;

            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }

            $tradeCheck = new TradeController();

            $checkBuyerTradeStage = $tradeCheck->checkBuyerTradeStage($tradeId,$tradeStage);
            $checkSellerTradeStage = $tradeCheck->checkSellerTradeStage($tradeId,$tradeStage);

            if($tradeStage->action_done_by =='BUYER'){
                if(!$checkBuyerTradeStage){
                    return response()->json([
                        'message' => 'NOT ALLOWED'
                    ],200);
                }
            }

            if($tradeStage->action_done_by =='SELLER'){
                if(!$checkSellerTradeStage){
                    return response()->json([
                        'message' => 'NOT ALLOWED'
                    ],200);
                }
            }

            // $tradeStage->update(['complete' =>$response]);

        
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/ongingtradeStage/showTradeStage/{ongingtradeStageId}",
 *     summary="Retrieve details of a trade stage",
 *     tags={"OnGoingTradeStage"},
 * security={{"bearerAuth": {}} },    
 *     @OA\Parameter(
 *         name="ongingtradeStageId",
 *         in="path",
 *         description="Ongoing trade stage ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Details of the trade stage",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Trade stage not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade stage not found")
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

    public function showTradeStage($ongingtradeStageId){
        try {
            $tradeStage = OngingTradeStage::find($ongingtradeStageId);
            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }

            return response()->json([
                'data' =>$tradeStage
            ],200);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


   

    public function checkPreviousStageCompletion($ongoingTradeStageId)
    {
        try {
            $tradeStage = OngingTradeStage::find($ongoingTradeStageId);

            if (!$tradeStage) {
                return response()->json([
                    'message' => 'Ongoing trade stage not found'
                ], 404);
            }

            // Vérifier si previous_step_id est défini
            if ($tradeStage->previous_step_id !== null) {
                // Récupérer l'étape précédente de TradeStage
                $previousTradeStage = TradeStage::find($tradeStage->previous_step_id);

                if (!$previousTradeStage) {
                    return response()->json([
                        'message' => 'Previous trade stage not found'
                    ], 404);
                }
                // return 5;

                // Trouver l'équivalent OngingTradeStage de cette étape précédente
                $previousOngoingTradeStage = OngingTradeStage::where('trade_id', $tradeStage->trade_id)
                 ->where('stage_title', $previousTradeStage->stage_title)
                                                            ->first();

                if (!$previousOngoingTradeStage || !$previousOngoingTradeStage->complete) {
                    return response()->json([
                        'message' => 'Cannot complete current trade stage. Previous stage is not completed.',
                        'previous' => $previousTradeStage,
                        'previous_stage' => $previousOngoingTradeStage
                    ], 400);
                }
            }

            // return true; // L'étape précédente est complétée, autoriser le changement de statut
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

   


          /**
 * @OA\Post(
 * path="/api/ongingtradeStage/handleTradeStageAction/{ongingtradeStageId}/{actionType}",
 *     tags={"OnGoingTradeStage"},
 *     security={{"bearerAuth": {}} },
 *     summary="Handle trade stage action",
 *     description="Handle trade stage action",
 *     operationId="handleTradeStageAction",
 *     @OA\Parameter(
 *         name="ongingtradeStageId",
 *         in="path",
 *         required=true,
 *         description="Onging trade stage ID",
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="actionType",
 *         in="path",
 *         required=true,
 *         description="Action type",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
 public function handleTradeStageAction($ongingtradeStageId, $actionType) {
    try {
        $ongingstage = new OngingTradeStageService();
        $checkAuth = $ongingstage->authenticateUser();
        if ($checkAuth) {
            return $checkAuth;
        }

        $tradeStage = $ongingstage->findTradeStage($ongingtradeStageId);
        if (!$tradeStage) {
            return $ongingstage->tradeStageNotFoundResponse();
        }

        $trade = $ongingstage->findTrade($tradeStage->trade_id);
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        if (!$escrowOrder) {
            return response()->json([
                'message' => 'Order not paid yet !'
            ], 400);
        }
        if ($ongingstage->isTradeFinished($trade)) {
            return $ongingstage->tradeFinishedResponse();
        }

        $authActionCheck = $this->checkAuthAction($ongingtradeStageId);
        if ($authActionCheck) {
            return $authActionCheck;
        }

        return $ongingstage->handleActionType($tradeStage, $trade, $actionType);

    } catch (Exception $e) {
        return $ongingstage->errorResponse($e);
    }
}


   /**
     * @OA\Post(
     *     path="/api/ongingtradeStage/updateOngingTradeStage/{tradeStageId}",
     *     summary="Update an ongoing trade stage",
     *     tags={"OnGoingTradeStage"},
     * security={{"bearerAuth": {}} },
     *     @OA\Parameter(
     *         name="tradeStageId",
     *         in="path",
     *         description="ID of the ongoing trade stage to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="stage_title", type="string", example="Updated Stage Title"),
     *             @OA\Property(property="steporder", type="integer", example=2),
     *             @OA\Property(property="yes_action", type="string", example="MOVE_TO_NEXT_STEP"),
     *             @OA\Property(property="no_action", type="string", example="END_TRADE"),
     *             @OA\Property(property="previous_step_id", type="integer", nullable=true),
     *             @OA\Property(property="next_step_id", type="integer", nullable=true),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade stage updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade stage updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trade stage not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade stage not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */

    public function updateOngingTradeStage(Request $request, $tradeStageId){
        try {
            // return 1;
                $tradeStage = OngingTradeStage::find($tradeStageId);
                 
                if(!$tradeStage){
                    return response()->json([
                        'message' => 'Trade stage not found'
                    ],200);
                }
               
                $previous_step_id = $request->previous_step_id ?? null;
                $next_step_id = $request->next_step_id ?? null;


                $this->editOngingTradeStage($tradeStageId,$request->stage_title, $request->steporder, $request->yes_action, $request->no_action,$previous_step_id,$next_step_id);

                return response()->json([
                    'message' => 'Trade stage updated successfully'
                ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }



    public function editOngingTradeStage($tradeStageId,$stage_title, $steporder, $yes_action, $no_action,$previous_step_id,$next_step_id){
        try {
                $tradeStage = OngingTradeStage::find($tradeStageId);

                $tradeStage->stage_title = $stage_title ?? $tradeStage->stage_title;
                $tradeStage->steporder = $steporder ?? $tradeStage->steporder;
                $tradeStage->yes_action = $yes_action ?? $tradeStage->yes_action;
                $tradeStage->no_action = $no_action ?? $tradeStage->no_action;
                $tradeStage->previous_step_id = $previous_step_id;
                $tradeStage->next_step_id = $next_step_id;
                $tradeStage->save();

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


                // $service = new Service();
              
                // $up = $service-> returnPersonAndUserId($trade->seller_id);
                // $personId =$up['person_id'];
                // // return $personId;
                // $walletSeller = CommissionWallet::where('person_id',$personId)->first();
                // $com = new CommissionWalletController();
                // if(!$walletSeller){
                //     $com->generateStandardUnAuthWallet($personId);
                // }
                // // return  $trade->order_detail->price * $trade->order_detail->quantity;
                // // return $trade->order_detail;
                // if($a == 1){
                //     $credit = $trade->order_detail->price * $trade->order_detail->quantity;
                //     // return $trade->order_detail->order_id;
                //     $order = new OrderController();
                //     $order->updateUserWallet($personId,$credit);
                //     $escrow = Escrow::where('order_id',$trade->order_detail->order_id)->first();
                //     // return $escrow;
                //     if(!$escrow){
                //         return response()->json([
                //             'message' => 'Escrow not found'
                //         ]);
                //     }
                //     $escrow->update(['status'=>' partially_released ']);
                //     $ec = new EscrowController();
                //     $ec->debitEscrow($escrow->id,$credit);
                //     return response()->json([
                //         'message' => 'END_TRADE'
                //     ]);
                // }else{
                //     return response()->json([
                //         'message' => 'Check if all stage are completed'
                //     ]);
                // }

                public function getEndTrade($perPage){
                    try {
                        $trades = Trade::whereDeleted(0)->where('status_id',TypeOfType::whereLibelle('endtrade')->first()->id)
                        ->paginate($perPage)
                        ;
                        return response()->json([
                            'data' => $trades
                        ]);
                    } catch(Exception $e){
                        return response()->json([
                            'error' => $e->getMessage()
                        ],500);
                    }
                }
}
