<?php

namespace App\Http\Controllers;

use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class TypeOfTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
 * @OA\Post(
 *     path="/api/typeoftype/store",
 *     summary="Create a new TypeOfType",
 *     description="Créer un nouveau TypeOfType",
 *     tags={"TypeOfType"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="libelle", type="string", example="New TypeOfType"),
 *             @OA\Property(property="codereference", type="string", example="REF123"),
 *             @OA\Property(property="parent_id", type="integer", example=1, nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="TypeOfType created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="TypeOfType created successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="The given data was invalid.")
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
    public function store(Request $request)
    {
        try {
            $request->validate([
                'libelle' => 'required|unique:type_of_types',
                'codereference'
            ]);

            $service = new Service();

            $type = new TypeOfType();
            $type->libelle = $request->libelle;
            $type->codereference = $request->codereference;
            $type->uid =  $service->generateUid($type);
            $type->codereference = $request->codereference??null;
            if ($request->has('parent_id')) {
                $type->parent_id = $request->parent_id;
            }
           
            $type->save();

            return response()->json([
                'message' => "type of type created successfuly"
            ],200);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }


    }

    /**
     * Display the specified resource.
     */
    /**
 * @OA\Get(
 *     path="/api/typeoftype/show/{id}",
 *     summary="Get TypeOfType by ID",
 *     description="Récupérer un TypeOfType spécifique par son ID",
 *     tags={"TypeOfType"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID du TypeOfType à récupérer",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="TypeOfType retrieved successfully",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="TypeOfType not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="TypeOfType not found")
 *         )
 *     ),
 * )
 */
public function show($id)
{
    try {
        $type = TypeOfType::find($id);

        if (!$type) {
            return response()->json([
                'error' => 'TypeOfType not found'
            ], 404);
        }

        return response()->json($type, 200);

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TypeOfType $typeOfType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
 * @OA\Post(
 *     path="/api/typeoftype/update/{id}",
 *     summary="Update TypeOfType",
 *     description="Mettre à jour un TypeOfType spécifique par son ID",
 *     tags={"TypeOfType"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID du TypeOfType à mettre à jour",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="libelle", type="string", example="Nouveau libellé"),
 *             @OA\Property(property="codereference", type="string", example="Nouveau code de référence"),
 *             @OA\Property(property="parent_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="TypeOfType updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="TypeOfType updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="TypeOfType not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="TypeOfType not found")
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
public function update(Request $request, $id)
{
    try {
        $request->validate([
            'libelle' => 'required|unique:type_of_types,libelle,' . $id,
            'codereference' => 'nullable|string'
        ]);

        $type = TypeOfType::find($id);

        if (!$type) {
            return response()->json([
                'error' => 'TypeOfType not found'
            ], 404);
        }

        $type->libelle = $request->libelle;
        $type->codereference = $request->codereference ?? $type->codereference;

        if ($request->has('parent_id')) {
            $type->parent_id = $request->parent_id;
        }

        $type->save();

        return response()->json([
            'message' => "TypeOfType updated successfully"
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
   /**
 * @OA\post(
 *     path="/api/typeoftype/delete/{id}",
 *     summary="Delete TypeOfType",
 *     description="Supprimer un TypeOfType spécifique par son ID",
 *     tags={"TypeOfType"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID du TypeOfType à supprimer",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="TypeOfType deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="TypeOfType deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="TypeOfType not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="TypeOfType not found")
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
        $type = TypeOfType::find($id);

        if (!$type) {
            return response()->json([
                'error' => 'TypeOfType not found'
            ], 404);
        }

        $type->delete();

        return response()->json([
            'message' => "TypeOfType deleted successfully"
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

}
