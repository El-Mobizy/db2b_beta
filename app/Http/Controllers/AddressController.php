<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function createAddress(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'formatted_address' => 'nullable|string',
            'place_id' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        if ($request->is_default) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = new Address();
        $address->user_id = $user->id;
        $address->name = $request->name;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->formatted_address = $request->formatted_address;
        $address->place_id = $request->place_id;
        $address->is_default = $request->is_default ?? false;
        $address->save();

        return (new Service())->apiResponse(200, $address, 'Address created successfully');
    }

    public function getAddress($addressUid)
    {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->whereUid($addressUid)->first();

        if (!$address) {
            return (new Service())->apiResponse(200, [], 'Address not found');
        }


        return (new Service())->apiResponse(200, $address, 'Detail of specific address');
    }

    public function getAllAuthAddresses()
    {
        $user = Auth::user();
        $addresses = Address::where('user_id', $user->id)->get();

        return (new Service())->apiResponse(200, $addresses, 'Auth user addresses');
    }

    public function getAllUserAddresses($userUid)
    {
        $user = User::whereUid($userUid)->first();
        if(!$user){
            return (new Service())->apiResponse(404, [], 'User not found');
        }
        $addresses = Address::where('user_id', $user->id)->get();

        return (new Service())->apiResponse(200, $addresses, 'specific user addresses');

    }

    public function updateAddress($addressUid, Request $request)
    {
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

    }

    public function deleteAddress($id)
    {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->where('id', $id)->first();

        if (!$address) {

            return (new Service())->apiResponse(200, $address, 'Address not found');
        }

        $address->delete();

        return (new Service())->apiResponse(200, [], 'Address deleted successfully');
    }




}
