<?php

namespace App\Services;

use App\Http\Controllers\CommissionWalletController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Service;
use App\Models\Favorite;
use App\Models\Ad;
use App\Models\Category;
use App\Models\CommissionWallet;
use App\Models\Escrow;
use App\Models\File;
use App\Models\OngingTradeStage;
use App\Models\Order;
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
        if ($tradeStage->next_step_id != null) {
            $tradeStage->update(['complete' => true]);
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
            return $this->completeTrade($trade);
        } else {
            return response()->json([
                'message' => 'Check if all stages are completed'
            ], 200);
        }
    }
    
    public function completeTrade($trade) {
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
    
        return $this->processEscrow($trade, $credit, $walletSeller, $sellerUserId);
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
        $walletSeller = CommissionWallet::where('person_id', $sellerPersonId)->first();
        if (!$walletSeller) {
            (new CommissionWalletController())->generateStandardUnAuthWallet($sellerPersonId);
            $walletSeller = CommissionWallet::where('person_id', $sellerPersonId)->first();
        }
        return $walletSeller;
    }
    
    public function updateUserWallet($personId, $amount) {
        return (new OrderController())->updateUserWallet($personId, $amount);
    }
    
    public function processEscrow($trade, $credit, $walletSeller, $sellerUserId) {
        $escrow = new EscrowController();
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        if (!$escrowOrder) {
            return response()->json([
                'message' => 'Order not paid yet'
            ], 400);
        }
    
        if ($escrowOrder->amount <= 0 || ($escrowOrder->amount < $credit)) {
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
        $transactionId = $orderController->createTransaction($trade->order_detail->order_id, $walletSeller, Order::find($trade->order_detail->order_id)->user_id, $sellerUserId, $credit);
    
        $errorCreateAllowTransaction = $orderController->createAllowTransaction($transactionId);
        if ($errorCreateAllowTransaction) {
            return $errorCreateAllowTransaction;
        }
    
        $escrowOrder = Escrow::where('order_id', $trade->order_detail->order_id)->first();
        $escrowOrder->update(['status' => 'partially_released']);
    
        Order::whereId($trade->order_detail->order_id)->update(['status' => TypeOfType::whereLibelle('started')->first()->id]);
    
        return response()->json([
            'message' => 'Trade end successfully'
        ], 200);
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
        $statut_trade_id = TypeOfType::whereLibelle('canceltrade')->first()->id;
        $status_order_id = TypeOfType::whereLibelle('canceled')->first()->id;
        $statut_escrow_id = TypeOfType::whereLibelle('partially_released')->first()->id;
    
        if (!$statut_trade_id || !$status_order_id || !$statut_escrow_id) {
            return $this->statusNotFoundResponse();
        }
    
        $credit = $trade->order_detail->price * $trade->order_detail->quantity;
        $this->refundBuyer($trade, $credit);
    
        $trade->enddate = now();
        $trade->status_id = $statut_trade_id;
        $trade->save();
    
        Order::whereId($trade->order_detail->order_id)->update(['status' => $status_order_id]);
    
        return response()->json([
            'message' => 'Trade canceled successfully'
        ]);
    }
    
    public function refundBuyer($trade, $credit) {
        $service = $this->getService();
        $buyerUserId = Order::find($trade->order_detail->order_id)->user_id;
        $buyerPersonId = $service->returnUserPersonId($buyerUserId);
        $walletBuyer = CommissionWallet::where('person_id', $buyerPersonId)->first();
        $buyerAmount = $walletBuyer->balance + $credit;
    
        $orderController = new OrderController();
        $errorUpdateUserWallet = $orderController->updateUserWallet($buyerPersonId, $buyerAmount);
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
    
        $transactionId = $orderController->createTransaction($trade->order_detail->order_id, $walletBuyer, $buyerUserId, $buyerUserId, $credit);
        $errorCreateAllowTransaction = $orderController->createAllowTransaction($transactionId);
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
}