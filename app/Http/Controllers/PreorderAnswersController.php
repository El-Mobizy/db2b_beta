<?php

namespace App\Http\Controllers;

use App\Models\PreorderAnswers;
use App\Models\TypeOfType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreorderAnswersController extends Controller
{
//       /**
//  * @OA\Get(
//  *     path="/api/preorder/getPreorderValidated",
//  *     tags={"Preorder"},
//  *     summary="Get all validated preorders",
//  *     description="Retrieves all preorders that have been validated.",
//  *     @OA\Response(
//  *         response=200,
//  *         description="A list of validated preorders",
//  *         @OA\JsonContent(
//  *             type="array",
//  *             @OA\Items(
//  *                 type="object",
//  *                 @OA\Property(property="id", type="integer", example=1),
//  *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
//  *                 @OA\Property(property="statut", type="integer", example=2),
//  *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
//  *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
//  *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
//  *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Internal Server Error",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="error",
//  *                 type="string",
//  *                 example="An error message"
//  *             )
//  *         )
//  *     )
//  * )
//  */
// public function getPreorderValidated(){
//     try {
//         // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('validated')->first()->id)->get();

//         $preorder = DB::select("SELECT * FROM preorders WHERE statut = (
//             SELECT id FROM type_of_types WHERE libelle = 'validated' LIMIT 1
//         )");
//         return response()->json([
//             'data' => $preorder
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => $e->getMessage()
//         ],500);
//     }
// }

//   /**
// * @OA\Get(
// *     path="/api/preorder/getPreorderRejected",
// *     tags={"Preorder"},
// *     summary="Get all rejected preorders",
// *     description="Retrieves all preorders that have been rejected.",
// *     @OA\Response(
// *         response=200,
// *         description="A list of rejected preorders",
// *         @OA\JsonContent(
// *             type="array",
// *             @OA\Items(
// *                 type="object",
// *                 @OA\Property(property="id", type="integer", example=1),
// *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
// *                 @OA\Property(property="statut", type="integer", example=2),
// *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
// *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
// *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
// *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
// *             )
// *         )
// *     ),
// *     @OA\Response(
// *         response=500,
// *         description="Internal Server Error",
// *         @OA\JsonContent(
// *             @OA\Property(
// *                 property="error",
// *                 type="string",
// *                 example="An error message"
// *             )
// *         )
// *     )
// * )
// */
// public function getPreorderRejected(){
// try {
//     // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('rejected')->first()->id)->get();

//     $preorder = DB::select("SELECT * FROM preorders WHERE statut = (
//         SELECT id FROM type_of_types WHERE libelle = 'rejected' LIMIT 1
//     )");

//     return response()->json([
//         'data' => $preorder
//     ]);

// } catch (\Exception $e) {
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }
// }

//       /**
//  * @OA\Post(
//  *     path="/api/preorder_answer/validatePreorderAnswer/{uid}",
//  *     tags={"PreorderAnswers"},
//  *  security={{"bearerAuth": {}}},
//  *     summary="Validate a preorder answer",
//  *     description="Validates a preorder answer by changing its status to 'validated' if its current status is 'pending'.",
//  *     @OA\Parameter(
//  *         name="uid",
//  *         in="path",
//  *         description="Unique identifier of the preorder",
//  *         required=true,
//  *         @OA\Schema(
//  *             type="string",
//  *             format="uuid"
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=200,
//  *         description="Preorder validated successfully!",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="message",
//  *                 type="string",
//  *                 example="Preorder validated successfully!"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=404,
//  *         description="Preorder not found",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="message",
//  *                 type="string",
//  *                 example="Preorder not found"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=400,
//  *         description="Statut of preorder must be pending. Please, check it !",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="message",
//  *                 type="string",
//  *                 example="Statut of preorder must be pending. Please, check it !"
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=500,
//  *         description="Internal Server Error",
//  *         @OA\JsonContent(
//  *             @OA\Property(
//  *                 property="error",
//  *                 type="string",
//  *                 example="An error message"
//  *             )
//  *         )
//  *     )
//  * )
//  */

//  public function validatePreorderAnswer($uid){
//     try {
//            //todo: check if a person who make this action is an admin

//     $preorder_answer = PreorderAnswers::where('uid',$uid)->first();

//     if(!$preorder_answer){
//         return response()->json([
//             'message' => ' Preorder not found'
//         ],404);
//     }

//     if($preorder_answer->statut != TypeOfType::whereLibelle('pending')->first()->id){
//         return response()->json([
//             'message' => 'Statut of preorder answer must be pending. Please, check it !'
//         ]);
//     }

//     $preorder_answer->statut = TypeOfType::whereLibelle('validated')->first()->id;
//     $preorder_answer->validated_on = now();
//     $preorder_answer->validated_by_id = Auth::user()->id;
//     $preorder_answer->save();

//     return response()->json([
//         'message' => 'Preorder answer validated successfully!'
//     ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => $e->getMessage()
//         ],500);
//     }
// }

//    /**
// * @OA\Post(
// *       path="/api/preorder_answer/rejectPreorderAnswer/{uid}",
// *     summary="Reject a preorder answer",
// *     description="Rejects a preorder answer and updates its status to 'rejected'.",
// *  security={{"bearerAuth": {}}},
// *     tags={"PreorderAnswers"},
// *     @OA\Parameter(
// *         name="uid",
// *         in="path",
// *         required=true,
// *         @OA\Schema(type="string"),
// *         description="The UID of the preorder answer"
// *     ),
// *     @OA\RequestBody(
// *         required=true,
// *         @OA\JsonContent(
// *             required={"reject_reason"},
// *             @OA\Property(property="reject_reason", type="string", description="Reason for rejecting the preorder")
// *         )
// *     ),
// *     @OA\Response(
// *         response=200,
// *         description="Preorder rejected successfully",
// *         @OA\JsonContent(
// *             @OA\Property(property="message", type="string")
// *         )
// *     ),
// *     @OA\Response(
// *         response=404,
// *         description="Preorder not found",
// *         @OA\JsonContent(
// *             @OA\Property(property="message", type="string")
// *         )
// *     ),
// *     @OA\Response(
// *         response=400,
// *         description="Statut of preorder must be pending. Please, check it !",
// *         @OA\JsonContent(
// *             @OA\Property(property="message", type="string")
// *         )
// *     ),
// *     @OA\Response(
// *         response=500,
// *         description="Internal Server Error",
// *         @OA\JsonContent(
// *             @OA\Property(property="error", type="string")
// *         )
// *     )
// * )
// */
// public function rejectPreorderAnswer($uid, Request $request){
//     try {

//            //todo: check if a person who make this action is an admin
//         $request->validate([
//             'reject_reason' => 'required'
//         ]);
//         $preorder_answer = PreorderAnswers::where('uid',$uid)->first();
//     if(!$preorder_answer){
//         return response()->json([
//             'message' => ' Preorder answer not found'
//         ],404);
//     }

//     if($preorder_answer->statut != TypeOfType::whereLibelle('pending')->first()->id){
//         return response()->json([
//             'message' => 'Statut of preorder answer must be pending. Please, check it !'
//         ]);
//     }

//     $preorder_answer->statut = TypeOfType::whereLibelle('rejected')->first()->id;
//     $preorder_answer->validated_on = now();
//     $preorder_answer->validated_by_id = Auth::user()->id;
//     $preorder_answer->reject_reason = $request->input('reject_reason');
//     $preorder_answer->save();

//     return response()->json([
//         'message' => 'Preorder answer rejected successfully!'
//     ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => $e->getMessage()
//         ],500);
//     }
// }

}
