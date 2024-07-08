<?php

namespace App\Http\Controllers;

use App\Models\CommissionWallet;
use App\Models\DeliveryAgency;
use App\Models\Order;
use App\Models\TypeOfType;
use App\Services\OngingTradeStageService;
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

    public function checkWalletBalance($deliveryPersonId, $orderAmount) {
       $service = new Service();
       $walletBalance = $service->returnSTDPersonWalletBalance($deliveryPersonId);

       if($walletBalance < $orderAmount){
            return response()->json([
                'message' => 'You cannot take this delivery because your wallet balance is below the order amount.'
            ], 404);
       }
    }

    public function storeEscrowDelivery($deliveryPersonId, $orderId){
        try {
            $escrowDelivery = new EscrowDelivery();
            $escrowDelivery->person_id = $deliveryPersonId;
            $escrowDelivery->order_id = $orderId;
            $escrowDelivery->order_amount = Order::whereId($orderId)->amount;
            $escrowDelivery->delivery_agent_amount = $escrowDelivery->order_amount;
            $escrowDelivery->status = TypeOfType::where('libelle','pending')->first()->id; 
            $escrowDelivery->pickup_date = null; // Date de prise en charge 
            $escrowDelivery->delivery_date = null; // Date de livraison 
            $escrowDelivery->created_at = now();
            $escrowDelivery->updated_at = now();
            $escrowDelivery->save();
        } catch (Exception $e) {
            return response()->json([
             'error' => $e->getMessage()
            ]);
         }
    }

    public function reserveAmount($deliveryPersonId, $orderId) {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
    
            $wallet = CommissionWallet::where('person_id', $deliveryPersonId)->first();
            if (!$wallet) {
                return response()->json(['error' => 'Wallet not found'], 404);
            }
    
            $errorcheckWalletBalance = $this->checkWalletBalance($deliveryPersonId, $order->amount);
            if($errorcheckWalletBalance){
                return $errorcheckWalletBalance;
            }

            $updateWallet = new OngingTradeStageService();
    
           $this->storeEscrowDelivery($deliveryPersonId, $orderId);
    
            return response()->json(['success' => 'Amount reserved successfully'], 200);
    
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
}
