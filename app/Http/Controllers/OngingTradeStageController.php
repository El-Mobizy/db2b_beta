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

    public function makeCompleteTradeStage($ongingtradeStageId){
        try {

            $tradeStage = OngingTradeStage::find($ongingtradeStageId);

            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }

            $tradeStage->update(['complete' =>true]);

            return response()->json([
                'message' => 'Trade stage complete successfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function makeInCompleteTradeStage($ongingtradeStageId){
        try {

            $tradeStage = OngingTradeStage::find($ongingtradeStageId);

            if(!$tradeStage){
                return response()->json([
                    'message' => 'Trade stage not found'
                ],200);
            }

            $tradeStage->update(['complete' =>false]);

            return response()->json([
                'message' => 'Trade stage complete successfully'
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


}
