<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;


class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::orderBy('created_at', 'desc')
                            ->where('status', 1);
        //Filter by category
        if (!empty($request->category)) {
            $carArray = explode(',', $request->category);
            $products = $products->whereIn('category_id', $carArray);
        }

        //Filter by brand
        if (!empty($request->brand)) {
            $brandArray = explode(',', $request->brand);
            $products = $products->whereIn('brand_id', $brandArray);
        }

        $products = $products->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function latestProducts()
    {
        $products = Product::latest()
                            ->take(4)
                            ->where('status', 1)
                            ->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function featuredProducts()
    {
        $products = Product::where('is_featured', 'yes')
                            ->where('status', 1)
                            ->take(4)
                            ->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function getCategories()
    {
        $categories = Category::orderBy('name')
                            ->where('status', 1)
                            ->get();

        return response()->json([
            'status' => 200,
            'data' => $categories
        ], 200);
    }

    public function getBrands()
    {
        $brands = Brand::orderBy('name')
                            ->where('status', 1)
                            ->get();

        return response()->json([
            'status' => 200,
            'data' => $brands
        ], 200);
    }

    public function getProductDetails($id)
    {
        $product = Product::with('product_images', 'sizes')
                            ->where('id', $id)
                            ->where('status', 1)
                            ->first();

        if ($product) {
            return response()->json([
                'status' => 200,
                'data' => $product
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }
    }
}
