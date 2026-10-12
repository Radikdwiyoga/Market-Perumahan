<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Pengguna - Panel Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-forest-700 hover:underline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Manajemen pengguna
        </a>

        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Manajemen pengguna</p>
            <h1 class="mt-2 text-3xl font-black">Edit Akun</h1>
            <p class="mt-1 text-sm text-sage-500">
                Role: <span class="font-semibold text-forest-700">{{ ucfirst($user->role) }}</span>
                &middot; Bergabung {{ $user->created_at->format('d M Y') }}
            </p>

            @if ($errors->any())
                <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="mt-8 space-y-6">
                @csrf @method('PUT')

                {{-- Info Pribadi --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="text-sm font-semibold sm:col-span-2">
                        Nama lengkap
                        <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                    <label class="text-sm font-semibold">
                        Nomor HP
                        <input name="phone" value="{{ old('phone', $user->phone) }}" required maxlength="30" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                    <label class="text-sm font-semibold">
                        Email <span class="font-normal text-sage-400">(opsional)</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                    <label class="text-sm font-semibold sm:col-span-2">
                        Alamat rumah
                        <input name="address" value="{{ old('address', $user->address) }}" required maxlength="255" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                    <label class="text-sm font-semibold">
                        Blok
                        <input name="block" value="{{ old('block', $user->block) }}" required maxlength="20" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                    <label class="text-sm font-semibold">
                        Nomor rumah
                        <input name="house_number" value="{{ old('house_number', $user->house_number) }}" required maxlength="20" class="mt-2 w-full rounded-lg border-warm-200">
                    </label>
                </div>

                {{-- Ganti Password (opsional) --}}
                <div class="border-t border-warm-200 pt-6">
                    <h2 class="text-xl font-black">Ganti Password</h2>
                    <p class="mt-1 text-sm text-sage-500">Kosongkan jika tidak ingin mengganti password.</p>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <label class="text-sm font-semibold">
                            Password baru
                            <input type="password" name="password" minlength="8" class="mt-2 w-full rounded-lg border-warm-200">
                        </label>
                        <label class="text-sm font-semibold">
                            Ulangi password
                            <input type="password" name="password_confirmation" class="mt-2 w-full rounded-lg border-warm-200">
                        </label>
                    </div>
                </div>

                {{-- Profil toko (khusus seller) --}}
                @if ($user->isSeller() && $user->sellerProfile)
                    <div class="border-t border-warm-200 pt-6">
                        <h2 class="text-xl font-black">Profil Toko</h2>
                        <p class="mt-1 text-sm text-sage-500">Info toko penjual ini.</p>
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <label class="text-sm font-semibold">
                                Nama toko
                                <input name="store_name" value="{{ old('store_name', $user->sellerProfile->store_name) }}" maxlength="255" class="mt-2 w-full rounded-lg border-warm-200">
                            </label>
                            <label class="text-sm font-semibold">
                                Nomor toko
                                <input name="store_phone" value="{{ old('store_phone', $user->sellerProfile->phone) }}" maxlength="30" class="mt-2 w-full rounded-lg border-warm-200">
                            </label>
                            <label class="text-sm font-semibold sm:col-span-2">
                                Alamat toko
                                <input name="store_address" value="{{ old('store_address', $user->sellerProfile->address) }}" maxlength="255" class="mt-2 w-full rounded-lg border-warm-200">
                            </label>
                            <label class="text-sm font-semibold sm:col-span-2">
                                Deskripsi toko
                                <textarea name="store_description" rows="3" maxlength="2000" class="mt-2 w-full rounded-lg border-warm-200">{{ old('store_description', $user->sellerProfile->description) }}</textarea>
                            </label>
                        </div>
                    </div>
                @endif

                <button class="w-full rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800 transition">
                    Simpan perubahan
                </button>
            </form>
        </section>
    </main>
</body>
</html>
