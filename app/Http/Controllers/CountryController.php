<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Exception;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
  

    /**
     * @OA\Post(
     *      path="/api/country/add",
     *      summary="Ajouter un pays.",
     *      tags={"Country"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Données requises pour ajouter un pays.",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"fullname", "shortcode", "callcode", "file"},
     *                  @OA\Property(property="fullname", type="string", example="Nom complet du pays"),
     *                  @OA\Property(property="shortcode", type="string", example="Code court du pays"),
     *                  @OA\Property(property="callcode", type="string", example="Code d'appel du pays"),
     *                  @OA\Property(property="file", type="string", format="binary", description="Fichier d'image du drapeau du pays (jpeg, jpg, png)")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Succès. Le pays a été ajouté avec succès."
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Requête invalide. Veuillez fournir toutes les données requises et vérifier les contraintes de validation."
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Erreur de serveur. Une erreur s'est produite lors du traitement de la requête."
     *      )
     * )
     */

    public function add(Request $request)
    {
        try {
            $db = DB::connection()->getPdo();
            $request->validate([
                'fullname' => 'required|unique:countries|max:255',
                'shortcode' => 'required|unique:countries|max:10',
                'callcode' => 'required|unique:countries|max:255',
                'file' => 'image|mimes:jpeg,jpg,png'
            ]);
            $ulid = Uuid::uuid1();
            $ulidCountry = $ulid->toString();
            $fullname =  htmlspecialchars($request->input('fullname'));
            $shortcode =  htmlspecialchars($request->input('shortcode'));
            $callcode =  htmlspecialchars($request->input('callcode'));
            $photo =  $request->file('file');
            $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];

            $extension = $photo->getClientOriginalExtension();

            if (!in_array($extension, $allowedExtensions)) {
                return response()->json(['message' =>"Veuillez télécharger une image (jpeg, jpg, png, gif)." ]);
            }
            $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
            $photoPath = $photo->move(public_path('image/country_flag'), $photoName);
            $photoUrl = url('/image/country_flag/' . $photoName);
            $location = $photoPath;
            $uid = $ulidCountry;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $flag = $photoUrl;
            $query = " INSERT INTO countries (fullname, shortcode, flag,callcode,uid,created_at,updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)" ;
            $statement = $db->prepare($query);

            $statement->bindParam(1, $fullname);
            $statement->bindParam(2, $shortcode);
            $statement->bindParam(3, $flag);
            $statement->bindParam(4,  $callcode);
            $statement->bindParam(5,  $uid);
            $statement->bindParam(6,  $created_at);
            $statement->bindParam(7,  $updated_at);
            $statement->execute();

            return response()->json([
                'message' => 'country added successfully!'
            ]);
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }

/**
 * Récupérer tous les pays.
 *
 * @OA\Get(
 *      path="/api/country/all",
 *      summary="Get all country.",
 *   tags={"Country"},
 *      @OA\Response(
 *          response=200,
 *          description="Success. Return all countries.",
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
 *          description="Serveur error."
 *      )
 * )
 */

 public function getAllCountries()
 {
     try {

         $db = DB::connection()->getPdo();
         $query = "SELECT * FROM countries WHERE deleted = false AND banned = false";
         $stmt = $db->prepare($query);
         $stmt->execute();
         $countries = $stmt->fetchAll($db::FETCH_ASSOC);
         return response()->json([
             'data' => $countries
         ]);

     } catch (Exception $e) {
        return response()->json([
         'error' => $e->getMessage()
        ]);
     }

 }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Country $country)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        //
    }
}
