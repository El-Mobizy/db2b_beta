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

    /**
     * @OA\Post(
     *     path="/api/chatMessage/createChatMessage/{tradeChatId}",
     *     summary="Create a chat message",
     *     tags={"ChatMessage"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tradeChatId",
     *         in="path",
     *         description="ID of the trade chat",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Hello, how are you?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat message created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chat message created successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, content is required",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The content field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Trade chat not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade chat not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/chatMessage/markMessageAsRead/{chatMessageId}",
     *     summary="Mark a message as read",
     *     tags={"ChatMessage"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="chatMessageId",
     *         in="path",
     *         description="ID of the chat message",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message read successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */


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


    /**
 * @OA\Post(
 *     path="/api/chatMessage/markMessageAsUnRead/{chatMessageId}",
 *     summary="Mark a message as unread",
 *     tags={"ChatMessage"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="chatMessageId",
 *         in="path",
 *         description="ID of the chat message",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Message marked as unread successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Message unread successfully!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Message not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Message not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An error occurred")
 *         )
 *     )
 * )
 */
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
