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
        return response()->json(['message' => 'save successfully']);
    }

    public function delete($id){
        Admin::find($id)->delete();
        return response()->json(['message' => 'delete successfully']);
    }
}
