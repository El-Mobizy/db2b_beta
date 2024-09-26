<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/address/createAddress",
 *     summary="Create a new address for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Home"),
 *             @OA\Property(property="latitude", type="number", example="40.712776"),
 *             @OA\Property(property="longitude", type="number", example="-74.005974"),
 *             @OA\Property(property="formatted_address", type="string", example="New York, NY, USA"),
 *             @OA\Property(property="place_id", type="string", example="ChIJrTLr-GyuEmsRBfy61i59si0"),
 *             @OA\Property(property="is_default", type="boolean", example=true),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Address created successfully",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
    public function createAddress(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'formatted_address' => 'nullable|string',
            'place_id' => 'nullable|string',
            'is_default' => 'required|boolean',
        ]);

        $user = Auth::user();

        if ($request->is_default) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        if(Address::whereUserId(Auth::user()->id)->whereName($request->name)->exists()){
            return (new Service())->apiResponse(404, [], 'Name already used');
        }

        if(Address::whereUserId(Auth::user()->id)->wherePlaceId($request->place_id)->exists()){
            return (new Service())->apiResponse(404, [], 'Place id already used');
        }

        if(Address::whereUserId(Auth::user()->id)->whereLatitude($request->latitude)->whereLongitude($request->longitude)->exists()){
            return (new Service())->apiResponse(404, [], 'You already have a registered address with the same longitude and the same attitude entered');
        }

        $address = new Address();
        $address->user_id = $user->id;
        $address->name = $request->name;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->formatted_address = $request->formatted_address;
        $address->place_id = $request->place_id;
        $address->is_default = $request->is_default ;
        $address->uid =(new Service())->generateUid($address);
        $address->save();

        return (new Service())->apiResponse(200, $address, 'Address created successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}
/**
 * @OA\Get(
 *     path="/api/address/getAddress/{addressUid}",
 *     summary="Get a specific address by UID for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="addressUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the address"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Detail of specific address",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Address not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function getAddress($addressUid)
{
    try {
        if((new Service())->isValidUuid($addressUid)){
            return (new Service())->isValidUuid($addressUid);
        }
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->whereUid($addressUid)->first();

        if (!$address) {
            return (new Service())->apiResponse(200, [], 'Address not found');
        }

        return (new Service())->apiResponse(200, $address, 'Detail of specific address');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}
/**
 * @OA\Get(
 *     path="/api/address/getAllAuthAddresses",
 *     summary="Get all addresses for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Auth user addresses",
 *         @OA\JsonContent(type="array", @OA\Items(ref=""))
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function getAllAuthAddresses()
{
    try {
        $user = Auth::user();
        $addresses = Address::where('user_id', $user->id)->get();

        return (new Service())->apiResponse(200, $addresses, 'Auth user addresses');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Get(
 *     path="/api/address/getAllUserAddresses/{userUid}",
 *     summary="Get all addresses for a specific user by their UID",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="userUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the user"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Specific user addresses",
 *         @OA\JsonContent(type="array", @OA\Items(ref=""))
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function getAllUserAddresses($userUid)
{
    try {
        $user = User::whereUid($userUid)->first();
        if (!$user) {
            return (new Service())->apiResponse(404, [], 'User not found');
        }

        $addresses = Address::where('user_id', $user->id)->get();

        return (new Service())->apiResponse(200, $addresses, 'Specific user addresses');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/address/updateAddress/{addressUid}",
 *     summary="Update a specific address by UID for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="addressUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the address"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Home"),
 *             @OA\Property(property="latitude", type="number", example="40.712776"),
 *             @OA\Property(property="longitude", type="number", example="-74.005974"),
 *             @OA\Property(property="formatted_address", type="string", example="New York, NY, USA"),
 *             @OA\Property(property="place_id", type="string", example="ChIJrTLr-GyuEmsRBfy61i59si0"),
 *             @OA\Property(property="is_default", type="boolean", example=true),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Address updated successfully",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Address not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function updateAddress($addressUid, Request $request)
{
    try {
        $request->validate([
            'name' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'formatted_address' => 'nullable|string',
            'place_id' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->whereUid($addressUid)->first();

        if (!$address) {
            return (new Service())->apiResponse(404, [], 'Address not found');
        }

        if ($request->is_default) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address->name = $request->name ?? $address->name;
        $address->latitude = $request->latitude ?? $address->latitude;
        $address->longitude = $request->longitude ?? $address->longitude;
        $address->formatted_address = $request->formatted_address ?? $address->formatted_address;
        $address->place_id = $request->place_id ?? $address->place_id;
        $address->is_default = $request->is_default ?? $address->is_default;
        $address->save();

        return (new Service())->apiResponse(200, $address, 'Address updated successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


/**
 * @OA\Post(
 *     path="/api/address/deleteAddress/{id}",
 *     summary="Delete a specific address by ID for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="The ID of the address"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Address deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Address not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function deleteAddress($id)
{
    try {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->where('id', $id)->first();

        if (!$address) {
            return (new Service())->apiResponse(200, [], 'Address not found');
        }

        $address->delete();

        return (new Service())->apiResponse(200, [], 'Address deleted successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/address/activateAddress/{addressUid}",
 *     summary="Activate a specific address by UID for the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="addressUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the address"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Address activated successfully",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Address not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function activateAddress($addressUid)
{
    try {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->whereUid($addressUid)->first();

        if (!$address) {
            return (new Service())->apiResponse(404, [], 'Address not found');
        }

        Address::where('user_id', $user->id)->update(['is_default' => false]);

        $address->is_default = true;
        $address->save();

        return (new Service())->apiResponse(200, $address, 'Address activated successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Get(
 *     path="/api/address/getActiveService",
 *     summary="Get active service of the authenticated user",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with active service",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="data", type="object", example={"id": 1, "user_id": 5, "is_default": true}),
 *             @OA\Property(property="message", type="string", example="Active service retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No active service found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="data", type="object", example={}),
 *             @OA\Property(property="message", type="string", example="No active service found")
 *         )
 *     )
 * )
 */
public function getActiveService()
{
    try {
        $user = Auth::user();
        $activeService = Address::where('user_id', $user->id)
                                ->where('is_default', true)
                                ->first();

        if (!$activeService) {
            return (new Service())->apiResponse(404, [], 'No active service found');
        }

        return (new Service())->apiResponse(200, $activeService, 'Active service retrieved successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


/**
 * @OA\Get(
 *     path="/api/address/getUserActiveService/{userUid}",
 *     summary="Get active service of a specific user by UID",
 *     tags={"Address"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="userUid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="UID of the user"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with active service",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="data", type="object", example={"id": 1, "user_id": 5, "is_default": true}),
 *             @OA\Property(property="message", type="string", example="Active service retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No active service found for the specified user",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="data", type="object", example={}),
 *             @OA\Property(property="message", type="string", example="No active service found")
 *         )
 *     )
 * )
 */
public function getUserActiveService($userUid)
{
    try {
        $user = User::where('uid', $userUid)->first();

        if (!$user) {
            return (new Service())->apiResponse(404, [], 'User not found');
        }

        $activeService = Address::where('user_id', $user->id)
                                ->where('is_default', true)
                                ->first();

        if (!$activeService) {
            return (new Service())->apiResponse(404, [], 'No active service found');
        }

        return (new Service())->apiResponse(200, $activeService, 'Active service retrieved successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}



}


