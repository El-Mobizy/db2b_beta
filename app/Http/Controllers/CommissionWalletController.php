<?php

namespace App\Http\Controllers;

use App\Exceptions\errorException;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\Person;
use App\Services\PaiementService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CommissionWalletController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/wallet/createWallet",
     *     tags={"Wallet"},
     *   security={{"bearerAuth": {}}},
     *     summary="Create a wallet",
     *     operationId="createWallet",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="personId",
     *                 type="integer"
     *             ),
     *             @OA\Property(
     *                 property="commissionId",
     *                 type="integer"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string"
     *             )
     *         )
     *     )
     * )
     */
    public function createWallet($personId,$commissionId){
        try {
            $service = new Service();

            $wallet = new CommissionWallet();
            $wallet->balance = 0;
            $wallet->prev_balance = 0;
            $wallet->commission_id = $commissionId;
            $wallet->person_id = $personId;
            $wallet->uid= $service->generateUid($wallet);
            $wallet->save();

        return response()->json([
            'message' => 'saved successfully'
        ],200);
            } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/wallet/listWallets",
 *     tags={"Wallet"},
 *   security={{"bearerAuth": {}}},
 *     summary="List all wallets",
 *     operationId="listWallets",
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */

    public function listWallets(){
        try {
          $service = new Service();
          $personId = $service->returnPersonIdAuth();
          $wallets = CommissionWallet::where('person_id',$personId)->whereDeleted(0)->get();
        return response()->json([
            'data' => $wallets
        ],200);
            } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/wallet/walletDetail/{commissionWalletId}",
 *     tags={"Wallet"},
 *   security={{"bearerAuth": {}}},
 *     summary="Get wallet details by ID",
 *     operationId="walletDetail",
 *     @OA\Parameter(
 *         name="commissionWalletId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 ref=""
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Wallet not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Check if this wallet is yours",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             )
 *         )
 *     )
 * )
 */
    public function walletDetail($commissionWalletId){

        try {

        $service = new Service();
        $personId = $service->returnPersonIdAuth();
        $wallet = CommissionWallet::where('id',$commissionWalletId)->first();

          if(!$wallet){
            return response()->json([
                'message' => 'Wallet not found'
            ],200);
          }

          if($wallet->person_id == $personId){
            return response()->json([
                'message' => 'Check if this wallet is yours'
            ],200);
          }

        return response()->json([
            'data' =>$wallet
        ],200);
            } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generateStandardWallet($type = 'STD'){
        try {
            $service = new Service();

            $personId = $service->returnPersonIdAuth();
            $wallet = new CommissionWallet();
            $wallet->balance = 0;
            $wallet->prev_balance = 0;
            $wallet->commission_id = Commission::where('short',$type)->first()->id;
            $wallet->person_id = $personId;
            $wallet->uid= $service->generateUid($wallet);
            $wallet->save();

            return response()->json([
                'message' => 'Wallet generate successffuly'
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generateStandardUnAuthWallet($personId,$type='STD'){
        try {
            $service = new Service();
            $wallet = new CommissionWallet();
            $wallet->balance = 0;
            $wallet->prev_balance = 0;
            $wallet->commission_id = Commission::where('short',$type)->first()->id;
            $wallet->person_id = $personId;
            $wallet->uid= $service->generateUid($wallet);
            $wallet->save();

            return response()->json([
                'message' => 'Wallet generate successffuly'
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/wallet/AuthWallet",
 *     summary="Get authenticated user's wallets",
 *     description="Retrieve all wallets associated with the authenticated user.",
 *     tags={"Wallet"},
 *     security={{"bearerAuth":{}}},
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
 *         description="Internal server error",
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


    public function AuthWallet(){
        try {
           $service = new Service();
           $personId = $service->returnPersonIdAuth();
           $wallets = CommissionWallet::where('person_id',$personId)->get();
            return response()->json([
                'data' =>$wallets
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/wallet/AuthSTDWalletDetail",
 *     summary="Get authenticated user's STD wallets",
 *     description="Get  STD wallet associated with the authenticated user.",
 *     tags={"Wallet"},
 *     security={{"bearerAuth":{}}},
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
 *         description="Internal server error",
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

    public function AuthSTDWalletDetail(){
        try {
            $service = new Service();
            $personId = $service->returnPersonIdAuth();
            $commissionId = Commission::whereShort('STD')->first()->id;
            $wallets = CommissionWallet::where('person_id',$personId)->where('commission_id',$commissionId)->first();
             return response()->json([
                 'data' =>$wallets
             ],200);
         } catch (\Exception $e) {
             return response()->json([
                 'error' => $e->getMessage()
             ]);
         }
    }


       /**
 * @OA\Post(
 *     path="/api/wallet/addFund",
* tags={"MANAGE WALLET"},
 *   security={{"bearerAuth":{}}},
 *     summary="Add funds to a user's wallet",
 *     description="Add funds to a user's wallet",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="amount", type="number", example=100.00),
 *              @OA\Property(property="type", type="string", example="kkiapay"),
 *              @OA\Property(property="transaction_id", type="string", example="za_i42jlk"),
 * @OA\Property(property="phone", type="string", example="za_i42jlk"),
 *              @OA\Property(property="status", type="number", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Funds added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Successfully credited wallet")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation error")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */

 public function addFund(Request $request){
    try{
        $request->validate([
            'amount' => 'required',
            'type' => 'required|string',
            'transaction_id' => 'required|string',
            'status' => 'nullable|boolean',
            'phone' => 'required'
        ]);

        $typeRequired = ['kkiapay', 'mtn','fedapay'];

        if (!in_array($request->type, $typeRequired)) {
            return (new Service())->apiResponse(404,[],"La valeur de 'type' doit être l'une des suivantes : " . implode(', ', $typeRequired));
        }

        if ($request->status != '0' && $request->status != '1') {
            return (new Service())->apiResponse(404,[], "La valeur de 'status' doit être soit '0', soit '1'.");
        }

        $typeId = Commission::whereShort('STD')->first()->id;
        $service = new Service();
        $personId = $service->returnPersonIdAuth();
        $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();

        if(!$wallet){
            $this->generateStandardWallet();
        }

        $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();
        $person = Person::whereId($personId)->first();


        // return (new FedapayController())->process($request->amount,$request->phone);
        return (new FedapayController())->processPackage($person,$request->amount,$request->phone);

        // $statusPayement =  $request->status;

        //    $status = (new PaiementService())->verifyFundWalletTransaction($request->type,$request->transaction_id);



        //     if($status['status'] == 'ERROR'){
        //         return (new Service())->apiResponse(404, [], $status['message'] );
        //     }


        //     if($status['status'] == 'FAILED'){
        //         $motif = $status['message'];
        //         if($request->status == 1){
        //             return (new Service())->apiResponse(404, [], "Vérifiez bien le statut de paiement que vous retourné. Dans ce cas, le paiement a échoué et vous nous envoyé un statut qui a pour valeur  ".$request->status);
        //         }
        //         $statusPayement = 0;
        //     }

        //     if($status['status'] == 'SUCCESS'){
        //         $motif =  "Recharge de portefeuille";
        //         if($request->status == 0){
        //             return (new Service())->apiResponse(404, [], "Vérifiez bien le statut de paiement que vous retourné. Dans ce cas, le paiement a réussi et vous nous envoyé un statut qui a pour valeur  ".$request->status);
        //         }
        //         $statusPayement = 1;
        //     }
        
        $statusPayement = 1;

            if($statusPayement == 1){
                (new PayementController())->storePayement(
                    $wallet->id,
                    $request->transaction_id,
                    $request->type,
                    $request->amount,
                    "SUCCESS",
                    null
                );
            }else{
                (new PayementController())->storePayement(
                    $wallet->id,
                    $request->transaction_id,
                    $request->type,
                    $request->amount,
                    "FAILED",
                    $motif??''
                );
            }

            // if($status['status'] == 'FAILED'){
            //     $motif = $status['message'];
            //     return (new Service())->apiResponse(404, [],$motif);
            // }

        $credit =  $request->amount + CommissionWallet::where('person_id',$personId)->first()->balance;

        (new WalletService())->updateUserWallet($personId,$credit);

        return (new Service())->apiResponse(200,[],'Successfully credited wallet');

    } catch (errorException $e) {
        return (new Service())->apiResponse(404, [], $e->getMessage());
    }catch(Exception $e){
        return (new Service())->apiResponse(500,[], $e->getMessage());
    }
}

}


