<?php

namespace App\Http\Controllers;

use App\Exceptions\errorException;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\Payement;
use Exception;
use Illuminate\Http\Request;

class PayementController extends Controller
{
    function storePayement($commission_wallet_id = null, $transaction_id = null, $payement_type = null, $amount = null, $statut = null,$motif=null)
    {
        try {

            if(Payement::whereTransactionId($transaction_id)->exists()){
                throw new errorException("This transaction ID already exists");
            }

            $payement = new Payement();
            $payement->uid =(new Service())->generateUid($payement);
            $payement->commission_wallet_id = $commission_wallet_id;
            $payement->transaction_id = $transaction_id;
            $payement->payement_type = $payement_type;
            $payement->amount = $amount;
            $payement->statut = $statut;
            $payement->motif = $motif;
            $payement->save();
        } catch (errorException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


  /**
 * @OA\Get(
 *     path="/api/payement/detail/user/{perpage}",
 *     summary="Retrieve all payments made by the user for wallet top-ups",
 *     tags={"MANAGE WALLET"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="perpage",
 *         in="path",
 *         description="Number of payments to retrieve per page",
 *         required=true,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="The page number for pagination",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of payments made when topping up the wallet",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="data", type="array", @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="transaction_id", type="string", example="txn12345"),
 *                     @OA\Property(property="payement_type", type="string", example="kkiapay"),
 *                     @OA\Property(property="amount", type="number", format="float", example=100.50),
 *                     @OA\Property(property="statut", type="string", example="SUCCESS"),
 *                     @OA\Property(property="motif", type="string", example="Recharge de portefeuille")
 *                 )),
 *                 @OA\Property(property="total", type="integer", example=100),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="last_page", type="integer", example=10),
 *                 @OA\Property(property="from", type="integer", example=1),
 *                 @OA\Property(property="to", type="integer", example=10)
 *             ),
 *             @OA\Property(property="message", type="string", example="List of payments made when topping up the wallet")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status_code", type="integer", example=500),
 *            @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             ),
 *             @OA\Property(property="message", type="string", example="An error occurred")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No payments found or invalid request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status_code", type="integer", example=404),
 *        @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             ),
 *             @OA\Property(property="message", type="string", example="No payments found or invalid request")
 *         )
 *     )
 * )
 */



    public function getUserPayements(Request $request, $perpage)
    {
        try {
            $typeId='STD';
            $personId = (new Service())->returnPersonIdAuth();
            $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',Commission::whereShort($typeId)->first()->id)->first();

            $perpage = $request->input('perpage', 10);

            $data = $wallet->payements()->paginate($perpage);

            return (new Service())->apiResponse(200,$data,"List of payments made when topping up the wallet");
        }catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}


