<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KkiapayController extends Controller
{
      /**
 * @OA\Post(
 *     path="/api/kkiapay/verifyTransaction/{transaction_id}",
 *     summary="Vérifie une transaction avec Kkiapay",
 *     tags={"Kkiapay"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="transaction_id",
 *         in="path",
 *         description="L'identifiant de la transaction à vérifier",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Réponse de vérification de la transaction",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 example="success"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 description="Les détails de la vérification de la transaction"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur interne du serveur",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="Message d'erreur"
 *             )
 *         )
 *     )
 * )
 */
public function verifyTransaction($transaction_id){
    try {

        $public_key =config('services.kkiapay.public_key');
        $private_key =config('services.kkiapay.private_key');
        $secret =config('services.kkiapay.secret_key');
        $sandbox =config('services.kkiapay.is_sandbox');

        $kkiapay = new \Kkiapay\Kkiapay($public_key,
        $private_key,
        $secret,
        $sandbox=$sandbox);
        $verification = $kkiapay->verifyTransaction($transaction_id);


        if(is_int($verification)){
            return (new Service())->apiResponse($verification, [],"Assurez vous que vous soyez dans le bon mode ou que l'id de la transaction soit correcte ou que vos données concernant ce service soient valide.");
        }

        return $verification;

    } catch(\Exception $e) {
     return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}
}
