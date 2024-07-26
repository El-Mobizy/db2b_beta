<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function createPerson($country_id, $email, $phone, Request $request){
        try{
            $service = new Service();
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
            $profile_img_code= $service->generateRandomAlphaNumeric(7,(new Person()),'profile_img_code');
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

          (new ClientController)->createClient( $person['id'], $request);
      
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
}
