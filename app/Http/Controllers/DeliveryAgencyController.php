<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAgency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DeliveryAgencyController extends Controller
{

 /**
 * @OA\Post(
 *     path="/api/deliveryAgency/add/{id}",
 *     summary="Ajouter une agence de livraison pour une personne",
 *     tags={"Delivery Agencies"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la personne à laquelle ajouter l'agence de livraison",
 *   
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="agent_type",
 *                     type="string",
 *                     description="Type de l'agent de livraison"
 *                 ),
 *                 example={"agent_type": "votre_type"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Succès de l'opération"
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Requête invalide"
 *     ),
 *     @OA\Response(
 *         response="500",
 *         description="Erreur interne du serveur"
 *     )
 * )
 */



    public function add(Request $request,$id)
    {
        
        try {
            $db = DB::connection()->getPdo();
            $request->validate([
                'agent_type' =>  ['required','string']
            ]);
            $ulid = Uuid::uuid1();
            $ulidDeliveryAgency = $ulid->toString();
            $agent_type = htmlspecialchars($request->agent_type);
            $person_id = $id;
            $uid = $ulidDeliveryAgency;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');

            $query = "INSERT INTO delivery_agencies (agent_type, person_id,uid,created_at,updated_at) VALUES (?, ?, ?,?,?)";

            $statement = $db->prepare($query);

            $statement->bindParam(1, $agent_type);
            $statement->bindParam(2, $person_id);
            $statement->bindParam(3,  $uid);
            $statement->bindParam(4,  $created_at);
            $statement->bindParam(5,  $updated_at);
            $statement->execute();
            return response()->json([
                'message' => 'add successfuly !'
            ]);
          
        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(DeliveryAgency $deliveryAgency)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeliveryAgency $deliveryAgency)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeliveryAgency $deliveryAgency)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeliveryAgency $deliveryAgency)
    {
        //
    }
}
