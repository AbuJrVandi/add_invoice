<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OwnerAdminCredentialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $owner = $request->user();

        $admins = User::query()
            ->where('role', 'admin')
            ->where('managed_by_owner_id', $owner?->id)
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
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Admin credentials created successfully.',
            'data' => $this->transformAdminCredentials($admin),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureManagedAdminForOwner($request, $user)) {
            return $response;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $owner = $request->user();

        $user->forceFill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'managed_by_owner_id' => $owner?->id ?? $user->managed_by_owner_id,
        ])->save();

        return response()->json([
            'message' => 'Admin account updated successfully.',
            'data' => $this->transformAdminCredentials($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureManagedAdminForOwner($request, $user)) {
            return $response;
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

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureManagedAdminForOwner($request, $user)) {
            return $response;
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $owner = $request->user();
        $isActive = (bool) $validated['is_active'];

        $user->forceFill([
            'is_active' => $isActive,
            'managed_by_owner_id' => $owner?->id ?? $user->managed_by_owner_id,
        ])->save();

        if (! $isActive) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $isActive
                ? 'Admin account activated successfully.'
                : 'Admin account deactivated successfully.',
            'data' => $this->transformAdminCredentials($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureManagedAdminForOwner($request, $user)) {
            return $response;
        }

        $invoiceIds = Invoice::query()
            ->where('created_by_user_id', $user->id)
            ->pluck('id');

        $paymentsQuery = Payment::query()->where('created_by', $user->id);
        if ($invoiceIds->isNotEmpty()) {
            $paymentsQuery->orWhereIn('invoice_id', $invoiceIds);
        }

        $paymentIds = $paymentsQuery->pluck('id');

        $salesQuery = DB::table('sales');
        if ($invoiceIds->isNotEmpty()) {
            $salesQuery->whereIn('invoice_id', $invoiceIds);
            if ($paymentIds->isNotEmpty()) {
                $salesQuery->orWhereIn('payment_id', $paymentIds);
            }
        } elseif ($paymentIds->isNotEmpty()) {
            $salesQuery->whereIn('payment_id', $paymentIds);
        } else {
            $salesQuery->whereRaw('1 = 0');
        }

        $saleCount = (clone $salesQuery)->count();
        $invoiceCount = $invoiceIds->count();
        $receiptCount = $paymentIds->count();

        $invoicePdfPaths = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->whereNotNull('pdf_path')
            ->pluck('pdf_path')
            ->values();

        DB::transaction(function () use ($user, $salesQuery, $paymentIds, $invoiceIds): void {
            $salesQuery->delete();

            if ($paymentIds->isNotEmpty()) {
                Payment::query()->whereIn('id', $paymentIds)->delete();
            }

            if ($invoiceIds->isNotEmpty()) {
                Invoice::query()->whereIn('id', $invoiceIds)->delete();
            }

            $user->tokens()->delete();
            $user->delete();
        });

        $this->deleteStoredInvoicePdfs($invoicePdfPaths);

        return response()->json([
            'message' => 'Admin account and related records deleted successfully.',
            'data' => [
                'deleted_admin_id' => $user->id,
                'deleted_invoices' => $invoiceCount,
                'deleted_receipts' => $receiptCount,
                'deleted_sales' => $saleCount,
            ],
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
            'is_active' => (bool) ($admin->is_active ?? true),
            'managed_by_owner_id' => $admin->managed_by_owner_id,
            'owner_password_changed_at' => $admin->owner_password_changed_at?->toISOString(),
            'created_at' => $admin->created_at?->toISOString(),
            'updated_at' => $admin->updated_at?->toISOString(),
        ];
    }

    private function ensureManagedAdminForOwner(Request $request, User $user): ?JsonResponse
    {
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admin accounts can be managed here.',
            ], 422);
        }

        if ((int) ($user->managed_by_owner_id ?? 0) !== (int) ($request->user()?->id ?? 0)) {
            return response()->json([
                'message' => 'Admin account not found for this owner.',
            ], 404);
        }

        return null;
    }

    private function deleteStoredInvoicePdfs(Collection $paths): void
    {
        if ($paths->isEmpty()) {
            return;
        }

        $relativePaths = $paths
            ->filter(fn ($path): bool => is_string($path) && trim($path) !== '')
            ->map(function (string $path): string {
                if (str_starts_with($path, 'storage/')) {
                    return substr($path, strlen('storage/'));
                }

                return ltrim($path, '/');
            })
            ->filter(fn (string $path): bool => $path !== '')
            ->unique()
            ->values()
            ->all();

        if ($relativePaths !== []) {
            Storage::disk('public')->delete($relativePaths);
        }
    }
}
