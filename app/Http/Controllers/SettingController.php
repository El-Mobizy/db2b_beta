<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;

class SettingController extends Controller
{
     /**
     * Crée un nouveau paramètre
     */
    public function createSetting(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:settings,name',
                'value' => 'required|string',
                'type' => 'nullable|string',
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

            if ($request->type === 'double' && !is_numeric($request->value)) {
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


    public function getAllSettings()
    {
        try {
            $settings = Setting::whereDeleted(false)->get();

            return (new Service())->apiResponse(200, ['settings'=>$settings], 'List of settings');

        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    public function updateSetting($uid, Request $request)
{
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

            if ($request->type === 'integer' && !is_numeric($request->value)) {
                return (new Service())->apiResponse(404, [], 'Value must be an integer when type is "integer"');
            }

            if ($request->type === 'double' && !filter_var($request->value, FILTER_VALIDATE_FLOAT)) {
                return (new Service())->apiResponse(404, [], 'Value must be a double when type is "double"');
            }
        }

        if ($request->name) {
            $setting->name = $request->name;
        }

        if ($request->value) {
            $setting->value = $request->value;
        }

        if ($request->type) {
            $setting->type = $request->type;
        }

        $setting->save();

        return (new Service())->apiResponse(200, [$setting], 'Specific settings details');

    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}



    public function deleteSetting($uid)
    {
        try {
            $setting = Setting::where('uid', $uid)->first();

            if (!$setting) {
                return (new Service())->apiResponse(404, [$setting], 'Seting ');

            }

            $setting->delete();

            return response()->json(['status' => 200, 'message' => 'Setting deleted successfully']);
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }
}
