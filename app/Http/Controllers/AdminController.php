<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function create($id){
        $admin = new Admin();
        $admin->person_id = $id;
        $admin->save();
        return  (new Service())->apiResponse(200,[],'save successfully');
        // return response()->json(['message' => 'save successfully']);
    }

    public function delete($id){
        Admin::find($id)->delete();
        return  (new Service())->apiResponse(200,[],'delete successfully');
        // return response()->json(['message' => 'delete successfully']);
    }
}
