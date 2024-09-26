<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Person;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class NotificationController extends Controller
{


    
    public function addNotification($personId, $object,$description){
        try {
            $notification = new Notification();
            $notification->person = $personId;
            $notification->object = $object;
            $notification->description = $description;
            $notification->save();
            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'notification created successffully'
            ]);
        } catch(Exception $e) {
            return response()->json($e->getMessage());
        }
    }


  /**
 * @OA\Post(
 *     path="/api/notification/create",
 *     tags={"Notifications"},
 *     summary="Create a notification",
 *     description="Creates a notification for multiple persons",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="object", type="string", example="notification object"),
 *             @OA\Property(property="description", type="string", example="notification description"),
 *             @OA\Property(property="personIds", type="array", @OA\Items(type="integer"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Notification sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="data", type="array",@OA\Items(type="string")),
 *             @OA\Property(property="message", type="string", example="notification sent successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data provided",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=400),
 *             @OA\Property(property="data", type="array",@OA\Items(type="string")),
 *             @OA\Property(property="message", type="string", example="The data provided is not valid."),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error"
 *     )
 * )
 */





    public function create(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'object' =>  ['required'],
                'description' => 'required',
                'personIds' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'The data provided is not valid.', 'errors' => $validator->errors()
                ], 200);
            }

            foreach($request->personIds as $personId){
                $notification = new Notification();
                $notification->person = $personId;
                $notification->object = $request->object;
                $notification->description = $request->description;
                $notification->save();
            }

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'notification sent successfully'
            ]);
        } catch(Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/api/notifications/{notificationUid}",
     *     summary="Mark notification as read or unread",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="notificationUid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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

    public function makeAsReadOrUnRead($notificationUid){
         try {
            if((new Service())->isValidUuid($notificationUid)){
                return (new Service())->isValidUuid($notificationUid);
            }
            $notification = Notification::whereUid($notificationUid)->first();

            if(!$notification){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'notification not found'
                ]);
            }

            if($notification->isRead == true){
                $notification->update(['isRead' => false]);
                return response()->json([
                    'status_code' => 200,
                    'data' =>[],
                    'message' => 'notification unread successfully'
                ]);
            }

            $notification->update(['isRead' => true]);

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'notification read successfully'
            ]);
         }catch(Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/api/notification/createGeneralNotification",
     *     summary="Create general notification",
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(property="object", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="General notification created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
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

    public function createGeneralNotification(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'object' =>  'required',
                'description' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'The data provided is not valid.', 'errors' => $validator->errors()
                ], 200);
            }

            $notification = new Notification();
            foreach (Person::whereDeleted(false)->get() as $person){
                $notification->person = $person->id;
                $notification->object = $request->object;
                $notification->description = $request->description;
                $notification->save();
            }

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'notification created successfully'
            ]);
        } catch(Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notification/getNotifications/{perpage}",
     *     summary="Get notifications for a user",
     *     tags={"Notifications"},
     *    security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="perpage",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of notifications",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="object", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="isRead", type="boolean"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
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

     
    public function getNotifications($perpage) {
        try {

            if($perpage >= 100){
                $perpage = 100;
            }
            $service = new Service();
            $personId = $service->returnPersonIdAuth();
            $notifications = Notification::where('person', $personId)
                ->orderBy('created_at', 'desc')
                ->paginate($perpage);

            return response()->json([
                'status_code' => 200,
                'data' => $notifications,
                'message' => 'Notifications retrieved successfully'
            ]);
        } catch(Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/notification/deleteNotification/{notificationUid}",
     *     summary="Delete a notification",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="notificationUid",
     *         in="path",
     *         description="Unique identifier of the notification",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array",@OA\Items(type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array",@OA\Items(type="string")),
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
    public function deleteNotification($notificationUid){
        try {

            $notification = Notification::whereUid($notificationUid)->first();

            if(!$notification){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'notification not found'
                ]);
            }

            $notification->delete();

            return response()->json([
                'status_code' => 200,
                'data' => [],
                'message' => 'Notifications deleted successfully'
            ]);
        } catch(Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
