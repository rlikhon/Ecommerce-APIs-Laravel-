<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;

class AccountController extends Controller
{
    public function register(Request $request)
    {
        // Registration logic here
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer', // Default role for new users
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 201,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user
            ]
        ], 201);
    }

    public function authenticate(LoginRequest $request): UserResource|JsonResponse
    {
         // 1. Attempt login with validated data
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();
        // 3. Generate Sanctum Token
        $token = $user->createToken('admin_token')->plainTextToken;

        // 4. Return formatted JSON response
        return (new UserResource($user))
            ->additional([
                'token'   => $token,
                'user'    => $user,
                'message' => 'Login successfully done.',
                'status'  => 200
            ]);
    }

    public function logout(Request $request)
    {
        // Logout logic here
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Logged out successfully'
        ], 200);
    }
}
