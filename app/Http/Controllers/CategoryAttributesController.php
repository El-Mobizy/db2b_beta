<?php

namespace App\Http\Controllers;

use App\Models\CategoryAttributes;
use Exception;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CategoryAttributesController extends Controller
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
            // $request->validate([
            //     'fieldtype' => 'required|string',
            //     'label' => 'required|string',
            //     'possible_value' => 'nullable|string',
            //     'isrequired' => 'nullable|boolean',
            //     'description' => 'nullable|string',
            //     'order_no' => 'required|integer',
            //     'is_price_field' => 'nullable|boolean',
            //     'is_crypto_price_field' => 'nullable|boolean',
            //     'search_criteria' => 'nullable|boolean',
            //     'is_active' => 'nullable|boolean',
            //     'deleted' => 'nullable|boolean',
            //     'uid' => 'required|string|unique:your_table_name_here',
            // ]);

         
            $field = new CategoryAttributes();
            $field->fieldtype = $request->input('fieldtype');
            $field->label = $request->input('label');
            if ($request->has('possible_value')) {
                $field->possible_value = $request->input('possible_value');
            }

            $exist = CategoryAttributes::where('fieldtype',$request->input('fieldtype'))->where('label', $request->input('label'))->exists();
            if($exist){
                return response()->json([
                    'message' => 'Attribute whith the same label and fieldtype already exist',
                ]);
            }

            $service = new Service();

            $field->isrequired = $request->input('isrequired', 0);
            $field->description = $request->input('description');
            $field->order_no = $request->input('order_no');
            $field->is_price_field = $request->input('is_price_field', 0);
            $field->is_crypto_price_field = $request->input('is_crypto_price_field', 0);
            $field->search_criteria = $request->input('search_criteria', 0);
            $field->is_active = $request->input('is_active', 0);
            $field->deleted = $request->input('deleted', 0);
            $field->uid = $service->generateUid($field);
    
            $field->save();
    
            return response()->json([
                'message' => 'Field created successfully',
                'data' => $field
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(CategoryAttributes $categoryAttributes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CategoryAttributes $categoryAttributes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CategoryAttributes $categoryAttributes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryAttributes $categoryAttributes)
    {
        //
    }
}
