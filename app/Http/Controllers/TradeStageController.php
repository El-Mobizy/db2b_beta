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


    /**
     * @OA\Post(
     *     path="/api/tradeStage/createTradeStage",
     *     summary="Create a trade stage",
     *     tags={"TradeStage"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="stage_title", type="string", example="Stage Title"),
     *             @OA\Property(property="steporder", type="integer", example=1),
     *             @OA\Property(property="yes_action", type="string", example="Action 1"),
     *             @OA\Property(property="no_action", type="string", example="Action 2"),
     *             @OA\Property(property="action_done_by", type="string", example="BUYER")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade stage created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade stage created successfully")
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
    function createTradeStage(Request $request){
        try {

            $request->validate([
                'stage_title' => 'required'
            ]);

            $tradeStage = new TradeStage();
            $service = new Service();
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


    /**
 * @OA\Get(
 *     path="/api/tradeStage/displayTradeStages/{tradeStageId}",
 *     summary="Display Trade Stage",
 *     description="Afficher un Trade Stage spécifique par ID",
 *     tags={"TradeStage"},
 *     @OA\Parameter(
 *         name="tradeStageId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="ID du Trade Stage"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade stage found",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object", ref="")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Trade stage not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="string", example="trade stage not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     ),
 *     security={{ "bearerAuth":{} }}
 * )
 */

    public function  displayTradeStages($tradeStageId){
        try {
            $tradeStage =  TradeStage::whereId($tradeStageId)->first();
            if(!$tradeStage){
                return response()->json([
                    'data' =>'trade stage not found'
                ],400);
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

    

    /**
     * @OA\Post(
     *     path="/api/tradeStage/updateTradeStage/{tradeStageId}",
     *     summary="Update a trade stage",
     *     tags={"TradeStage"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tradeStageId",
     *         in="path",
     *         description="ID of the trade stage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="stage_title", type="string", example="Updated Stage Title"),
     *             @OA\Property(property="steporder", type="integer", example=2),
     *             @OA\Property(property="yes_action", type="string", example="Updated Action 1"),
     *             @OA\Property(property="no_action", type="string", example="Updated Action 2"),
     *             @OA\Property(property="previous_step_id", type="integer", example=1),
     *             @OA\Property(property="next_step_id", type="integer", example=3)
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
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */

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


    /**
 * @OA\Get(
 *     path="/api/tradeStage/index",
 *     summary="List Trade Stages",
 *     description="Lister tous les Trade Stages dont le champ deleted est à false",
 *     tags={"TradeStage"},
 *     @OA\Response(
 *         response=200,
 *         description="List of trade stages",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     ),
 *     security={{ "bearerAuth":{} }}
 * )
 */
public function index()
{
    try {
        $tradeStages = TradeStage::where('deleted', false)->get();
        return response()->json([
            'data' => $tradeStages
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


/**
 * @OA\Post(
 *     path="/api/tradeStage/delete/{id}",
 *     summary="Delete Trade Stage",
 *     description="Modifier la valeur du champ deleted à true",
 *     tags={"TradeStage"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Trade Stage to delete",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trade Stage deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Trade Stage deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Trade Stage not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Trade Stage not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error message")
 *         )
 *     ),
 *     security={{ "bearerAuth":{} }}
 * )
 */
public function delete($id)
{
    try {
        $tradeStage = TradeStage::find($id);
        if (!$tradeStage) {
            return response()->json([
                'error' => 'Trade Stage not found'
            ], 404);
        }

        $tradeStage->deleted = true;
        $tradeStage->save();

        return response()->json([
            'message' => 'Trade Stage deleted successfully'
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


}




// try {

// } catch(Exception $e){
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }