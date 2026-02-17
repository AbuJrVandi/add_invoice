<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            if (($user->role ?? 'admin') === 'admin' && (bool) $user->owner_force_password_notice) {
                return response()->json([
                    'message' => 'Owner changed your account. Try to reach out and get new credentials.',
                ], 401);
            }

            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (($user->role ?? 'admin') === 'admin' && (bool) $user->owner_force_password_notice) {
            $user->forceFill([
                'owner_force_password_notice' => false,
            ])->save();
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Authenticated successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
