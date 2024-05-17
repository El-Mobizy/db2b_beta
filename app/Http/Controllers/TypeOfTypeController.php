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
                'libelle' => 'required|unique:type_of_types'
            ]);

            $help = new CategoryController();
            $randomString =  $help->generateRandomAlphaNumeric(15);
            $ulid = Uuid::uuid1();
            $ulidType = $ulid->toString();

            $type = new TypeOfType();
            $type->libelle = $request->libelle;
            $type->uid = $ulidType;
            $type->codereference = $randomString;
            if ($request->has('parent_id')) {
                $type->parent_id = $request->parent_id;
            }
            if($request->hasFile('files')){
                foreach($request->file('files') as $index => $photo){

                    $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        
                    $extension = $photo->getClientOriginalExtension();
        
                    if (!in_array($extension, $allowedExtensions)) {
                        return response()->json(['message' =>"Veuillez télécharger une image (jpeg, jpg, png, gif)." ]);
                    }
                }

                $file = new FileController();
                $file->store($request,$randomString,"typeoftype");
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
