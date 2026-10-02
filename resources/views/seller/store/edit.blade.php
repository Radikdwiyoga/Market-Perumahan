<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Toko - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('seller.products.index') }}" class="text-sm font-bold text-emerald-700">&larr; Kembali ke produk</a>
        <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">Profil pedagang</p>
            <h1 class="mt-2 text-3xl font-black">Pengaturan toko</h1>
            <p class="mt-2 text-slate-500">Informasi ini akan terlihat oleh warga saat menemukan produk Anda.</p>
            @if (session('status'))<div class="mt-6 rounded-lg bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('seller.store.update') }}" method="POST" class="mt-8 space-y-5" enctype="multipart/form-data">@csrf @method('PUT')
                <label class="block text-sm font-semibold">Nama toko<input name="store_name" value="{{ old('store_name', $store->store_name) }}" required class="mt-2 w-full rounded-lg border-slate-300" /></label>
                <label class="block text-sm font-semibold">Nomor HP<input name="phone" value="{{ old('phone', $store->phone) }}" required class="mt-2 w-full rounded-lg border-slate-300" /></label>
                <label class="block text-sm font-semibold">Alamat toko<input name="address" value="{{ old('address', $store->address) }}" required class="mt-2 w-full rounded-lg border-slate-300" /></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Jam buka<input type="time" name="open_time" value="{{ old('open_time', $store->open_time?->format('H:i')) }}" class="mt-2 w-full rounded-lg border-slate-300" /></label>
                    <label class="block text-sm font-semibold">Jam tutup<input type="time" name="close_time" value="{{ old('close_time', $store->close_time?->format('H:i')) }}" class="mt-2 w-full rounded-lg border-slate-300" /></label>
                </div>
                <label class="block text-sm font-semibold">Foto toko
                    @if ($store->image)
                        <img src="{{ asset('storage/'.$store->image) }}" alt="{{ $store->store_name }}" class="mt-2 h-24 w-24 rounded-xl object-cover" />
                    @endif
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-lg border border-slate-300 p-2 text-sm" />
                    <span class="mt-1 block text-xs text-slate-400">JPG, PNG, atau WebP maksimal 5 MB.</span>
                </label>
                <label class="block text-sm font-semibold">Deskripsi<textarea name="description" rows="4" class="mt-2 w-full rounded-lg border-slate-300">{{ old('description', $store->description) }}</textarea></label>
                <label class="block text-sm font-semibold">Status toko<select name="status" class="mt-2 w-full rounded-lg border-slate-300"><option value="open" @selected(old('status', $store->status) === 'open')>Buka</option><option value="closed" @selected(old('status', $store->status) === 'closed')>Tutup sementara</option></select></label>
                <button class="w-full rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white hover:bg-emerald-900">Simpan pengaturan</button>
            </form>
        </section>
    </main>
</body>
</html>
