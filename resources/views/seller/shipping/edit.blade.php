<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Pengiriman - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <x-back-button :fallback="route('seller.products.index')" />
        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Opsi pengiriman</p>
            <h1 class="mt-2 text-3xl font-black">Pengaturan pengiriman</h1>
            <p class="mt-2 text-sage-500">Tentukan metode pengiriman, ongkir, dan minimal pembelian untuk toko {{ $store->store_name }}.</p>
            @if (session('status'))<div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('seller.shipping.update') }}" method="POST" class="mt-8 space-y-5">@csrf @method('PUT')
                <div class="space-y-3">
                    <label class="flex items-center gap-3 rounded-xl border border-warm-200 p-4">
                        <input type="checkbox" name="enable_delivery" value="1" @checked(old('enable_delivery', $store->enable_delivery)) class="rounded border-warm-200" />
                        <span><strong class="block">Diantar oleh penjual</strong><small class="text-sage-500">Pedagang mengantar pesanan ke alamat pembeli.</small></span>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-warm-200 p-4">
                        <input type="checkbox" name="enable_pickup" value="1" @checked(old('enable_pickup', $store->enable_pickup)) class="rounded border-warm-200" />
                        <span><strong class="block">Ambil sendiri di toko</strong><small class="text-sage-500">Pembeli datang ke {{ $store->address }} dengan kode pengambilan.</small></span>
                    </label>
                </div>
                <label class="block text-sm font-semibold">Biaya ongkir (Rp)<input type="number" name="delivery_fee" value="{{ old('delivery_fee', $store->delivery_fee) }}" min="0" required class="mt-2 w-full rounded-lg border-warm-200" /></label>
                <label class="block text-sm font-semibold">Minimal pembelian (Rp)<input type="number" name="min_order_amount" value="{{ old('min_order_amount', $store->min_order_amount) }}" min="0" required class="mt-2 w-full rounded-lg border-warm-200" /><span class="mt-1 block text-xs text-sage-400">Pesanan di bawah nilai ini tidak dapat memakai pengiriman diantar.</span></label>
                <label class="block text-sm font-semibold">Gratis ongkir mulai (Rp)<input type="number" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $store->free_shipping_threshold) }}" min="0" class="mt-2 w-full rounded-lg border-warm-200" /><span class="mt-1 block text-xs text-sage-400">Kosongkan jika tidak ada gratis ongkir.</span></label>
                <button class="w-full rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Simpan pengaturan pengiriman</button>
            </form>
        </section>
    </main>
</body>
</html>



