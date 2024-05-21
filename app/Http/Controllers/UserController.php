<?php

namespace App\Http\Controllers;

use App\Models\Restricted;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

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
 *     tags={"Authentification"},
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

            $db = DB::connection()->getPdo();

            $email =  htmlspecialchars($request->input('email'));
            $ip_address =  htmlspecialchars($request->input('ip_address'));

            $existEmail = User::where('email',$email)->exists();

            if(!$existEmail){
                return response()->json([
                    'message' =>'Check if the email exist'
                ]);
            }

            $currentDateTime = Carbon::now();
            // $ip_address = $request->ip();
            $created_at = $currentDateTime->format('Y-m-d H:i:s');
            $updated_at = $currentDateTime->format('Y-m-d H:i:s');

            $query = "INSERT INTO restricteds (email, ip_address, created_at,updated_at) VALUES (?, ?, ?,?)";
            $statement = $db->prepare($query);
            $statement->bindParam(1, $email);
            $statement->bindParam(2,  $ip_address);
            $statement->bindParam(3,  $created_at);
            $statement->bindParam(4,  $updated_at);
            $statement->execute();

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
 *     tags={"Authentification"},
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
                        'durée total' => $timeDifference,
                        'date actuelle' => $currentTime,
                        'time' =>$time
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
                        // dd('salut');
                        // if (!$token) {
                        //     $token = Auth::attempt(['phone' => $username, 'password' => $password]);
                        // }

                        if (!$token) {  
                            return response()->json(['message' => 'Unauthorized'], 401);
                        }

                        $user = Auth::user();
                        DB::update('UPDATE users SET connected = ? WHERE id = ?', [1, $user->id]);

                        return response()->json([
                            'user' => $user,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                        ]);
                    } else {
                        return response()->json(['error' => 'Mot de passe invalide.'], 401);
                    }
                } else {
                    return response()->json(['error' => 'Mot de passe invalide.'], 401);
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
 *     tags={"Authentification"},
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

           $request->validate([
               'phone' =>  ['required', 'regex:/^\+?[0-9]+$/',  'unique:users'],
               'email' => 'required|email|unique:users',
               'password' => [
                   'required',
                   'string',
                   'min:8',
                   'confirmed',
                   'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]+$/',
               ],
               'password_confirmation' => 'required|string',
               'country_id' => 'required'
           ]);

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

          $this->createPerson($country_id, $email, $phone);

         
    
            return response()->json('User created successfully');
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createPerson($country_id, $email, $phone){
        try{
            $user = User::where('email', $email)->first();
            $ulid = Uuid::uuid1();
            $ulidPeople = $ulid->toString();
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
            $uid = $ulidPeople;
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
              'uid' => $ulidPeople,
              'type' => $type,
              'country_id' => $country_id,
              'created_at' => $created_at,
              'updated_at' => $updated_at
          ]);
      
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
 *     tags={"Authentification"},
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
 *     tags={"Authentification"},
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
    
                return response()->json([
                    'data' =>Auth::user(),
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
 *     tags={"Utilisateurs"},
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
                    SELECT users.*, people.*
                    FROM users, people
                    WHERE users.id = people.user_id
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
 *     tags={"Utilisateurs"},
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
                    SELECT users.*, people.*
                    FROM users, people
                    WHERE users.id = people.user_id
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
 *     tags={"Utilisateurs"},
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
                SELECT users.*, people.*
                FROM users, people
                WHERE users.id = people.user_id
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
 *     tags={"Authentification"},
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

                    return response()->json([
                        'status_code' => 200,
                        'message' => "Email sent successfully"
                     ]);
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

}
