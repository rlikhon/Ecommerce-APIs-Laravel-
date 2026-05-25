<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 200,
            'message' => 'Category retrieved successfully.',
            'data' => $category
        ]);
    }

    //
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug'        
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = new Category();
        $category->name = $request->name;
        $category->description = $request->description;
        $category->status = $request->status;
        $category->slug = $request->slug;
        $category->save();

        return response()->json([
            'status' => 201,
            'message' => 'Category created successfully.',
            'data' => $category
        ], 201);
    }

     public function show($id)
    {
        $category = Category::find($id);
        
        if ($category === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category retrieved successfully.',
            'data' => $category
        ]);
    }

     public function update( $id, Request $request)
    {
        $category = Category::find($id);

        if ($category === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found.',
                'data' => []
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug'        
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $category->name = $request->name;
        $category->description = $request->description;
        $category->status = $request->status;
        $category->slug = $request->slug;
        $category->save();

        return response()->json([
            'status' => 201,
            'message' => 'Category updated successfully.',
            'data' => $category
        ], 201);
    }

     public function destroy($id)
    {
        $category = Category::find($id);

        if ($category === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found.',
                'data' => []
            ], 404);
        }

        $category->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Category deleted successfully.',
            'data' => []
        ]);
    }
}
