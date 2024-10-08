<?php

namespace App\Http\Controllers;

use App\Models\CommissionWallet;
use App\Models\Escrow;
use App\Models\Order;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;

class EscrowController extends Controller
{


    public function createEscrow($orderId){
        try {
            $service = new Service();
            $escrow = new Escrow();

            $wallet = CommissionWallet::where('person_id',Person::whereId(
                (new Service)->returnUserPersonId(
                    User::whereId(
                        Order::whereId($orderId)->first()->user_id
                        )->first()->id
                    )
                )->first()->id
            )->first();

            $order = Order::find($orderId);
            $escrow->order_id = $orderId;
            $escrow->status = 'Secured';
            $escrow->amount =  $order->amount;
            $escrow->uid= $service->generateUid($escrow);

            if($escrow->save()){
                return (new TransactionController)->createTransaction($orderId,$wallet,
                User::whereId(Order::whereId($orderId)->first()->user_id)->first()->id, null,$order->amount,'credit' );
            }

        } catch(\Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
    public function debitEscrow($escrowId,$debitValue){
        try {
            $escrow = Escrow::find($escrowId);
            if (!$escrow) {
                return response()->json(['message' => 'Escrow not found'], 400);
            }
            $escrow->amount -= $debitValue;
            if($escrow->save()){
                
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/escrow/getEscrow/{perpage}",
     *     summary="Get paginated list of escrows",
     *     description="Returns a paginated list of escrows",
     *     tags={"Escrow"},
     *     @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         description="Number of items per page",
     *         required=true,
     *         @OA\Schema(type="integer")
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
     *         description="Internal Server Error",
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

    public function getEscrow($perpage){
        try {
            $escrows = Escrow::paginate(intval($perpage));
            return response()->json([
                'data' => $escrows
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/escrow/showEscrow/{id}",
     *     summary="Get details of a specific escrow",
     *     description="Returns details of a specific escrow by ID",
     *     tags={"Escrow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the escrow",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 ref=""
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Escrow not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
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


    public function showEscrow($id){
        try {
            $escrow = Escrow::find($id);
            if (!$escrow) {
                return response()->json(['message' => 'Escrow not found'], 400);
            }
            return response()->json([
                'data' => $escrow
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


}
