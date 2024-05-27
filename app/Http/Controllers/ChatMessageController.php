<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailwithoutfile;
use App\Mail\NotificationEmail;
use App\Models\ChatMessage;
use App\Models\Person;
use App\Models\User;
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

    public function sendChatMessage ($reciever_id,$sender_id,$content){
        try {
        $message = new ChatMessage();
        $message->reciever_id = $reciever_id;
        $message->sender_id = $sender_id;
          $message->content = $content;
          $message->save();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }
}