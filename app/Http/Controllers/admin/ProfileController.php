<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    /**
     * Update textual profile data parameters.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $admin = $request->user(); // Get authenticated user context safely

        // Map React Hook Form names cleanly to your DB columns
        $admin->update([
            'name'     => $request->fullName,
            'bio'      => $request->bio,
            'location' => $request->location,
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Profile textual information updated successfully.',
            'data'    => $admin
        ], 200);
    }

    /**
     * Upload and scale down the admin profile avatar picture.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $admin = $request->user();
        $file = $request->file('avatar');

        // 1. Structural housekeeping: Delete old avatar if it exists on disk
        if ($admin->avatar && File::exists(public_path('uploads/avatars/' . $admin->avatar))) {
            File::delete(public_path('uploads/avatars/' . $admin->avatar));
        }

        // 2. Generate clean, identifiable unique filename structure
        $filename = 'admin_' . $admin->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $uploadPath = public_path('uploads/avatars');

        if (!File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true, true);
        }

        // 3. Scale down via Intervention Image to keep load metrics lightweight
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        
        // Profiles look best cropped as squares
        $image->cover(180, 180); 
        $image->save($uploadPath . '/' . $filename, 85); // Compress slightly to retain memory bandwidth

        // 4. Persist change string track back to DB
        $admin->update(['avatar' => $filename]);

        return response()->json([
            'status'  => 200,
            'message' => 'Avatar graphic uploaded successfully.',
            'data'    => [
                'avatar_url' => asset('uploads/avatars/' . $filename) // Returns absolute URL for React hydration
            ]
        ], 200);
    }

    /**
     * Process Administrative password change tokens securely.
     */
    public function changePassword(UpdatePasswordRequest $request)
    {
        $admin = $request->user();

        // 1. Fail-Fast Check: Validate old password authenticity matches hash safely
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation failed',
                'errors'  => [
                    'current_password' => ['The provided password does not match your active account matrix records.']
                ]
            ], 422);
        }

        // 2. Hash and commit raw password update payload
        $admin->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Account access security credentials updated successfully.'
        ], 200);
    }
}

