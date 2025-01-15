<?php

namespace App\Http\Controllers;

use App\Exceptions\errorException;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\Country;
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
    public function createWallet(Request $request){
        try {
            $service = new Service();
            $personId = $request->personId;
            $commissionId = $request->commissionId;

            if(CommissionWallet::wherePersonId($personId)->whereCommissionId($commissionId)->exists()){
                return(new Service())->apiResponse(404,(object)[],"You already own this wallet !");
            }

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
 *              @OA\Property(property="type", type="string", example="mtn_open"),
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
            'amount' => 'required|numeric',
            'type' => 'required|string',
        ]);

        $typeId = Commission::whereShort('STD')->first()->id;
        $service = new Service();
        $personId = $service->returnPersonIdAuth();
        $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();

        if((new AddressController())->getActiveService()->original['status_code'] !==200){
            $message = (new AddressController())->getActiveService()->original['message'];
            return (new Service())->apiResponse(404, (object)[], $message);
        }
        $activeAddress = (new AddressController())->getActiveService()->original['data']['activeAddress'];

        if(!$wallet){
            $this->generateStandardWallet();
        }

        $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();
        $person = Person::whereId($personId)->first();
        $country = Country::whereId($activeAddress->country_id)->first()->shortcode;

        $response = (new FedapayController())->processPackage($person,$request->amount,$activeAddress->phone,'bj',$request->type);

        $transactionId = $response['payment_intent']['intentable_id'];
        $amount = $request->amount;
        $payementType = $response['payment_intent']['mode'];
        $statut = $response['payment_intent']['status'];
        $userEmail = $response['payment_intent']['metadata']['paid_customer']['email'];
        $motif = 'Credit account';

        (new PayementController())->storePayement($userEmail, $transactionId, $payementType, $amount, $statut, $motif,$request->country);

        return (new Service())->apiResponse(200, [$response], 'Your payment is being processed. The status of your transaction will be updated once the payment is successfully confirmed.');

    } catch (errorException $e) {
        return (new Service())->apiResponse(404, [], $e->getMessage());
    }catch(Exception $e){
        return (new Service())->apiResponse(500,[], $e->getMessage());
    }
}

}


