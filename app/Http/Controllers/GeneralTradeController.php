<?php

namespace App\Http\Controllers;

use App\Models\GeneralTrade;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;

class GeneralTradeController extends Controller
{

    public function storeGeneralTrade($orderId, $personId, $tradeId)
    {

        try {
            $status = TypeOfType::whereLibelle('pending')->first()->libelle ?? null;

            if (is_null($status)) {
                throw new Exception('Statut "pending" introuvable.');
            }

            $generalTrade = new GeneralTrade();
            $generalTrade->order_id = $orderId;
            $generalTrade->person_id = $personId;
            $generalTrade->trade_id = $tradeId;
            $generalTrade->status = $status;
            $generalTrade->save();
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
        }

    }
}
