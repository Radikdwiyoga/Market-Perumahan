<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajukan Jadi Penjual - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <x-back-button :fallback="route('dashboard')" />
        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Verifikasi pedagang</p>
            <h1 class="mt-2 text-3xl font-black">Ajukan toko Anda</h1>
            <p class="mt-2 text-sage-500">Lengkapi data toko, lalu pengelolaan akan meninjau sebelum toko Anda bisa berjualan.</p>

            @if ($store?->isVerificationRejected())
                <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Pengajuan sebelumnya ditolak.</p>
                    <p class="mt-1">Alasan: {{ $store->rejection_reason }}</p>
                    <p class="mt-1">Perbaiki data di bawah ini lalu kirim ulang.</p>
                </div>
            @endif
            @if (session('status'))
                <div class="mt-6 rounded-2xl bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('seller.application.store') }}" method="POST" class="mt-8 space-y-5">@csrf
                <label class="block text-sm font-semibold">Nama toko<input name="store_name" value="{{ old('store_name', $store?->store_name) }}" required class="mt-2 w-full rounded-lg border-warm-200" /></label>
                <label class="block text-sm font-semibold">Nomor HP toko<input name="phone" value="{{ old('phone', $store?->phone ?? auth()->user()->phone) }}" required class="mt-2 w-full rounded-lg border-warm-200" /></label>
                <label class="block text-sm font-semibold">Alamat toko<input name="address" value="{{ old('address', $store?->address ?? auth()->user()->address.', Blok '.auth()->user()->block.' No. '.auth()->user()->house_number) }}" required class="mt-2 w-full rounded-lg border-warm-200" /></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Jam buka<input type="time" name="open_time" value="{{ old('open_time', $store?->open_time?->format('H:i')) }}" class="mt-2 w-full rounded-lg border-warm-200" /></label>
                    <label class="block text-sm font-semibold">Jam tutup<input type="time" name="close_time" value="{{ old('close_time', $store?->close_time?->format('H:i')) }}" class="mt-2 w-full rounded-lg border-warm-200" /></label>
                </div>
                <label class="block text-sm font-semibold">Deskripsi toko<textarea name="description" rows="4" class="mt-2 w-full rounded-lg border-warm-200" placeholder="Ceritakan produk yang Anda jual untuk warga sekitar.">{{ old('description', $store?->description) }}</textarea></label>
                <button class="w-full rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Kirim pengajuan</button>
            </form>
        </section>
        <section class="mt-6 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-lg font-black">Alur persetujuan</h2>
            <ol class="mt-4 space-y-2 text-sm text-sage-600">
                <li class="flex gap-3"><span class="font-bold text-forest-700">1.</span> Lengkapi data toko</li>
                <li class="flex gap-3"><span class="font-bold text-forest-700">2.</span> Kirim pengajuan ke pengelola</li>
                <li class="flex gap-3"><span class="font-bold text-forest-700">3.</span> Pengelola meninjau dan menyetujui atau menolak</li>
                <li class="flex gap-3"><span class="font-bold text-forest-700">4.</span> Toko aktif dan produk Anda tampil di katalog</li>
            </ol>
        </section>
    </main>
</body>
</html>



