<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Trade;
use App\Models\TradeChat;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;

class TradeChatController extends Controller
{
    public function createTradeChat($tradeId){
        try {

            if(Auth::user()->id == Trade::find($tradeId)->buyer_id){
                $send_to =Trade::find($tradeId)->buyer_id;
            }else{
                $send_to =Trade::find($tradeId)->seller_id;
            }
            $service = new Service();
            $tradeChat = new TradeChat();
            $tradeChat->sentBy = Auth::user()->id;
            $tradeChat->sentTo = $send_to;
            $tradeChat->trade_id = $tradeId;
            $tradeChat->lastMessage = 'a';
            $tradeChat->uid = $service->generateUid($tradeChat);
            $tradeChat->save();
            return response()->json([
                'message' => 'Trade chat created successffuly !'
            ],200);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function updateTradeChatLastMessage($tradeChatId, $message){
        try {

          TradeChat::whereId($tradeChatId)->update(['lastMessage' => $message]);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function markTradeChatAsSpam($tradeChatId){
        try {
            
            $a = $this->checkIfmessageexist($tradeChatId);

            if($a){
                return $a;
            }

            TradeChat::whereId($tradeChatId)->update(['isSpam' => true]);
            return response()->json([
                'message' => 'Trade chat marked as spam successffuly !'
            ]);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    public function archiveTradeChat($tradeChatId){
        try {
            $a = $this->checkIfmessageexist($tradeChatId);

            if($a){
                return $a;
            }

            TradeChat::whereId($tradeChatId)->update(['isArchived' => true]);
            return response()->json([
                'message' => 'Trade chat marked archived successffuly !'
            ]);
        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function destroy($tradeChatId){
        try {

        $this->checkIfmessageexist($tradeChatId);

        TradeChat::whereId($tradeChatId)->update(['deleted' => true]);

        return response()->json([
            'message' => 'Message deleted successfully !'
        ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function checkIfmessageexist($tradeChatId){
        try {

            $chat = TradeChat::find($tradeChatId);

            if(!$chat){
                return response()->json([
                    'message' => 'Chat message not found !'
                ],200);
            }

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function getMessageOfTradeChat($tradeChatId){
        try {

            return response()->json([
                'data' => ChatMessage::whereDeleted(false)->where('tradeChats',$tradeChatId)->get()
            ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

}





// try {

// } catch(Exception $e){
//     return response()->json([
//         'error' => $e->getMessage()
//     ],500);
// }