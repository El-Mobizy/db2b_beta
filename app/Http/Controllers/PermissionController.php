<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Exception;
use Illuminate\Validation\Rule;
class PermissionController extends Controller
{
      /**
     * @OA\Get(
     *     path="/api/permission/index",
     *     summary="Get all permissions",
     *     tags={"Permission"},
     * security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of permissions"
     * 
     *     )
     * )
     */
    public function index()
    {
        try{
            $permissions = Permission::all();
            return response()->json([
                'permissions' => $permissions
            ],200);
            }catch (Exception $e){
                  return response()->json(['error' => $e->getMessage()], 500);
            }
        

    }


  /**
      * @OA\Post(
      *     path="/api/permission/store",
      *     summary="Create a new permission ",
      *     tags={"Permission"},
      *security={{"bearerAuth": {}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"name"},
      *             @OA\Property(property="name", type="string", example="create"),
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Permission  created successfully"
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
                'name' => 'required|unique:permissions|max:255',
            ]);
            $exist = Permission::where('name',$request->name)->exists();
            if($exist){
                return response()->json([
                    "message"=>"This name has already taken"
                ]);
            }
                $permission = new Permission();
                $permission->name = $request->name;
                $permission->guard_name= "web";
                $permission->save();
                return response()->json([
                    'message' =>'Successfully created',
                    'permission' => $permission
                ],200);
        }catch (Exception $e){
              return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }

     /**
     * @OA\Get(
     *     path="/api/permission/show/{id}",
     *     summary="Get a specific permission by ID",
     *     tags={"Permission"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the permission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission details"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        try{
            $permission = Permission::find($id);
            return response()->json([
                'data' => $permission
            ],200);
        }catch (Exception $e){
              return response()->json(['error' => $e->getMessage()], 500);
        }
    }
/**
     * @OA\Get(
     *     path="/api/permission/indexbycategorie",
     *     summary="Get all permissions groupe by categorie",
     *     tags={"Permission"},
     * security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of permissions groupe by category"
     * 
     *     )
     * )
     */
    public function indexbycategorie()
{
    try {
        $permissions = Permission::all();
        
        // Initialiser un tableau vide pour stocker les permissions par catégorie
        $categorizedPermissions = [];

        // Parcourir toutes les permissions
        foreach ($permissions as $permission) {
            // Extraire le préfixe avant le point dans le nom de la permission
            $prefix = explode('.', $permission->name)[0];

            // Vérifier si la catégorie existe déjà dans le tableau
            if (!isset($categorizedPermissions[$prefix])) {
                // Si non, créer une nouvelle entrée dans le tableau
                $categorizedPermissions[$prefix] = [];
            }

            // Ajouter la permission à la catégorie correspondante
            $categorizedPermissions[$prefix][] = $permission;
        }

        // Retourner les permissions catégorisées
        return response()->json([
            'categorized_permissions' => $categorizedPermissions
        ], 200);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}