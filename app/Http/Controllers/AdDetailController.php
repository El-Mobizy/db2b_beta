<?php

namespace App\Http\Controllers;

use App\Models\AdDetail;
use Illuminate\Http\Request;

class AdDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'status_code' => 400,
            'data' =>[],
            'message' => 'The data provided is not valid.', 'errors' => 'error'
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return(new Service())->apiResponse(400,[],'data provided');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AdDetail $adDetail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AdDetail $adDetail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AdDetail $adDetail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdDetail $adDetail)
    {
        //
    }
}
