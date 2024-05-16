<?php

namespace App\Http\Controllers;

use App\Models\Right;
use Illuminate\Http\Request;
use Exception;

class RightController extends Controller
{

      /**
     * @OA\Get(
     *     path="/api/right/index",
     *     summary="Get all rights",
     *     tags={"Right"},
     * security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of rights"
     *
     *     )
     * )
     */
    public function index()
    {
        try{
            $rightRights = Right::all();
            return response()->json([
                'data' => $rightRights
            ]);
    } catch(Exception $e) {
        return response()->json($e->getMessage());
    }
    }

    
    /**
     * @OA\Post(
     *     path="/api/right/store",
     *     summary="add new right",
     *     tags={"Right"},
     * security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="traveler")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="new right created successfuly",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="new right created successfuly")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try{
            $data = $request->validate([
                'name' => 'required|unique:rights|max:255',
            ]);
          $right = new Right();
          $right->name = $request->name;
          $right->save();
          return response()->json([
            'data' => 'right created successfully'
        ]);
    } catch(Exception $e) {
        return response()->json($e->getMessage());
    }
    }

    
    /**
     * @OA\Put(
     *     path="/api/right/update/{id}",
     *     summary="Update a right by ID",
     *     tags={"Right"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the right",
     *         @OA\Schema(type="integer")
     *     ),
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="traveler")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Right updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Right not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request,  $id)
    {
        try{
            if (!Right::find($id)) {
                return response()->json([
                    'message' => 'right not found'
                ]);
            }
            $data = $request->validate([
                'name' => 'required|unique:rights|max:255',
            ]);
            $rightRights = Right::whereId($id)->update($data);

            return response()->json([
                'message' => 'right updated successfully'
            ]);
    } catch(Exception $e) {
        return response()->json($e->getMessage());
    }
    }

         /**
     * @OA\Delete(
     *     path="/api/right/destroy/{id}",
     *     summary="Delete a right by ID",
     *     tags={"Right"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the right",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Right deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Right not found"
     *     )
     * )
     */
    public function destroy( $id)
    {
        try{
            if (!Right::find($id)) {
                return response()->json([
                    'message' => 'right not found'
                ]);
            }
            $rightRights = Right::whereId($id)->delete();
            return response()->json([
                'message' => 'right deleted successfully'
            ]);
    } catch(Exception $e) {
        return response()->json($e->getMessage());
    }
    }
}
