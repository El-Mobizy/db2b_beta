<?php

namespace App\Http\Controllers;

use App\Models\Right;
use Illuminate\Http\Request;
use App\Interfaces\RightRepositoryInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RightController extends Controller
{

    private RightRepositoryInterface $rightRepository;

    public function __construct(RightRepositoryInterface $rightRepository)
    {
        $this->rightRepository = $rightRepository;
    }
    public function index():JsonResponse
    {
        try{
           return response()->json([
            'data' =>$this->rightRepository->getAll()
           ]);
        } catch(Exception $e) {
            return response()->json($e->getMessage());
        }
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
    public function store(Request $request):JsonResponse
    {
        try{
            return response()->json([
                'data' =>$this->rightRepository->store($request)
               ]);

         } catch(Exception $e) {
             return response()->json($e->getMessage());
         }
    }


    public function update(Request $request,  $id)
    {
        try{

            return response()->json([
                'data' =>$this->rightRepository->update($request,$id)
               ]);

         } catch(Exception $e) {
             return response()->json($e->getMessage());
         }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
        try{

            return response()->json([
                'data' =>$this->rightRepository->destroy($id)
               ]);

         } catch(Exception $e) {
             return response()->json($e->getMessage());
         }
    }

}
