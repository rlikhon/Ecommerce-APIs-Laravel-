<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function authenticate(LoginRequest $request): UserResource|JsonResponse
    {
         // 1. Attempt login with validated data
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        // 2. Strict Role Check
        if ($user->role !== 'admin') {
            // Immediately revoke session and tokens for security
            $user->tokens()->delete(); 
            Auth::guard('web')->logout();

            return response()->json([
                'status'  => 403,
                'message' => 'Access Denied: Admin privileges required.'
            ], 403);
        }

        // 3. Generate Sanctum Token
        $token = $user->createToken('admin_token')->plainTextToken;

        // 4. Return formatted JSON response
        return (new UserResource($user))
            ->additional([
                'token'   => $token,
                'message' => 'Login successfully done.',
                'status'  => 200
            ]);
    }
}
