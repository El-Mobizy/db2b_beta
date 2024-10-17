<?php

namespace App\Http\Controllers;

use App\Models\File;
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


    /**
 * @OA\Post(
 *     path="/api/person/updatePersonInformation",
 *     summary="Update person information",
 * security={{"bearerAuth": {}}},
 *     description="Updates the information of a person identified by the provided UID.",
 *     tags={"Persons"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="first_name", type="string", example="John"),
 *                 @OA\Property(property="last_name", type="string", example="Doe"),
 *                 @OA\Property(property="sex", type="boolean", example="1", description ="make 1 if you are a male"),
 *                 @OA\Property(property="dateofbirth", type="string", format="date", example="1990-01-01"),
 *                 @OA\Property(property="country_id", type="integer", example=1),
 *                 @OA\Property(property="phonenumber", type="string", example="1234567890")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Information updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Information updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Person not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Person not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=500),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="An error occurred")
 *         )
 *     ),
 *     @OA\Tag(name="Persons")
 * )
 */
    public function updatePersonInformation(Request $request){
        try {

          $data = [];

          $personUid = (new Service())->returnPersonUidAuth();
          if((new Service())->isValidUuid($personUid)){
            return (new Service())->isValidUuid($personUid);
        }

          $person =  Person::whereUid($personUid)->first();

          if(!$person){
              return(new Service())->apiResponse(404,$data, 'Person not found');
          }

          $person->first_name = $request->first_name ?? $person->first_name;
          $person->last_name = $request->last_name ?? $person->last_name;
          $person->sex = $request->sex ?? $person->sex;
          $person->dateofbirth = $request->dateofbirth ?? $person->dateofbirth;
          $person->country_id = $request->country_id ?? $person->country_id;
          $person->phonenumber = $request->phonenumber ?? $person->phonenumber;
          $person->save();

            return(new Service())->apiResponse(200,$data, 'Information updated successfully');

        } catch (Exception $e) {
            // $error = 'An error occured';
            $error = $e->getMessage();
            return(new Service())->apiResponse(500,$data,$error);
        }
    }


    /**
 * @OA\Post(
 *       path="/api/person/AddOrUpdateProfileImg",
 *     summary="Add or update profile image",
 *     description="Uploads a new profile image for the authenticated person, replacing any existing image.",
 *     tags={"Persons"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="files",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary"),
 *                     description="The file(s) to upload"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Picture added successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Picture added successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Person not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Person not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=500),
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="An error occurred")
 *         )
 *     ),
 *     @OA\Tag(name="Profile")
 * )
 */
    public function AddOrUpdateProfileImg(Request $request){
        try {

            $data = [];


            $personUid = (new Service())->returnPersonUidAuth();

            $person =  Person::whereUid($personUid)->first();

            if(!$person){
                return(new Service())->apiResponse(404,$data, 'Person not found');
                
            }

            if(File::where('referencecode',$person->profile_img_code)->exists()){
                File::where('referencecode',$person->profile_img_code)->delete();
            }

             (new Service())->uploadFiles($request,$person->profile_img_code,'profile_img');

              return(new Service())->apiResponse(200,$data, 'Picture add successfully');

          } catch (Exception $e) {
              $error = $e->getMessage();
              return(new Service())->apiResponse(500,[],$error);
          }
      }
    }

