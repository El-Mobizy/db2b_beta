<?php

namespace App\Services;

use App\Http\Controllers\CommissionWalletController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Service;
use App\Models\Favorite;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Commission;
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

class WalletService
{
    public function updateUserWallet($personId,$diff,$type ='STD'){
        try{

            $typeId = Commission::whereShort($type)->first()->id;

            $wallet = CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->first();
            $walletAmount = $wallet->balance;

            CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->update([
                'prev_balance' => $walletAmount
            ]);

            CommissionWallet::where('person_id',$personId)->where('commission_id',$typeId)->update([
                'balance' => $diff
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


}