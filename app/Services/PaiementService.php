<?php

namespace App\Services;

use App\Http\Controllers\KkiapayController;
use App\Http\Controllers\Service;
use Exception;


class PaiementService
{
    public function verifyFundWalletTransaction($type,$transactionId){
        try{
            $kkiapay = 'kkiapay';
            $mtn = 'mtn';


            switch ($type) {
                case $kkiapay:
                  return $this->getVerificationKkiapayStatus($transactionId);
                case $mtn:
                    return $this->getVerificationMtnStatus($transactionId);
            }
        
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    private function getVerificationKkiapayStatus($transactionId){
        $result = (new KkiapayController())->verifyTransaction($transactionId);

        $validTransaction = isset($result->status)?true:false;

        if($validTransaction == false){
            return [
                'status' => 'ERROR',
                'transaction_id' => $transactionId,
                'message' =>'ID de transaction invalid. '.$transactionId
            ] ;
        }
        if($result->status == "SUCCESS"){
            return [
                'status' => 'SUCCESS',
                'transaction_id' => $transactionId,
                'message' =>$result->reason
            ] ;
        }else{
            return [
                'status' => 'FAILED',
                'transaction_id' => $transactionId,
                'message' =>$result->reason
            ] ;
        }
    }

    private function getVerificationMtnStatus($transactionId){
        
    }


}