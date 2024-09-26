<?php

namespace App\Http\Controllers;

use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\CategoryAttributes;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;

class CategoryController extends Controller
{

/**
         * @OA\Post(
         *     path="/api/category/add",
         *     summary="Ajouter une nouvelle catégorie",
         *     tags={"Category"},
         * security={{"bearerAuth": {}}},
 * @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *       mediaType="multipart/form-data",
 *       @OA\Schema(
 *         type="object",
 *         @OA\Property(property="title", type="string", example=""),
 *    @OA\Property(property="parent_id", type="integer"),
 *          @OA\Property(
 *                     property="files[]",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary", description="Image de la catégorie (JPEG, PNG, JPG, GIF, taille max : 2048)")
 *                 ),
 *       )
 *     )
 *   ),
         *     @OA\Response(
         *         response=200,
         *         description="Criteria  created successfully"
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="Invalid credentials"
         *     )
         * )
         */
    public function add(Request $request)
    {
          try {
            $db = DB::connection()->getPdo();
            $request->validate([
                'title' => 'required|unique:categories|max:255',
                // 'files' => 'image|mimes:jpeg,jpg,png,gif'
            ]);


            $service = new Service();

            $exist = new Category();
                $randomString = $service->generateRandomAlphaNumeric(7,$exist,'filecode');
            $ulid = Uuid::uuid1();
            $ulidCategory = $ulid->toString();
            $title =  htmlspecialchars($request->input('title'));
            // $parent_id = $request->input('parent_id') !== null ? intval($request->input('parent_id')) : null;

            if($request->input('parent_id') !== null){
                $parent_id = intval($request->input('parent_id'));
               if(count($request->file('files')) == 0  ){
                    return response()->json([
                        'message' => 'file is required'
                    ],200);
               }
               $file = new Service();
               $file->uploadFiles($request,$randomString,"category");
            }else{
                $parent_id = null;
            }

            $filecode = $randomString;
            $slug = Str::slug($title);
            $uid = $ulidCategory;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $query = "INSERT INTO categories (title, parent_id, filecode,slug,uid,created_at,updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $statement = $db->prepare($query);

            $statement->bindParam(1, $title);
            $statement->bindParam(2, $parent_id);
            $statement->bindParam(3, $filecode);
            $statement->bindParam(4,  $slug);
            $statement->bindParam(5,  $uid);
            $statement->bindParam(6,  $created_at);
            $statement->bindParam(7,  $updated_at);

           

            $statement->execute();
            return response()->json([
                'message' => "category created successfuly"
            ],200);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }


    


  

/**
 * @OA\Get(
 *      path="/api/category/detail/{uid}",
 *      summary="Récupérer les détails d'une catégorie.",
 *   tags={"Category"},
 *      @OA\Parameter(
 *          in="path",
 *          name="uid",
 *          required=true,
 *          description="L'UID de la catégorie à récupérer.",
 *          @OA\Schema(type="string")
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Succès. Retourne les détails de la catégorie avec ses fichiers associés.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="object"
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Non trouvé. La catégorie avec l'UID spécifié n'existe pas."
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Erreur de serveur. Une erreur s'est produite lors du traitement de la requête."
 *      )
 * )
 */



    public function showCategoryDetail(Request $request, $uid)
    {
           try {
            $db = DB::connection()->getPdo();
            if((new Service())->isValidUuid($uid)){
                return (new Service())->isValidUuid($uid);
            }
            $query = "SELECT * FROM categories WHERE uid = :uid";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':uid', $uid);
            $stmt->execute();
            $category = $stmt->fetch($db::FETCH_ASSOC);

            if ($category) {
                $queryFiles = "SELECT * FROM files WHERE referencecode = :referencecode";
                $stmtFiles = $db->prepare($queryFiles);
                $stmtFiles->bindParam(':referencecode', $category['filecode']);
                $stmtFiles->execute();
                $files = $stmtFiles->fetchAll($db::FETCH_ASSOC);
                $category['files'] = $files;
            }

            return response()->json([
                'data' => $category
            ]);
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }

 /**
 * Récupérer toutes les catégories.
 *
 * @OA\Get(
 *      path="/api/category/all",
 *      summary="Récupérer toutes les catégories.",
 *   tags={"Category"},
 *      @OA\Response(
 *          response=200,
 *          description="Succès. Retourne toutes les catégories.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="array",
 *                  @OA\Items(ref="")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Erreur de serveur. Une erreur s'est produite lors du traitement de la requête."
 *      )
 * )
 */

    public function getAllCategories()
    {
        try {

            $categories =  Category::with('file')->whereDeleted(0)->with('subcategories')->get();
            foreach($categories as $categorie){

                if(File::where('referencecode',$categorie->filecode)->exists()){
                    $categorie->category_icone = File::where('referencecode',$categorie->filecode)->first()->location;
                }
            }

            return response()->json([
                'data' =>$categories
            ]);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }

     /**
 * Récupérer toutes les catégories.
 *
 * @OA\Get(
 *      path="/api/category/all/{perpage}",
 *      summary="Récupérer toutes les catégories.",
 *   tags={"Category"},
 *  @OA\Parameter(
 *          in="query",
 *          name="perpage",
 *          required=true,
 *          description="Number of element perpage",
 *          @OA\Schema(type="string")
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Succès. Retourne toutes les catégories.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="array",
 *                  @OA\Items(ref="")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Erreur de serveur. Une erreur s'est produite lors du traitement de la requête."
 *      )
 * )
 */

 public function getAllPaginateCategories($perpage)
 {
     try {

         $categories =  Category::with('file')->whereDeleted(0)->with('subcategories')->paginate($perpage);
         foreach($categories as $categorie){

            if(File::where('referencecode',$categorie->filecode)->exists()){
                $categorie->category_icone = File::where('referencecode',$categorie->filecode)->first()->location;
            }

            
        }

         return response()->json([
             'data' =>$categories
         ]);

     } catch (Exception $e) {
        return response()->json([
         'error' => $e->getMessage()
        ]);
     }

 }

 /**
 * @OA\Get(
 *     path="/api/category/getAllSubCategory",
 *     summary="Get all sub-subcategories",
 *     tags={"SubCategories"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
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
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */

 public function getAllSubSubcategory()
{
    try {
        // Récupère toutes les sous-catégories avec leurs fichiers associés
        $subCategories = Category::with('file')
            ->whereDeleted(0)
            ->whereNotNull('parent_id')
            ->get();

        $attributes = [];

        foreach ($subCategories as $subCategory) {
            if (File::where('referencecode', $subCategory->filecode)->exists()) {
                $subCategory->category_icone = File::where('referencecode', $subCategory->filecode)->first()->location;
            }

            if ($subCategory->attribute_group_id != null) {
                $attributes[] = $subCategory->attribute_group;
                // $attributes[] = CategoryAttributes::whereId($subCategory->attribute_group->attributes->id)->first();
                // foreach (AttributeGroup::where('group_title_id', $subCategory->attribute_group->group_title_id)->get() as $group) {
                //     $attributes[] = CategoryAttributes::whereId($group->attribute_id)->first();
                // }
            }

            $subCategory->attribute = $attributes;
        }

        return response()->json([
            'data' => $subCategories
        ]);

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
}


/**
 * @OA\Get(
 *     path="/api/category/getAllPaginateSubCategory/paginate",
 *     summary="Get paginated sub-subcategories",
 *     tags={"SubCategories"},
 *     @OA\Parameter(
 *         name="perpage",
 *         in="query",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             example=10
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
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
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */

public function getAllPaginateSubSubcategory($perpage)
{
    try {
        $subCategories = Category::with('file')->whereDeleted(0)->whereNotNull('parent_id')->paginate(intval($perpage));

        foreach ($subCategories as $subCategory) {
            if (File::where('referencecode', $subCategory->filecode)->exists()) {
                $subCategory->category_icone = File::where('referencecode', $subCategory->filecode)->first()->location;
            }
           
        }

     

        return response()->json([
            'data' => $subCategories
        ]);

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * @OA\Get(
 *      path="/api/category/search",
 *      summary="Rechercher une catégorie par titre.",
 *  tags={"Category"},
 *      @OA\Parameter(
 *          in="query",
 *          name="search",
 *          required=true,
 *          description="Le terme de recherche pour le titre de la catégorie.",
 *          @OA\Schema(type="string")
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Succès. Retourne les catégories correspondant à la recherche.",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="data",
 *                  type="array",
 *                  @OA\Items(ref="")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Erreur de serveur. Une erreur s'est produite lors du traitement de la requête."
 *      )
 * )
 */

    public function searchCategory(Request $request)
    {
        try {
            $db = DB::connection()->getPdo();
            $search = htmlspecialchars($request->input('search'));
            $s = '%'.$search.'%';
            $query = "SELECT * FROM categories WHERE title LIKE :title AND deleted = false ";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':title',$s);
            $stmt->execute();
            $categories = $stmt->fetchAll($db::FETCH_ASSOC);

            return response()->json([
                'data' => $categories
            ],200);
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }


    /**
 * @OA\Post(
 *     path="/api/category/updateCategorie/{id}",
 *     tags={"Category"},
 *          security={{"bearerAuth": {}}},
 *     summary="Update an existing category",
 *     description="Update an existing category by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the category to update",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", example="New Category Title"),
 *             @OA\Property(property="attribute_group_id", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Update successfully!",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Update successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Category not found!",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Category not found!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error message")
 *         )
 *     )
 * )
 */

    public function updateCategorie($id,Request $request){
        try {

            $category = Category::find($id);
            if(!$category){
                return response()->json([
                    'message' => 'Category not found!'
                ],200);
            }
            $category->title = $request->title??$category->title;
            $category->attribute_group_id = $request->attribute_group_id??$category->attribute_group_id;
            $category->save();
            return response()->json([
                'message' => 'update sucessfully!'
            ],200);
        }  catch (Exception $e) {
            return response()->json([
             'error' => $e->getMessage()
            ],500);
         }
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function deleteCategoryFile( $uid)
    {
        
    }
}

