<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/zone/index/{paginate}",
     *     summary="List all zones with pagination",
     *     tags={"Zones"},
     *  security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paginate",
     *         in="path",
     *         required=true,
     *         description="Number of items per page (maximum 100)",
     *         @OA\Schema(
     *             type="integer",
     *             example=20
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of zones",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(
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
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
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
     *     )
     * )
     */
    public function index($paginate)
    {

        try {

            if($paginate > 100){
                $paginate = 100;
            }

            $zones = Zone::where('deleted', false)->paginate($paginate);
            return response()->json([
                'status_code' => 200,
                'data' =>$zones,
                'message' => 'List of zone'
            ]);
        }  catch (Exception $e) {
                return response()->json([
                    'status_code' => 500,
                    'data' =>[],
                    'message' => $e->getMessage()
                ],500);
            }
     
    }


     /**
 * @OA\Post(
 *     path="/api/zone/store",
 *     summary="Store a new delivery zone",
 *     tags={"Zones"},
 * security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="latitudes",
 *                 type="array",
 *                 description="Array of latitudes",
 *                 @OA\Items(type="number", format="float")
 *             ),
 *             @OA\Property(
 *                 property="longitudes",
 *                 type="array",
 *                 description="Array of longitudes",
 *                 @OA\Items(type="number", format="float")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Zone saved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer",
 *                 example=200
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 example={}
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Zone saved successfully"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer",
 *                 example=500
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 example={}
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Error message"
 *             )
 *         )
 *     )
 * )
 */
    public function store(Request $request){
        try {

            $request->validate([
                'latitudes' => 'required|array',
                'latitudes.*' => 'numeric',
                'longitudes' => 'required|array',
                'longitudes.*' => 'numeric',
            ]);

            $service = new Service();

            $checkAuth=$service->checkAuth();
            if($checkAuth){
               return $checkAuth;
            }

            $deliveryPersonId= $service->checkIfDeliveryAgent();

            if($deliveryPersonId == 0){

                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'Only delivery agent can accept orders'
                ],200);
            }

            $checkCoorLength = $this->checkArraySize($request->latitudes,$request->longitudes);

            if($checkCoorLength){
                return $checkCoorLength;
            }

            $zone = new Zone();
            $zone->uid = (new Service())->generateUid($zone);
            $zone->delivery_agency_id = (new Service())->returnDeliveryAgentIdOfAuth();
            $zone->save();

            (new DeliveryAgentZoneController)->addZone($zone->id, $request->latitudes, $request->longitudes);

        return response()->json([
            'status_code' => 200,
            'data' =>[],
            'message' => 'Zone saved successfully'
        ]);

        }  catch (Exception $e) {
                return response()->json([
                    'status_code' => 500,
                    'data' =>[],
                    'message' => $e->getMessage()
                ],500);
            }
    }

    public function checkArraySize($latitudes,$longitudes){
        if (count($latitudes) !== count($longitudes)) {
            return response()->json([
                'status_code' => 400,
                'data' =>[],
                'message' => "Length of arrays don't match"
            ],200);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/zone/destroy/{uid}",
     *     summary="Delete a zone by UID",
     *     tags={"Zones"},
     *  security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="UID of the zone to delete",
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
     *         description="Zone not found",
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
     *     )
     * )
     */
    public function destroy($uid){
        try {

            $zone = Zone::whereUid($uid)->first();

            if(!$zone){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'Zone not found'
                ]);
            }

            $zone->update(['deleted' => true]);

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone deleted successfully'
            ]);

        }  catch (Exception $e) {
                return response()->json([
                    'status_code' => 500,
                    'data' =>[],
                    'message' => $e->getMessage()
                ],500);
            }
    }


    /**
     * @OA\Post(
     *     path="/api/zone/makeZoneActiveOrNot/{uid}",
     *     summary="Activate or deactivate a zone by UID",
     *     tags={"Zones"},
     *  security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="UID of the zone to activate/deactivate",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zone status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Zone not found",
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
     *     )
     * )
     */
    public function makeZoneActiveOrNot($uid){
        try {

            $zone = Zone::whereUid($uid)->first();

            if(!$zone){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'Zone not found'
                ]);
            }

            if($zone->active == true){
                $zone->update(['active' => false]);
                return response()->json([
                    'status_code' => 200,
                    'data' =>[],
                    'message' => 'Zone disabled successfully'
                ]);
            }

            $zone->update(['active' => true]);

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone enabled successfully'
            ]);

        }  catch (Exception $e) {
                return response()->json([
                    'status_code' => 500,
                    'data' =>[],
                    'message' => $e->getMessage()
                ],500);
            }
    }


    /**
 * @OA\Post(
 *     path="/api/zone/update/{uid}",
 *     summary="Update a zone by UID",
 *     tags={"Zones"},
 *  security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         description="UID of the zone to update",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="city_name", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Zone updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Zone not found",
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
 *     )
 * )
 */
    public function update($uid, Request $request){
        try {
            $zone = Zone::whereUid($uid)->first();

            if(!$zone){
                return response()->json([
                    'status_code' => 400,
                    'data' =>[],
                    'message' => 'Zone not found'
                ]);
            }
            

            $zone = new Zone();
            $zone->city_name = $request->city_name??$zone->city_name;
            $zone->latitude = $request->latitude??$zone->latitude;
            $zone->longitude = $request->longitude??$zone->longitude;
            $zone->update();

            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Zone updated successfully'
            ]);

        }  catch (Exception $e) {
                return response()->json([
                    'status_code' => 500,
                    'data' =>[],
                    'message' => $e->getMessage()
                ],500);
            }
    }


    public function isWithinDeliveryZone($longitude, $latitude)
{
    $zones = DB::table('zones')
        ->join('delivery_agent_zones', 'zones.id', '=', 'delivery_agent_zones.zone_id')
        ->select('zones.id as zone_id', 'zones.delivery_agency_id', 'delivery_agent_zones.latitude', 'delivery_agent_zones.longitude', 'delivery_agent_zones.id')
        ->orderBy('delivery_agent_zones.point_order')
        ->get()
        ->groupBy('zone_id');

    $polygons = [];
    foreach ($zones as $zoneId => $points) {
        foreach ($points as $point) {
            $polygons[$zoneId][] = [
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'zone_id' => $zoneId
            ];
        }
    }


    $inPolygon = [];
    $inNotPolygon = [];

    foreach ($polygons as $zoneId => $polygon) {
        // return $polygon;
        if ($this->isPointInPolygon($longitude, $latitude, $polygon)) {
            $inPolygon[] = $zoneId;
        } else {
            $inNotPolygon[] = $zoneId;
        }
    }

    foreach($inPolygon as $zoneId){
        (new DeliveryAgencyController)->notifyDeliveryAgentsConcerned(Zone::find($zoneId)->delivery_agency->person->user_id);
    }


    // return [
    //     'inPolygon' => $inPolygon,
    //     'inNotPolygon' => $inNotPolygon
    // ];
}

private function isPointInPolygon($longitude, $latitude, $polygon)
{
    $numPoints = count($polygon);
    $inPolygon = false;
    
    for ($i = 0, $j = $numPoints - 1; $i < $numPoints; $j = $i++) {
        $xi = $polygon[$i]['longitude'];
        $yi = $polygon[$i]['latitude'];
        $xj = $polygon[$j]['longitude'];
        $yj = $polygon[$j]['latitude'];

        if ((($yi > $latitude) != ($yj > $latitude)) &&
            ($longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi)) {
            $inPolygon = !$inPolygon;
        }
    }

    return $inPolygon;
}


public function isWithinDeliveryZoneO($longitude, $latitude, Request $request)
{

    // {
//     "polygon" : [
//         {
//             "latitude": "1.000000000",
//             "longitude": "1.000000000",
//             "zone_id": 8
//         },
//         {
//             "latitude": "4.000000000",
//             "longitude": "8.000000000",
//             "zone_id": 8
//         },
//         {
//             "latitude": "5.000000000",
//             "longitude": "1.000000000",
//             "zone_id": 8
//         }
//     ]
// }

// {
//     "polygon" : [
//         {
//             "latitude": "1.000000000",
//             "longitude": "1.000000000",
//             "zone_id": 9
//         },
//         {
//             "latitude": "5.000000000",
//             "longitude": "1.000000000",
//             "zone_id": 9
//         },
//         {
//             "latitude": "3.000000000",
//             "longitude": "8.000000000",
//             "zone_id": 9
//         }
//     ]
// }
    $zones = DB::table('zones')
        ->join('delivery_agent_zones', 'zones.id', '=', 'delivery_agent_zones.zone_id')
        ->select('zones.id', 'zones.delivery_agency_id', 'delivery_agent_zones.latitude', 'delivery_agent_zones.longitude')
        ->orderBy('delivery_agent_zones.point_order')
        ->get()
        ->groupBy('zones.id');

    foreach ($zones as $zoneId => $points) {
        if ($this->isPointInPolygon($longitude, $latitude, $request->polygon)) {
            return [
                'inPolygon' => true,
                'zone_id' => $zoneId
            ];
        }
    }

    return [
        'inPolygon' => false,
        'zone_id' => null
    ];
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


// foreach ($zones as $zoneId => $points) {
    //     $polygon = $points->map(function ($point) {
    //         return [
    //             'latitude' => $point->latitude,
    //             'longitude' => $point->longitude,
    //             'zone_id' => $point->id
    //         ];
    //     })->toArray();

    

    //     return $polygon;

    //     if ($this->isPointInPolygon($longitude, $latitude, $polygon)) {
    //         return [
    //             'inPolygon' => true,
    //             'zone_id' => $zoneId
    //         ];
    //     }
    // }

    // return [
    //     'inPolygon' => false,
    //     'zone_id' => null
    // ];