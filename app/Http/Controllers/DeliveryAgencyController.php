<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\DeliveryAgency;
use App\Models\EscrowDelivery;
use App\Models\Order;
use App\Models\Person;
use App\Models\TypeOfType;
use App\Models\User;
use App\Services\OngingTradeStageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class DeliveryAgencyController extends Controller
{


/**
     * @OA\Post(
     *     path="/api/deliveryAgency/add",
     *     summary="Add a new delivery agency",
     *     tags={"Delivery Agencies"},
     *  security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="agent_type",
     *                     type="string",
     *                     description="Type of the agent"
     *                 ),
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     description="Parent ID of the delivery agency",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     description="Address of the delivery agency",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Files to upload, required if agent_type is 'company'"
     *                 ),
     *                 @OA\Property(
     *                     property="company_name",
     *                     type="string",
     *                     description="Name of the company, required if agent_type is 'company'",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Delivery agency created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Delivery agency created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type"="string"})
     *         )
     *     )
     * )
     */

 public function add(Request $request)
 {

     $service = new Service();
     $id = $service->returnPersonIdAuth();
     $validator = Validator::make($request->all(), [
         'agent_type' => 'required|string',
     ]);

     if ($validator->fails()) {
         return response()->json([
             'message' => 'Validation error',
             'errors' => $validator->errors()
            ], 422);
        }

        $errorcheckifAlreadyDeliveryAgent= $this->checkifAlreadyDeliveryAgent($id);
   
        if($errorcheckifAlreadyDeliveryAgent){
           return $errorcheckifAlreadyDeliveryAgent;
        }

        $parent_id = $request->parent_id ?? null;
        $address = $request->address ?? null;


     if ($request->agent_type == 'company') {

        $request->validate([
            'files' => 'required',
            'company_name' => 'required'
        ]);
        $file_reference_code = $this->create($request->agent_type,$id,$parent_id,$request->company_name,$address);

        $errorUploadFile = $service->uploadFiles($request,$file_reference_code,"delivery_agent_pieces");

        if($errorUploadFile){
            return $errorUploadFile;
        }

        return response()->json([
            'status' =>200,
            'data' => [],
             'message' => 'Delivery agency created successfully',
         ], 201);
    }

    $this->create($request->agent_type,$id,$parent_id,null,$address);

     return response()->json([
        'status' =>200,
        'data' => [],
         'message' => 'Delivery agency created successfully',
     ], 201);
 }

 public function create($agent_type,$person_id,$parent_id =null,$company_name = null,$address=null){
    $service = new Service();
    $deliveryAgency = new DeliveryAgency();
     $deliveryAgency->agent_type =$agent_type;
     $deliveryAgency->person_id = $person_id;
     $deliveryAgency->parent_id = $parent_id;
     $deliveryAgency->company_name =$company_name;
     $deliveryAgency->address =$address;
     $deliveryAgency->uid = $service->generateUid($deliveryAgency);
     $deliveryAgency->created_at = now();
     $deliveryAgency->updated_at = now();
     $deliveryAgency->file_reference_code = $service->generateRandomAlphaNumeric(7,$deliveryAgency,'file_reference_code');
     $deliveryAgency->statut = TypeOfType::whereLibelle('pending')->first()->id;
     $deliveryAgency->save();

     $title = "Confirmation of your delivery agent registration";
     $body = "Your registration as a delivery agent has been received. Please wait while an administrator reviews and validates your request. We'll notify you of the next steps. Thank you!";

     $titleAdmin = "New Delivery Agent Registration Request";
     $bodyAdmin = "A new registration request as a delivery agent has been submitted. Please log in to your account to review and validate the request promptly. Your attention is required. Thank you!";


     //notify delivery agent

    (new MailController())->sendNotification(Auth::user()->id,$title,$body, 2);
    //  dispatch(new SendEmail(Auth::user()->id,$title,$body,2));

      //notify admin
      $service->notifyAdmin($titleAdmin,$bodyAdmin);

     return  $deliveryAgency->file_reference_code;
 }

 public function checkifAlreadyDeliveryAgent($personId){
   
     $exist = DeliveryAgency::where('person_id', $personId)->exists();
 
     if ($exist) {
         return response()->json([
             'message' => 'Already exist'
         ], 200);
     }
 }


    public function checkWalletBalance($deliveryPersonId, $orderAmount) {
       $service = new Service();
       $walletBalance = $service->returnSTDPersonWalletBalance($deliveryPersonId);

       if($walletBalance < $orderAmount){
            return response()->json([
                'message' => 'You cannot take this delivery because your wallet balance is below the order amount.'
            ], 404);
       }
    }

    public function storeEscrowDelivery($deliveryPersonUid, $orderUid){
        try {
            if((new Service())->isValidUuid($deliveryPersonUid)){
                return (new Service())->isValidUuid($deliveryPersonUid);
            }
            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            } 
            if(!Order::whereUid($orderUid)->first()){
                return (new Service())->apiResponse(404, [], 'Order not found');
            }
            $escrowDelivery = new EscrowDelivery();
            $escrowDelivery->person_uid = $deliveryPersonUid;
            $escrowDelivery->order_uid = $orderUid;
            $escrowDelivery->order_amount = Order::whereUid($orderUid)->first()->amount;
            $escrowDelivery->delivery_agent_amount = $escrowDelivery->order_amount;
            $escrowDelivery->status = TypeOfType::where('libelle','pending')->first()->id; 
            $escrowDelivery->pickup_date = null; // Date de prise en charge 
            $escrowDelivery->delivery_date = null; // Date de livraison 
            $escrowDelivery->created_at = now();
            $escrowDelivery->updated_at = now();
            $escrowDelivery->save();
        } catch (Exception $e) {
            return response()->json([
             'error' => $e->getMessage()
            ]);
         }
    }

    public function reserveAmount($deliveryPersonId, $orderUid) {
        try {
            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }
            $order = Order::whereUid($orderUid)->first();
            // return $order;
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            $typeId = Commission::whereShort('STD')->first()->id;
            $wallet = CommissionWallet::where('person_id', $deliveryPersonId)->where('commission_id',$typeId)->first();
            if (!$wallet) {
                return response()->json(['error' => 'Wallet not found'], 404);
            }
            
            $updateWallet = new OngingTradeStageService();
            $deliveryAgentAmount = $wallet->balance - $order->amount;
            $errorUpdateUserWallet = $updateWallet->updateUserWallet($deliveryPersonId, $deliveryAgentAmount);
            if ($errorUpdateUserWallet) {
                return $errorUpdateUserWallet;
            }
            // return  $wallet->balance ;
            // return 1;
 
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    /**
     * @OA\Post(
     *     path="/api/deliveryAgency/acceptOrder/{orderUid}",
      * security={{"bearerAuth": {}}},
     *     summary="Accept an order",
     *     description="Allows a delivery person to accept an order after validating wallet balance and reserving the amount.",
     *     tags={"Delivery Agencies"},
     *     @OA\Parameter(
     *         name="orderUid",
     *         in="path",
     *         description="ID of the order to accept",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),   
     *     @OA\Response(
     *         response=200,
     *         description="Order accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order accepted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Only delivery people can accept orders",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Only delivery people can accept orders")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error: Wallet balance insufficient",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Wallet balance insufficient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     ),
     *     
     * )
     */


    public function acceptOrder($orderUid){
        try{
            // return Order::whereUid($orderUid)->first();
            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            if(!Order::whereUid($orderUid)->first()){
                return response()->json([
                    'message' => 'Order not found'
                ], 200);
            }

            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }

            $order = Order::whereId(Order::whereUid($orderUid)->first()->id)->first();

            $pendingStatusId = TypeOfType::where('libelle', 'pending')->first()->id;
            $paidStatusId = TypeOfType::where('libelle', 'paid')->first()->id;

            if($order->status != $paidStatusId){
                return response()->json(['error' => 'Order must be paid'], 404);
            }


            $deliveryPersonId= $service->checkIfDeliveryAgent();

            if($deliveryPersonId == 0){
                return response()->json(['error' => 'Only delivery agent can accept orders'], 404);
            }
            $personUid = Person::whereId($deliveryPersonId)->first()->uid;

            $existAcceptingOrder = EscrowDelivery::where('order_uid',$orderUid)->where('person_uid',$personUid)->where('status',$pendingStatusId)->exists();

            $checkIfIndividualHaveOrderInProgress = EscrowDelivery::where('person_uid',$personUid)->where('status',$pendingStatusId)->count();

            if($checkIfIndividualHaveOrderInProgress >= 2 && DeliveryAgency::where('person_id',$deliveryPersonId)->first()->agent_type == 'individual' ){
                return response()->json(['error' => 'You already have orders in progress'], 404);
            }

            // return  $existAcceptingOrder;

            if($existAcceptingOrder){
                return response()->json([
                    'message' => 'Order already accepted'
                ], 200);
            }

            $deliveryPersonUid = Person::find($deliveryPersonId)->uid;

            $errorcheckWalletBalance = $this->checkWalletBalance($deliveryPersonId, $order->amount);
            if($errorcheckWalletBalance){
                return $errorcheckWalletBalance;
            }

            $errorreserveAmount = $this->reserveAmount($deliveryPersonId, $orderUid);
            if($errorreserveAmount){
                return $errorreserveAmount;
            }

            $errorstoreEscrowDelivery = $this->storeEscrowDelivery($deliveryPersonUid, $orderUid);
            if($errorstoreEscrowDelivery){
                return $errorstoreEscrowDelivery;
            }


            return response()->json([
                'message' => 'Order accepted successfully'
            ], 200);
        }catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
    * @OA\Get(
     *     path="/api/deliveryAgency/getAvailableOrders/{perpage}",
     *     summary="Get available orders",
     *     description="Retrieve orders with 'paid' status, not present in EscrowDelivery, and matching the location_id of the authenticated delivery person.",
     *     tags={"Delivery Agencies"},
     *  @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref=""))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */

    public function getAvailableOrders($perpage = 10) {
        try {
            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }

            $personId = $service->returnPersonIdAuth();
            $personLocation = Person::where('user_id', $personId)->first()->country_id;
            $paidStatusId = TypeOfType::where('libelle', 'paid')->first()->id;

            $orders = Order::where('status', $paidStatusId)
                ->whereNotIn('uid', EscrowDelivery::pluck('order_uid'))
                ->whereHas('user.person', function ($query) use ($personLocation) {
                    $query->where('country_id',$personLocation);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(intval($perpage));
    
            return response()->json(['data' => $orders]);
    
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function notifyDeliveryAgents($orderUid) {
        try {

            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            $data = $this->getDeliveryAgentConcernedByOrder($orderUid);

           $title =  "New Order in Your Area: Immediate Action Required";
           $body =  "A new order has just been placed in your area. Please log in to your account to view and accept the delivery. Your timely response is essential. Thank you!";

           foreach($data as $item){

            (new MailController())->sendNotification($item->id,$title,$body, 2);
            // dispatch(new SendEmail($item->id,$title,$body,2));
           }


        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function notifyDeliveryAgentsConcerned($userId) {
        try {
           $title =  "New Order in Your Area: Immediate Action Required";
           $body =  "A new order has just been placed in your area. Please log in to your account to view and accept the delivery. Your timely response is essential. Thank you!";

           (new MailController())->sendNotification($userId,$title,$body, 2);
        //    dispatch(new SendEmail($userId,$title,$body,2));

    
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getDeliveryAgentConcernedByOrder($orderUid){
        try{
            $data= [];
            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }
            $deliveryAgents =$this->getDeliveryAgent(1);
            $userLocation = Person::whereId(Order::whereUid($orderUid)->first()->user_id)->first()->country_id ;


        foreach($deliveryAgents as $deliveryAgent){
            if(Person::whereUserId($deliveryAgent->id)->first()->country_id == $userLocation){
                $data[] = $deliveryAgent ;
                }
            }

           return $data;

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
   
    }


    /**
     * @OA\Get(
     *     path="/api/deliveryAgency/getDeliveryAgent",
     *     summary="Get all delivery agents",
     *     tags={"Delivery Agencies"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="")
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

    public function getDeliveryAgent($list =0){
        try{

            // return $this->getDeliveryAgentConcernedByOrder('008bd858-3ef0-11ef-8c56-00ff5210c7f1');
            $service = new Service();
            $users = User::whereDeleted(0)->get();
            $data = [];
     
            foreach($users as $user){
                $personId =$service->returnUserPersonId($user->id);
                $personUid = Person::whereId($personId)->first()->uid;
                $exist = DeliveryAgency::where('person_id',$personId)->exists();

                if($exist){
                    $data [] = $user;
                }
             }

             if($list == 1){
                return $data;
             }
     
             return response()->json([
                'data' => $data
             ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * @OA\Post(
     *     path="/api/deliveryAgency/rejectOrder/{orderUid}",
     *     summary="Reject an order",
     *     description="Rejects an order that has been previously accepted by a delivery agent.",
     *     operationId="rejectOrder",
     *     tags={"Delivery Agencies"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="orderUid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         ),
     *         description="The UID of the order to be rejected"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="Order rejected successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found or other errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order must be paid before to be rejected")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error message")
     *         )
     *     )
     * )
     */
    public function rejectOrder($orderUid){
        try{

            if((new Service())->isValidUuid($orderUid)){
                return (new Service())->isValidUuid($orderUid);
            }

            if(!Order::whereUid($orderUid)->first()){
                return response()->json([
                    'message' => 'Order not found'
                ], 200);
            }


            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }

            $order = Order::whereId(Order::whereUid($orderUid)->first()->id)->first();

            $pendingStatusId = TypeOfType::where('libelle', 'pending')->first()->id;
            $paidStatusId = TypeOfType::where('libelle', 'paid')->first()->id;
            $validatedStatusId = TypeOfType::where('libelle', 'validated')->first()->id;

            if($order->status != $paidStatusId){
                return response()->json(['error' => 'Order must be paid before to be rejected'], 404);
            }

            $deliveryPersonId= $service->checkIfDeliveryAgent();

            if($deliveryPersonId == 0){
                return response()->json(['error' => 'Only delivery agent can reject orders'], 404);
            }

            $personUid = Person::whereId($deliveryPersonId)->first()->uid;

            $existAcceptingOrder = EscrowDelivery::where('order_uid',$orderUid)->where('person_uid',$personUid)->where('status',$pendingStatusId)->exists();

            if(!$existAcceptingOrder){
                return response()->json([
                    'message' => 'Are you sure you have agreed to deliver this order?'
                ], 200);
            }

            $acceptingOrder = EscrowDelivery::where('order_uid',$orderUid)->where('person_uid',$personUid)->where('status',$pendingStatusId)->first();


            if($acceptingOrder->status == $validatedStatusId){
                return response()->json([
                    'message' => 'fund already sent'
                ], 200);
            }

            $deliveryPersonUid = Person::find($deliveryPersonId)->uid;

            (new OngingTradeStageService ())->refundDeliveryAgent($order->id);

            EscrowDelivery::where('order_uid',$orderUid)->where('person_uid',$deliveryPersonUid)->delete();

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Order rejected successfully'
            ]);


        }catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/deliveryAgency/getAcceptedOrder/{perpage}",
     *     summary="Get accepted orders",
     *     description="Retrieve a list of orders accepted by the authenticated delivery agent.",
     *     operationId="getAcceptedOrder",
     *     tags={"Delivery Agencies"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=10
     *         ),
     *         description="Number of orders to return per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of orders accepted by delivery agent",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string", example="list of orders accepted by delivery agent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="An error message")
     *         )
     *     )
     * )
     */
    public function getAcceptedOrder($perpage = 10){
        try {

            $personUid = Person::whereId((new Service())->returnPersonIdAuth())->first()->uid;

            $orders = Order::whereIn('uid', function($query) use ($personUid) {
            $query->select('order_uid')
                ->from('escrow_deliveries')
                ->where('person_uid', $personUid)
                ->orderBy('created_at', 'desc');
            })->paginate(intval($perpage));

        return response()->json([
                        'status_code' => 200,
                        'data' =>$orders,
                        'message' => 'list of orders accepted by delivery agent'
                    ],200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'data' =>[],
                'message' => $e->getMessage()
            ],500);
        }
    }


// try {
   // return response()->json([
        //         'status_code' => 200,
        //         'data' =>[],
        //         'message' => ''
        //     ],200);
// } catch (Exception $e) {
//     return response()->json([
//         'status_code' => 500,
//         'data' =>[],
//         'message' => $e->getMessage()
//     ],500);
// }
}
