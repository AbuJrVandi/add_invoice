<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class OwnerAdminCredentialController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $admins->map(fn (User $admin): array => $this->transformAdminCredentials($admin))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $owner = $request->user();

        $admin = User::query()->create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => 'admin',
            'password' => $validated['password'],
            'managed_by_owner_id' => $owner?->id,
            'owner_password_ciphertext' => Crypt::encryptString($validated['password']),
            'owner_password_changed_at' => now(),
            'owner_force_password_notice' => false,
        ]);

        return response()->json([
            'message' => 'Admin credentials created successfully.',
            'data' => $this->transformAdminCredentials($admin),
        ], 201);
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admin accounts can be managed here.',
            ], 422);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $owner = $request->user();

        $user->forceFill([
            'password' => $validated['password'],
            'managed_by_owner_id' => $owner?->id,
            'owner_password_ciphertext' => Crypt::encryptString($validated['password']),
            'owner_password_changed_at' => now(),
            'owner_force_password_notice' => true,
        ])->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Admin password changed by owner.',
            'data' => $this->transformAdminCredentials($user->fresh()),
        ]);
    }

    private function transformAdminCredentials(User $admin): array
    {
        $password = null;

        if (! empty($admin->owner_password_ciphertext)) {
            try {
                $password = Crypt::decryptString($admin->owner_password_ciphertext);
            } catch (\Throwable) {
                $password = null;
            }
        }

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'password' => $password,
            'managed_by_owner_id' => $admin->managed_by_owner_id,
            'owner_password_changed_at' => $admin->owner_password_changed_at?->toISOString(),
            'created_at' => $admin->created_at?->toISOString(),
            'updated_at' => $admin->updated_at?->toISOString(),
        ];
    }
}
