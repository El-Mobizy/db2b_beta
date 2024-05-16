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
            $db = DB::connection()->getPdo();
            $request->validate([
                'libelle' => 'required'
            ]);

            $help = new CategoryController();
            $randomString =  $help->generateRandomAlphaNumeric(15);
            $ulid = Uuid::uuid1();
            $ulidType = $ulid->toString();

            $type = new TypeOfType();
            $type->libelle = $request->libelle;
            $type->uid = $ulidType;
            if ($request->has('parent_id')) {
                $type->parent_id = $request->parent_id;
            }
            if ($request->hasFile('files')) {
                $type->codereference = $randomString;

                foreach($request->file('files') as $index => $photo){
                    $size = filesize($photo);
                    $ulid = Uuid::uuid1();
                    $ulidPhoto = $ulid->toString();
                    $created_at = date('Y-m-d H:i:s');
                    $updated_at = date('Y-m-d H:i:s');
                    $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
                    $photoPath = $photo->move(public_path('image/photo_type'), $photoName);
                    $photoUrl = url('/image/photo_type/' . $photoName);
                    $type = $photo->getClientOriginalExtension();
                    $location = $photoPath;
                    $referencecode = $randomString;
                    $filename = md5(uniqid()) . '.' . $type;
                    $uid = $ulidPhoto;
                    $q = "INSERT INTO files (filename, type, location, size, referencecode, uid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)";
                    $stmt = $db->prepare($q);
                    $stmt->bindParam(1, $filename);
                    $stmt->bindParam(2, $type);
                    $stmt->bindParam(3, $location);
                    $stmt->bindParam(4,  $size);
                    $stmt->bindParam(5,  $referencecode);
                    $stmt->bindParam(6,  $uid);
                    $stmt->bindParam(7,  $created_at);
                    $stmt->bindParam(8,  $updated_at);
                    $stmt->execute();
                }
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
