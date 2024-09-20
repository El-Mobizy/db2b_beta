<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Models\OtpPasswordForgotten;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Carbon\Carbon;


class OtpPasswordForgottenController extends Controller
{
    public function createForgottenOtp($otp_code,$userId){
        try {

            $otp = new OtpPasswordForgotten();
            $otp->code_otp = $otp_code;
            $otp->user_id = $userId;
            $otp->expired_at = $otp->expired_at = (new DateTime())->modify('+30 minutes')->format('Y-m-d H:i:s');
            $otp->save();
            $email = User::whereId($userId)->first()->email;

            // return $email;

           $this->notifyOtpClientForgotten($email, $otp_code);

        //    return 1;

        } catch(Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function notifyOtpClientForgotten($email, $otp_code){
        $title= "Recovery your password";
        $body = "$otp_code is your password reset code. You have thirty minutes before this code becomes invalid. ";

        dispatch(new SendEmail(User::where('email',$email)->first()->id,$title,$body,'Email sent successfully !',1));


       
    }


    /**
 * @OA\Post(
 *     path="/api/users/resendForgottenOtp/{uid}",
 *     summary="Resend OTP for password recovery",
 *     description="Generate a new OTP and send it to the user's uid for password recovery.",
 *     tags={"Password Recovery"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="Uid of the user",
 *         required=true,
 *         @OA\Schema(type="string", example="dhshfjsg-sgsg-sgg-s")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="We would like to inform you that a message containing 6 digits has been sent to you by e-mail. Please enter the code to change your password.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="User not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="An unexpected error occurred.")
 *         )
 *     )
 * )
 */
    public function resendForgottenOtp($uid){
        try {



            $otp_code = (new Service())->generateSixDigitNumber();

            $userId = User::whereUid($uid)->first()->id;

            $errorcreateForgottenOtp = (new OtpPasswordForgottenController)->createForgottenOtp($otp_code,$userId,$uid);

                    if( $errorcreateForgottenOtp){
                        return  $errorcreateForgottenOtp;
                    }

                    return response()->json([
                        'status_code' => 200,
                        'message' => "Code sent successfully !"
                     ],200);

        } catch(Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    

}
