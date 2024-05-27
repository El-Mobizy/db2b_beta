<?php

namespace App\Http\Controllers;

use App\Models\TypeOfType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class TypeOfTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'libelle' => 'required|unique:type_of_types',
                'codereference'
            ]);

            $service = new Service();

            $type = new TypeOfType();
            $type->libelle = $request->libelle;
            $type->uid =  $service->generateUid($type);
            $type->codereference = $request->codereference??null;
            if ($request->has('parent_id')) {
                $type->parent_id = $request->parent_id;
            }
           
            $type->save();

            return response()->json([
                'message' => "type of type created successfuly"
            ],200);

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }


    }

    /**
     * Display the specified resource.
     */
    public function show(TypeOfType $typeOfType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TypeOfType $typeOfType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeOfType $typeOfType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeOfType $typeOfType)
    {
        //
    }
}
