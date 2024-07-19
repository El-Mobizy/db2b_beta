<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAgency;
use App\Models\DeliveryAgentZone;
use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;

class DeliveryAgentZoneController extends Controller
{


    // /**
    //  * @OA\Post(
    //  *     path="/api/deliveryzone/addZone/{zoneUid}",
    //  *     summary="Add a zone to the delivery agent's list",
    //  *     tags={"DeliveryAgentZones"},
    //  *     @OA\Parameter(
    //  *         name="zoneUid",
    //  *         in="path",
    //  *         description="UID of the zone to be added",
    //  *         required=true,
    //  *         @OA\Schema(
    //  *             type="string"
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Zone added successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status_code", type="integer"),
    //  *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
    //  *             @OA\Property(property="message", type="string")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Bad request",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status_code", type="integer"),
    //  *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
    //  *             @OA\Property(property="message", type="string")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Internal server error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status_code", type="integer"),
    //  *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
    //  *             @OA\Property(property="message", type="string")
    //  *         )
    //  *     ),
    //  *     security={{"bearerAuth": {}}}
    //  * )
    //  */
    public function addZone($zoneId, $longitudes, $latitudes){
        try {


            for ($i = 0; $i < count($latitudes); $i++) {
                    $deliveryAgentZone = new DeliveryAgentZone();
                    $deliveryAgentZone->zone_id = $zoneId;
                    $deliveryAgentZone->latitude = $latitudes[$i];
                    $deliveryAgentZone->longitude = $longitudes[$i];
                    $deliveryAgentZone->uid = (new Service())->generateUid($deliveryAgentZone);
                    $deliveryAgentZone->point_order = $i + 1;
                    $deliveryAgentZone->save();
            }

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone added succesfully'
            ],200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'data' =>[],
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function checkDeliveryAgentZoneNumber($deliveryAgentId){

        $limitCount = 3;
        $countDeliveryAgentZone =  DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereDeleted(false)->count();

        if(DeliveryAgency::whereId($deliveryAgentId)->first()->agent_type == 'individual' && $countDeliveryAgentZone > $limitCount){
            return response()->json([
                'status_code' => 400,
                'data' =>[],
                'message' => 'You have reached the limit for adding a zone '
            ],200);
        }
    }

    public function checkIfZoneAlreadyAdded($deliveryAgentId,$zoneUid){

        if(DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($zoneUid)->first()->id)->whereDeleted(false)->exists()){

            return response()->json([
                'status_code' => 400,
                'data' =>[],
                'message' => 'You already add this zone'
            ],200);

        }
    }

    public function checkIfZoneAlreadyAddedButWasDeleted($deliveryAgentId,$zoneUid){

        if(DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($zoneUid)->first()->id)->whereDeleted(true)->exists()){

            DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($zoneUid)->first()->id)->whereDeleted(true)->first()->update(['deleted' =>false]);;

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone added succesfully'
            ],200);

        }
    }

    public function storeDeliveryAgent($deliveryAgentId,$zoneUid){
        $deliveryAgentZone = new DeliveryAgentZone();
        $deliveryAgentZone->delivery_agency_id = $deliveryAgentId;
        $deliveryAgentZone->zone_id = Zone::whereUid($zoneUid)->first()->id;
        $deliveryAgentZone->uid = (new Service())->generateUid($deliveryAgentZone); ;
        $deliveryAgentZone->save();
    }


    /**
     * @OA\Post(
     *     path="/api/deliveryzone/removeZone/{zoneUid}",
     *     summary="Remove a zone from the delivery agent's list",
     *     tags={"DeliveryAgentZones"},
     *     @OA\Parameter(
     *         name="zoneUid",
     *         in="path",
     *         description="UID of the zone to be removed",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zone deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Zone already deleted",
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
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function removeZone($ZoneUid){
        try {
            $deliveryAgentId = (new Service())->returnDeliveryAgentIdOfAuth();

            $exist = DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($ZoneUid)->first()->id)->whereDeleted(true)->exists();

            // return $exist;

            if($exist){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'You already delete this zone'
                ],200);
            }

        //    return  DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($ZoneUid)->first()->id)->first();

            DeliveryAgentZone::whereDeliveryAgencyId($deliveryAgentId)->whereZoneId(Zone::whereUid($ZoneUid)->first()->id)->first()->update(['deleted' =>true]);

             return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone deleted successfully'
            ],200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'data' =>[],
                'message' => $e->getMessage()
            ],500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/deliveryzone/DeliveryAgentZones",
     *     summary="Get the list of zones covered by the delivery agent",
     *     tags={"DeliveryAgentZones"},
     *     @OA\Response(
     *         response=200,
     *         description="List of specific agent delivery zones",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="uid", type="string"),
     *                     @OA\Property(property="city_name", type="string"),
     *                     @OA\Property(property="latitude", type="number", format="float"),
     *                     @OA\Property(property="longitude", type="number", format="float"),
     *                     @OA\Property(property="country_id", type="integer"),
     *                     @OA\Property(property="active", type="boolean"),
     *                     @OA\Property(property="deleted", type="boolean"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function DeliveryAgentZones($perpage = 20){
        try {
            $deliveryAgentId = (new Service())->returnDeliveryAgentIdOfAuth();

            $zones = Zone::join('delivery_agent_zones', 'zones.id', '=', 'delivery_agent_zones.zone_id')
                 ->where('delivery_agent_zones.delivery_agency_id', $deliveryAgentId)
                 ->where('delivery_agent_zones.deleted', false)
                 ->where('zones.deleted', false)
                 ->where('zones.active', true)
                 ->orderByDesc('zones.created_at')
                 ->select('zones.*')
                 ->paginate($perpage);
           

        return response()->json([
                        'status_code' => 200,
                        'data' =>$zones,
                        'message' => 'list of specific agent delivery'
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
