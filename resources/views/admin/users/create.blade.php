<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Akun - Panel Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-emerald-700">&larr; Panel admin</a>
        <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">Manajemen pengguna</p><h1 class="mt-2 text-3xl font-black">Buat akun buyer atau seller</h1><p class="mt-2 text-slate-500">Akun langsung aktif dan dapat digunakan untuk login.</p>
            @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('admin.users.store') }}" method="POST" class="mt-8 space-y-6">@csrf
                <div class="grid gap-5 sm:grid-cols-2"><label class="text-sm font-semibold">Nama lengkap<input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Nomor HP<input name="phone" value="{{ old('phone') }}" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Email <span class="font-normal text-slate-400">(opsional)</span><input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Role<select name="role" required class="mt-2 w-full rounded-lg border-slate-300"><option value="buyer" @selected(old('role', 'buyer') === 'buyer')>Buyer</option><option value="seller" @selected(old('role') === 'seller')>Seller</option></select></label><label class="text-sm font-semibold sm:col-span-2">Alamat rumah<input name="address" value="{{ old('address') }}" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Blok<input name="block" value="{{ old('block') }}" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Nomor rumah<input name="house_number" value="{{ old('house_number') }}" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Password<input type="password" name="password" required class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Ulangi password<input type="password" name="password_confirmation" required class="mt-2 w-full rounded-lg border-slate-300"></label></div>
                <div class="border-t border-slate-200 pt-6"><h2 class="text-xl font-black">Profil toko seller</h2><p class="mt-1 text-sm text-slate-500">Wajib diisi jika role yang dipilih adalah seller.</p><div class="mt-4 grid gap-5 sm:grid-cols-2"><label class="text-sm font-semibold">Nama toko<input name="store_name" value="{{ old('store_name') }}" class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold">Nomor toko<input name="store_phone" value="{{ old('store_phone') }}" class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold sm:col-span-2">Alamat toko<input name="store_address" value="{{ old('store_address') }}" class="mt-2 w-full rounded-lg border-slate-300"></label><label class="text-sm font-semibold sm:col-span-2">Deskripsi<textarea name="store_description" rows="3" class="mt-2 w-full rounded-lg border-slate-300">{{ old('store_description') }}</textarea></label></div></div>
                <button class="w-full rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white hover:bg-emerald-900">Buat akun</button>
            </form>
        </section>
    </main>
</body>
</html>
