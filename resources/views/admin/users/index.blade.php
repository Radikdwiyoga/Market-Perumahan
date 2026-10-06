<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna Admin - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
<main class="mx-auto max-w-6xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-forest-700">&larr; Panel admin</a>
            <h1 class="mt-2 text-3xl font-black">Manajemen pengguna</h1>
            <p class="mt-1 text-sage-500">Kelola buyer, seller, dan verifikasi toko.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-forest-700 px-5 py-3 font-bold text-white">Buat akun</a>
    </div>
    @if (session('status'))
        <div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>
    @endif
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ ! $role && ! $verification ? 'bg-forest-700 text-white' : 'bg-warm-white text-sage-600' }}">Semua</a>
        @foreach (['buyer' => 'Buyer', 'seller' => 'Seller', 'admin' => 'Admin'] as $value => $label)
            <a href="{{ route('admin.users.index', ['role' => $value]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $role === $value ? 'bg-forest-700 text-white' : 'bg-warm-white text-sage-600' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.users.index', ['verification' => 'pending']) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $verification === 'pending' ? 'bg-amber-600 text-white' : 'bg-warm-white text-sage-600' }}">
            Menunggu verifikasi
            @if ($pendingVerificationCount > 0)
                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $pendingVerificationCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.users.index', ['verification' => 'rejected']) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $verification === 'rejected' ? 'bg-red-700 text-white' : 'bg-warm-white text-sage-600' }}">Ditolak</a>
    </div>
    <div class="mt-6 overflow-hidden rounded-2xl bg-warm-white shadow-soft">
        @forelse ($users as $user)
            <div class="border-b border-warm-100 px-6 py-5 last:border-0">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-bold">{{ $user->name }}</h2>
                        <p class="mt-1 text-sm text-sage-500">{{ $user->email ?: $user->phone }} &middot; {{ ucfirst($user->role) }}</p>
                        @if ($user->isSeller() && $user->sellerProfile)
                            <p class="mt-1 text-sm text-forest-700">Toko: {{ $user->sellerProfile->store_name }}</p>
                            <p class="mt-1 text-xs text-sage-500">
                                Diajukan {{ $user->sellerProfile->submitted_at?->format('d M Y H:i') ?? '-' }}
                                @if ($user->sellerProfile->verified_at)
                                    &middot; Ditinjau {{ $user->sellerProfile->verified_at->format('d M Y') }}
                                    @if ($user->sellerProfile->verifier)
                                        oleh {{ $user->sellerProfile->verifier->name }}
                                    @endif
                                @endif
                            </p>
                        @endif
                        @if ($user->sellerProfile?->isVerificationRejected())
                            <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">Alasan penolakan: {{ $user->sellerProfile->rejection_reason }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->status === 'active' ? 'bg-forest-100 text-forest-700' : 'bg-warm-50 text-sage-500' }}">{{ ucfirst($user->status) }}</span>
                        @if ($user->isSeller() && $user->sellerProfile)
                            <p class="mt-2 text-xs text-sage-500">Toko: {{ ucfirst($user->sellerProfile->status) }}</p>
                            <p class="mt-1 text-xs font-bold {{ match ($user->sellerProfile->verification_status) {
                                'approved' => 'text-forest-700',
                                'pending' => 'text-amber-700',
                                'rejected' => 'text-red-700',
                                default => 'text-sage-500',
                            } }}">{{ $user->sellerProfile->verificationStatusLabel() }}</p>
                        @endif
                    </div>
                </div>
                @if (! $user->isAdmin())
                    <div class="mt-4 flex flex-wrap items-end gap-2">
                        @if ($user->isSeller() && $user->sellerProfile?->isVerificationPending())
                            <form action="{{ route('admin.users.verify-seller', $user) }}" method="POST">@csrf @method('PATCH')
                                <button class="rounded-lg bg-forest-700 px-3 py-2 text-xs font-bold text-white">Setujui toko</button>
                            </form>
                            <form action="{{ route('admin.users.reject-seller', $user) }}" method="POST" class="flex flex-wrap items-end gap-2">@csrf @method('PATCH')
                                <label class="text-xs font-semibold text-sage-600">Alasan penolakan
                                    <input type="text" name="rejection_reason" required maxlength="500" placeholder="Data toko belum lengkap." class="mt-1 block w-64 rounded-lg border-warm-200 text-xs" />
                                </label>
                                <button class="rounded-lg bg-red-700 px-3 py-2 text-xs font-bold text-white">Tolak pengajuan</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.users.status', $user) }}" method="POST">@csrf @method('PATCH')
                            <button class="rounded-lg border border-warm-200 px-3 py-2 text-xs font-bold">{{ $user->status === 'active' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="px-6 py-12 text-center text-sage-500">Belum ada pengguna.</div>
        @endforelse
    </div>
</main>
</body>
</html>



