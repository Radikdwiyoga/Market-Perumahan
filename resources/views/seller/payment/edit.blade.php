<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Pembayaran - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('seller.products.index') }}" class="text-sm font-bold text-emerald-700">&larr; Kembali ke produk</a>
        <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">Pembayaran toko</p>
            <h1 class="mt-2 text-3xl font-black">Pengaturan pembayaran</h1>
            <p class="mt-2 text-slate-500">Simpan rekening dan QRIS yang akan digunakan pembeli saat berbelanja di toko {{ $store->store_name ?? '' }}.</p>
            @if (session('status'))<div class="mt-6 rounded-lg bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('seller.payment.update') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-5">@csrf @method('PUT')
                <div class="border-b border-slate-200 pb-6">
                    <h2 class="font-bold">Transfer bank</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-semibold">Nama bank<input name="bank_name" value="{{ old('bank_name', $setting?->bank_name) }}" class="mt-2 w-full rounded-lg border-slate-300" /></label>
                        <label class="text-sm font-semibold">Nomor rekening<input name="bank_account_number" value="{{ old('bank_account_number', $setting?->bank_account_number) }}" class="mt-2 w-full rounded-lg border-slate-300" /></label>
                    </div>
                    <label class="mt-4 block text-sm font-semibold">Nama pemilik rekening<input name="bank_account_name" value="{{ old('bank_account_name', $setting?->bank_account_name) }}" class="mt-2 w-full rounded-lg border-slate-300" /></label>
                </div>
                <div>
                    <h2 class="font-bold">QRIS toko</h2>
                    <p class="mt-1 text-sm text-slate-500">JPG, JPEG, atau PNG maksimal 5 MB.</p>
                    @if ($setting?->qris_image)
                        <img src="{{ Storage::disk('public')->url($setting->qris_image) }}" alt="QRIS toko" class="mt-4 h-48 w-48 rounded-xl object-cover" />
                        <p class="mt-2 text-sm font-semibold text-emerald-700">QRIS aktif</p>
                    @endif
                    <label class="mt-4 block text-sm font-semibold">{{ $setting?->qris_image ? 'Ganti QRIS' : 'Upload QRIS' }}<input type="file" name="qris_image" accept="image/jpeg,image/png" class="mt-2 block w-full rounded-lg border border-slate-300 p-2" /></label>
                </div>
                <button class="w-full rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white hover:bg-emerald-900">Simpan pengaturan</button>
            </form>
            @if ($setting?->qris_image)
                <form action="{{ route('seller.payment.qris.destroy') }}" method="POST" class="mt-3">@csrf @method('DELETE')<button class="w-full rounded-xl border border-red-200 px-5 py-3 font-bold text-red-700">Hapus QRIS</button></form>
            @endif
        </section>
    </main>
</body>
</html>
