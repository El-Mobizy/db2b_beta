<?php

namespace App\Http\Controllers;

use App\Models\OngingTradeStage;
use App\Models\Trade;
use App\Models\TradeStage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TradeStageController extends Controller
{
    function createTradeStage($tradeId,Request $request){
        try {

            $tradeStage = new TradeStage();
            $service = new Service();
            $tradeStage->trade_id = $tradeId;
            $tradeStage->stage_title = $request->stage_title;
            $tradeStage->steporder = $request->steporder;
            $tradeStage->yes_action = $request->yes_action;
            $tradeStage->no_action = $request->no_action;
            $tradeStage->action_done_by = $request->action_done_by;
            $tradeStage->uid = $service->generateUid($tradeStage);
            $tradeStage->save();
          
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function  displayTradeStages($tradeId){
        try {
            return response()->json([
                'data' => TradeStage::whereTradeId($tradeId)->whereDeleted(0)->get()
            ],200);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function initializeTradeStage($tradeId, Request $request){

        try {

            $verification = $this-> initializeTradeStageVerification($request);

            if($verification){
                return $verification;
            }

            foreach($request->input('stage_title') as $index => $value){
                $this->createTradeStage(
                    $tradeId,
                    $request->input('stage_title')[$index],
                    $request->input('steporder')[$index],
                    $request->input('yes_action')[$index],
                    $request->input('no_action')[$index],
                );
            }

            return response()->json([
                'message' => 'Trade stage created sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function initializeTradeStageVerification( Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'stage_title' => 'required',
                'steporder' => 'required',
                'yes_action' => 'required',
                'no_action' => 'required',
                // 'action_done_by' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }

            if(
                (count($request->input('stage_title')) != count($request->input('steporder')) )||
                count($request->input('steporder')) != count($request->input('yes_action')) ||
                count($request->input('yes_action')) != count($request->input('no_action'))  ||
                // count($request->input('no_action')) != count($request->input('action_done_by')) ||
                count($request->input('no_action')) != count($request->input('stage_title'))
                 ){
                return response()->json([
                    'message' => 'Check tables length',
                    'stage_title' =>count($request->input('stage_title')),
                    'steporder'=> count($request->input('steporder')),
                    'yes_action'=> count($request->input('yes_action')),
                    'no_action' => count($request->input('no_action')),
                    // 'action_done_by' => count($request->input('action_done_by')),
                ],200);
            }

            } catch(Exception $e){
                return response()->json([
                    'error' => $e->getMessage()
                ],500);
            }
    }

    public function updateTradeStage(Request $request, $tradeStageId){
        try {
                $tradeStage = TradeStage::find($tradeStageId);
                 


                if(!$tradeStage){
                    return response()->json([
                        'message' => 'Trade stage not found'
                    ],200);
                }
               
                $previous_step_id = $request->previous_step_id ?? null;
                $next_step_id = $request->next_step_id ?? null;

                $this->editTradeStage($tradeStageId,$request->stage_title, $request->steporder, $request->yes_action, $request->no_action,$previous_step_id,$next_step_id);

                return response()->json([
                    'message' => 'Trade stage updated successfully'
                ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function editTradeStage($tradeStageId,$stage_title, $steporder, $yes_action, $no_action,$previous_step_id,$next_step_id){
        try {
                $tradeStage = TradeStage::find($tradeStageId);

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




// try {

// } catch(Exception $e){
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }