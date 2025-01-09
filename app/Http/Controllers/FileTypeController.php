<?php

namespace App\Http\Controllers;

use App\Models\FileType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FileTypeController extends Controller
{

    private $validTypePersons = ['Merchant', 'Client', 'DeliveryAgent'];

    /**
 * @OA\Get(
 *     path="/api/file/type/index/{typePerson}",
 *     summary="Liste des fichiers disponibles pour un type de personne avec un filtre de statut",
 *     tags={"File Type"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="typePerson",
 *         in="path",
 *         required=true,
 *         description="Type de personne (Merchant, Client, DeliveryAgent)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         required=true,
 *         description="Statut des fichiers (all, active, inactive)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des fichiers filtrés",
 *         @OA\JsonContent(type="array", @OA\Items(ref=""))
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur"
 *     )
 * )
 */
public function index($typePerson, Request $request)
{
    try {
        $status = $request->query('status');

        $validStatuses = ['all', 'active', 'inactive'];

        if (!in_array($status, $validStatuses)) {
            return (new Service())->apiResponse(404, [], 'Invalid status value. Allowed values are: all, active, inactive.');
        }

        if ($status == 'active') {
            $fileTypes = FileType::whereDeleted(0)
                ->whereTypePerson($typePerson)
                ->whereIsActif(true)
                ->get();
        } elseif ($status == 'inactive') {
            $fileTypes = FileType::whereDeleted(0)
                ->whereTypePerson($typePerson)
                ->whereIsActif(false)
                ->get();
        } else {
            $fileTypes = FileType::whereDeleted(0)
                ->whereTypePerson($typePerson)
                ->get();
        }

        if (count($fileTypes) == 0) {
            return (new Service())->apiResponse(404, [], 'No file type found for this category of person');
        }

        return (new Service())->apiResponse(200, $fileTypes, 'List of file types for this category of person');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}



     /**
 * @OA\Get(
 *     path="/api/file/type/index",
 *     summary="Liste des fichiers disponibles avec un filtre de statut",
 *     tags={"File Type"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         required=true,
 *         description="Statut des fichiers (all, active, inactive)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des fichiers filtrés",
 *         @OA\JsonContent(type="array", @OA\Items(ref=""))
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur"
 *     )
 * )
 */
public function list(Request $request)
{
    try {
        $status = $request->query('status');

        $validStatuses = ['all', 'active', 'inactive'];

        if (!in_array($status, $validStatuses)) {
            return (new Service())->apiResponse(404, [], 'Invalid status value. Allowed values are: all, active, inactive.');
        }

        if ($status == 'active') {
            $fileTypes = FileType::whereDeleted(0)
                ->whereIsActif(true)
                ->get();
        } elseif ($status == 'inactive') {
            $fileTypes = FileType::whereDeleted(0)
                ->whereIsActif(false)
                ->get();
        } else {
            $fileTypes = FileType::whereDeleted(0)
                ->get();
        }

        if (count($fileTypes) == 0) {
            return (new Service())->apiResponse(404, [], 'No file type found');
        }

        return (new Service())->apiResponse(200, $fileTypes, 'List of file types');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


    /**
     * @OA\Get(
     *     path="/api/file/type/show/{uid}",
     *     summary="Détail d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *    @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="UID du type de fichier",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détail du type de fichier",
     *         @OA\JsonContent(ref="")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de fichier non trouvé ou déjà supprimé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */

    public function show($uid)
    {
        try{
            $fileType = FileType::whereUid($uid)->first();

            if (!$fileType) {
                return (new Service())->apiResponse(404, [], 'File type not found');
            }

            if ($fileType->deleted) {
                return (new Service())->apiResponse(404, [], 'File type already deleted');
            }


            return (new Service())->apiResponse(200, $fileType, 'File type detail');

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }

    }

    /**
     * @OA\Post(
     *     path="/api/file/type/store",
     *     summary="Création d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "format", "type_person"},
     *             @OA\Property(property="name", type="string", example="Identity Card"),
     *             @OA\Property(property="type_person", type="string", example="Client"),
     *             @OA\Property(property="is_actif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document créé avec succès",
     *         @OA\JsonContent(ref="")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nom déjà existant"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */

    public function store(Request $request, Service $service)
    {
        try{
            $request->validate([
                'name' => 'required|string',
                'type_person' => 'required',
                'is_actif' => 'nullable|boolean',
            ]);

            if(FileType::whereName($request->name)->whereTypePerson($request->type_person)->exists()){
                return (new Service())->apiResponse(404, [], 'This name of file type already exist for this type of person');
            }

            if(!in_array($request->type_person,$this->validTypePersons)){
                return (new Service())->apiResponse(404, [], 'Invalid type person. Type person should be one of: ' . implode(', ', $this->validTypePersons));
            }

            $defaultFormat =["image", "doc", "image/doc"];

            $encodedFormat = json_encode($defaultFormat);

            $fileType = new FileType();
            $fileType->name = $request->name;
            $fileType->format = $encodedFormat;
            $fileType->type_person = $request->type_person;
            $fileType->is_actif = $request->is_actif ?? true;
            $fileType->uid = $service->generateUid($fileType);

            $fileType->save();

            return (new Service())->apiResponse(200, $fileType, 'Document créé avec succès');

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/file/type/update/{uid}",
     *     summary="Mise à jour d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="UID du type de fichier",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type_person"},
     *             @OA\Property(property="name", type="string", example="Identity Card"),
     *             @OA\Property(property="type_person", type="string", example="Client"),
     *            @OA\Property(property="format", type="string", example="[gagaqssds]"),
     *             @OA\Property(property="is_actif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document mis à jour avec succès",
     *         @OA\JsonContent(ref="")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de fichier non trouvé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */

    public function update(Request $request, $uid)
    {
        try{
            $fileType = FileType::whereUid($uid)->first();

            if (!$fileType) {
                return (new Service())->apiResponse(404, [], 'File type not found');
            }

            if($request->type_person){
                if(!in_array($request->type_person,$this->validTypePersons)){
                    return (new Service())->apiResponse(404, [], 'Invalid type person. Type person should be one of: ' . implode(', ', $this->validTypePersons));
                }
            }

            if($request->format){
                if(!is_array($request->format)){
                    return (new Service())->apiResponse(404, [], 'Invalid format. Format should be an array');
                }
            }

            $fileType->name = $request->name?? $fileType->name;

            $fileType->format = $request->format?? $fileType->format;

            $fileType->type_person = $request->type_person??$fileType->type_person;
            $fileType->is_actif = $request->is_actif ?? $fileType->is_actif;

            $fileType->save();

            return (new Service())->apiResponse(200, $fileType, 'Document mis à jour avec succès');

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/api/file/type/destroy/{uid}",
     *     summary="Suppression logique d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="UID du type de fichier",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document supprimé avec succès",
     *         @OA\JsonContent(type="object", @OA\Property(property="message", type="string", example="Document supprimé avec succès"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Enregistrement non trouvé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */

    public function destroy($uid)
    {
        try{
            $fileType = FileType::whereUid($uid)->first();

            if (!$fileType) {
                return (new Service())->apiResponse(404, [], 'File type not found');
            }
    
            $fileType->deleted = true;
            $fileType->save();

            return (new Service())->apiResponse(404, [], 'File type delete successfully');

            }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    /**
 * @OA\Post(
 *     path="/api/file/type/deactivate/{uid}",
 *     summary="Désactiver un type de fichier",
 *     tags={"File Type"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         description="UID du type de fichier",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Type de fichier désactivé avec succès",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Type de fichier non trouvé ou déjà supprimé"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur"
 *     )
 * )
 */
public function deactivate($uid)
{
    try {
        $fileType = FileType::whereUid($uid)->first();

        if (!$fileType) {
            return (new Service())->apiResponse(404, [], 'File type not found');
        }

        if ($fileType->deleted) {
            return (new Service())->apiResponse(404, [], 'File type already deleted');
        }

        $fileType->is_actif = false;
        $fileType->save();

        return (new Service())->apiResponse(200, $fileType, 'File type deactivated successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

/**
 * @OA\Post(
 *     path="/api/file/type/activate/{uid}",
 *     summary="Activer un type de fichier",
 *     tags={"File Type"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="uid",
 *         in="path",
 *         required=true,
 *         description="UID du type de fichier",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Type de fichier activé avec succès",
 *         @OA\JsonContent(ref="")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Type de fichier non trouvé ou déjà supprimé"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur"
 *     )
 * )
 */
public function activate($uid)
{
    try {
        $fileType = FileType::whereUid($uid)->first();

        if (!$fileType) {
            return (new Service())->apiResponse(404, [], 'File type not found');
        }

        if ($fileType->deleted) {
            return (new Service())->apiResponse(404, [], 'File type already deleted');
        }

        $fileType->is_actif = true;
        $fileType->save();

        return (new Service())->apiResponse(200, $fileType, 'File type activated successfully');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}

}


// try{

// }catch(Exception $e){
//     return (new Service())->apiResponse(500, [], $e->getMessage());
// }