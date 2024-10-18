<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Models\Client;
use App\Models\DeliveryAgency;
use App\Models\Notification;
use App\Models\OtpPasswordForgotten;
use App\Models\Person;
use App\Models\Restricted;
use App\Models\Shop;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *      title="db2b-api",
 *      version="1.0.0",
 *      description="Développement d'une plateforme B2B multilingue complète",
 *      @OA\Contact(
 *          email="ayenaaurel15@gmail.com",
 *      )
 * )
 */
class UserController extends Controller
{

/**
     * @OA\Post(
     *     path="/api/users/validateEmail",
     *     summary="Valider un e-mail",
     *     description="Valide si l'e-mail fourni existe dans la base de données",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données d'entrée requises",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E-mail valide",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="E-mail valide"),
     *             @OA\Property(property="data", type="object", ref="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="E-mail invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="E-mail invalide")
     *         )
     *     )
     * )
     */

    public function validateEmail(Request $request){
        try
        {
            $request->validate([
                'email' => 'required',
            ]);

            $email = htmlspecialchars($request->input('email'));


            $user = User::whereEmail($email)->whereDeleted(false)->first();
            if (empty($user)) {
                return (new Service())->apiResponse(404, [], 'Email invalid');
            }

            if ($user->enabled == false) {
                return (new Service())->apiResponse(404, [], 'You are disabled');
            }
            unset($user->code);
            unset($user->password);

            return (new Service())->apiResponse(200,$user->email, 'Email valid');
        }
        catch (Exception $e)
        {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    /**
 * @OA\Post(
 *     path="/api/users/restrictedUser",
 *     summary="Ajouter un utilisateur restreint",
 *     description="Ajoute un utilisateur restreint à la base de données avec son email et adresse IP.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"email"},
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     description="L'email de l'utilisateur à restreindre"
 *                 ),
 *                 @OA\Property(
 *                     property="ip_address",
 *                     type="string",
 *                     format="ipv4",
 *                     description="L'adresse IP de l'utilisateur à restreindre"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Message de succès"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Détails de l'erreur de validation"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */
    public function restrictedUser(Request $request){
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email =  htmlspecialchars($request->input('email'));
            $ip_address =  htmlspecialchars($request->input('ip_address'));

            $existEmail = User::where('email',$email)->exists();

            if(!$existEmail){
                return (new Service())->apiResponse(404, [], 'Check if the email exist');
            }
            $service = new Service();
            $restrict = new Restricted();
            $restrict->email = $email;
            $restrict->ip_address = $ip_address;
            $restrict->uid =  $service->generateUid($restrict);
            $restrict->save();

            return (new Service())->apiResponse(200, [], 'Saved successfully');

        } catch (\Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

   /**
 * @OA\Post(
 *     path="/api/users/login",
 *     summary="Connexion de l'utilisateur",
 *     description="Authentifie l'utilisateur et renvoie un jeton d'accès en cas de succès.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"username", "password"},
 *                 @OA\Property(
 *                     property="username",
 *                     type="string",
 *                     description="Nom d'utilisateur (email ou numéro de téléphone)"
 *                 ),
 *                 @OA\Property(
 *                     property="password",
 *                     type="string",
 *                     description="Mot de passe de l'utilisateur"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Connexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="user",
 *                 type="object",
 *                 description="Détails de l'utilisateur",
 *                 ref=""
 *             ),
 *             @OA\Property(
 *                 property="access_token",
 *                 type="string",
 *                 description="Jeton d'accès JWT"
 *             ),
 *             @OA\Property(
 *                 property="token_type",
 *                 type="string",
 *                 description="Type de jeton (Bearer)"
 *             ),
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur de validation"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Message d'erreur"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */


    public function login(Request $request)
    {
        try
        {

            // return User::whereEmail($request->username)->first();

            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);

            $username = htmlspecialchars($request->input('username'));
            $password = htmlspecialchars($request->input('password'));


            $user = User::whereEmail($username)->whereDeleted(false)->whereEnabled(true)->first();

           $errorcheckIfUserIsRestricted = $this->checkIfUserIsRestricted($username);
           if($errorcheckIfUserIsRestricted){
                    return $errorcheckIfUserIsRestricted;
           }
        
            if(!$user){
                return (new Service())->apiResponse(404, [], 'Email is not valid or you are blocked');
            }
                $hashedPassword = $user->password;
    
                if (password_verify($password, $hashedPassword)) {
                    $authenticatedUser = DB::select("
                        SELECT * FROM users
                        WHERE (email = :username OR phone = :username)
                        AND password = :password
                        LIMIT 1
                    ", [
                        'username' => $username,
                        'password' => $hashedPassword
                    ]);

                    if (!empty($authenticatedUser)) {
                        $token = Auth::attempt(['email' => $username, 'password' => $password]);
                        $codes =  (new Service())->generateSixDigitNumber();
                       

                        $user->code = $codes;
                        $user->save();
                       

                        if (!$token) {
                            return response()->json(['message' => 'Unauthorized'], 200);
                        }

                        $user = Auth::user();
                        DB::update('UPDATE users SET connected = ? WHERE id = ?', [1, $user->id]);

                        $title=  'Help us protect your account';
                        $body =$codes;
                        $mail = new MailController();
                        $n=0;

                        // return $mail->sendLoginConfirmationNotification(Auth::user()->id, $title, $body, 'code sent successfully !');

                        if ( $mail->sendLoginConfirmationNotification(Auth::user()->id, $title, $body, 'code sent successfully !')) {
                            Log::info('Login confirmation email sent to user ID: ' . Auth::user()->id);
                            $n = $n + 1;
                        }


                        unset($user->code);

                        $data = [
                                'user' => $user,
                                'access_token' => $token,
                                // 'token_type' => 'Bearer',
                                // 'expires_in' => Auth::factory()->getTTL() * 60,
                                // 'n' => $n
                        ];
                        return (new Service())->apiResponse(200, $data, 'Logged sucessfully');
                    } else {
                        return (new Service())->apiResponse(404, [], 'Empty authentificate user');
                    }
                    } else {
                        return (new Service())->apiResponse(404, [], 'Invalid password !');
                }
        }
        catch (Exception $e)
        {
             return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    public function checkIfUserIsRestricted($username){
        try{
            $exist = Restricted::where('email', $username)->exists();

            if($exist){

                $a = DB::table('restricteds')
                ->where('email', $username)
                ->orderBy('created_at', 'desc')
                ->first();

            $formattedDateTime = Carbon::parse($a->created_at)->format('H:i:s');


                $currentDateTime = Carbon::now();
                $currentTime = $currentDateTime->toTimeString();
                $timeDifference = Carbon::parse($currentTime)->diff(Carbon::parse($formattedDateTime));
                $duration =  180;

                $hours = $timeDifference->h;
                $minutes = $timeDifference->i;
                $seconds = $timeDifference->s;
                $time = ($hours*60)+$minutes;
                $d = $duration-$time;
                $d=$d+1;
                $h = $d/60;
                if ($currentDateTime->format('Y-m-d') == Carbon::parse($a->created_at)->format('Y-m-d')) {
                    if($time <=$duration){
                    $sumDateTime = $currentDateTime->addMinutes($minutes)->addSeconds($seconds);
                    $formattedSumDateTime = $sumDateTime->format('Y m d H:i:s');

                    $unlockDateTime = Carbon::parse($formattedDateTime)->copy()->addMinutes($duration);
                    $formattedUnlockDateTime = $unlockDateTime->format('Y-m-d H:i:s');

                    // Convertir le résultat en heures, minutes et secondes
                    $totalSeconds = $unlockDateTime->diffInSeconds($formattedDateTime);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;

                    $data = [
                        "message" =>"You have to wait.",
                        "bloked" => "You're restricted",
                        "bloc_date" => $a->created_at,
                        "debloc_date" => $formattedUnlockDateTime, // Ajout de l'heure de déblocage
                        "duree" => [
                            "heures" => $hours,
                            "minutes" => $minutes,
                            "secondes" => $seconds
                        ]
                        // 'durée total' => $timeDifference,
                        // 'date actuelle' => $currentTime,
                        // 'time' =>$time
                        ];

                    return (new Service())->apiResponse(404, $data, 'detail');
                }
                }

                // else{
                //     return response()->json([
                //         'message' =>"Vous avez fait $hours h $minutes min $seconds s  vous pouvez vous  connecter.",
                //         'différence en heure' => $timeDifference->h,
                //         'heure d enregistrement'=>$formattedDateTime,
                //         'heure actuelle' => $currentTime
                //     ]);
                // }
            }
        } catch (Exception $e)
        {
             return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    /**
 * @OA\Post(
 *     path="/api/users/register",
 *     summary="Enregistrer un nouvel utilisateur",
 *     description="Crée un nouvel utilisateur avec l'email, le numéro de téléphone et le mot de passe fournis.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"phone", "email", "password", "password_confirmation"},
 *                 @OA\Property(
 *                     property="phone",
 *                     type="integer",
 *                     description="Numéro de téléphone de l'utilisateur (format international avec le préfixe +)"
 *                 ),
        *   @OA\Property(
        *                      property="country_id",
        *                      type="integer",
        *                      description="Numéro de téléphone de l'utilisateur (format international avec le préfixe +)"
        *                  ),
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     description="Adresse e-mail de l'utilisateur"
 *                 ),
 *                 @OA\Property(
 *                     property="password",
 *                     type="string",
 *                     format="password",
 *                     description="Mot de passe de l'utilisateur (au moins 8 caractères, une lettre majuscule, un chiffre et un caractère spécial)"
 *                 ),
 *                 @OA\Property(
 *                     property="password_confirmation",
 *                     type="string",
 *                     format="password",
 *                     description="Confirmation du mot de passe de l'utilisateur"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur créé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Message de succès"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur de validation"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */


    public function register(Request $request)
    {
        try
        {

            $validator = Validator::make($request->all(), [
                'phone' =>  ['required', 'regex:/^\+?[0-9]+$/',  'unique:users'],
                'email' => 'required|email|unique:users',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    // 'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]+$/',
                ],
                // 'password_confirmation' => 'required|string',
                'country_id' => 'required'
            ]);

            if ($validator->fails()) {
                return (new Service())->apiResponse(404,  $validator->errors(), 'The data provided is not valid.');
            }


           $ulid = Uuid::uuid1();
           $ulidUser = $ulid->toString();

          $db = DB::connection()->getPdo();
          $email =  htmlspecialchars($request->input('email'));
          $country_id = htmlspecialchars($request->input('country_id'));
        //   dd($request);
          $phone =  htmlspecialchars($request->input('phone'));
          $password = bcrypt($request->input('password'));
          $uid = $ulidUser;
          $last_ip_login = $request->ip();
          $created_at = date('Y-m-d H:i:s');
          $service = new Service();
          $updated_at = date('Y-m-d H:i:s');
          $userObject = new User();
          $code_user = $service->generateRandomAlphaNumeric(7,(new User()),'code_user');

          $testEmail = new TestEmailController();

          $test = $testEmail->verifyEmail($request->email);
     
          if($test == 'undeliverable'){
            return (new Service())->apiResponse(404, [], 'Please enter a functional email address');
          }

          $query = "INSERT INTO users (email, phone, password,uid,last_ip_login,created_at,updated_at,code_user) VALUES (?, ?, ?, ?, ?, ?, ?,?)";

          $statement = $db->prepare($query);

          $statement->bindParam(1, $email);
          $statement->bindParam(2, $phone);
          $statement->bindParam(3, $password);
          $statement->bindParam(4,  $uid);
          $statement->bindParam(5,  $last_ip_login);
          $statement->bindParam(6,  $created_at);
          $statement->bindParam(7,  $updated_at);
          $statement->bindParam(8,  $code_user);

          $statement->execute();

          $user = User::Where('email',$email)->update(['enabled' => true]);

          $createPerson = (new PersonController())->createPerson($country_id, $email, $phone,$request);
          if($createPerson){
            return (new Service())->apiResponse(200, [], $createPerson->original['message']);
          }

          $title = "Confirmation registration";
          $body = "Welcome to DB2B, we are happy to have you join us";

          $user = User::Where('email',$email)->first();
  

          dispatch(new SendEmail($user->id,$title,$body,2));

          return (new Service())->apiResponse(200,[],'User created successfully!');

        }
        catch (Exception $e)
        {
             return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Post(
 *     path="/api/users/logout",
 *     summary="Déconnexion de l'utilisateur",
 *     description="Déconnecte l'utilisateur authentifié.",
 *     tags={"Authentication"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Déconnexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Message de succès"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Message d'erreur"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */

    public function logout()
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                DB::update('UPDATE users SET connected = ? WHERE id = ?', [0, $user->id]);
                Auth::logout();
                return (new Service())->apiResponse(200, [], 'Successfully logged out');
            }
            // else {
            //     return response()->json([
            //         'error' => 'User is not authenticated',
            //     ], 401);
            // }
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
    
   /**
 * @OA\Get(
 *     path="/api/user",
 *     summary="Obtenir les informations de l'utilisateur authentifié",
 *     description="Renvoie les informations de l'utilisateur authentifié.",
 *     tags={"Authentication"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Informations de l'utilisateur récupérées avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 description="Détails de l'utilisateur authentifié"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Message d'erreur"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */

 public function userAuth()
 {
     try {
         $service = new Service();
         $checkAuth = $service->checkAuth();
 
         if ($checkAuth) {
             return $checkAuth;
         }

         $personId = (new Service())->returnPersonIdAuth();

         $person = Person::whereId($personId)->first();
 
         $client = Client::where('person_id', $personId)->first();
         $deliveryAgency = DeliveryAgency::where('person_id', $personId)->first();

         $roles = [];
 
         $user = Auth::user();
         unset($user->password, $user->code);
 
         
         $roles[] = 'user'; 
 
         if ($client && $client->is_merchant) {
             $roles[] = 'merchant';
         }
 
         if ($deliveryAgency) {
             $roles[] = 'delivery_agent';
         }
 
         $data = [
             'User_details' => $user,
             'notification' =>Notification::wherePerson((new Service())->returnPersonIdAuth())->where('isRead',false)->count(),
             'Person_detail' => $person->file,
             'client_detail' => $client,
             'delivery_agency_detail' => $deliveryAgency,
             'role' => $roles,
             'file' => $user->person->file!=null? $user->person->file->location:null
         ];

         return (new Service())->apiResponse(200, $data, 'Authentificated user detail');

     } catch (Exception $e) {
         return (new Service())->apiResponse(500, [], $e->getMessage());
     }
 }
 

/**
 * @OA\Get(
 *     path="/api/users/getUser",
 *     summary="Obtenir la liste des utilisateurs",
 *     description="Renvoie la liste de tous les utilisateurs enregistrés dans le système.",
 *     tags={"Users"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(),
 *                 description="Liste des utilisateurs avec leurs informations"
 *             ),
 *             @OA\Property(
 *                 property="total_users",
 *                 type="integer",
 *                 description="Nombre total d'utilisateurs dans le système"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */

 
        public function getUser()
        {
            try {

                $users = User::with('person')->where('deleted',0)->get();

                $data = [];


                foreach($users as $user){
                    $user->image = $user->person->file!=null? $user->person->file->location:null;
                    unset($user->password);
                    unset($user->code);
                    $data[] = $user;
                }

                $totalUsers = count($users);

                $data = [
                    'data' => $users,
                    'total_users' => $totalUsers
                ];

                return (new Service())->apiResponse(200, $data, 'List of users');

            } catch (Exception $e) {
                return (new Service())->apiResponse(500, [], $e->getMessage());
            }
        }


        /**
 * @OA\Post(
 *     path="/api/users/updatePassword",
 *     summary="Mettre à jour le mot de passe de l'utilisateur",
 *     description="Permet à l'utilisateur de mettre à jour son mot de passe.",
 *     tags={"Authentication"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"old_password", "new_password"},
 *                 @OA\Property(
 *                     property="old_password",
 *                     type="string",
 *                     format="password",
 *                     description="Ancien mot de passe de l'utilisateur"
 *                 ),
 *                 @OA\Property(
 *                     property="new_password",
 *                     type="string",
 *                     format="password",
 *                     description="Nouveau mot de passe de l'utilisateur (au moins 8 caractères)"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Mot de passe mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Message de succès"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Message d'erreur de validation"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 description="Détails de l'erreur serveur"
 *             )
 *         )
 *     )
 * )
 */


        public function updatePassword(Request $request)
        {
            $userId = Auth::id();
        
            // Récupération de l'utilisateur
            $query = "
                SELECT * FROM users
                WHERE id = :id
                LIMIT 1
            ";

            $db = DB::connection()->getPdo();
            $statement = $db->prepare($query);
            $statement->execute(['id' => $userId]);
            $user = $statement->fetch($db::FETCH_ASSOC);
        
            // // Validation des données
            // $validator = Validator::make($request->all(), [
            //     'old_password' => 'required',
            //     'new_password' => 'required|min:8',
            // ]);
        
            // if ($validator->fails()) {
            //     return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
            // }
        
            // Vérification de l'ancien mot de passe
            if (!password_verify($request->old_password, $user['password'])) {
                return (new Service())->apiResponse(404, [], 'Old password is incorrect');
            }
        
            // Mise à jour du mot de passe
            $newPasswordHash = password_hash($request->new_password, PASSWORD_DEFAULT);
            $query = "
                UPDATE users
                SET password = :password
                WHERE id = :id
            ";
            $statement = $db->prepare($query);
            $statement->execute([
                'password' => $newPasswordHash,
                'id' => $userId,
            ]);
            return (new Service())->apiResponse(200, [], 'Password updated successfully.');
        }
        

 /**
 * @OA\Post(
 *     path="/api/users/password_recovery_start_step",
 *     summary="Start the password recovery process",
 *     tags={"Password Recovery"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Email sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Email sent successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Email not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Email not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\AdditionalProperties(
 *                     type="array",
 *                     @OA\Items(type="string", example="The email field is required.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Internal server error")
 *         )
 *     )
 * )
 */
        public function password_recovery_start_step(Request $request){
            try{

                $request->validate([
                    'email' => 'required',
                ]);
                // return 1;
                $email =  htmlspecialchars($request->input('email'));

                $user = User::whereEmail($email)->whereDeleted(false)->whereEnabled(true)->first();

                if($user){

                    $otp_code = (new Service())->generateSixDigitNumber();

                    $errorcreateForgottenOtp = (new OtpPasswordForgottenController)->createForgottenOtp($otp_code,$user->id);

                    if( $errorcreateForgottenOtp){
                        return  $errorcreateForgottenOtp;
                    }

                    $uid = User::whereEmail($email)->first()->uid;

                    $data = [
                        'uid' => $uid
                    ];

                    return (new Service())->apiResponse(200,$data, "We would like to inform you that a message containing 6 digits has been sent to you by e-mail. Please enter the code to change your password.");
                }else{
                    return (new Service())->apiResponse(404,[], "Email not found or already disabled.");
                }
            } catch(Exception $e) {
                return (new Service())->apiResponse(500, [], $e->getMessage());
            }
        }

   /**
 * @OA\Post(
 *   path="/api/users/password_recovery_second_step",
 *   summary="Complete the password recovery process",
 *   description="Validate the OTP code and mark it as used if valid. Sends a notification email if the OTP code is expired.",
 *   tags={"Password Recovery"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"otp_code"},
 *       @OA\Property(property="otp_code", type="string", example="123456")
 *     )
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Successful response with OTP validation result",
 *     @OA\JsonContent(
 *       @OA\Property(property="status_code", type="integer", example=200),
 *       @OA\Property(property="message", type="string", example="otp valide")
 *     )
 *   ),
 *   @OA\Response(
 *     response=404,
 *     description="Code OTP not found or already used",
 *     @OA\JsonContent(
 *       @OA\Property(property="message", type="string", example="Code OTP non trouvé ou déjà utilisé")
 *     )
 *   ),
 *   @OA\Response(
 *     response=400,
 *     description="Validation Error",
 *     @OA\JsonContent(
 *       @OA\Property(property="message", type="string", example="The given data was invalid."),
 *       @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *           property="otp_code",
 *           type="array",
 *           @OA\Items(type="string", example="The otp_code field is required.")
 *         )
 *       )
 *     )
 *   )
 * )
 */


 public function password_recovery_second_step(Request $request){
    try {

            $request->validate([
                'otp_code' =>'required'
            ]);


            $existOtp = OtpPasswordForgotten::where('code_otp', $request->otp_code)
            ->where('deleted', false)
            ->first();

        if ($existOtp) {
            $currentDateTime = Carbon::now();
            $expiredAt = Carbon::parse($existOtp->expired_at);
            $differenceInMinutes = $currentDateTime->diffInMinutes($expiredAt);

            if (($differenceInMinutes * (-1)) > 30) {
                return (new Service())->apiResponse(404,[], 'code already expired');
            } else {
                $existOtp->deleted = true;
                $existOtp->save();
                return (new Service())->apiResponse(200,[], 'otp valide');
            }
        } else {
            return (new Service())->apiResponse(200,[], 'Code not found or already used');
        }

          } catch(Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/users/password_recovery_end_step/{uid}",
 *     summary="Complete the password recovery process",
 *     description="Update the user's password after successful recovery.",
 *     tags={"Password Recovery"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="User's uid ",
 *         required=true,
 *         @OA\Schema(type="string", format="uid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"password", "password_confirmation"},
 *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Password updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="password",
 *                     type="array",
 *                     @OA\Items(type="string", example="The password field is required.")
 *                 ),
 *                 @OA\Property(
 *                     property="password_confirmation",
 *                     type="array",
 *                     @OA\Items(type="string", example="The password confirmation field is required.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
 *         )
 *     )
 * )
 */


public function password_recovery_end_step(Request $request,$uid){
    try {

            $request->validate([
                'password' =>'required',
                'password_confirmation' =>'required'
            ]);
            if((new Service())->isValidUuid($uid)){
                return (new Service())->isValidUuid($uid);
            }
    

            if($request->password !== $request->password_confirmation){
                return (new Service())->apiResponse(404, [], 'password does not match');
            }

            // User::whereUid($uid)->update(['password' => bcrypt($request->password)]);
            $user = User::whereUid($uid)->first();
            $user->password = bcrypt($request->password);
            $user->save();

            return (new Service())->apiResponse(200, [], 'password updated successfully');

          } catch(Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


/**
 * @OA\Post(
 *     path="/api/users/disabledUser/{uid}",
 *     summary="Disable a user",
 *     description="Disable a user account by setting the 'enabled' field to false.",
 *     tags={"Password Recovery"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="query",
 *         description="User's unique identifiant",
 *         required=true,
 *         @OA\Schema(type="string", format="uid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User disabled successfully or user not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="User disabled successfully !")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="User not found !")
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
public function disabledUser($uid){
    try {

        if((new Service())->isValidUuid($uid)){
            return (new Service())->isValidUuid($uid);
        }

       $user = User::whereUid($uid)->first();

       if(!$user){
            return (new Service())->apiResponse(404, [], 'User not found  !');
       }

        if(!$user->enabled){
            return (new Service())->apiResponse(404, [], 'User  already disabled !');
       }

       $user->enabled = false;
       $user->save();

       return (new Service())->apiResponse(200, [], 'Too many attempt, you are disabled !');

    } catch(Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


        /**
 * @OA\Post(
 *     path="/api/users/verification_code",
 *     tags={"Authentication"},
 *  security={{"bearerAuth": {}}},
 *     summary="Vérification du code de vérification",
 *     description="Vérifie le code de vérification envoyé par l'utilisateur.",
 *     requestBody={
 *         "required": true,
 *         "content": {
 *             "application/json": {
 *                 "schema": {
 *                     "type": "object",
 *                     "properties": {
 *                         "code": {
 *                             "type": "string",
 *                             "description": "Le code de vérification à vérifier."
 *                         }
 *                     },
 *                     "required": "code"
 *                 }
 *             }
 *         }
 *     },
 *     @OA\Response(
 *         response="200",
 *         description="Échec de la vérification",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer",
 *                 example=200,
 *                 description="Le code d'état de la réponse."
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Check failed",
 *                 description="Le message indiquant que la vérification a échoué."
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="500",
 *         description="Erreur interne du serveur",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer",
 *                 example=500,
 *                 description="Le code d'état de la réponse."
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 description="Le message d'erreur détaillé."
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="default",
 *         description="Réponse par défaut pour les autres cas",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer",
 *                 example=200,
 *                 description="Le code d'état de la réponse."
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Verification passed",
 *                 description="Le message indiquant que la vérification a réussi."
 *             ),
 *             @OA\Property(
 *                 property="verification",
 *                 type="string",
 *                 description="Le code de vérification vérifié."
 *             )
 *         )
 *     )
 * )
 */
public function verification_code(Request $request)
{
    try {
        $service = new Service();
        $checkAuth=$service->checkAuth();

        if($checkAuth){
            return $checkAuth;
        }
        $verification = $request->code;
        $user = User::where('code', $verification)->exists();
        // return [$verification,User::where('code', $verification)->exists()];

        if (!$user) {
            return (new Service())->apiResponse(404, [], 'Code invalid');
        }
      
        if ($user) {
            $users =User::whereId(Auth::user()->id)->whereCode($verification)->first();
            $users->code = 0;
            $users->save();
            return (new Service())->apiResponse(200, [], 'Verification passed');
        }

    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


/**
 * @OA\Post(
 *     path="/api/users/new_code/{id}",
 *     summary="Generate a new code for user",
 *   security={{"bearerAuth": {}}},
 *     tags={"Authentication"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="User ID",
 *         @OA\Schema(
 *             type="integer"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Code sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Code sent successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="This id does not exist",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="This id does not exist")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
public function new_code($id) {
    try {

        $user = User::find($id);

        if(!$user){
            return (new Service())->apiResponse(404, [], 'Check if user exist');
        }

        // if(Auth::user()->id != $id){
        //     return response()->json([
        //         'message' => 'Check receiver identity',
        //     ],200);
        // }
            $codes = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            if($user->code !== null) {
                $user->code = $codes;
                $user->save();
            
            $title=  'Help us protect your account';
            $body =$user->code;
            $mail = new MailController();
            $mes =  $mail->sendLoginConfirmationNotification(Auth::user()->id,$title,$body, 'code sent successfully !');

            if($mes){
                return (new Service())->apiResponse(200, [], $mes->original['message']);
              }

        }
        return (new Service())->apiResponse(404, [], 'This id does not exist');

    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/users/regenerateToken",
 *     tags={"Authentication"},
 *     summary="Régénère un jeton d'accès pour un utilisateur connecté",
 *     description="Cette route permet de régénérer un jeton JWT pour un utilisateur existant en fonction de son UID.",
 *     operationId="regenerateToken",
 *     security={{"bearerAuth": {}}},
 *
 *
 *     @OA\Response(
 *         response=200,
 *         description="Jeton régénéré avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="data", type="string", description="Jeton d'accès régénéré"),
 *             @OA\Property(property="message", type="string", example="Token regénéré pour l'utilisateur")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="message", type="string", example="User not found")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Erreur interne du serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=500),
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
 *         )
 *     )
 * )
 */



public function regenerateToken(){
    try {

        $user =Auth::user();
 
        if(!$user){
            return (new Service())->apiResponse(404, [], "User not found");
        }

        $token = Auth::login($user);

        return (new Service())->apiResponse(200,$token, "Token regénéré pour {$user->person->first_name} {$user->person->last_name} ");

     } catch(Exception $e) {
         return (new Service())->apiResponse(500, [], $e->getMessage());
     }
}






// $newPassword = $this->generateAndUpdateUserPassword($user);

// $title= "Recovery your password";
// $body = "Dear user, we would like to inform you that you have forgotten your password. This new password has been generated so that you can log in and change the password that was generated.\n\nPassword : $newPassword.\n\nDon't forget to change your password once you've logged in.";


// $mail = new MailController();
// $mes =  $mail->sendNotification(User::where('email',$email)->first()->id,$title,$body, 'Email sent successfully !');

// if($mes){
// return response()->json([
//       'message' =>$mes->original['message']
// ]);
// }


// public function enabledUser($uid){
    // try {

    //    $user = User::whereUid($uid)->first();

    //    if(!$user){
    //         return response()->json([
    //             'status_code' => 200,
    //             'message' => "User not found or already disabled !"
    //         ],200);
    //    }

    //    $user->enabled = true;
    //    $user->save();

    //     return response()->json([
    //         'status_code' => 200,
    //         'message' => "enabled"
    //     ],200);

    // } catch(Exception $e) {
    //     return response()->json([
    //         'error' => $e->getMessage()
    //     ], 500);
    // }
// }

}