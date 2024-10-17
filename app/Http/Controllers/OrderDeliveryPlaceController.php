<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderDeliveryPlace;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderDeliveryPlaceController extends Controller
{
    public function createDeliveryPlace($orderId, $addressId)
    {
        try {

            $deliveryPlace = new OrderDeliveryPlace();
            $deliveryPlace->uid =  (new Service())->generateUid($deliveryPlace);
            $deliveryPlace->order_id = $orderId;
            $deliveryPlace->address_id = $addressId;
            $deliveryPlace->deleted = false;
            $deliveryPlace->save();

            return (new Service())->apiResponse(200, $deliveryPlace, 'Delivery place created successfully.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Post(
 *     path="/api/deliveryplace/updateDeliveryPlaceAddress/{uuid}",
 *     summary="Update the address of a specific delivery place",
 *     description="This endpoint allows a user to update the address associated with a specific delivery place.",
 *     tags={"Order Delivery Place"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="The UUID of the delivery place",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="address_id", type="integer", description="ID of the new address")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Delivery place updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Delivery place updated successfully."),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Delivery place or address not found or unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Delivery place not found.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred.")
 *         )
 *     )
 * )
 */

    public function updateDeliveryPlaceAddress(Request $request, $uuid)
    {
        try {
            $request->validate([
                'address_id' => 'required|exists:addresses,id',
            ]);

            if((new Service())->isValidUuid($uuid)){
                return (new Service())->isValidUuid($uuid);
            }

            $deliveryPlace = OrderDeliveryPlace::where('uuid', $uuid)->first();

            $address = Address::whereId($request->address_id)->first();
            
            if(!$deliveryPlace){
                return (new Service())->apiResponse(404, $deliveryPlace, 'Delivery place not found.');
            }

            if(!$address){
                return (new Service())->apiResponse(404, $address, 'address not found.');
            }

            if(Auth::user()->id != Order::whereId($deliveryPlace->order_id)->first()->user_id){
                return (new Service())->apiResponse(404, $deliveryPlace, "This delivery order place don't belongs to you");
            }

            if(Auth::user()->id != $address->user_id){
                return (new Service())->apiResponse(404, $deliveryPlace, "This address don't belongs to you");
            }

            $deliveryPlace->address_id = $request->address_id;
            $deliveryPlace->save();

            return (new Service())->apiResponse(200, $deliveryPlace, 'Delivery place updated successfully.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Get(
 *     path="/api/deliveryplace/getUserDeliveryPlaces",
 *     summary="Get all delivery places of the connected user",
 *     description="This endpoint retrieves all delivery places associated with the connected user.",
 *     tags={"Order Delivery Place"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="User delivery places retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="User delivery order place list."),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred.")
 *         )
 *     )
 * )
 */

    public function getUserDeliveryPlaces()
    {
        try {
            $userId = Auth::id();

            $deliveryPlaces = OrderDeliveryPlace::whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with('address')->get();

            return (new Service())->apiResponse(200, $deliveryPlaces, 'User delivery order place listr.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }



}
