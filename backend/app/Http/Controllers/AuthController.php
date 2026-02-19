<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $ownerEmail = (string) env('OWNER_EMAIL', 'owner@invoicesystem.com');
        $ownerName = (string) env('OWNER_NAME', 'Owner');
        $ownerPassword = (string) env('OWNER_PASSWORD', 'password');
        $emailMatchesOwner = strcasecmp($validated['email'], $ownerEmail) === 0;

        // Self-heal owner login: only create if owner doesn't exist yet.
        if ($emailMatchesOwner && hash_equals($ownerPassword, $validated['password'])) {
            $user = User::query()->firstOrCreate(
                ['email' => $ownerEmail],
                [
                    'name' => $ownerName,
                    'role' => 'owner',
                    'password' => Hash::make($ownerPassword),
                ]
            );
        } else {
            $user = User::query()->where('email', $validated['email'])->first();
        }

        if (! $user) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (($user->role ?? 'admin') === 'admin' && empty($user->managed_by_owner_id)) {
            return response()->json([
                'message' => 'This admin account is not active. Owner must create your credentials.',
            ], 403);
        }

        if (($user->role ?? 'admin') === 'admin' && ! (bool) ($user->is_active ?? true)) {
            return response()->json([
                'message' => 'This account is deactivated. Contact owner to reactivate it.',
            ], 403);
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

        // Keep a single active token per user to avoid unbounded token-table growth.
        $user->tokens()->delete();
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Authenticated successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
                'is_active' => (bool) ($user->is_active ?? true),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
