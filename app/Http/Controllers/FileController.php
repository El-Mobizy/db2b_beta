<?php

namespace App\Http\Controllers;

use App\Interfaces\Interfaces\FileRepositoryInterface;
use App\Models\File;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FileController extends Controller
{

    function checkImageExtension($photo)
{
    

    return true;
}




    public function store( $photo, $randomString, $location)
    {
        try {
            $db = DB::connection()->getPdo();


                            $size = filesize($photo);
                            $ulid = Uuid::uuid1();
                            $ulidPhoto = $ulid->toString();
                            $created_at = date('Y-m-d H:i:s');
                            $updated_at = date('Y-m-d H:i:s');
                            $photoName = uniqid() . '.' . $photo->getClientOriginalExtension();
                            $photoPath = $photo->move(public_path("image/$location"), $photoName);
                            $photoUrl = url("/image/$location/" . $photoName);
                            $type = $photo->getClientOriginalExtension();
                            $location = $photoUrl;
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

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        //
    }
}
