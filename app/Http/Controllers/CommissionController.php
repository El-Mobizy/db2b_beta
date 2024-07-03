<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/commission/store",
     *     summary="Store a new commission",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "short"},
     *             @OA\Property(property="name", type="string", example="Commission de transfert"),
     *             @OA\Property(property="short", type="string", example="TRANSF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission saved successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The data provided is not valid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="short",
     *                     type="array",
     *                     @OA\Items(type="string", example="The short field is required.")
     *                 )
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
    public function store(Request $request){
        try {

            $service = new Service();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:commissions',
                'short' => 'required|unique:commissions'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
            }

            $commission = new Commission();
            $commission->name = $request->name;
            $commission->short = $request->short;
            $commission->uid= $service->generateUid($commission);
            $commission->save();

            return response()->json([
                'message' => 'Commission saved successffuly!'
              ],200);

        } catch (Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/commission/index",
     *     summary="Retrieve a list of active commissions",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of active commissions",
     *         @OA\JsonContent(
     *             type="object",
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
     *             type="object",
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */
    public function index(){
        try {
            return response()->json([
                'data' => Commission::whereDeleted(0)->get()
              ],500);
        } catch (\Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/commission/show/{id}",
     *     summary="Retrieve a specific commission by ID",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the commission"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 ref=""
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commission not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Commission not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */
    public function show($id){
        try {
            $commission = Commission::find($id);
            if(!$commission){
                return response()->json([
                    'message' => 'Commission not found'
                  ],200);
            }
            return response()->json([
                'data' =>$commission
              ],200);
        } catch (\Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }


        /**
     * @OA\Post(
     *     path="/api/commission/update/{id}",
     *     summary="Update a specific commission by ID",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the commission"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "short"},
     *             @OA\Property(property="name", type="string", example="Updated Commission Name"),
     *             @OA\Property(property="short", type="string", example="UPDCOMM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission updated successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission not found")
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
    public function update($id, Request $request){
        try {
            $commission = Commission::find($id);
            if(!$commission){
                return response()->json([
                    'message' => 'Commission not found'
                  ],200);
            }
            $commission->name = $request->name??$commission->name;
            $commission->short = $request->short??$commission->short;
            $commission->save();
            return response()->json([
                'message' => 'Commission updated successffuly'
              ],200);

        } catch (\Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/commission/destroy/{id}",
     *     summary="Delete a specific commission by ID",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the commission"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission deleted successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission not found")
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

    public function destroy($id){
        //todo: On ne peut pas supprimer une commissions juste comme ça sans certaines conditions supplémentaires donc je dois revoir cette fonction
        try {
            $commission = Commission::find($id);
            if(!$commission){
                return response()->json([
                    'message' => 'Commission not found'
                  ],200);
            }
            if($commission->deleted == true){
                return response()->json([
                    'message' => 'Commission already deleted'
                  ],200);
            }
            Commission::whereId($id)->update(['deleted' => true]);
            return response()->json([
                'message' => 'Commission deleted successffuly'
              ],200);
        } catch (\Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/commission/restore/{id}",
     *     summary="Restore a deleted commission by ID",
     *     tags={"Commission"},
     *   security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the commission to restore"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission restored successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commission not found")
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

    public function restore($id){
        try {
            $commission = Commission::find($id);
            if(!$commission){
                return response()->json([
                    'message' => 'Commission not found'
                  ],200);
            }
            if($commission->deleted == false){
                return response()->json([
                    'message' => 'Commission already restored'
                  ],200);
            }
            Commission::whereId($id)->update(['deleted' => false]);
            return response()->json([
                'message' => 'Commission restored successffuly'
              ],200);
        } catch (\Exception $e) {
          return response()->json([
            'error' =>$e->getMessage()
          ],500);
        }
    }
}
