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
            // $path = $photo->store($location, 'public');
            $photoUrl = url("/image/$location/" . $photoName);
            $type = $photo->getClientOriginalExtension();
            $locationFile = $photoUrl;
            $referencecode = $randomString;
            $filename = md5(uniqid()) . '.' . $type;
            $uid = $ulidPhoto;
            $q = "INSERT INTO files (filename, type, location, size, referencecode, uid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $db->prepare($q);
            $stmt->bindParam(1, $filename);
            $stmt->bindParam(2, $type);
            $stmt->bindParam(3, $locationFile);
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
 * @OA\Get(
 *     path="/api/file/getFilesByFileCode/{file}/{returnSingleFile}",
 *     summary="Get files by file code",
 *     tags={"File"},
 *     @OA\Parameter(
 *         name="filecode",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="File code"
 *     ),
 *     @OA\Parameter(
 *         name="returnSingleFile",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer", default=0),
 *         description="Return single file (0 or 1)"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Files retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="")),
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="File not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="File not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Internal server error message")
 *         )
 *     )
 * )
 */
    public function getFilesByFileCode($filecode,$returnSingleFile = 0)
    {

        if(!File::whereReferencecode($filecode)->first()){
            return response()->json([
                'message' => 'File not found'
            ]);
        }

        if($returnSingleFile == 1){
            return response()->json([
                'data' => File::whereReferencecode($filecode)->whereDeleted(0)->first()
            ]);
        }

        return response()->json([
            'data' => File::whereReferencecode($filecode)->whereDeleted(0)->get()
        ]);
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
