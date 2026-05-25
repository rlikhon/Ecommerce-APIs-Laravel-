<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index()
    {
        $brand = Brand::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 200,
            'message' => 'Brand retrieved successfully.',
            'data' => $brand
        ]);
    }

    //
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug'        
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->status = $request->status;
        $brand->slug = $request->slug;
        $brand->save();

        return response()->json([
            'status' => 201,
            'message' => 'Brand created successfully.',
            'data' => $brand
        ], 201);
    }

     public function show($id)
    {
        $brand = Brand::find($id);
        
        if ($brand === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Brand retrieved successfully.',
            'data' => $brand
        ]);
    }

     public function update( $id, Request $request)
    {
        $brand = Brand::find($id);

        if ($brand === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found.',
                'data' => []
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug'        
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->status = $request->status;
        $brand->slug = $request->slug;
        $brand->save();

        return response()->json([
            'status' => 201,
            'message' => 'Brand updated successfully.',
            'data' => $brand
        ], 201);
    }

     public function destroy($id)
    {
        $brand = Brand::find($id);

        if ($brand === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found.',
                'data' => []
            ], 404);
        }

        $brand->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Brand deleted successfully.',
            'data' => []
        ]);
    }
}
