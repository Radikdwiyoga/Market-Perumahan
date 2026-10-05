<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $role = $request->string('role')->value();
        $verification = $request->string('verification')->value();

        return view('admin.users.index', [
            'users' => User::query()
                ->with('sellerProfile')
                ->when(in_array($role, ['buyer', 'seller', 'admin'], true), fn ($query) => $query->where('role', $role))
                ->when(in_array($verification, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->whereHas('sellerProfile', fn ($storeQuery) => $storeQuery->where('verification_status', $verification)))
                ->latest()
                ->get(),
            'role' => $role,
            'verification' => $verification,
            'pendingVerificationCount' => SellerProfile::query()->where('verification_status', 'pending')->count(),
        ]);
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:255'],
            'block' => ['required', 'string', 'max:20'],
            'house_number' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:buyer,seller'],
            'password' => ['required', 'confirmed', 'min:8'],
            'store_name' => ['required_if:role,seller', 'nullable', 'string', 'max:255'],
            'store_description' => ['nullable', 'string', 'max:2000'],
            'store_phone' => ['required_if:role,seller', 'nullable', 'string', 'max:30'],
            'store_address' => ['required_if:role,seller', 'nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'], 'phone' => $validated['phone'], 'email' => $validated['email'] ?? null,
                'address' => $validated['address'], 'block' => $validated['block'], 'house_number' => $validated['house_number'],
                'role' => $validated['role'], 'status' => 'active', 'password' => $validated['password'], 'email_verified_at' => now(),
            ]);

            if ($validated['role'] === 'seller') {
                SellerProfile::create([
                    'user_id' => $user->id, 'store_name' => $validated['store_name'], 'description' => $validated['store_description'] ?? null,
                    'phone' => $validated['store_phone'], 'address' => $validated['store_address'], 'status' => 'open',
                ]);
            }

            return $user;
        });

        AuditLogger::log('USER_CREATED', 'User', $user->id, ['role' => $user->role, 'name' => $user->name]);

        return redirect()->route('admin.dashboard')->with('status', 'Akun '.$validated['role'].' berhasil dibuat.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_if($user->isAdmin(), 422, 'Akun admin tidak dapat dinonaktifkan dari panel ini.');

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        DB::transaction(function () use ($user, $newStatus): void {
            $user->update(['status' => $newStatus]);

            if ($user->isSeller() && $user->sellerProfile) {
                // Toko hanya dibuka kembali bila verifikasinya sudah disetujui,
                // agar toko yang ditolak/ditinjau tidak ikut tayang.
                $user->sellerProfile->update([
                    'status' => match (true) {
                        $newStatus === 'active' && $user->sellerProfile->isVerificationApproved() => 'open',
                        default => $newStatus === 'active' ? 'closed' : 'suspended',
                    },
                ]);
            }
        });

        AuditLogger::log('USER_STATUS_UPDATED', 'User', $user->id, ['status' => $newStatus, 'role' => $user->role]);

        return back()->with('status', 'Status akun berhasil diperbarui.');
    }

    public function verifySeller(User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->isSeller(), 404);
        abort_if($user->sellerProfile === null, 422, 'Seller belum memiliki profil toko.');
        abort_unless($user->sellerProfile->isVerificationPending(), 422, 'Toko ini tidak sedang menunggu verifikasi.');

        DB::transaction(function () use ($user): void {
            $user->update(['status' => 'active']);
            $user->sellerProfile->update([
                'status' => 'open',
                'verification_status' => 'approved',
                'rejection_reason' => null,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        });

        AuditLogger::log('SELLER_VERIFIED', 'SellerProfile', $user->sellerProfile->id, ['store_name' => $user->sellerProfile->store_name]);
        UserNotification::send($user->id, 'Toko terverifikasi', "Toko {$user->sellerProfile->store_name} telah diverifikasi dan siap berjualan.", 'seller_verified');

        return back()->with('status', 'Seller berhasil diverifikasi dan tokonya dibuka.');
    }

    public function rejectSeller(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->isSeller(), 404);
        abort_if($user->sellerProfile === null, 422, 'Seller belum memiliki profil toko.');
        abort_unless($user->sellerProfile->isVerificationPending(), 422, 'Toko ini tidak sedang menunggu verifikasi.');

        $reason = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ])['rejection_reason'];

        $user->sellerProfile->update([
            'status' => 'closed',
            'verification_status' => 'rejected',
            'rejection_reason' => $reason,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        AuditLogger::log('SELLER_REJECTED', 'SellerProfile', $user->sellerProfile->id, [
            'store_name' => $user->sellerProfile->store_name,
            'rejection_reason' => $reason,
        ]);
        UserNotification::send($user->id, 'Pengajuan toko ditolak', "Pengajuan toko {$user->sellerProfile->store_name} ditolak. Alasan: {$reason}", 'seller_rejected');

        return back()->with('status', 'Pengajuan toko ditolak dan seller diberi tahu alasannya.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
