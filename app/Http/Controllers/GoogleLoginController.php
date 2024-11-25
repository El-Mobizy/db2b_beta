<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite ;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

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


        /**
 * @OA\Post(
 *     path="/api/users/connexion/google",
 *     summary="Connexion via Google",
 *     description="Permet à un utilisateur de se connecter via Google OAuth 2.0.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", description="Code d'autorisation retourné par Google", example="4/0AX4XfWhd-example-code")
 *         )
 *     ),
 *     @OA\Response(
 *     response=200,
 *     description="Connexion réussie",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="status_code", type="integer", example=200),
 *         @OA\Property(property="data", type="object"),
 *         @OA\Property(property="message", type="string", example="Connexion réussie."),
 *           @OA\Property(property="error", type="string", example=""),
 *             @OA\Property(property="success", type="boolean", example=true)
 *     )
 * ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status_code", type="integer", example=400),
 *            @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Email ou mot de passe incorrect."),
 *             @OA\Property(property="error", type="string", example="Erreur"),
 *             @OA\Property(property="success", type="boolean", example=false)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur interne du serveur",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status_code", type="boolean", example=false),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Erreur interne."),
 *             @OA\Property(property="error", type="string", example="Erreur inattendue."),
 *             @OA\Property(property="success", type="boolean", example=false)
 *         )
 *     )
 * )
 */

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
                "password" =>null
            ];
    
    
            $email = $userInfoResponse['email'];
    
            $request = new Request($data);
    
            $user = User::whereEmail($email)->first();
    
            if(!$user){
                $user = new User();

                $phone = $data['phone'] ?? null;
                $user->email = $data['email'];
                $user->phone =$phone;
                $user->password = $data['password'] ?? 'P@$$w0rd';
                $user->uid = Str::uuid();
                $user->last_ip_login = request()->ip();
                $user->code_user = (new Service())->generateRandomAlphaNumeric(7,(new User()),'code_user');
                $user->created_at = date('Y-m-d H:i:s');;
                $user->updated_at =date('Y-m-d H:i:s');

                $user->save();

                (new PersonController())->createPerson(null, $user->email, $phone,$request);
            }

            $user = User::whereEmail($email)->first();

            Auth::login($user);

            $token = JWTAuth::fromUser($user);

            unset($user->code);

            $data = [
                    'user' => $user,
                    'access_token' => $token,
            ];

            return (new Service())->apiResponse(200, $data, 'Logged sucessfully');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }

    }
}
