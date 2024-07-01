<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionWallet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    public function generateStandardWallet(){
        try {
            $service = new Service();

            $personId = $service->returnPersonIdAuth();
            $wallet = new CommissionWallet();
            $wallet->balance = 0;
            $wallet->prev_balance = 0;
            // return 1;
            $wallet->commission_id = Commission::where('short','STD')->first()->id;
            // return Commission::where('short','STD')->first()->id;
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

    public function generateStandardUnAuthWallet($personId){
        try {
            $service = new Service();
            $wallet = new CommissionWallet();
            $wallet->balance = 0;
            $wallet->prev_balance = 0;
            $wallet->commission_id = Commission::where('short','STD')->first()->id;
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

}
