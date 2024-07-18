<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;

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
     *     summary="Create a new zone",
     *     tags={"Zones"},
     *  security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"city_name", "latitude", "longitude", "country_id"},
     *             @OA\Property(property="city_name", type="string"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="country_id", type="integer", description="ID of the country where the zone is located")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zone saved successfully",
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

    public function store(Request $request){
        try {
            $request->validate([
                'city_name' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'country_id' => 'required|exists:countries,id',
            ]);

        if(Zone::whereCityName($request->city_name)->exists()){
            return response()->json([
                'status_code' => 200,
                'data' =>[],
                'message' => 'Name Already taken'
            ]);
        }
        $service = new Service();
        $zone = new Zone();
        $zone->city_name = $request->city_name;
        $zone->latitude = $request->latitude;
        $zone->longitude = $request->longitude;
        $zone->country_id = $request->country_id;
        $zone->uid = $service->generateUid($zone);

        $zone->save();

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
