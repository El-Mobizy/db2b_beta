<?php

namespace App\Http\Controllers;

use App\Models\OngingTradeStage;
use App\Models\OrderDetail;
use App\Models\Trade;
use App\Models\TradeChat;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TradeController extends Controller
{
    public function createTrade($orderDetailId, $buyerId, $sellerId,$enddate, $amount){
        try{


            $trade = new Trade();
            $service = new Service();
            $trade->order_detail_id = $orderDetailId;
            $trade->ad_id = OrderDetail::find($orderDetailId)->ad_id;
            $trade->buyer_id = $buyerId;
            $trade->seller_id = $sellerId;
            $trade->status_id = TypeOfType::whereLibelle('pending')->first()->id;
            $trade->ref = 'Trade-'.$service->generateRandomAlphaNumeric(7,$trade,'ref') ;
            $trade->uid = $service->generateUid($trade);
            $trade->amount = $amount;
            $trade->enddate = $enddate;
            if($trade->save()){
                $OngingTradeStage = new OngingTradeStageController();
                $OngingTradeStage->ongingCreateTradeStage($trade->id);
                return response()->json([
                    'message' => 'Trade created sucessfully'
                ],200);
            }else{
                return response()->json([
                    'message' => 'No'
                ],200);
            }
           
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function updateTradeStatusCompleted($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('completed')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    public function updateTradeStatusCanceled($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('canceled')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function updateTradeStatusDisputed($tradeId){
        try {

            Trade::find($tradeId)->update(['status_id' => TypeOfType::whereLibelle('disputed')->first()->id]);

            return response()->json([
                'message' => 'Trade updated sucessfully'
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function displayTrades(){
        try {
            $userId = Auth::user()->id;

            $trades = Trade::where('buyer_id', $userId)->orWhere('seller_id', $userId)->get();

            return response()->json([
                'data' => $trades
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

        public function displayTradesWithoutEndDate(){
            try {
                return response()->json([
                    'data' => Trade::where('enddate','1000-10-10 10:10:10')->get()
                ],200);
            } catch(Exception $e){
                return response()->json([
                    'error' => $e->getMessage()
                ],500);
            }
    }

    public function updateEndDate($tradeId,Request $request){
        try {

                $request->validate([
                    'enddate' => 'required'
                ]);

                Trade::find($tradeId)->update([
                    'enddate' => $request->input('enddate')
                ]);

                return response()->json([
                    'message' => 'end date updated successfully !'
                ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function getTradeStage($tradeId){
        try {
            $stages = OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->get();

            foreach($stages as $stage){
                if(Auth::user()->id == Trade::find($tradeId)->buyer_id && $stage->action_done_by =='BUYER'){
                    $stage->buyer = true;
                    $stage->seller = false;
                }else if(Auth::user()->id == Trade::find($tradeId)->seller_id && $stage->action_done_by =='SELLER'){
                    $stage->buyer = true;
                    $stage->seller = false;
                }else{
                    $stage->buyer = false;
                    $stage->seller = false;
                }
                $data[] = $stage ;
            }

            return response()->json([
                'data' => $data
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function getTradeStageDone($tradeId){
        try {

            return response()->json([
                'data' => OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->whereComplete(true)->get()
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function getTradeStageNotDoneYet($tradeId){
        try {

            return response()->json([
                'data' => OngingTradeStage::where('trade_id',$tradeId)->whereDeleted(false)->whereComplete(false)->get()
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    public function getTradeChat($tradeId){
        try {
            $chats = TradeChat::where('trade_id',$tradeId)->whereDeleted(false)->get();

           

            return response()->json([
                'data' => $chats
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

  
}

// try {
//     //code...
// } catch(Exception $e){
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }