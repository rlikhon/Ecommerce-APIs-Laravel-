<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\TempImage;

class TempImageController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

         $tempImage = new TempImage();
         $tempImage->name = $request->file('image')->getClientOriginalName();
         $tempImage->save();

         $image = $request->file('image');
         $imageName = $tempImage->id . '_' . time() . '.' . $image->getClientOriginalExtension();
         $image->move(public_path('uploads/temp'), $imageName);

         $tempImage->name = $imageName;
         $tempImage->save();

         // create image manager instance using the desired driver
        $manager = ImageManager::usingDriver(Driver::class);

        // read image data from path
        $img = $manager->decodePath(public_path('uploads/temp/' . $imageName));
        // resize image instance
        $img->coverDown(400, 460);
        
        // save image in desired format
        $img->save(public_path('uploads/temp/thumb/' . $imageName), 80);

        return response()->json([
            'status' => 200,
            'message' => 'Image uploaded successfully',
            'data' => $tempImage
        ], 200);
    }
}
