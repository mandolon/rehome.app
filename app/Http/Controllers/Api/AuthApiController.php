<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return Api::error('INVALID_CREDENTIALS', 'The provided credentials are incorrect.', 401);
        }

        // Revoke existing tokens for this device to prevent token accumulation
        $user->tokens()->where('name', $request->device_name)->delete();

        // Create new token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return Api::success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account' => [
                    'id' => $user->account->id,
                    'name' => $user->account->name,
                ],
            ],
        ], 'Login successful');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return Api::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'account' => [
                'id' => $user->account->id,
                'name' => $user->account->name,
            ],
        ], 'User info retrieved');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return Api::success(null, 'Logout successful');
    }

    /**
     * Revoke all tokens for user (logout from all devices)
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return Api::success(null, 'Logged out from all devices');
    }
}
