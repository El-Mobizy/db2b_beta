<?php

namespace App\Http\Controllers;

use App\Models\Escrow;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    public function debitEscrow($escrowId,$debitValue){
        try {
            $escrow = Escrow::find($escrowId);
            if (!$escrow) {
                return response()->json(['error' => 'Escrow not found'], 404);
            }
            $escrow->amount -= $debitValue;
            $escrow->save();

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
}
