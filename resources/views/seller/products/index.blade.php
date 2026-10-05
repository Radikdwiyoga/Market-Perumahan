<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk Toko - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="border-b border-warm-200 bg-warm-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
            <div class="flex items-center gap-3"><x-brand-logo /><h1 class="text-xl font-black sm:text-2xl">Produk {{ $store->store_name }}</h1></div>
            <div class="flex items-center gap-3"><a href="{{ route('dashboard') }}" class="text-sm font-semibold text-sage-600">Dashboard</a><form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-lg border border-warm-200 px-4 py-2 text-sm font-semibold">Keluar</button></form></div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))<div class="mb-6 rounded-lg bg-forest-100 px-4 py-3 text-sm font-semibold text-forest-700">{{ session('status') }}</div>@endif
        <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-sage-500">{{ $products->count() }} produk terdaftar</p><p class="mt-1 text-sm text-sage-500">{{ $store->address }}</p></div><div class="flex flex-wrap gap-3"><a href="{{ route('seller.store.edit') }}" class="rounded-xl border border-warm-200 bg-warm-white px-5 py-3 font-bold text-sage-700">Atur toko</a><a href="{{ route('seller.shipping.edit') }}" class="rounded-xl border border-warm-200 bg-warm-white px-5 py-3 font-bold text-sage-700">Pengiriman</a><a href="{{ route('seller.products.create') }}" class="rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Tambah produk</a></div></div>
        <div class="mt-8 overflow-hidden rounded-2xl bg-warm-white shadow-soft">
            <div class="hidden grid-cols-[1fr_150px_100px_110px] gap-4 border-b border-warm-200 px-6 py-4 text-xs font-bold uppercase tracking-wider text-sage-400 sm:grid"><span>Produk</span><span>Kategori</span><span>Stok</span><span>Harga</span></div>
            @forelse ($products as $product)
                <div class="grid gap-3 border-b border-warm-100 px-6 py-5 last:border-0 sm:grid-cols-[1fr_150px_100px_150px_130px] sm:items-center sm:gap-4"><div><div class="flex items-center gap-3">@if ($product->image)<img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="h-12 w-12 rounded-lg object-cover">@endif<h2 class="font-bold">{{ $product->name }}</h2></div><p class="mt-1 text-sm text-sage-500">{{ $product->description }}</p></div><span class="text-sm text-sage-600">{{ $product->category->name }}</span><span class="text-sm font-semibold">{{ $product->stock }}</span><strong class="text-forest-700">@if ($product->hasDiscount())<span class="mr-1 text-xs font-normal text-sage-400 line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span><span>Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</span><span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">-{{ $product->discount_percent }}%</span>@else Rp{{ number_format($product->price, 0, ',', '.') }}@endif</strong><div class="flex gap-2"><a href="{{ route('seller.products.edit', $product) }}" class="rounded-lg border border-warm-200 px-3 py-2 text-xs font-bold">Edit</a><form action="{{ route('seller.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700">Hapus</button></form></div></div>
            @empty
                <div class="px-6 py-14 text-center"><h2 class="text-xl font-bold">Belum ada produk</h2><p class="mt-2 text-sage-500">Tambahkan produk pertama untuk mulai berjualan.</p></div>
            @endforelse
        </div>
    </main>
</body>
</html>



