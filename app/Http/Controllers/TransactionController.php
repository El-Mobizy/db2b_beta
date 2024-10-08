<?php

namespace App\Http\Controllers;

use App\Models\AllowTransaction;
use App\Models\Order;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function createTransaction($orderId,$wallet,$sender_id,$receiver_id,$amount,$transactionType = 'transfer'){
        try {
            $service = new Service();
            $order = Order::find($orderId);
            $transaction = new Transaction();
            $transaction->order_id = $orderId;
            $transaction->sender_id = $sender_id;
            $transaction->receiver_id = $receiver_id;
            $transaction->commission_wallet_id = $wallet->id;
            $transaction->amount =  $amount;
            $transaction->transaction_type = $transactionType;
            $transaction->uid= $service->generateUid($transaction);
            $transaction->save();
            return $transaction->id;
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    public function createAllowTransaction($transactionId,$validaterId = null){
        try {
            $service = new Service();
            $transactionAllow = new AllowTransaction();
            $transactionAllow->validated_by_id = $validaterId;
            $transactionAllow->transaction_id = $transactionId;
            $transactionAllow->validated_on =  now();
            $transactionAllow->uid= $service->generateUid($transactionAllow);
            $transactionAllow->save();
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

     /**
     * @OA\Get(
     *     path="/api/transaction/getUserTransactions",
     *     summary="Get all transactions for the authenticated user",
     *     tags={"Transactions"},
     * @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="sent_transactions",
     *                 type="array",
     *                 @OA\Items(ref="")
     *             ),
     *             @OA\Property(
     *                 property="received_transactions",
     *                 type="array",
     *                 @OA\Items(ref="")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getUserTransactions($perpage=10)
    {
        try {
            $userId = Auth::id();

            $sentTransactions = Transaction::where('sender_id', $userId)->paginate($perpage);

            $receivedTransactions = Transaction::where('receiver_id', $userId)->paginate($perpage);

            return response()->json([
                'status' => 200,
                'sent_transactions' => $sentTransactions,
                'received_transactions' => $receivedTransactions,
                'message' => 'Transactions retrieved successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
