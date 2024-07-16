<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailWithoutfile;
use App\Mail\NotificationEmail;
use App\Models\User;
use Exception;
use App\Mail\login;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendNotification($reciever_id,$title,$body,$return){
        try {
            $mail = [
                'title' => $title,
                'body' =>$body
               ];

           

            $receiver = User::find($reciever_id);
            Mail::to($receiver->email)->send(new NotificationEmailWithoutfile($mail));

            $notification = new NotificationController();
            $service = new Service();
            $notification->addNotification($service->returnUserPersonId($reciever_id),$title,$body);

               return response()->json([
                'message' =>$return
            ],200);
        } catch (Exception $e) {
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

            $notification = new NotificationController();
            $service = new Service();
            $notification->addNotification($service->returnUserPersonId($reciever_id),$title,$body);

               return response()->json([
                'message' =>$return
            ],200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }
}
