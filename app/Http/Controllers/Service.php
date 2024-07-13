<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Admin;
use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\Client;
use App\Models\CommissionWallet;
use App\Models\Country;
use App\Models\DeliveryAgency;
use App\Models\File;
use App\Models\Person;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as F ;
use Ramsey\Uuid\Uuid;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class Service extends Controller
{
    public function generateRandomAlphaNumeric($length,$class,$colonne) {
        $bytes = random_bytes(ceil($length * 3 / 4));
        $randomS =  substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);

        $exist = $class->where($colonne,$randomS)->first();
        while($exist){
            $randomS =  substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        }

        return $randomS;
    }

    public function generateUid($class){
        $ulid = Uuid::uuid1();
        $uid = $ulid->toString();
        $exist = $class->where('uid',$uid)->first();
            while($exist){
                $uid =  $ulid->toString();
            }
        return $uid;
    }

    public function checkFile(Request $request){
        try {
            if(!$request->hasFile('files')){
                return response()->json([
                    'message' =>'the field file is required'
                ],404);
            }
        } catch (\Exception $th) {

        }
    
    }

     public function validateLocation($id)
    {
        if(!Country::find($id)){
            return response()->json([
                'error' => 'location not found'
            ]);
        }

    }

    public function validateCategory($id)
    {
        if(!Category::find($id)){
            return response()->json([
                'error' => 'category not found'
            ]);
        }else{

        }
    }

    public function validateFile( $file){
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif','JPEG','JPG','PNG','GIF'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Veuillez télécharger une image (jpeg, jpg, png, gif)');
        }
        $errorcheckImageSize = $this->checkImageSize($file);
        if($errorcheckImageSize){
            return $errorcheckImageSize;
        }
    }

    public function uploadFiles(Request $request, $randomString,$location){
        foreach($request->file('files') as $photo){
            $errorUploadFiles = $this->validateFile($photo);
            $this->storeFile($photo, $randomString, $location);

            if($errorUploadFiles){
                return $errorUploadFiles;
            }
        }
    }



    private function storeFile( $photo, $randomString, $location){
        try {

            $db = DB::connection()->getPdo();

            $size = filesize($photo);
            $ulid = Uuid::uuid1();
            $ulidPhoto = $ulid->toString();
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
            $photoPath = $photo->move(public_path("image/$location"), $photoName);
            $photoUrl = url("/image/$location/" . $photoName);
            $type = $photo->getClientOriginalExtension();
            $locationFile = $photoUrl;
            $referencecode = $randomString;
            $filename = md5(uniqid()) . '.' . $type;
            $uid = $ulidPhoto;
            $q = "INSERT INTO files (filename, type, location, size, referencecode, uid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $db->prepare($q);
            $stmt->bindParam(1, $filename);
            $stmt->bindParam(2, $type);
            $stmt->bindParam(3, $locationFile);
            $stmt->bindParam(4,  $size);
            $stmt->bindParam(5,  $referencecode);
            $stmt->bindParam(6,  $uid);
            $stmt->bindParam(7,  $created_at);
            $stmt->bindParam(8,  $updated_at);
            $stmt->execute();

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

    public function checkImageSize ($photo){
        if(filesize($photo) >= 2097152){
            return response()->json([
                'message' =>'The image size exceeds what is normally required (< 2mo)',
                'size' =>filesize($photo)
            ]);
        }
    }

    public function validateDoc( $file){
        $allowedExtensions = ['pdf', 'doc', 'docx', 'pptx','PDF', 'DOC', 'DOX', 'PPTX'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Veuillez télécharger un document valide (pdf, doc, pptx, dox)');
        }
    }

    public function validateZip( $file){

        $allowedExtensions = [
            'zip',  'zipx',  '7z','rar','tar','tar.gz'   
        ];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Veuillez télécharger un bon fichier zippé (zip, zipx, rar, tar)');
        }
    }

    public function uploadDocs(Request $request, $randomString,$location){
        foreach($request->file('files') as $photo){
            $this->validateDoc($photo);
            $this->storeFile($photo, $randomString, $location);
        }
    }

    public function uploadZipFile(Request $request, $randomString,$location){
        foreach($request->file('files') as $photo){
            $this->validateZip($photo);
            $this->storeFile($photo, $randomString, $location);
        }
    }


     /**
 * @OA\Post(
 *     path="/api/ad/uploadAdImage/{ad_uid}",
 *     summary="Upload an image for an ad",
 *     tags={"Ad"},
 *     @OA\Parameter(
 *         name="ad_uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="Unique identifier for the ad"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="files[]",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary")
 *                 ),
 *                 required={"files"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Image added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Image added successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ad not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ad not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Validation error message")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal server error message")
 *         )
 *     )
 * )
 */
    public function uploadAdImage(Request $request,$ad_uid){
        try {
            $request->validate([
                'files' => 'required'
            ]);
            
            if(!Ad::whereUid($ad_uid)->first()){
                
                return response()->json([
                    'message' => 'Ad not found'
                ]);
            }
            
            $ad = Ad::whereUid($ad_uid)->first();

            $this->uploadFiles($request,$ad->file_code,'ad');

            return response()->json([
                'message' =>'image added to ad successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Delete(
 *     path="/api/file/removeFile/{uid}",
 *     summary="Remove a file",
 *     tags={"File"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="Unique identifier for the file"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File removed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="File removed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="File not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="File not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal server error message")
 *         )
 *     )
 * )
 */
    public function removeFile($uid){
        try {

            if(!File::whereUid($uid)->first()){
                return response()->json([
                    'message' =>'File not found'
                ]);
            }

            $file = File::whereUid($uid)->first();

            $oldProfilePhotoUrl = $file->location;
            if ($oldProfilePhotoUrl) {
                $parsedUrl = parse_url($oldProfilePhotoUrl);
                $oldProfilePhotoPath = public_path($parsedUrl['path']);
                if (F::exists($oldProfilePhotoPath)) {
                    F::delete($oldProfilePhotoPath);
                }
            }

            if($file->deleted == true){
                return response()->json([
                    'message' => 'This file is already removed'
                ]);
            }

            File::whereUid($uid)->update([
                'deleted' => true,
                'updated_at' => now()
            ]);

            return response()->json([
                'message' => 'remove successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkAdAttribute(Request $request,$category_id){
        $category = Category::find($category_id);
        $attributeGroups = AttributeGroup::where('group_title_id',$category->attribute_group_id)->get();
        $a = $request->input('value_entered');

        if (is_array($a) && count($a) === 1) {
            $values = explode(",", $a[0]);
            $c = count($values);

            if ($attributeGroups->count() != $c) {

                return response()->json([
                    'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".$c
                ]);
            }

        } else{

            if ($attributeGroups->count() != count( $request->input('value_entered'))) {

                return response()->json([
                    'message' => " le nombre de valeur entree doit être égale au nombre d attribut {$attributeGroups->count()} ".count( $request->input('value_entered'))
                ]);
        }
        }
    }

   public function returnClientIdAuth(){
    try {
        if (!Auth::user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ]);
        }

        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        $client = Client::where('person_id',$person->id)->first();

        return $client->id;
    } catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
   }

   public function returnClientIdUser($userId){
    try {
        
        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => $userId]);

        $client = Client::where('person_id',$person->id)->first();

        return $client->id;
    } catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
   }

   public function returnPersonIdAuth(){
    try {
        if (!Auth::user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ]);
        }

        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        return $person->id;
    } catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
   }

   public function returnPersonUidAuth(){
    try {
        if (!Auth::user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ]);
        }

        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => Auth::user()->id]);

        return $person->uid;
    } catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
   }

   public function returnUserPersonId($userId){
    try {


        $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => $userId]);

        return $person->id;
    } catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
   }

   public function checkAuth(){
    if (!Auth::user()) {
        return response()->json([
            'message' => 'UNAUTHENTIFICATED'
        ],200);
    }
   }

   public function returnPersonAndUserId($clientId) {
    try {
        // Récupérer le client en fonction de l'ID client
        $client = Client::find($clientId);

        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        // Requête SQL pour récupérer la personne
        $personQuery = "SELECT * FROM person WHERE id = :personId";
        $person = DB::selectOne($personQuery, ['personId' => $client->person_id]);

        if (!$person) {
            return response()->json([
                'message' => 'Person not found'
            ], 404);
        }

        $data = [
            'person_id' => $person->id,
            'user_id' => $person->user_id
        ];

        return $data;
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function returnSTDPersonWalletBalance($personId){
    $wallet = CommissionWallet::where('person_id',$personId)->first();
    return $wallet->balance;
}

public function checkIfDeliveryAgent(){
    
    $personId =$this->returnPersonIdAuth();
    $personUid = Person::whereId($personId)->first()->uid;
    $exist = DeliveryAgency::where('person_id',$personId)->exists();
    if($exist){
        return $personId;
    }else{
        return 0;
    }
}

public function adminUserAccount(){
    try{
        $users = User::whereDeleted(0)->get();
        $admins = [];
        
        foreach($users as $user){
            $personId = $this->returnUserPersonId($user->id);
            if(DB::table('admin')->where('person_id', $personId)->exists()){
                $admins[] = $user;
            }
        }
        return $admins;
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function notifyAdmin($title,$body){
    try{
       $admins = $this->adminUserAccount();
       $message = new ChatMessageController();

       foreach($admins as $user){
            $message->sendNotification($user->id,$title,$body, '');
       }

    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

// EscrowDelivery(id, person_id, order_id, delivery_agent_amount, order_amount, status, pickup_date, delivery_date, created_at, updated_at)

}
