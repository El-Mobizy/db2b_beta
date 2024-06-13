<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Right;
use App\Models\User_right;
use Exception;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
      /**
     * @OA\Get(
     *     path="/api/role/index",
     *     summary="Get all roles",
     *     tags={"Role"},
     * security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of roles"
     * 
     *     )
     * )
     */
    public function index()
    {
        try{
            $roles = Role::all();
            return response()->json([
                'roles' => $roles
            ],200);
        }catch (Exception $e){
              return response()->json(['error' => $e->getMessage()], 500);
        }
        

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

  /**
      * @OA\Post(
      *     path="/api/role/store",
      *     summary="Create a new role ",
      *     tags={"Role"},
      *security={{"bearerAuth": {}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"name"},
      *             @OA\Property(property="name", type="string", example="traveler"),
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Role  created successfully"
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
                'name' => 'required|unique:roles|max:255',
            ]);

            $exist = Role::where('name',$request->name)->exists();
            if($exist){
                return response()->json([
                    "message"=>"This name has already taken"
                ]);
            }
            $role = new Role();
            $role->name = $request->name;
            $role->guard_name= "web";
            $role->save();

            $right = new Right();
            $right->name = $request->name;
            $right->save();
            return response()->json([
                'message' =>'Successfully created',
                'role' => $role
            ],200);
        }catch (Exception $e){
              return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }

     /**
     * @OA\Get(
     *     path="/api/role/show/{id}",
     *     summary="Get a specific role by ID",
     *     tags={"Role"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role details"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        try{
                $role = Role::find($id);
                return response()->json([
                    'data' => $role
                ],200);
        }catch (Exception $e){
              return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }


 /**
     * @OA\Delete(
     *     path="/api/role/destroy/{id}",
     *     summary="Delete a role by ID",
     *     tags={"Role"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Role deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            
            $role = Role::find($id);
            if (!$role) {
                return response()->json(['message' => 'Rôle introuvable.']);
            }
    
            $usersWithRole = User_right::where('right_id', $id)->exists();
            if ($usersWithRole) {
                return response()->json([
                    'message' => 'Impossible de supprimer le rôle car il est utilisé par un ou plusieurs utilisateurs.'
                ]);
            }
    
     
            Role::destroy($id);
            Right::destroy($id);
    
            return response()->json(['message' => 'Rôle supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}
