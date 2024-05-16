<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;

class CategoryController extends Controller
{
    public $a =1;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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
           
            $randomString = $this->generateRandomAlphaNumeric(15);
            $categories = Category::all();
            foreach ($categories as $c) {
                $exist = Category::where('filecode',$randomString)->first();
                while($exist){
                    $randomString = $this->generateRandomAlphaNumeric(15);
                }
            }
            $ulid = Uuid::uuid1();
            $ulidCategory = $ulid->toString();
            $title =  htmlspecialchars($request->input('title'));
            $parent_id = $request->input('parent_id') !== null ? intval($request->input('parent_id')) : null;

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
            if($request->hasFile('files')){
                foreach($request->file('files') as $index => $photo){
                    $size = filesize($photo);
                    $ulid = Uuid::uuid1();
                    $ulidPhoto = $ulid->toString();
                    $created_at = date('Y-m-d H:i:s');
                    $updated_at = date('Y-m-d H:i:s');
                    $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
                    $photoPath = $photo->move(public_path('image/photo_category'), $photoName);
                    $photoUrl = url('/image/photo_category/' . $photoName);
                    $type = $photo->getClientOriginalExtension();
                    $location = $photoUrl;
                    $referencecode = $randomString;
                    $filename = md5(uniqid()) . '.' . $type;
                    $uid = $ulidPhoto;
                    $q = "INSERT INTO files (filename, type, location, size, referencecode, uid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)";
                    $stmt = $db->prepare($q);
                    $stmt->bindParam(1, $filename);
                    $stmt->bindParam(2, $type);
                    $stmt->bindParam(3, $location);
                    $stmt->bindParam(4,  $size);
                    $stmt->bindParam(5,  $referencecode);
                    $stmt->bindParam(6,  $uid);
                    $stmt->bindParam(7,  $created_at);
                    $stmt->bindParam(8,  $updated_at);
                    $stmt->execute();
                }
            }
            return response()->json([
                'message' => "category created successfuly"
            ],200);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

     public function generateRandomAlphaNumeric($length) {
        $bytes = random_bytes(ceil($length * 3 / 4));
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
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

            $db = DB::connection()->getPdo();
            $query = "SELECT * FROM categories WHERE deleted = false";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll($db::FETCH_ASSOC);
            return response()->json([
                'data' => $categories
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
            $query = "SELECT * FROM categories WHERE title LIKE :title";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':title',$s);
            $stmt->execute();
            $categories = $stmt->fetchAll($db::FETCH_ASSOC);

            return response()->json([
                'data' => $categories
            ]);
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function deleteCategoryFile( $uid)
    {
        
    }
}
