public function storeZone(Request $request)
{
    $zone = DeliveryAgentZone::create([
        'uid' => $request->input('uid'),
        'delivery_agency_id' => $request->input('delivery_agency_id'),
        'latitude' => $request->input('latitude'), // Optionnel
        'longitude' => $request->input('longitude'), // Optionnel
        'active' => true
    ]);

    $polygons = $request->input('polygons'); // Expects an array of ['latitude' => ..., 'longitude' => ..., 'point_order' => ...]
    foreach ($polygons as $index => $polygon) {
        DeliveryAgentZonePolygon::create([
            'delivery_agent_zone_id' => $zone->id,
            'latitude' => $polygon['latitude'],
            'longitude' => $polygon['longitude'],
            'point_order' => $index
        ]);
    }

    return response()->json(['message' => 'Zone de service créée avec succès']);
}


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
