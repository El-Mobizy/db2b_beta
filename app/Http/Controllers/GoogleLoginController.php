<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite ;
use Illuminate\Support\Facades\Http;

class GoogleLoginController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            $scopes = [
                "https://www.googleapis.com/auth/drive"
                ];
                $url = Socialite::driver('google')
                    ->scopes($scopes)
                    ->stateless()  
                    ->redirectUrl(config('services.google.redirect')) 
                    ->redirect(); 
        
                $redirectUrl = $url->getTargetUrl() . '&prompt=consent';
        
                return redirect()->away($redirectUrl);
    
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }

    }


    public function connexionGoogle(Request $request){
        try {

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
                'code' => $request->code,
                'prompt' => 'consent',
            ]);
            $googleToken = $response->json();
    
    
            if (isset($googleToken['error'])) {
                return (new Service())->apiResponse(404, [$googleToken['error']], $googleToken['error_description']);
            }
    
            
            $tk =  $googleToken['access_token'];
    
            $userInfoResponse = Http::withToken($tk)
                ->get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json');
    
            $data =  [
                "lastname" => $userInfoResponse['family_name'],
                "firstname" => $userInfoResponse['given_name'],
                "email" => $userInfoResponse['email'],
                "passwword" =>null
            ];
    
    
            $email = $userInfoResponse['email'];
    
            $request = new Request($data);
    
            $user = User::whereEmail($email)->first();
    
            if(!$user){
                //todo: create user
            }
    
    
           //todo: connect user

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }

    }
}
