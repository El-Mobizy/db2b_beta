<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileType;
use App\Models\PersonFile;
use Exception;
use Illuminate\Http\Request;

class PersonFileController extends Controller
{

    public function show($id)
    {
        try {
            $personFile = PersonFile::whereId($id)->first();

            if (!$personFile || $personFile->deleted) {
                return (new Service())->apiResponse(404, [], 'Person file not found.');
            }


            $personId = (new Service())->returnPersonIdAuth();

            // if($personFile->person_id != $personId){
            //     return (new Service())->apiResponse(404, [], 'You cannot delete this file because it\'s not belonging to you.');
            // }

            $personFile->file = File::whereReferenceCode($personFile->filecode)->whereDeleted(false)->first()->location;
            $personFile->is_validated = ($personFile->validated_by != null && $personFile->validated_on != null) ? true : false;

            return (new Service())->apiResponse(200, $personFile, 'Détails du fichier de personne récupérés avec succès.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function store(Request $request)
{
    try {
        $request->validate([
            'data' => 'required|array',
            'data.*.file_type_id' => 'required',
            'data.*.images' => 'required|file',
        ]);

        $personId = (new Service())->returnPersonIdAuth();

        foreach($request->data as $item){
            $fileType = FileType::whereId($item['file_type_id'])->first();

            if(!$fileType){
                return (new Service())->apiResponse(404, $item['file_type_id'], 'File type not found');
            }

            if($fileType->deleted){
                return (new Service())->apiResponse(404, $item['file_type_id'], 'File type already deleted');
            }

            if(!$fileType->is_actif){
                return (new Service())->apiResponse(404, $item['file_type_id'], 'File type not enabled');
            }
        }

        foreach ($request->data as $item) {
            $personFile = new PersonFile();
            $personFile->file_type_id = $item['file_type_id'];
            $personFile->person_id = $personId;
            $personFile->filecode = (new Service())->generateRandomAlphaNumeric(7, $personFile, 'filecode');
            $personFile->uid = (new Service())->generateUid($personFile);

            (new Service())->uploadFiles($item['images'], $personFile->filecode, "personFile");

            $personFile->save();
        }

        return (new Service())->apiResponse(200, [], 'Fichiers de personne créés avec succès.');
    } catch (Exception $e) {
        return (new Service())->apiResponse(500, [], $e->getMessage());
    }
}


    public function updatePersonFile(Request $request, $id)
    {
        try {
            $personFile = PersonFile::find($id);

            if (!$personFile || $personFile->deleted) {
                return (new Service())->apiResponse(404, [], 'Fichier de personne non trouvé.');
            }

            $personId = (new Service())->returnPersonIdAuth();

            if($personFile->person_id != $personId){
                return (new Service())->apiResponse(404, [], 'You cannot delete this file because it\'s not belonging to you.');
            }

            if($personFile->validated_by != null && $personFile->validated_on != null){
                return (new Service())->apiResponse(404, [], 'You cannot update this file because it\'s validated yet.');
            }

            File::whereReferenceCode($personFile->filecode)->whereDeleted(false)->update([
                'deleted' => true
            ]);

            (new Service())->uploadFiles($request->images, $personFile->filecode, "personFile");

            return (new Service())->apiResponse(200, $personFile, 'Fichier de personne mis à jour avec succès.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $personFile = PersonFile::find($id);

            if (!$personFile) {
                return (new Service())->apiResponse(404, [], 'Person file not found.');
            }

            $personId = (new Service())->returnPersonIdAuth();

            if($personFile->person_id != $personId){
                return (new Service())->apiResponse(404, [], 'You cannot delete this file because it\'s not belonging to you.');
            }

            if($personFile->validated_by != null && $personFile->validated_on != null){
                return (new Service())->apiResponse(404, [], 'You cannot delete this file because it\'s validated yet.');
            }

            $personFile->deleted = true;
            $personFile->save();

            return (new Service())->apiResponse(200, [], 'Fichier de personne supprimé avec succès.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

    public function getAuthFile(){
        try {
            $personId = (new Service())->returnPersonIdAuth();
            $personFiles = PersonFile::where('person_id',$personId)->get();

            foreach($personFiles as $personFile){
                $personFile->file = File::whereReferenceCode($personFile->filecode)->whereDeleted(false)->first()->location;
                $personFile->is_validated = ($personFile->validated_by != null && $personFile->validated_on != null) ? true : false;
            }

            return (new Service())->apiResponse(200, $personFiles, 'Liste des fichiers de personnes spécifiés avec leur statut.');
        } catch (Exception $e) {
            return (new Service())->apiResponse(500, [], $e->getMessage());
        }
    }

}
