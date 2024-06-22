<?php

namespace App\Http\Controllers;

use App\Mail\login;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailwithoutfile;
use App\Mail\NotificationEmail;
use App\Models\ChatMessage;
use App\Models\Person;
use App\Models\TradeChat;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatMessageController extends Controller
{
    public function sendNotification($reciever_id,$title,$body,$return){
        try {
            $mail = [
                'title' => $title,
                'body' =>$body
               ];

            $receiver = User::find($reciever_id);
               Mail::to($receiver->email)->send(new NotificationEmailwithoutfile($mail));
               return response()->json([
                'message' =>$return
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function sendLoginConfirmationNotification($reciever_id,$title,$body,$return){
        try {
            $mail = [
                'title' => $title,
                'body' =>$body
               ];

            $receiver = User::find($reciever_id);
               Mail::to($receiver->email)->send(new login($mail));
               return response()->json([
                'message' =>$return
            ],200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function createChatMessage($tradeChatId, Request $request){
        try {

            $request->validate([
                'content' => 'required'
            ]);

            $tradeChat = new TradeChatController();

            $a = $tradeChat->checkIfmessageexist($tradeChatId);

            if($a){
                return $a;
            }

            $reciever = TradeChat::find($tradeChatId)->sentTo;
            $sender = TradeChat::find($tradeChatId)->sentBy;

            // return  TradeChat::find($tradeChatId);

            // return [$reciever,$sender];

            $service = new Service();

            $chat = new ChatMessage();
            $chat->content = $request->content;
            $chat->tradeChats = $tradeChatId;
            $chat->sender = $sender;
            $chat->reciever = $reciever;
            $chat->isTradeChat = true;
            $chat->uid = $service->generateUid($chat);
            if($chat->save()){
                $tradeChat->updateTradeChatLastMessage($tradeChatId,$chat->content);
            }

            return response()->json([
                'message' => 'Chat message created  successffuly !'
            ]);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    

    public function markMessageAsRead($chatMessageId){
        try {

            $a = $this->checkIfmessageexist($chatMessageId);

            if($a){
                return $a;
            }
    

            ChatMessage::whereId($chatMessageId)->update(['isRead' =>true]);

            return response()->json([
                'message' => 'Message read successfully !'
            ],200);


        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function markMessageAsUnRead($chatMessageId){
        try {

            $a = $this->checkIfmessageexist($chatMessageId);

        if($a){
            return $a;
        }


            ChatMessage::whereId($chatMessageId)->update(['isRead' =>false]);

            return response()->json([
                'message' => 'Message unread successfully !'
            ],200);


        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function destroy($chatMessageId){
        try {

        $a = $this->checkIfmessageexist($chatMessageId);

        if($a){
            return $a;
        }

        ChatMessage::whereId($chatMessageId)->update(['deleted' => true]);

        return response()->json([
            'message' => 'Message deleted successfully !'
        ],200);

        } catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function checkIfmessageexist($chatMessageId){
        try {

            $chat = ChatMessage::find($chatMessageId);

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



}
