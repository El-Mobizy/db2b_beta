<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\PreorderAnswers;
use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailwithoutfile;
use App\Mail\NotificationEmail;
use App\Models\Ad;
use App\Models\Client;
use App\Models\Review;
use Ramsey\Uuid\Type\Integer;
use Ramsey\Uuid\Uuid;

class PreorderController extends Controller
{

/**
 * @OA\Post(
 *    path="/api/preorder/createPreorder",
 *     summary="Create a new preorder",
 *     tags={"Preorder"},
 *   security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"title", "description", "location_id", "category_id"},
 *                 @OA\Property(property="title", type="string"),
 *                 @OA\Property(property="description", type="string"),
 *                 @OA\Property(property="minimumbudget", type="string", pattern="^\d{1,2}\.\d{6,}$"),
 *                 @OA\Property(property="maximumbudget", type="string", pattern="^\d{1,2}\.\d{6,}$"),
 *                 @OA\Property(property="address", type="string"),
 *                 @OA\Property(property="location_id", type="integer"),
 *                 @OA\Property(property="category_id", type="integer"),
 *                 @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary"))
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Success", @OA\JsonContent(
 *         @OA\Property(property="message", type="string")
 *     )),
 *     @OA\Response(response="500", description="Internal Server Error", @OA\JsonContent(
 *         @OA\Property(property="error", type="string")
 *     ))
 * )
 */
    public function createPreorder(Request $request){
        try {
            $request->validate([
                'title' => 'required',
                'description' => 'required',
                'minimumbudget' => '',
                'maximumbudget' => '',
                'address' => 'string',
                'location_id' =>'required',
                'category_id' => 'required',
                'files'
            ]);

            $service = new Service();

            $exist = Preorder::where('user_id',Auth::user()->id)->whereTitle($request->title)->exists();

            if($exist){
                return response()->json([
                    'message' => 'Preorder already exists'
                ],200);
            }

            $validateLocation=$service->validateLocation($request->location_id);
            if($validateLocation){
                return $validateLocation;
            }

            $validateCategory = $service->validateCategory($request->category_id);
            if($validateCategory){
                return $validateCategory;
            }

            if($request->input('maximumbudget') < $request->input('minimumbudget')){
                return response()->json([
                    'message' => 'Make sure the minimum budget does not exceed the maximum budget'
                ],200);
            }

            $preorder = new Preorder();
            $preorder->title = $request->input('title');
            $preorder->description = $request->input('description');
            $preorder->maximumbudget = $request->input('maximumbudget')??0.00;
            $preorder->minimumbudget = $request->input('minimumbudget')??0.00;
            $preorder->address = $request->input('address')??'XXXX';
            $preorder->location_id = $request->input('location_id');
            $preorder->category_id = $request->input('category_id');
            $preorder->user_id = Auth::user()->id;

            $preorder->uid = $service->generateUid($preorder);

            $randomString = $service->generateRandomAlphaNumeric(7,$preorder,'filecode');
            $preorder->filecode = $randomString;
            $preorder->statut =  TypeOfType::whereLibelle('pending')->first()->id;

            if ($request->hasFile('files')) {
                $service->uploadFiles($request,$randomString,"preorder");
            }

            $preorder->save();


                $title= "Confirmation of your pre-order shipment";
                $body ="Your pre-order has been registered, please wait while an administrator analyzes it. We'll let you know what happens next. Thank you!";


               $message = new ChatMessageController();
              $mes =  $message->sendNotification(Auth::user()->id,$title,$body, 'preorder created successfully !');

              if($mes){
                return response()->json([
                      'message' =>$mes->original['message']
                ]);
              }

               //todo: Envoyer un mail aux administrateurs pour leur signaler qu'un client vient de faire une précommande
               //todo: Envoyer un mail aux vendeurs qui commercialisent des produit de même catégorie
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

 /**
 * @OA\Post(
 *     path="/api/preorder_answer/createPreorderAnswer/{preorderId}",
 *     summary="Create a new preorder answer",
 *   tags={"PreorderAnswers"},
 *   security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="preorderId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="content", type="string"),
 *                 @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary")),
 *                 @OA\Property(property="price", type="string", pattern="^\d{1,2}\.\d{6,}$"),
 *                  @OA\Property(property="delivery_time", type="integer"),
 *                 @OA\Property(property="parent_id", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Preorder answer created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Preorder not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function createPreorderAnswer(Request $request,$preorderId){
        try {

            $request->validate([
                'content' => 'required',
                'files',
                'price',
                'delivery_time'
            ]);

            if(!Preorder::find($preorderId)){
                return response()->json([
                    'message' => 'PreOrder not found'
                ],404);
            }

            if(Preorder::find($preorderId)->statut !=TypeOfType::whereLibelle('validated')->first()->id ){
                return response()->json([
                    'message' => 'Statut of preorder must be validated. Please, check it !'
                ],403);
            }

          $a = new AdController();
          $checkIfmerchant = $a->checkMerchant();

          if($checkIfmerchant ==0){
              return response()->json([
                  'message' => 'You are not merchant'
                  ],200);
                  }


        $service = new Service();
        $preorder_answer = new PreorderAnswers();
        $preorder_answer->content = $request->input('content');
        $preorder_answer->preorder_id =  Preorder::find($preorderId)->id;
        $preorder_answer->uid = $service->generateUid($preorder_answer);
        $preorder_answer->price = $request->input('price')??0.00;
        $randomString = $service->generateRandomAlphaNumeric(7,$preorder_answer,'filecode');
        $preorder_answer->delivery_time = $request->input('delivery_time')??0;
        $preorder_answer->filecode = $randomString;
        $preorder_answer->statut =  TypeOfType::whereLibelle('pending')->first()->id;
        $preorder_answer->user_id = Auth::user()->id;

        if($request->has('parent_id')){
            $preorder_answer->parent_id = $request->input('parent_id');
        }

        if ($request->hasFile('files')) {
            $service->uploadFiles($request,$randomString,"preorder_answers");
        }

        // return($preorder_answer->preorder->user->email);
        $preorder_answer ->save();

        $title = "Confirmation that your pre-order reply has been sent";
        $body = "Your answer has been saved, please wait while an administrator analyzes it. We'll let you know what happens next. Thank you!";

        $message = new ChatMessageController();
        $mes = $message->sendNotification(Auth::user()->id,$title,$body, 'preorder answers created successfully !');
        if($mes){
            return response()->json([
                  'message' =>$mes->original['message']
            ]);
          }

           //todo: Envoyer un mail aux administrateurs pour leur signaler qu'un marchand  vient de répondre à une précommande et qu'il doit valider

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


  

       /**
 * @OA\Get(
 *     path="/api/preorder/getPreorderPending",
 *     tags={"Preorder"},
 *     summary="Get all pending preorders",
 *     description="Retrieves all preorders what been pending.",
 *     @OA\Response(
 *         response=200,
 *         description="A list of pending preorders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
 *                 @OA\Property(property="statut", type="integer", example=2),
 *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
 *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */
public function getPreorderPending(){
    try {
        
        // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('pending')->first()->id)->get();

        $preorder = DB::select("SELECT * FROM preorders WHERE statut = (
            SELECT id FROM type_of_types WHERE libelle = 'pending' AND deleted = false LIMIT 1 
        )");

        if(count($preorder) == 0){
            return response()->json([
                'message' => 'No data found'
            ]);
        }

        return response()->json([
            'data' => $preorder
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}


    /**
 * @OA\Post(
 *     path="/api/preorder/validatePreorder/{uid}",
 *     tags={"Preorder"},
 *  security={{"bearerAuth": {}}},
 *     summary="Validate a preorder",
 *     description="Validates a preorder by changing its status to 'validated' if its current status is 'pending'.",
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="Unique identifier of the preorder",
 *         required=true,
 *         @OA\Schema(
 *             type="string",
 *             format="uuid"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Preorder validated successfully!",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Preorder validated successfully!"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Preorder not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Preorder not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Statut of preorder must be pending. Please, check it !",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Statut of preorder must be pending. Please, check it !"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */
    public function validatePreorder($uid){
        try {
               //todo: check if a person who make this action is an admin

        $preorder = Preorder::where('uid',$uid)->first();

        if(!$preorder){
            return response()->json([
                'message' => ' Preorder not found'
            ],404);
        }

        if($preorder->deleted == 1){
            return response()->json([
                'message' => 'this preorder is deleted'
            ]);
        }

        if($preorder->statut != TypeOfType::whereLibelle('pending')->first()->id){
            return response()->json([
                'message' => 'Statut of preorder must be pending. Please, check it !'
            ]);
        }

        $preorder->statut = TypeOfType::whereLibelle('validated')->first()->id;
        $preorder->validated_on = now();
        $preorder->validated_by_id = Auth::user()->id;
        $preorder->save();

        $title = "Confirmation of your pre-order";
        $body = "Your pre-order has just been validated by the admin, and you will shortly be receiving proposals from merchants. We'll keep you informed if there's a proposal.";

        $message = new ChatMessageController();
        $mes = $message->sendNotification($preorder->user_id,$title,$body, 'preorder answers validated successfully !');
        if($mes){
            return response()->json([
                  'message' =>$mes->original['message']
            ]);
          }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }


    /**
 * @OA\Post(
 *       path="/api/preorder/rejectPreorder/{uid}",
 *     summary="Reject a preorder",
 *     description="Rejects a preorder and updates its status to 'rejected'.",
 *  security={{"bearerAuth": {}}},
 *     tags={"Preorder"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the preorder"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"reject_reason"},
 *             @OA\Property(property="reject_reason", type="string", description="Reason for rejecting the preorder")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Preorder rejected successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Preorder not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Statut of preorder must be pending. Please, check it !",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */
    public function rejectPreorder($uid, Request $request){
        try {

               //todo: check if a person who make this action is an admin
            $request->validate([
                'reject_reason' => 'required'
            ]);
            $preorder = Preorder::where('uid',$uid)->first();
        if(!$preorder){
            return response()->json([
                'message' => ' Preorder not found'
            ],404);
        }

        if($preorder->statut != TypeOfType::whereLibelle('pending')->first()->id){
            return response()->json([
                'message' => 'Statut of preorder must be pending. Please, check it !'
            ]);
        }

        $preorder->statut = TypeOfType::whereLibelle('rejected')->first()->id;
        $preorder->validated_on = now();
        $preorder->validated_by_id = Auth::user()->id;
        $preorder->reject_reason = $request->input('reject_reason');
        // $preorder->save();

        $title = "Confirmation that your pre-order has been rejected";
        $body = "Your pre-order has been rejected by an admininstrator. Here is the reason <<$request->reject_reason>>";

        $message = new ChatMessageController();
        $mes = $message->sendNotification($preorder->user_id,$title,$body,  'Preorder rejected successfully!');
        if($mes){
            return response()->json([
                  'message' =>$mes->original['message']
            ]);
          }



        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],500);
        }
    }

  

        /**
 * @OA\Get(
 *     path="/api/preorder_answer/getPreorderAnswerValidated",
 *     tags={"PreorderAnswers"},
 *     summary="Get all validated preorders answer",
 *     description="Retrieves all preorders answer that have been validated.",
 *     @OA\Response(
 *         response=200,
 *         description="A list of validated preorders answer",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
 *                 @OA\Property(property="statut", type="integer", example=2),
 *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
 *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */
public function getPreorderAnswerValidated(){
    try {
        // $preorder_answer = PreorderAnswers::whereStatut(TypeOfType::whereLibelle('validated')->first()->id)->get();

        $preorder_answer = DB::select("SELECT * FROM preorder_answers WHERE statut = (
            SELECT id FROM type_of_types WHERE libelle = 'validated' LIMIT 1
        )");

if(count($preorder_answer) == 0){
    return response()->json([
        'message' => 'No data found'
    ]);
}

        return response()->json([
            'data' => $preorder_answer,
        ],200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}

      /**
 * @OA\Get(
 *     path="/api/preorder_answer/getPreorderAnswerRejected",
 *     tags={"PreorderAnswers"},
 *     summary="Get all rejected preorders answer",
 *     description="Retrieves all preorders answer that have been rejected.",
 *     @OA\Response(
 *         response=200,
 *         description="A list of rejected preorders answer",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
 *                 @OA\Property(property="statut", type="integer", example=2),
 *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
 *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */
public function getPreorderAnswerRejected(){
    try {
        // $preorder_answer = PreorderAnswers::whereStatut(TypeOfType::whereLibelle('rejected')->first()->id)->get();

        $preorder_answer = DB::select("SELECT * FROM preorder_answers WHERE statut = (
            SELECT id FROM type_of_types WHERE libelle = 'rejected' LIMIT 1
        )");

        if(count($preorder_answer) == 0){
            return response()->json([
                'message' => 'No data found'
            ]);
        }

        return response()->json([
            'data' => $preorder_answer,
        ],200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}

   /**
* @OA\Get(
*     path="/api/preorder_answer/getPreorderAnswerPending",
*     tags={"PreorderAnswers"},
*     summary="Get all pending preorders answer",
*     description="Retrieves all preorders answers what been pending.",
*     @OA\Response(
*         response=200,
*         description="A list of pending preorders answers",
*         @OA\JsonContent(
*             type="array",
*             @OA\Items(
*                 type="object",
*                 @OA\Property(property="id", type="integer", example=1),
*                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
*                 @OA\Property(property="statut", type="integer", example=2),
*                 @OA\Property(property="content", type="string", example="Sample preorder content"),
*                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
*                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
*                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
*             )
*         )
*     ),
*     @OA\Response(
*         response=500,
*         description="Internal Server Error",
*         @OA\JsonContent(
*             @OA\Property(
*                 property="error",
*                 type="string",
*                 example="An error message"
*             )
*         )
*     )
* )
*/
public function getPreorderAnswerPending(){
try {
    // $preorder_answer = PreorderAnswers::whereStatut(TypeOfType::whereLibelle('pending')->first()->id)->get();

    $preorder_answer = DB::select("SELECT * FROM preorder_answers WHERE statut = (
        SELECT id FROM type_of_types WHERE libelle = 'pending' LIMIT 1
    )");
    if(count($preorder_answer) == 0){
        return response()->json([
            'message' => 'No data found'
        ]);
    }
    return response()->json([
        'data' => $preorder_answer
    ]);
} catch (\Exception $e) {
    return response()->json([
        'error' => $e->getMessage()
    ],500);
}
}


 /**
* @OA\Get(
*     path="/api/preorder/getPreorderWitnAnswer",
*     tags={"Preorder"},
*     summary="Get all validated preorders with their answers ",
*     description="Retrieves all preorders with their answers.",
*     @OA\Response(
*         response=200,
*         description="A list of  validated preorders with their answers",
*         @OA\JsonContent(
*             type="array",
*             @OA\Items(
*                 type="object",
*                 
*             )
*         )
*     ),
*     @OA\Response(
*         response=500,
*         description="Internal Server Error",
*         @OA\JsonContent(
*             @OA\Property(
*                 property="error",
*                 type="string",
*                 example="An error message"
*             )
*         )
*     )
* )
*/
public function getPreorderWitnAnswer(){
    try {

        $preorders = Preorder::whereHas('preorder_answers', function ($query) {
            $query->whereDeleted(0)->where('statut', TypeOfType::whereLibelle('validated')->first()->id);
        })->with(['preorder_answers' => function ($query) {
            $query->whereDeleted(0)->where('statut', TypeOfType::whereLibelle('validated')->first()->id);
        }])->whereDeleted(0)->get();



        return response()->json([
            'data' => $preorders
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}


/**
 * @OA\Get(
 *     path="/api/preorder/getPreorderWithValidatedAnswers/{uid}",
 *     summary="Récupérer les informations d'une précommande avec les réponses validées",
 *     tags={"Preorder"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="Identifiant unique de la précommande",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Récupération réussie",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Précommande non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="string",
 *                 example="Précommande non trouvée !"
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
 *                 example="Une erreur s'est produite"
 *             )
 *         )
 *     )
 * )
 */
public function getPreorderWithValidatedAnswers($uid)
{
    try {
        if(!Preorder::whereUid($uid)->first()){
            return response()->json([
                'data' => 'Preorder not found !'
            ,404]);
        }

        $validatedStatusId = TypeOfType::whereLibelle('validated')->firstOrFail()->id;

        $preorder = Preorder::with(['preorder_answers' => function ($query) use ($validatedStatusId) {
            $query->where('statut', $validatedStatusId)->whereDeleted(0);
        }])->with('file')->whereDeleted(0)->whereUid($uid)->get();

        return response()->json([
            'data' => $preorder
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


/**
 * @OA\Get(
 *     path="/api/preorder_answer/getSpecificPreorderAnswer/{uid}",
 *     summary="Récupère une réponse pré-commande spécifique",
 *     description="Cette opération récupère une réponse pré-commande spécifique en fonction de l'UID fourni.",
 *     tags={"PreorderAnswers"},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="L'UID de la réponse pré-commande",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="La réponse pré-commande a été trouvée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 ref=""
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="La réponse pré-commande n'a pas été trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="data",
 *                 type="string"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Une erreur s'est produite lors de la recherche de la réponse pré-commande",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             )
 *         )
 *     ),
 * )
 */
public function getSpecificPreorderAnswer($uid){
    try {
        if(!PreorderAnswers::whereUid($uid)->first()){
            return response()->json([
                'data' => 'Preorder answer not found !'
            ,404]);
        }
        return response()->json([
            'data' => PreorderAnswers::whereUid($uid)->with('file')->with('user')->first()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
 * @OA\Get(
 *     path="/api/preorder/getPreorderValidated",
 *     tags={"Preorder"},
 *     summary="Get all validated preorders",
 *     description="Retrieves all preorders that have been validated.",
 *     @OA\Response(
 *         response=200,
 *         description="A list of validated preorders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
 *                 @OA\Property(property="statut", type="integer", example=2),
 *                 @OA\Property(property="content", type="string", example="Sample preorder content"),
 *                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */
public function getPreorderValidated(){
    try {
        // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('validated')->first()->id)->get();

        $preorder = DB::select("SELECT * FROM preorders WHERE statut = (
            SELECT id FROM type_of_types WHERE libelle = 'validated' AND deleted = false LIMIT 1
        )");

        if(count($preorder) == 0){
            return response()->json([
                'message' => 'No data found'
            ]);
        }
        return response()->json([
            'data' => $preorder
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}

  /**
* @OA\Get(
*     path="/api/preorder/getPreorderRejected",
*     tags={"Preorder"},
*     summary="Get all rejected preorders",
*     description="Retrieves all preorders that have been rejected.",
*     @OA\Response(
*         response=200,
*         description="A list of rejected preorders",
*         @OA\JsonContent(
*             type="array",
*             @OA\Items(
*                 type="object",
*                 @OA\Property(property="id", type="integer", example=1),
*                 @OA\Property(property="uid", type="string", format="uuid", example="0bac4ef8-182a-11ef-9357-00ff5210c7f1"),
*                 @OA\Property(property="statut", type="integer", example=2),
*                 @OA\Property(property="content", type="string", example="Sample preorder content"),
*                 @OA\Property(property="filecode", type="string", example="2VYfU4F"),
*                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-22 10:57:13"),
*                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-22 10:57:13")
*             )
*         )
*     ),
*     @OA\Response(
*         response=500,
*         description="Internal Server Error",
*         @OA\JsonContent(
*             @OA\Property(
*                 property="error",
*                 type="string",
*                 example="An error message"
*             )
*         )
*     )
* )
*/
public function getPreorderRejected(){
try {
    // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('rejected')->first()->id)->get();

    $preorder = DB::select("SELECT * FROM preorders WHERE statut = (
        SELECT id FROM type_of_types WHERE libelle = 'rejected' AND deleted = false LIMIT 1
    )");

    if(count($preorder) == 0){
        return response()->json([
            'message' => 'No data found'
        ]);
    }

    return response()->json([
        'data' => $preorder
    ]);

} catch (\Exception $e) {
    return response()->json([
        'error' => $e->getMessage()
    ],500);
}
}

      /**
 * @OA\Post(
 *     path="/api/preorder_answer/validatePreorderAnswer/{uid}",
 *     tags={"PreorderAnswers"},
 *  security={{"bearerAuth": {}}},
 *     summary="Validate a preorder answer",
 *     description="Validates a preorder answer by changing its status to 'validated' if its current status is 'pending'.",
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         description="Unique identifier of the preorder",
 *         required=true,
 *         @OA\Schema(
 *             type="string",
 *             format="uuid"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Preorder validated successfully!",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Preorder validated successfully!"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Preorder not found",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Preorder not found"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Statut of preorder must be pending. Please, check it !",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Statut of preorder must be pending. Please, check it !"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 example="An error message"
 *             )
 *         )
 *     )
 * )
 */

 public function validatePreorderAnswer($uid){
    try {
           //todo: check if a person who make this action is an admin

    $preorder_answer = PreorderAnswers::where('uid',$uid)->first();

    if(!$preorder_answer){
        return response()->json([
            'message' => ' Preorder not found'
        ],404);
    }

    if($preorder_answer->statut != TypeOfType::whereLibelle('pending')->first()->id){
        return response()->json([
            'message' => 'Statut of preorder answer must be pending. Please, check it !'
        ]);
    }

    $preorder_answer->statut = TypeOfType::whereLibelle('validated')->first()->id;
    $preorder_answer->validated_on = now();
    $preorder_answer->validated_by_id = Auth::user()->id;
    $preorder_answer->save();

    return response()->json([
        'message' => 'Preorder answer validated successfully!'
    ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}

   /**
* @OA\Post(
*       path="/api/preorder_answer/rejectPreorderAnswer/{uid}",
*     summary="Reject a preorder answer",
*     description="Rejects a preorder answer and updates its status to 'rejected'.",
*  security={{"bearerAuth": {}}},
*     tags={"PreorderAnswers"},
*     @OA\Parameter(
*         name="uid",
*         in="path",
*         required=true,
*         @OA\Schema(type="string"),
*         description="The UID of the preorder answer"
*     ),
*     @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(
*             required={"reject_reason"},
*             @OA\Property(property="reject_reason", type="string", description="Reason for rejecting the preorder")
*         )
*     ),
*     @OA\Response(
*         response=200,
*         description="Preorder rejected successfully",
*         @OA\JsonContent(
*             @OA\Property(property="message", type="string")
*         )
*     ),
*     @OA\Response(
*         response=404,
*         description="Preorder not found",
*         @OA\JsonContent(
*             @OA\Property(property="message", type="string")
*         )
*     ),
*     @OA\Response(
*         response=400,
*         description="Statut of preorder must be pending. Please, check it !",
*         @OA\JsonContent(
*             @OA\Property(property="message", type="string")
*         )
*     ),
*     @OA\Response(
*         response=500,
*         description="Internal Server Error",
*         @OA\JsonContent(
*             @OA\Property(property="error", type="string")
*         )
*     )
* )
*/
public function rejectPreorderAnswer($uid, Request $request){
    try {

           //todo: check if a person who make this action is an admin
        $request->validate([
            'reject_reason' => 'required'
        ]);
        $preorder_answer = PreorderAnswers::where('uid',$uid)->first();
    if(!$preorder_answer){
        return response()->json([
            'message' => ' Preorder answer not found'
        ],404);
    }

    if($preorder_answer->statut != TypeOfType::whereLibelle('pending')->first()->id){
        return response()->json([
            'message' => 'Statut of preorder answer must be pending. Please, check it !'
        ]);
    }

    $preorder_answer->statut = TypeOfType::whereLibelle('rejected')->first()->id;
    $preorder_answer->validated_on = now();
    $preorder_answer->validated_by_id = Auth::user()->id;
    $preorder_answer->reject_reason = $request->input('reject_reason');
    $preorder_answer->save();

    return response()->json([
        'message' => 'Preorder answer rejected successfully!'
    ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}

/**
 * @OA\Get(
 *     path="/api/preorder/getAuthPreorderValidated",
          * security={{"bearerAuth": {}}},
 *     summary="Retrieve the validated preorder for the authenticated user",
 *     description="This endpoint retrieves the validated preorder for the authenticated user. It returns a JSON response containing the preorder data.",
 *     tags={"Preorder"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"bearerAuth": {}}}
 * )
 */
public function getAuthPreorderValidated(){
    try {
        // $preorder = Preorder::whereStatut(TypeOfType::whereLibelle('validated')->first()->id)->whereUserId(Auth::user()->id)->get();

        $preorder = DB::select("
        SELECT *
        FROM preorders
        WHERE statut = (SELECT id FROM type_of_types WHERE libelle = 'validated') AND deleted = false
          AND user_id = ?
    ", [Auth::user()->id]);


    if(count($preorder) == 0){
        return response()->json([
            'message' => 'No data found'
        ]);
    }

        return response()->json([
            'data' => $preorder
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ],500);
    }
}


/**
 * @OA\Post(
 *     path="/api/review/write/{PreordersAnswerUid}",
 *     summary="Chat with a seller regarding a pre-order response",
 *     tags={"Review"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="PreordersAnswerUid",
 *         in="path",
 *         required=true,
 *         description="Uid of preorder response",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="content", type="string", description="Contenu de la réponse"),
 *                 @OA\Property(property="files[]", type="array", @OA\Items(type="file"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
public function write(Request $request, $PreordersAnswerUid){
    try{
        $validator = Validator::make($request->all(), [
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The data provided is not valid.', 'errors' => $validator->errors()], 200);
        }

        if(!PreorderAnswers::whereUid($PreordersAnswerUid)->first()){
            return response()->json([
                'message' => 'Preorder answer not found'
            ],404);
        }

        if(PreorderAnswers::whereUid($PreordersAnswerUid)->first()->statut !=TypeOfType::whereLibelle('validated')->first()->id ){
            return response()->json([
                'message' => 'Statut of preorder answer must be validated. Please, check it !'
            ],403);
        }

        $service = new Service();
        $review = new Review();
        $review->user_id = Auth::user()->id;
        $review->preorder_answer_uid = $PreordersAnswerUid;
        $review->content = $request->input('content');
        $review->uid = $service->generateUid($review);
        $randomString = $service->generateRandomAlphaNumeric(7,$review,'filecode');
        $review->filecode = $randomString;

        if ($request->hasFile('files')) {
            $service->uploadFiles($request,$randomString,"preorder");
        }

        $review->save();

        $title = "Confirmation of Receipt of a Response";
        $body = "You have received a response on one of your submissions in relation to a pre-order";

        $r = PreorderAnswers::whereUid($PreordersAnswerUid)->first();

        $receiver_id= $r->user->id;

       $message = new ChatMessageController();
      $mes =  $message->sendNotification($receiver_id,$title,$body, 'comment submited successfully !');

      if($mes){
        return response()->json([
              'message' =>$mes->original['message']
        ]);
      }

    }catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
}


/**
* @OA\Get(
*     path="/api/preorder/merchantAffectedByPreorder/{perPage}",
*     summary="Get pre-orders that concern the merchant",
*     tags={"Preorder"},
*     security={{"bearerAuth": {}}},
*     @OA\Parameter(
*         name="perPage",
*         in="path",
*         required=true,
*         description="number of elements perpage",
*         @OA\Schema(type="integer")
*     ),
*     @OA\Response(
*         response=200,
*         description="OK",
*         @OA\JsonContent(
*             type="object",
*             @OA\Property(property="data", type="array", @OA\Items(ref="")),
*             @OA\Property(property="nombre", type="integer")
*         )
*     ),
*     @OA\Response(
*         response=400,
*         description="Bad Request",
*         @OA\JsonContent(
*             type="object",
*             @OA\Property(property="error", type="string")
*         )
*     )
* )
*/
public function merchantAffectedByPreorder($perPage){
    try{

            $user = Auth::user();

            $categories = Ad::where('owner_id', $user->id)
                            ->where('deleted', false)
                            ->pluck('category_id');

                            // return [Auth::user()->id,$categories];

            $validatedStatus = TypeOfType::where('libelle', 'validated')->first()->id;

            $preorders = Preorder::where('statut', $validatedStatus)
                         ->whereIn('category_id', $categories)
                         ->whereDeleted(0)
                         ->orderBy('created_at', 'desc')
                         ->paginate($perPage);

                         if(count($preorders) == 0){
                            return response()->json([
                                'message' => 'No data found'
                            ]);
                        }

            return response()->json([
                'data' => $preorders,
                'nombre' => $preorders->count()
            ]);

    }catch(Exception $e){
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * @OA\Get(
 *     path="/api/preorder/getValidatedPreordersWithAnswers/{preorderUid}/{perPage}",
 *     summary="Get pre-orders validated with responses",
 *     tags={"Preorder"},
          * security={{"bearerAuth": {}}},
         *    @OA\Parameter(
 *         name="preorderUid",
 *         in="path",
 *         required=true,
 *         description="Uid of preorder response",
 *         @OA\Schema(type="string")
 *     ),

    *       @OA\Parameter(
*         name="perPage",
*         in="path",
*         required=true,
*         description="ID of the merchant or related entity",
*         @OA\Schema(type="integer")
*     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="preorder_answers", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */
public function getValidatedPreordersWithAnswers($preorderUid,$perPage)
    {
        try{
            $user = Auth::user();

            $validatedStatus = TypeOfType::where('libelle', 'validated')->first()->id;

            $preorders = Preorder::where('user_id', $user->id)
                                 ->where('statut', $validatedStatus)
                                 ->whereDeleted(0)
                                 ->where('uid', $preorderUid)
                                 ->get();

            $preorderAnswers = PreorderAnswers::whereIn('preorder_id', $preorders->pluck('id'))
                                             ->whereDeleted(0)
                                             ->where('statut', $validatedStatus)
                                             ->orderBy('created_at', 'desc')
                                             ->paginate($perPage);


            return response()->json([
                'preorders' => $preorders,
                'preorder_answers' => $preorderAnswers
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }

    }

    /**
 * @OA\Get(
 *     path="/api/preorder/getPreordersAnswerSortedByDeliveryTime/{preorderUid}/{perPage}",
 *     summary="Get pre-orders validated with responses filtered by delivery time",
 *          security={{"bearerAuth": {}}},
 *     tags={"Preorder"},
 *    @OA\Parameter(
 *         name="preorderUid",
 *         in="path",
 *         required=true,
 *         description="Uid of preorder response",
 *         @OA\Schema(type="string")
 *     ),
 *    @OA\Parameter(
*         name="perPage",
*         in="path",
*         required=true,
*         description="ID of the merchant or related entity",
*         @OA\Schema(type="integer")
*     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="preorder_answers", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */
    public function getPreordersAnswerSortedByDeliveryTime($preorderUid,$perPage)
    {
        try{
            $user = Auth::user();

            $validatedStatus = TypeOfType::where('libelle', 'validated')->first()->id;

            $preorders = Preorder::where('user_id', $user->id)
                                 ->where('statut', $validatedStatus)
                                 ->where('uid', $preorderUid)
                                 ->get();

            $preorderAnswers = PreorderAnswers::whereIn('preorder_id', $preorders->pluck('id'))
                                             ->where('statut', $validatedStatus)
                                             ->orderBy('delivery_time')
                                             ->orderBy('created_at', 'desc')
                                             ->paginate($perPage);

            return response()->json([
                // 'preorders' => $preorders,
                'preorder_answers' => $preorderAnswers
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/preorder/getPreordersAnswerSortedByPrice/{preorderUid}/{perPage}",
 *     summary="Get pre-orders validated with responses filtered by price",
 *     security={{"bearerAuth": {}}},
 *     tags={"Preorder"},
 *     @OA\Parameter(
 *         name="preorderUid",
 *         in="path",
 *         required=true,
 *         description="UID of the preorder",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="path",
 *         required=true,
 *         description="Number of results per page",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="preorder_answers", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */



    public function getPreordersAnswerSortedByPrice($preorderUid,$perPage)
    {
        try{
            $user = Auth::user();

            $validatedStatus = TypeOfType::where('libelle', 'validated')->first()->id;

            $preorders = Preorder::where('user_id', $user->id)
                                 ->where('statut', $validatedStatus)
                                 ->where('uid', $preorderUid)
                                 ->get();

            $preorderAnswers = PreorderAnswers::whereIn('preorder_id', $preorders->pluck('id'))
                                             ->where('statut', $validatedStatus)
                                             ->orderBy('price')
                                             ->orderBy('created_at', 'desc')
                                             ->paginate($perPage);

            return response()->json([
                // 'preorders' => $preorders,
                'preorder_answers' => $preorderAnswers
            ]);
        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/review/answerReviews/{preorderAnswerUid}",
 *     summary="Get reviews for a specific preorder answer",
 *     tags={"Review"},
 * security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="preorderAnswerUid",
 *         in="path",
 *         required=true,
 *         description="Uid of preorder answer",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref=""))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
    public function answerReviews($preorderAnswerUid){
        try{

            $reviews = Review::whereDeleted(0)->where('preorder_answer_uid',$preorderAnswerUid)->with('file')->get();
            return response()->json([
                'data' => $reviews
            ]);

        }catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

}
