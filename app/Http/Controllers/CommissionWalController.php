<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionWallet;
use Illuminate\Http\Request;

class CommissionWalController extends Controller
{
    public function generateStandardWallet(){
        try {
            $service = new Service();

            $personId = $service->returnPersonIdAuth();

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
