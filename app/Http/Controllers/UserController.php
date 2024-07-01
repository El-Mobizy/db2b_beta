<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
     *     tags={"Validation"},
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

            // $user = DB::select("
            //     SELECT * FROM users
            //     WHERE email = :username OR phone = :username
            //     LIMIT 1
            // ", [
            //     'username' => $email
            // ]);
            $db = DB::connection()->getPdo();
            $query = "
                            SELECT * FROM users
                            WHERE email = :email
                            LIMIT 1
                        ";
            $statement = $db->prepare($query);
            $statement->execute([
                'email' => $email,
            ]);
            $user = $statement->fetch($db::FETCH_ASSOC);
            if (empty($user)) {
                return response()->json([
                    'message' => 'Email invalid',
                ]);
            }

            return response()->json([
                'message' => 'Email valid',
                'data' => $user
            ]);
        }
        catch (Exception $e)
        {
            return response()->json($e->getMessage());
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
                return response()->json([
                    'message' =>'Check if the email exist'
                ]);
            }
            $service = new Service();
            $restrict = new Restricted();
            $restrict->email = $email;
            $restrict->ip_address = $ip_address;
            $restrict->uid =  $service->generateUid($restrict);
            $restrict->save();

            return response()->json(['message' => 'stocké avec succès'],200);

        } catch (\Exception $e) {
           return response()->json($e->getMessage(),500);
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
            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);

            $username = htmlspecialchars($request->input('username'));
            $password = htmlspecialchars($request->input('password'));

            $pdo = DB::connection()->getPdo();

            $query = "
                SELECT * FROM users
                WHERE email = :email OR phone = :phone
                LIMIT 1
            ";
        
            $statement = $pdo->prepare($query);
        
            $statement->execute([
                'email' => $username,
                'phone' => $username
            ]);
        
            $user = $statement->fetch( $pdo::FETCH_ASSOC);
            // dd($user);

            $exist = Restricted::where('email', $username)->exists();

            if($exist){

               $a = DB::table('restricteds')
                   ->where('email', $username)
                   ->orderBy('created_at', 'desc')
                   ->first();
            $formattedDateTime = Carbon::parse($a->created_at)->format('H:i:s');


                $currentDateTime = Carbon::now('Africa/Porto-Novo');
                $currentTime = $currentDateTime->toTimeString();
                $timeDifference = Carbon::parse($currentTime)->diff(Carbon::parse($formattedDateTime));
                $duration =  180;

                $hours = $timeDifference->h;
                $minutes = $timeDifference->i;
                $seconds = $timeDifference->s;
                $time = ($hours*60)+$minutes;
                $d = $duration-$time;
                $d=$d+1;
                if ($currentDateTime->format('Y-m-d') == Carbon::parse($a->created_at)->format('Y-m-d')) {
                    if($time <=$duration){
                    $sumDateTime = $currentDateTime->addMinutes($minutes)->addSeconds($seconds);
                    $formattedSumDateTime = $sumDateTime->format('Y m d H:i:s');
                    return response()->json([
                        "message" =>"Vous devez attendre $d minutes et quelques secondes  avant de vous connecter.",
                        "bloked" => "Vous êtes bloqué"
                        // 'durée total' => $timeDifference,
                        // 'date actuelle' => $currentTime,
                        // 'time' =>$time
                    ]);
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

            $user = User::where('email',$username)->first();
            if(!$user){
                return response()->json([
                    'message' => 'Email n est pas valide'
                ]);
            }
            // dd($user->password);
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
                        $codes = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                       
                        if(User::where('code',$codes)->exists()){
                            $codes = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        }
                        $user->code = $codes;
                        $user->save();
                        // dd('salut');
                        // if (!$token) {
                        //     $token = Auth::attempt(['phone' => $username, 'password' => $password]);
                        // }

                        if (!$token) {
                            return response()->json(['message' => 'Unauthorized'], 200);
                        }

                        $user = Auth::user();
                        DB::update('UPDATE users SET connected = ? WHERE id = ?', [1, $user->id]);

                        $title=  'Help us protect your account';
                        $body =$codes;
                        $message = new ChatMessageController();
                        $n=0;
                        // if( $mes =  $message->sendLoginConfirmationNotification(Auth::user()->id,$title,$body, 'code sent successfully !')){
                        //     $n = $n+1;
                        // }

                        if ($mes = $message->sendLoginConfirmationNotification(Auth::user()->id, $title, $body, 'code sent successfully !')) {
                            Log::info('Login confirmation email sent to user ID: ' . Auth::user()->id);
                            $n = $n + 1;
                        }
                        
                       
            
                        // $mes = $message->sendNotification(Auth::user()->id,$title,$body, 'code sent successfully !');
            
                        // if($mes){
                        //     return response()->json([
                        //           'message' =>$mes->original['message']
                        //     ]);
                        //   }

                        unset($user->code);
                        return response()->json([
                            'user' => $user,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'expires_in' => Auth::factory()->getTTL() * 60,
                            'n' => $n
                        ]);
                    } else {
                        return response()->json(['error' => 'Mot de passe invalide.'], 200);
                    }
                    } else {
                    return response()->json(['error' => 'Mot de passe invalide.'], 200);
                }
           
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()]);
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
 *                     type="string",
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
                return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
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
          $updated_at = date('Y-m-d H:i:s');

          $query = "INSERT INTO users (email, phone, password,uid,last_ip_login,created_at,updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)";

          $statement = $db->prepare($query);

          $statement->bindParam(1, $email);
          $statement->bindParam(2, $phone);
          $statement->bindParam(3, $password);
          $statement->bindParam(4,  $uid);
          $statement->bindParam(5,  $last_ip_login);
          $statement->bindParam(6,  $created_at);
          $statement->bindParam(7,  $updated_at);

          $statement->execute();

          $createPerson = $this->createPerson($country_id, $email, $phone,$request);
          if($createPerson){
            return response()->json([
                'message' =>$createPerson->original['message']
              ]);
           
          }

          $title = "Confirmation registration";
          $body = "Welcome to DB2B, we are happy to have you join us";

          $user = User::Where('email',$email)->first();
  
          $message = new ChatMessageController();
          $mes = $message->sendNotification($user->id,$title,$body, 'User created successfully!');
          if($mes){
              return response()->json([
                'message' =>$mes->original['message']
              ]);
            }

         
    
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createPerson($country_id, $email, $phone, Request $request){
        try{
            $db = DB::connection()->getPdo();
            $user = User::where('email', $email)->first();
            $ulid = Uuid::uuid1();
            $ulidPerson = $ulid->toString();
            $first_name =  'XXXXX';
            $last_name =  'XXXXX';
            $user_id = $user->id;
            $connected = 0;
            $sex = 1;
            $dateofbirth =  date('Y-m-d');
            $profile_img_code=  'XXX';
            $first_login =  1;
            $phonenumber =  $phone;
            $deleted = 0;
            $type =  'client';
            $country_id = $country_id;
            $uid = $ulidPerson;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
      
            DB::table('person')->insert([
              'first_name' => $first_name,
              'last_name' => $last_name,
              'user_id' => $user_id,
              'connected' => $connected,
              'sex' => $sex,
              'dateofbirth' => $dateofbirth,
              'profile_img_code' => $profile_img_code,
              'first_login' => $first_login,
              'phonenumber' => $phonenumber,
              'deleted' => $deleted,
              'uid' => $uid,
              'type' => $type,
              'country_id' => $country_id,
              'created_at' => $created_at,
              'updated_at' => $updated_at
          ]);

          $query = "SELECT id FROM person WHERE user_id = :user_id LIMIT 1";

          $stmt = $db->prepare($query);
          $stmt->bindParam(':user_id', $user_id, $db::PARAM_INT);
          $stmt->execute();

      
          $person = $stmt->fetch($db::FETCH_ASSOC);

          $this->createClient( $person['id'], $request);
      
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function createClient($personId, Request $request){
        try{
            $service = new Service();
            $client = new Client();
            $client->uid = $service->generateUid($client);
            $client->person_id = $personId;
        
            $client->save();
           
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
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
                return response()->json([
                    'message' => 'Successfully logged out',
                ]);
            } else {
                return response()->json([
                    'error' => 'User is not authenticated',
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
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

        public function userAuth(){
            try{
                $personQuery = "SELECT * FROM person WHERE user_id = :userId";
                $person = DB::selectOne($personQuery, ['userId' =>Auth::user()->id]);
                $client = Client::where('person_id', $person->id)->first();
                $data = [
                    'User details' =>Auth::user(),
                    'Person detail' => $person,
                    'client detail' => $client
                ];
                unset(Auth::user()->code);
                return response()->json([
                    'data' =>$data
                ]);
    
            } catch (Exception $e) {
                return response()->json($e->getMessage());
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
                $users = DB::select("
                    SELECT users.*, person.*
                    FROM users, person
                    WHERE users.id = person.user_id
                    AND users.deleted = FALSE
                ");

                $totalUsers = count($users);

                return response()->json([
                    'data' => $users,
                    'total_users' => $totalUsers
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
            }
        }


        /**
 * @OA\Get(
 *     path="/api/users/getUserLogin",
 *     summary="Obtenir la liste des utilisateurs connectés",
 *     description="Renvoie la liste de tous les utilisateurs connectés au système.",
 *     tags={"Users"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs connectés récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(),
 *                 description="Liste des utilisateurs connectés avec leurs informations"
 *             ),
 *             @OA\Property(
 *                 property="total_users",
 *                 type="integer",
 *                 description="Nombre total d'utilisateurs connectés dans le système"
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
        public function getUserLogin(){
            try {
                $users = DB::select("
                    SELECT users.*, person.*
                    FROM users, person
                    WHERE users.id = person.user_id
                    AND  users.deleted = FALSE AND users.connected = TRUE
                ");

                $totalUsers = count($users);

                return response()->json([
                    'data' => $users,
                    'total_users' => $totalUsers
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /**
 * @OA\Get(
 *     path="/api/users/getUserLogout",
 *     summary="Obtenir la liste des utilisateurs déconnectés",
 *     description="Renvoie la liste de tous les utilisateurs déconnectés du système.",
 *     tags={"Users"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs déconnectés récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(),
 *                 description="Liste des utilisateurs déconnectés avec leurs informations"
 *             ),
 *             @OA\Property(
 *                 property="total_users",
 *                 type="integer",
 *                 description="Nombre total d'utilisateurs déconnectés dans le système"
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


        public function getUserLogout(){
            try {
                $users = DB::select("
                SELECT users.*, person.*
                FROM users, person
                WHERE users.id = person.user_id
                AND  users.deleted = FALSE AND users.connected = FALSE
                ");

                $totalUsers = count($users);

                return response()->json([
                    'data' => $users,
                    'total_users' => $totalUsers
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ]);
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
                return response()->json(['message' => 'Old password is incorrect.'], 422);
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
        
            return response()->json(['message' => 'Password updated successfully.'], 200);
        }
        

 /**
 * @OA\Post(
 *     path="/api/users/password_recovery_start_step",
 *     summary="Start the password recovery process",
 *     tags={"Authentication"},
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
                $email =  htmlspecialchars($request->input('email'));
                $db = DB::connection()->getPdo();
                $query = "SELECT * FROM users WHERE email= :email LIMIT 1";
                $statement = $db->prepare($query);
                $statement->execute([
                    'email' => $email,
                ]);
                $user =  $statement->fetch($db::FETCH_ASSOC);
                // dd($user);
                if($user){

                    $title= "Recovery your password";
                    $body ="Go to quotidishop.com to update your password";

                   $message = new ChatMessageController();
                  $mes =  $message->sendNotification(User::where('email',$email)->first()->id,$title,$body, 'Email sent successfully !');

                  if($mes){
                    return response()->json([
                          'message' =>$mes->original['message']
                    ]);
                  }
                }else{
                    return response()->json([
                        'status_code' => 404,
                        'message' => "Email not found"
                     ]);
                }

            } catch(Exception $e) {
                return response()->json($e->getMessage());
            }
        }

        /**
 * @OA\Post(
 *     path="/api/users/password_recovery_end_step",
 *     summary="Complete the password recovery process by setting a new password",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "new_password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="new_password", type="string", format="password", example="newSecurePassword123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password changed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Password changed successfully")
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

        public function password_recovery_end_step(Request $request){
            try {
        
                $request->validate([
                    'email' => 'required',
                    'new_password' => 'required'
                ]);
                $email =  htmlspecialchars($request->input('email'));
        
                $db = DB::connection()->getPdo();
                $query = "SELECT * FROM users WHERE email= :email LIMIT 1";
                $statement = $db->prepare($query);
                $statement->execute([
                    'email' => $email,
                ]);

                $user =  $statement->fetch($db::FETCH_ASSOC);

                if($user){
                    // $user->update(['password' => Hash::make($request->new_password)]);
                    $q = " UPDATE users SET password= :password WHERE email= :email ";
                    $stmt = $db->prepare($q);
                    $stmt->execute([
                        'password' => Hash::make($request->new_password),
                        'email' => $email,
                    ]);

                    return response()->json([
                        'status_code' => 200,
                        'message' => 'Password changed successfully'
                    ]);
                }else {
                    return response()->json([
                        'status_code' => 200,
                        'message' => 'Email not found'
                        ]);
                }
        
                  } catch(Exception $e) {
                    return response()->json($e->getMessage());
                    }
        }


        /**
 * @OA\Post(
 *     path="/api/users/verification_code",
 *     tags={"Authentication"},
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
        // $service = new Service();
        // $checkAuth=$service->checkAuth();

        // if($checkAuth){
        //     return $checkAuth;
        // }
        $verification = $request->code;
        $user = User::where('code', $verification)->exists();
        // return [$verification,User::where('code', $verification)->exists()];

        if (!$user) {
            return response()->json([
                'status_code' => 200,
                'message' => 'Code invalid',
            ]);
        }
      
        if ($user) {
            // $users =User::whereId(Auth::user()->id)->whereCode($verification)->first();
            $users =User::whereId(Auth::user()->id)->whereCode($verification)->first();
            $users->code = 0;
            $users->save();
            return response()->json([
                'status_code' => 200,
                'message' => 'Verification passed',
            ]);
        }

       

     

    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => $e->getMessage(),
        ]);
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
            return response()->json([
                'message' => 'Check if user exist',
            ],200);
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
            $message = new ChatMessageController();
            $mes =  $message->sendLoginConfirmationNotification(Auth::user()->id,$title,$body, 'code sent successfully !');
            // dd('allo');

          
            if($mes){
                return response()->json([
                      'message' =>$mes->original['message']
                ]);
              }

        } 
        return response()->json([
            'status_code' => 404,
            'message' => 'This id does not exist'
        ]);

    } catch (Exception $e) {
        return response()->json(['message' =>$e->getMessage()], 500);
    }
}

}