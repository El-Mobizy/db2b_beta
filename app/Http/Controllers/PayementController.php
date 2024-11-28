<?php

namespace App\Http\Controllers;

use App\Models\Payement;
use Exception;
use Illuminate\Http\Request;

class PayementController extends Controller
{
    function storePayement($commission_wallet_id = null, $transaction_id = null, $payement_type = null, $amount = null, $statut = null,$motif=null)
    {
        try {

            if(Payement::whereTransactionId($transaction_id)->exists()){
                throw new Exception("This transaction ID already exists");
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
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}


