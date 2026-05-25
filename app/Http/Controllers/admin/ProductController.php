<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\TempImage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\ProductImage;
use App\Models\ProductSize;

class ProductController extends Controller
{
    public function index()
    {
        
        $products = Product::with(['product_images', 'product_sizes'])
                    ->orderBy('created_at', 'DESC')
                    ->get();
        
        return response()->json([
            'status' => 200,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',            
            'price' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
            'sku' => 'required|unique:products,sku',
            'status' => 'required',
            'qty' => 'required',
            'is_featured' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' =>  'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = new Product();
        $product->title = $request->title;
        $product->description = $request->description ?? null;
        $product->short_description = $request->short_description ?? null;
        $product->price = $request->price;
        $product->compare_price = $request->compare_price ?? null;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->sku = $request->sku;
        $product->status = $request->status;
        $product->qty = $request->qty;
        $product->is_featured = $request->is_featured;
        $product->barcode = $request->barcode ?? null;
        $product->save();
        
        if ($request->has('sizes')) {
            // 1. Pluck and cast to integers to be safe
            $sizeIds = collect($request->sizes)->map(fn($id) => (int) $id)->toArray();
            
            // 2. Sync with your pivot table
            $product->sizes()->sync($sizeIds);
        }
        
        // Attach temp images to product
        if(!empty($request->gallary)) {
            foreach($request->gallary as $key => $tempImageId) {
                $tempImage = TempImage::find($tempImageId);                
                $extarray = explode('.', $tempImage->name);
                
                $ext = end($extarray);
                $imageName = $product->id . '-' . $tempImage->name . '.' . $ext;
                
                $manager = ImageManager::usingDriver(Driver::class);
                $img = $manager->decodePath(public_path('uploads/temp/' . $tempImage->name));
                $img->scaleDown(1200);
                $img->save(public_path('uploads/products/large/' . $imageName), 80);

                // Small Thumbnail 
                $manager = ImageManager::usingDriver(Driver::class);
                $img = $manager->decodePath(public_path('uploads/temp/' . $tempImage->name));
                $img->coverDown(400, 460);
                $img->save(public_path('uploads/products/small/' . $imageName), 80);

                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->image = $imageName;
                $productImage->save();

                if($key == 0) {
                    $product->image = $imageName;
                    $product->save();
                }
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Product created successfully',
            'data' => $product
        ], 200);
    }

    public function show($id)
    {
        $product = Product::with(['product_images', 'product_sizes'])
                            ->findOrFail($id);
        
        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);           
        }
        $product->size_ids = $product->product_sizes()->pluck('size_id')->toArray();
        $product->makeHidden('sizes');

        return response()->json([
            'status' => 200,
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);           
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',            
            'price' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
            'sku' => 'required|unique:products,sku,'.$id,'id',
            'status' => 'required',
            'is_featured' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->title = $request->title;
        $product->description = $request->description ?? null;
        $product->short_description = $request->short_description ?? null;
        $product->price = $request->price;
        $product->compare_price = $request->compare_price ?? null;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->sku = $request->sku;
        $product->status = $request->status;
        $product->is_featured = $request->is_featured;
        $product->barcode = $request->barcode ?? null;
        $product->save();

        // Inside your Controller (e.g., saveProduct)
        if ($request->has('sizes')) {
            // 1. Pluck and cast to integers to be safe
            $sizeIds = collect($request->sizes)->map(fn($id) => (int) $id)->toArray();
            
            // 2. Sync with your pivot table
            $product->sizes()->sync($sizeIds);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    public function destroy($id)
    {        
        $product = Product::with(['product_images'])->find($id);
     
        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);           
        }
     
        $product->delete();

        if($product->product_images->isNotEmpty()) {
            foreach($product->product_images as $image) {
                @unlink(public_path('uploads/products/large/' . $image->image));
                @unlink(public_path('uploads/products/small/' . $image->image));
            }

        }

        return response()->json([
            'status' => 200,
            'message' => 'Product deleted successfully'
        ], 200);
    }

    public function saveProductImage(Request $request)
    {
        // Ensure files are included in the validation
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }              

        $image = $request->file('image');
        $imageName = $request->product_id . '_' . time() . '.' . $image->getClientOriginalExtension();        

        // Large Image
        $manager = new ImageManager(new Driver());        
        $img = $manager->decodePath($image->getPathName());
        $img->scaleDown(1200);
        $img->save(public_path('uploads/products/large/' . $imageName), 80);

        // Small Thumbnail 
        $manager = new ImageManager(new Driver());
        //$img = $manager->read($image->getPathName());
        $img = $manager->decodePath($image->getPathName());
        $img->coverDown(400, 460);
        $img->save(public_path('uploads/products/small/' . $imageName), 80);

        // Insert into product_images table
        $productImage = new ProductImage();
        $productImage->product_id = $request->product_id;
        $productImage->image = $imageName;
        $productImage->save();        

        return response()->json([
            'status' => 200,
            'message' => 'Image uploaded successfully',
            'data' => $productImage
        ], 200);
    }

    public function deleteProductImage($id)
    {
        $productImage = ProductImage::findOrFail($id);
        
        if($productImage == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product image not found'
            ], 404);           
        }

        // Delete image files
        @unlink(public_path('uploads/products/large/' . $productImage->image));
        @unlink(public_path('uploads/products/small/' . $productImage->image));

        $productImage->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Product image deleted successfully'
        ], 200);
    }

    public function deleteTempImage($id)
    {
        $tempImage = TempImage::findOrFail($id);
        
        if($tempImage == null){
            return response()->json([
                'status' => 404,
                'message' => 'Temp image not found'
            ], 404);           
        }

        // Delete image files
        @unlink(public_path('uploads/temp/' . $tempImage->name));
        @unlink(public_path('uploads/temp/thumb/' . $tempImage->name));

        $tempImage->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Temp image deleted successfully'
        ], 200);
    }

    public function updateDefaultImage(Request $request)
    {
        $product = Product::find($request->product_id);                
        
        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);           
        }

        $product->image = $request->image;
        $product->save();

        return response()->json([
            'status' => 200,
            'message' => 'Default image updated successfully',
            'data' => $product
        ], 200);        
    }
}
