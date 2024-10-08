<?php

namespace App\Http\Controllers;

use App\Models\AttributeGroup;
use Exception;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class AttributeGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
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
    public function store(Request $request)
    {
        try {
                    $request->validate([
                        'attribute_ids' => '',
                        'group_title_id' => ''
                    ]);
           
            foreach($request->attribute_ids as $attribute_id){
                $ulid = Uuid::uuid1();
                $ulidGroup = $ulid->toString();
                $group = new AttributeGroup();
                $group->attribute_id = $attribute_id;
                $group->group_title_id = $request->group_title_id;
                $group->uid = $ulidGroup;
                $exist = AttributeGroup::where('attribute_id',$request->attribute_id)->where('group_title_id',$request->group_title_id)->exists();
                if($exist){
                    return (new Service())->apiResponse(200,[],'This association already exist');
                }
                $group->save();
            }


            return (new Service())->apiResponse(200,[],'attribute group created successfuly');

        } catch (Exception $e) {
           return response()->json([
            'error' => $e->getMessage()
           ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AttributeGroup $attributeGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttributeGroup $attributeGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AttributeGroup $attributeGroup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttributeGroup $attributeGroup)
    {
        //
    }
}
