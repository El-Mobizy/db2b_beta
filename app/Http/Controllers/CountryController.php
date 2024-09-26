<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Exception;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class CountryController extends Controller
{


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
 
         // Boucler sur chaque pays et mettre le flag en majuscule
         foreach ($countries as &$country) {
             $country['flag'] = mb_strtolower($country['flag']);
         }
 
         return response()->json([
             'data' => $countries
         ]);
 
     } catch (Exception $e) {
         return response()->json([
             'error' => $e->getMessage()
         ]);
     }

    // try {
    //     // Récupérer les données du fichier JSON
    //     $countryRaw = $this->getData();

    //     // Retourner directement les données du fichier JSON
    //     return response()->json([
    //         'data' => $countryRaw,
    //         'message' => 'Countries loaded successfully'
    //     ]);

    // } catch (Exception $e) {
    //     return response()->json([
    //         'error' => $e->getMessage()
    //     ], 500);
    // }
 }
 

 private function getData(): mixed
{
    $path = storage_path('country.json');

    $data = file_get_contents($path);

    return json_decode($data, false, 512, JSON_THROW_ON_ERROR);
}


/**
 * @OA\Post(
 *     path="/api/country/load",
 *     summary="Load countries from JSON data",
 *     description="This endpoint loads country data from a JSON file and saves it to the database",
 *     tags={"Country"},
 *     @OA\Response(
 *         response=200,
 *         description="Countries loaded successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */

    public function load()
    {
        // Récupérer les données du fichier JSON
        $countryRaw = $this->getData();

        DB::transaction(function() use ($countryRaw) {
            foreach ($countryRaw as $country) {
                $countryNew = new Country();
                $countryNew->fullname = $country->fullname;
                $countryNew->flag = $country->flag;
                $countryNew->shortcode = $country->shortcode;
                
                if ($country->callcode !== null) {
                    $countryNew->callcode = $country->callcode;
                }
                
                if ($country->symbol !== null) {
                    $countryNew->symbol = $country->symbol;
                }
                
                if ($country->currency !== null) {
                    $countryNew->currency = $country->currency;
                }
                
                $countryNew->banned = $country->banned !== 0;
                
                $countryNew->save();
            }
        });

        return response()->json([
            'message' => 'Country generate successfuly'
        ]);
    }


    /**
 * Récupérer tous les pays.
 *
 * @OA\Get(
 *      path="/api/country/all/{perpage}",
 *      summary="Get all country.",
 *   tags={"Country"},
 * @OA\Parameter(
 *         name="perpage",
 *         in="path",
 *         description="number of element perpage",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
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

 public function getAllPaginateCountries($perpage=10)
 {
     try {


         $countries = Country::whereDeleted(false)->where('banned',false)->paginate($perpage);
         return response()->json([
             'data' => $countries
         ]);

     } catch (Exception $e) {
        return response()->json([
         'error' => $e->getMessage()
        ]);
     }

 }

}
