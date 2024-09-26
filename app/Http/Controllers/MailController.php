<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailWithoutfile;
use App\Mail\NotificationEmail;
use App\Models\User;
use Exception;
use App\Mail\login;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendNotification($reciever_id,$title,$body,$mode = 2){
        try {
            //2 => notif et mail
            //0 => notif seul
            //1 => mail seul
            $mail = [
                'title' => $title,
                'body' =>$body
               ];

            if($mode==0 || $mode==2){
                $notification = new NotificationController();
                $service = new Service();
                $notification->addNotification($service->returnUserPersonId($reciever_id),$title,$body);
            }
    
            if($mode==1 || $mode==2){
                $receiver = User::find($reciever_id);
                Mail::to($receiver->email)->send(new NotificationEmailWithoutfile($mail));
            }
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

            // $notification = new NotificationController();
            // $service = new Service();
            // $notification->addNotification($service->returnUserPersonId($reciever_id),$title,$body);

            return (new Service())->apiResponse(404,[],$return);

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
}
