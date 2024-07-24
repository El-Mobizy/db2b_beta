<?php

namespace App\Services;

use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CommissionWalletController;
use App\Http\Controllers\DeliveryAgencyController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Service;
use App\Http\Controllers\TransactionController;
use App\Models\Favorite;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\DeliveryAgency;
use App\Models\Escrow;
use App\Models\EscrowDelivery;
use App\Models\File;
use App\Models\OngingTradeStage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\Trade;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;


class OngingTradeStageService  
{
    public function authenticateUser() {
        $service = new Service();
        return $service->checkAuth();
    }
    
    public function findTradeStage($ongingtradeStageId) {
        return OngingTradeStage::find($ongingtradeStageId);
    }
    
    public function tradeStageNotFoundResponse() {
        return response()->json([
            'message' => 'Trade stage not found'
        ], 200);
    }
    
    public function findTrade($tradeId) {
        return Trade::find($tradeId);
    }
    
    public function isTradeFinished($trade) {
        return $trade->status_id == TypeOfType::whereLibelle('endtrade')->first()->id || $trade->status_id == TypeOfType::whereLibelle('canceltrade')->first()->id;
    }
    
    public function tradeFinishedResponse() {
        return response()->json([
            'message' => 'This trade is already finished'
        ], 200);
    }
    
    public function handleActionType($tradeStage, $trade, $actionType) {
        if ($actionType == 'yes') {
            return $this->handleYesAction($tradeStage, $trade);
        } else if ($actionType == 'no') {
            return $this->handleNoAction($tradeStage, $trade);
        }
    }

    public function handleYesAction($tradeStage, $trade) {
        if ($tradeStage->yes_action == 'MOVE_TO_NEXT_STEP') {
            return $this->moveToNextStep($tradeStage);
        } else if ($tradeStage->yes_action == 'END_TRADE') {
            return $this->endTrade($tradeStage, $trade);
        }
    }

    public function moveToNextStep($tradeStage) {
        $tradeStage->update(['complete' => true]);
        if ($tradeStage->next_step_id != null) {
            return response()->json([
                'message' => 'MOVE_TO_NEXT_STEP',
                'data' => $tradeStage->next_step_id
            ]);
        } else {
            return response()->json([
                'message' => 'null',
                'data' => $tradeStage->next_step_id
            ]);
        }
    }

    public function endTrade($tradeStage, $trade) {
        $tradeStage->update(['complete' => true]);
        $allStagesCompleted = $trade->onging_trade_stage->every('complete') ? 1 : 0;
        if ($allStagesCompleted == 1) {
            return $this->completeTrade($trade,$tradeStage);
           
        } else {
            $tradeStage->update(['complete' => false]);
            return response()->json([
                'message' => 'Check if all stages are completed'
            ], 200);
        }
    }
    
    public function completeTrade($trade,$tradeStage) {
        $statut_trade_id = TypeOfType::whereLibelle('endtrade')->first()->id;
        $status_order_id = TypeOfType::whereLibelle('started')->first()->id;
        $statut_escrow_id = TypeOfType::whereLibelle('partially_released')->first()->id;
    
        if (!$statut_trade_id || !$status_order_id || !$statut_escrow_id) {
            return $this->statusNotFoundResponse();
        }
    
        $trade->enddate = now();
        $trade->status_id = $statut_trade_id;
        $trade->save();
    
        $user_person = $this->getService()->returnPersonAndUserId($trade->seller_id);
        $sellerPersonId = $user_person['person_id'];
        $sellerUserId = $user_person['user_id'];
    
        $walletSeller = $this->getOrCreateWallet($sellerPersonId);
        $credit = $trade->order_detail->price * $trade->order_detail->quantity;
        $sellerAmount = $walletSeller->balance + $credit;

        $errorUpdateUserWallet = $this->updateUserWallet($sellerPersonId, $sellerAmount);
        if ($errorUpdateUserWallet) {
            return $errorUpdateUserWallet;
        }

        $productName = Ad::whereId($trade->order_detail->ad_id)->first()->title;

        $this->notifySellerCredit($sellerUserId,$sellerPersonId,$productName);

        return $this->processEscrow($trade, $credit, $walletSeller, $sellerUserId,$tradeStage);
    }
    
    public function statusNotFoundResponse() {
        return response()->json([
            'message' => 'Status not found'
        ]);
    }
    
    public function getService() {
        return new Service();
    }
    
    public function getOrCreateWallet($sellerPersonId) {
        $typeId = Commission::whereShort('STD')->first()->id;
        $walletSeller = CommissionWallet::where('person_id', $sellerPersonId)->where('commission_id',$typeId)->first();
        if (!$walletSeller) {
            (new CommissionWalletController())->generateStandardUnAuthWallet($sellerPersonId);
            $walletSeller = CommissionWallet::where('person_id', $sellerPersonId)->where('commission_id',$typeId)->first();
        }
        return $walletSeller;
    }
    
    public function updateUserWallet($personId, $amount) {
        return (new WalletService())->updateUserWallet($personId, $amount);
    }
    
    public function processEscrow($trade, $credit, $walletSeller, $sellerUserId,$tradeStage) {
        $escrow = new EscrowController();
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        if (!$escrowOrder) {
            return response()->json([
                'message' => 'Order not paid yet'
            ], 400);
        }
    
        if ($escrowOrder->amount <= 0 || ($escrowOrder->amount < $credit)) {
            $tradeStage->update(['complete' => false]);
            $trade->enddate = now();
            $trade->status_id = TypeOfType::whereLibelle('pending')->first()->id;
            $trade->save();
            return response()->json([
                'message' => 'Insufficient order escrow balance',
                'escrowAmount' => $escrowOrder->amount,
                'sellerAmount' => $walletSeller->balance + $credit
            ], 200);
        }
    
        $errorDebitEscrow = $escrow->debitEscrow($escrowOrder->id, $credit);
        if ($errorDebitEscrow) {
            return $errorDebitEscrow;
        }
    
        return $this->finalizeTrade($trade, $walletSeller, $sellerUserId, $credit);
    }
    
    public function finalizeTrade($trade, $walletSeller, $sellerUserId, $credit) {
        $orderController = new OrderController();
        $transactionId = (new TransactionController())->createTransaction($trade->order_detail->order_id, $walletSeller, Order::find($trade->order_detail->order_id)->user_id, $sellerUserId, $credit);
    
        $errorCreateAllowTransaction = (new TransactionController())->createAllowTransaction($transactionId);
        if ($errorCreateAllowTransaction) {
            return $errorCreateAllowTransaction;
        }
    
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        $escrowOrder->update(['status' => 'partially_released']);
    
        Order::whereId($trade->order_detail->order_id)->update(['status' => TypeOfType::whereLibelle('started')->first()->id]);

        return $this->checkAndValidateSpecificOrder($trade->order_detail->order_id);

        return response()->json([
            'message' => 'Trade end successfully'
        ], 200);
    }

    public function checkAndValidateSpecificOrder($orderId) {
        try {

            $endTradeStatusId = TypeOfType::where('libelle', 'endtrade')->first()->id;
            $cancelTradeStatusId = TypeOfType::where('libelle', 'canceltrade')->first()->id;
    
            $order = Order::find($orderId);
    
            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }
    
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
    
            $allTradesHaveDesiredStatus = true;
    
            foreach ($orderDetails as $od) {
                $trade = Trade::where('order_detail_id', $od->id)->first();
    
                if ($trade->status_id !== $endTradeStatusId && $trade->status_id !== $cancelTradeStatusId) {
                    $allTradesHaveDesiredStatus = false;
                    break;
                }
            }

            if ($allTradesHaveDesiredStatus) {
                $validatedStatusId = TypeOfType::where('libelle', 'validated')->first()->id;
                $paidStatusId = TypeOfType::where('libelle', 'paid')->first()->id;
                $order->status = $validatedStatusId;
                $order->save();

                $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
                $escrowOrder->update(['status' => 'ended']);

                $errorrefundDeliveryAgent = $this->refundDeliveryAgent($orderId);
                if($errorrefundDeliveryAgent){
                    return $errorrefundDeliveryAgent;
                }

                $errorfundDeliveryAgent = $this->fundDeliveryAgent($orderId);
                if($errorfundDeliveryAgent){
                    return $errorfundDeliveryAgent;
                }

                $this->notifyAgentDeliveryCredit($order->id);

                EscrowDelivery::where('order_uid',Order::whereId($orderId)->first()->uid)->update(['status'=>$validatedStatusId]);

            }


            return response()->json([
                'message' => 'Order checked and updated successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function handleNoAction($tradeStage, $trade) {
        if ($tradeStage->no_action == 'MOVE_TO_PREV_STEP') {
            return $this->moveToPrevStep($tradeStage);
        } else if ($tradeStage->no_action == 'CANCEL_TRADE') {
            return $this->cancelTrade($tradeStage, $trade);
        }
    }

    public function moveToPrevStep($tradeStage) {
        if ($tradeStage->previous_step_id != null) {
            return response()->json([
                'message' => 'MOVE_TO_PREV_STEP',
                'data' => $tradeStage->previous_step_id
            ]);
        } else {
            return response()->json([
                'message' => 'null',
                'data' => $tradeStage->previous_step_id
            ]);
        }
    }

    public function cancelTrade($tradeStage, $trade) {
        $tradeStage->update(['complete' => true]);
        $statut_trade_id = TypeOfType::whereLibelle('canceltrade')->first()->id;
        $status_order_id = TypeOfType::whereLibelle('started')->first()->id;
        $statut_escrow_id = TypeOfType::whereLibelle('partially_released')->first()->id;

        if (!$statut_trade_id || !$status_order_id || !$statut_escrow_id) {
            return $this->statusNotFoundResponse();
        }

        $tradeStage->update(['complete' => true]);
        $allStagesCompleted = $trade->onging_trade_stage->every('complete') ? 1 : 0;
        if ($allStagesCompleted == 1) {
            $credit = $trade->order_detail->price * $trade->order_detail->quantity;
            $this->refundBuyer($trade, $credit,$tradeStage);

            $trade->enddate = now();
            $trade->status_id = $statut_trade_id;
            $trade->save();

            Order::whereId($trade->order_detail->order_id)->update(['status' => $status_order_id]);

            return $this->checkAndValidateSpecificOrder($trade->order_detail->order_id);

            return response()->json([
                'message' => 'Trade canceled successfully'
            ]);

        } else {
            $tradeStage->update(['complete' => false]);
            return response()->json([
                'message' => 'Check if all stages are completed'
            ], 200);
        }

    }
    
    public function refundBuyer($trade, $credit,$tradeStage) {
        $service = $this->getService();
        $typeId = Commission::whereShort('STD')->first()->id;
        $buyerUserId = Order::find($trade->order_detail->order_id)->user_id;
        $buyerPersonId = $service->returnUserPersonId($buyerUserId);
        $walletBuyer = CommissionWallet::where('person_id', $buyerPersonId)->where('commission_id',$typeId)->first();
        $buyerAmount = $walletBuyer->balance + $credit;
    
        $orderController = new OrderController();
        $errorUpdateUserWallet = (new WalletService())->updateUserWallet($buyerPersonId, $buyerAmount);
        if ($errorUpdateUserWallet) {
            return $errorUpdateUserWallet;
        }

    
        $escrow = new EscrowController();
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        if (!$escrowOrder) {
            return response()->json([
                'message' => 'Order not paid yet'
            ], 400);
        }

    
        if ($escrowOrder->amount <= 0 || ($escrowOrder->amount < $credit)) {
            $tradeStage->update(['complete' => false]);
            $trade->enddate = now();
            $trade->status_id = TypeOfType::whereLibelle('pending')->first()->id;
            $trade->save();
            return response()->json([
                'message' => 'Insufficient order escrow balance',
                'escrowAmount' => $escrowOrder->amount,
                'sellerAmount' => $buyerAmount
            ], 200);
        }

        $errorDebitEscrow = $escrow->debitEscrow($escrowOrder->id, $credit);
        if ($errorDebitEscrow) {
            return $errorDebitEscrow;
        }

        $transactionId =  (new TransactionController())->createTransaction($trade->order_detail->order_id, $walletBuyer, null, $buyerUserId, $credit);
        $errorCreateAllowTransaction = (new TransactionController())->createAllowTransaction($transactionId);
        if ($errorCreateAllowTransaction) {
            return $errorCreateAllowTransaction;
        }

        $escrowOrder->update(['status' => 'partially_released']);

    }

    public function errorResponse($e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }

    public function refundDeliveryAgent($orderId){

        $walletService = new WalletService();
        $typeId = Commission::whereShort('STD')->first()->id;
        $order = Order::find($orderId);
        $deliveryAgentEscrowDelivery = EscrowDelivery::where('order_uid',$order->uid)->first();
        $deliveryAgentPersonUid = $deliveryAgentEscrowDelivery->person_uid;
        $deliveryAgentPersonid = Person::whereUid($deliveryAgentPersonUid)->first()->id;
        $wallet = CommissionWallet::where('person_id',$deliveryAgentPersonid)->where('commission_id',$typeId)->first();
        $credit = $deliveryAgentEscrowDelivery->delivery_agent_amount + $wallet->balance;

        $errorUpdateDeliveryAgentWallet = $walletService->updateUserWallet($deliveryAgentPersonid, $credit);
        if ($errorUpdateDeliveryAgentWallet) {
            return $errorUpdateDeliveryAgentWallet;
        }
    }

    public function fundDeliveryAgent($orderId){
        $order = Order::find($orderId);
        $escrowOrder = Escrow::where('order_id', $orderId)->first();
        $deliveryAgentEscrowDelivery = EscrowDelivery::where('order_uid',$order->uid)->first();
        $deliveryAgentPersonUid = $deliveryAgentEscrowDelivery->person_uid;
        $deliveryAgentPersonid = Person::whereUid($deliveryAgentPersonUid)->first()->id;
        $typeId = Commission::whereShort('DLV')->first()->id;

        $wallet = CommissionWallet::where('person_id',$deliveryAgentPersonid)->where('commission_id',$typeId)->first();

        if(!$wallet){
            $walletC = new CommissionWalletController();
            $walletC->generateStandardUnAuthWallet($deliveryAgentPersonid,'DLV');
        }
        $wallet = CommissionWallet::where('person_id',$deliveryAgentPersonid)->where('commission_id',$typeId)->first();

        $credit = floatval($escrowOrder->amount)*(DeliveryAgency::where('person_id',$deliveryAgentPersonid)->first()->commission / 100  ) + $wallet->balance;


        $errorUpdateDeliveryAgentWallet = (new WalletService())->updateUserWallet($deliveryAgentPersonid, $credit,'DLV');
        if ($errorUpdateDeliveryAgentWallet) {
            return $errorUpdateDeliveryAgentWallet;
        }

        $errorDebitEscrow = (new EscrowController())->debitEscrow($escrowOrder->id, $credit);
        if ($errorDebitEscrow) {
            return $errorDebitEscrow;
        }

        $userId = Person::whereId($deliveryAgentPersonid)->first()->user_id;

        $transactionId =  (new TransactionController())->createTransaction($orderId, $wallet, null, $userId, $credit);
        $errorCreateAllowTransaction = (new TransactionController())->createAllowTransaction($transactionId);
        if ($errorCreateAllowTransaction) {
            return $errorCreateAllowTransaction;
        }

    }




    public function notifySellerCredit($sellerUserId,$sellerPersonId,$productName){
        try {

           $service = new Service();
           $balance = $service->returnSTDPersonWalletBalance($sellerPersonId);

            $title = "Payment Received for Your Product";
            $body = "We are pleased to inform you that the payment for your product, $productName, has been successfully processed. The amount has been credited to your account, bringing your current balance to $balance XOF.

            Thank you for your continued partnership.

            Best regards.";

            $mail = new MailController();

            $mail->sendNotification($sellerUserId,$title,$body, '');

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function notifyAgentDeliveryCredit($orderId){
        try {
            $order = Order::find($orderId);
            $escrowDelivery = EscrowDelivery::where('order_uid', $order->uid)->first();
            $deliveryPersonUid = $escrowDelivery->person_uid;
            $deliveryPersonId = Person::whereUid($deliveryPersonUid)->first()->id;

           $service = new Service();
           $balance = $service->returnSTDPersonWalletBalance($deliveryPersonId,'DLV');
    
            $title = "Delivery Completed Successfully!";
            $body = "The delivery for order #{$order->uid} has been completed successfully. Your Bonus Delivery Commission wallet has been credited. Your new balance is {$balance} XOF.";
    
            $mail = new MailController();
            $mail->sendNotification(Person::whereId($deliveryPersonId)->first()->user_id, $title, $body,'');
    
            return response()->json([
                'message' => 'Notification sent successfully'
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}