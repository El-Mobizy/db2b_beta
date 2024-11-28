<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;

class SettingController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/settings/create",
 *     summary="Create a new setting",
 *     description="Adds a new setting to the system. Only accessible by admins.",
 *     tags={"Settings"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="site_name", description="The name of the setting"),
 *             @OA\Property(property="value", type="string", example="My Website", description="The value of the setting"),
 *             @OA\Property(property="type", type="string", example="string", description="The type of the setting. Allowed types: string, integer, double")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Setting created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Setting created successfully"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Invalid type value",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Invalid type value. Allowed types are 'string', 'integer', and 'double'"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

    public function createSetting(Request $request)
    {
        (new Service())->checkAdmin();
        try {
            $request->validate([
                'name' => 'required|string|unique:settings,name',
                'value' => 'required',
                'type' => 'required|string',
            ]);

            $typeAvailable = ['string', 'integer','double'];

            if (!in_array($request->type, $typeAvailable)) {
                return (new Service())->apiResponse(404, [], 'Invalid type value. Allowed types are "string", "integer", and "double"');
            }

            if ($request->type === 'string' && !is_string($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be a string when type is "string"');
            }

            if ($request->type === 'integer' && !is_int($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be an integer when type is "integer"');
            }

            if ($request->type === 'double' && !is_float($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be a double when type is "double"');
            }

            if (!in_array($request->type, $typeAvailable)) {
                return (new Service())->apiResponse(404, [], 'Invalid type value. Allowed types are "string" and "integer"');
            }


            $setting = new Setting();
            $setting->uid = (new Service())->generateUid($setting);
            $setting->name = $request->name;
            $setting->value = $request->value;
            $setting->type = $request->type;
            $setting->save();

            return (new Service())->apiResponse(200, [], 'Setting created successfully');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Get(
 *     path="/api/settings/show/{uid}",
 *     summary="Get a specific setting",
 *     description="Retrieve details of a specific setting by its UID.",
 *     tags={"Settings"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the setting"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Specific settings details",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Specific settings details"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Setting not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Setting not found"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

    public function getSetting($uid)
    {
        try {
            $setting = Setting::where('uid', $uid)->whereDeleted(false)->first();

            if (!$setting) {
                return (new Service())->apiResponse(404, [], 'Setting not found');
            }

            return (new Service())->apiResponse(200, [$setting], 'Specific settings details');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Get(
 *     path="/api/settings/list",
 *     summary="List all settings",
 *     description="Retrieve a list of all settings. Only accessible by admins.",
 *     tags={"Settings"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of settings",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="List of settings"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

    public function getAllSettings()
    {
        (new Service())->checkAdmin();
        try {
            $settings = Setting::whereDeleted(false)->get();

            return (new Service())->apiResponse(200, ['settings'=>$settings], 'List of settings');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Post(
 *     path="/api/settings/update/{uid}",
 *     summary="Update a setting",
 *     description="Update details of an existing setting. Only accessible by admins.",
 *     tags={"Settings"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the setting to update"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="site_name", description="The new name of the setting"),
 *             @OA\Property(property="value", type="string", example="New Value", description="The new value of the setting"),
 *             @OA\Property(property="type", type="string", example="string", description="The new type of the setting. Allowed types: string, integer, double")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Setting updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Setting updated successfully"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Setting not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Setting not found"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

    public function updateSetting($uid, Request $request)
{
    (new Service())->checkAdmin();
    try {
        $setting = Setting::where('uid', $uid)->first();

        if (!$setting) {
            return (new Service())->apiResponse(404, [], 'Setting not found');
        }

        $typeAvailable = ['string', 'integer', 'double'];

        $request->validate([
            'name' => 'nullable|string|unique:settings,name,' . $setting->id,
            'value' => 'nullable',
            'type' => 'nullable|string',
        ]);

        if ($request->has('type') && !in_array($request->type, $typeAvailable)) {
            return (new Service())->apiResponse(404, [], 'Invalid type value. Allowed types are "string", "integer", and "double"');
        }

        if ($request->has('type')) {
            if ($request->type === 'string' && !is_string($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be a string when type is "string"');
            }

            if ($request->type === 'integer' && !is_int($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be an integer when type is "integer"');
            }

            if ($request->type === 'double' && !is_float($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be a double when type is "double"');
            }
        }

        if ($request->name) {
            $setting->name = $request->name;
        }

        if ($request->type) {
            $setting->type = $request->type;
        }

        if ($request->value) {
            if(!$request->type){
                if ($setting->type === 'string' && !is_string($request->value)) {
                    return (new Service())->apiResponse(404, [], 'Value must be a string when type is "string"');
                }
    
                if ($setting->type === 'integer' && !is_int($request->value)) {
                    return (new Service())->apiResponse(404, [], 'Value must be an integer when type is "integer"');
                }
    
                if ($setting->type === 'double' && !is_float($request->value)) {
                    return (new Service())->apiResponse(404, [], 'Value must be a double when type is "double"');
                }
                $setting->value = $request->value;
            }
        }
        $setting->save();

        return (new Service())->apiResponse(200, [$setting], 'Specific settings details');

    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/settings/delete/{uid}",
 *     summary="Delete a setting",
 *     description="Delete a setting by its UID. Only accessible by admins.",
 *     tags={"Settings"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The UID of the setting to delete"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Setting deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Setting deleted successfully"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Setting not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Setting not found"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

    public function deleteSetting($uid)
    {
        (new Service())->checkAdmin();
        try {
            $setting = Setting::where('uid', $uid)->first();

            if (!$setting) {
                return (new Service())->apiResponse(404, [$setting], 'Seting ');

            }

            $setting->delete();

            return (new Service())->apiResponse(200, [], 'Setting deleted successfully');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
}
