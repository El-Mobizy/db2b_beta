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
     *     path="/api/fileType/index/{typePerson}",
     *     summary="Liste des fichiers actifs disponibles pour un type de personne",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="typePerson",
     *         in="path",
     *         required=true,
     *         description="Type de personne (Merchant, Client, DeliveryAgent)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des fichiers actifs",
     *         @OA\JsonContent(type="array", @OA\Items(ref=""))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */

    public function index($typePerson)
    {
        try{
            $fileTypes = FileType::whereDeleted(0)->whereTypePerson($typePerson)->whereIsActif(true)->get();

            return (new Service())->apiResponse(200, $fileTypes, 'Liste des fichiers actif disponible pour un type de personne');
        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
        
    }

    /**
     * @OA\Get(
     *     path="/api/fileType/show/{id}",
     *     summary="Détail d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du type de fichier",
     *         @OA\Schema(type="integer")
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

    public function show($id)
    {
        try{
            $fileType = FileType::whereId($id)->first();

            if (!$fileType) {
                return (new Service())->apiResponse(404, [], 'File type not found');
            }

            if ($fileType->deleted) {
                return (new Service())->apiResponse(404, [], 'File type already deleted');
            }

            if (!$fileType->is_actif) {
                return (new Service())->apiResponse(404, [], 'File type not actif');
            }

            return (new Service())->apiResponse(200, $fileType, 'File type detail');

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }

    }

    /**
     * @OA\Post(
     *     path="/api/fileType/store",
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

           
            if(FileType::whereName($request->name)->exists()){
                return (new Service())->apiResponse(404, [], 'Name already exist');
            } 

            if(!in_array($request->type_person,$this->validTypePersons)){
                return (new Service())->apiResponse(404, [], 'Invalid type person. Type person should be one of: ' . implode(', ', $this->validTypePersons));
            }

            $defaultFormat =["image", "doc", "image/doc"];
            
            $fileType = new FileType();
            $fileType->name = $request->name;
            $fileType->format = $defaultFormat;
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
     *     path="/api/fileType/update/{id}",
     *     summary="Mise à jour d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du type de fichier",
     *         @OA\Schema(type="integer")
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

    public function update(Request $request, $id)
    {
        try{
            // return $request->format;
            $fileType = FileType::whereId($id)->first();
            
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
     *     path="/api/fileType/destroy/{id}",
     *     summary="Suppression logique d'un type de fichier",
     *     tags={"File Type"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du type de fichier",
     *         @OA\Schema(type="integer")
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

    public function destroy($id)
    {
        try{

        }catch(Exception $e){
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
        $fileType = FileType::find($id);

        if (!$fileType) {
            return response()->json(['message' => 'Enregistrement non trouvé'], 404);
        }

        $fileType->deleted = true;
        $fileType->save();

        return response()->json(['message' => 'Document supprimé avec succès']);
    }
}


// try{

// }catch(Exception $e){
//     return (new Service())->apiResponse(500, [], $e->getMessage());
// }