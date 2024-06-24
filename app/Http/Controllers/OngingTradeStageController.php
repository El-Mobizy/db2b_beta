<?php

namespace App\Http\Controllers;

use App\Models\OngingTradeStage;
use App\Models\Trade;
use App\Models\TradeStage;
use App\Models\TypeOfType;
use Illuminate\Http\Request;
use Exception;

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
        $a = $this->makeActionOnTradeStage($ongingtradeStageId,true,'trade stage complete successfully');

        if($a){
            return $a;
        }
    }

    //makeInCompleteTradeStage

    public function makeInCompleteTradeStage($ongingtradeStageId){

        $tradeStage = OngingTradeStage::find($ongingtradeStageId);

        if ($tradeStage->complete == false) {
            return response()->json([
                'message' => 'Trade stage already uncomplete'
            ],200);
        }
        $a = $this->makeActionOnTradeStage($ongingtradeStageId,true,'trade stage complete successfully');

        if($a){
            return $a;
        }
    }

    public function makeActionOnTradeStage($ongingtradeStageId,$response,$message){
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

            $tradeStage->update(['complete' =>$response]);

            return response()->json([
                'message' =>$message
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

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

    public function yes_action($ongingtradeStageId){
        try {

            $tradeStage = OngingTradeStage::find($ongingtradeStageId);

            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }
            if($tradeStage->yes_action == 'MOVE_TO_NEXT_STEP'){
                if($tradeStage->next_step_id != null){
                    return response()->json([
                        'message' =>'MOVE_TO_NEXT_STEP',
                        'data' =>  $tradeStage->next_step_id
                    ]);
                }else{
                    return response()->json([
                        'message' =>'null',
                        'data' =>  $tradeStage->next_step_id
                    ]);
                }
            }
            else if($tradeStage->yes_action == 'END_TRADE'){
                Trade::whereId($tradeStage->trade_id)->update(['status_id'=>TypeOfType::whereLibelle('endtrade')->first()->id]);
                return response()->json([
                    'message' => 'END_TRADE'
                ]);
            }
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function no_action($ongingtradeStageId){
        try {

            $tradeStage = OngingTradeStage::find($ongingtradeStageId);

            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }
            if($tradeStage->yes_action == 'MOVE_TO_PREV_STEP'){
                if($tradeStage->next_step_id != null){
                    return response()->json([
                        'message' =>'MOVE_TO_PREV_STEP',
                        'data' =>  $tradeStage->previous_step_id
                    ]);
                }else{
                    return response()->json([
                        'message' =>'null',
                        'data' =>  $tradeStage->next_step_id
                    ]);
                }
            }
            else if($tradeStage->yes_action == 'CANCEL_TRADE'){
                Trade::whereId($tradeStage->trade_id)->update(['status_id'=>TypeOfType::whereLibelle('canceltrade')->first()->id]);
                return response()->json([
                    'message' => 'CANCEL_TRADE'
                ]);
            }
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

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

}
