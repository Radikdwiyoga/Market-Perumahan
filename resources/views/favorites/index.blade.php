<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Favorit - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="sticky top-0 z-40 border-b border-warm-200/60 bg-warm-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex min-w-0 items-center gap-2">
                <x-back-button :fallback="route('dashboard')" />
                <x-brand-logo />
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('marketplace.index') }}" class="rounded-lg border border-warm-200 px-3 py-2 text-sm font-semibold hover:bg-warm-50">Katalog</a>
                <a href="{{ route('dashboard') }}" class="rounded-full bg-forest-700 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-800 sm:px-5">Dashboard</a>
            </div>
        </nav>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <div><p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Daftar keinginan Anda</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">Favorit</h1></div>
        @if (session('status'))<div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>@endif
        @if ($favorites->isEmpty())
            <section class="mt-10 rounded-2xl bg-warm-white p-8 text-center shadow-soft sm:p-12"><h2 class="text-2xl font-black">Belum ada produk favorit</h2><p class="mt-2 text-sm text-sage-500 sm:text-base">Simpan produk yang Anda suka supaya mudah ditemukan lagi.</p><a href="{{ route('marketplace.index') }}" class="mt-6 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white">Jelajahi katalog</a></section>
        @else
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($favorites as $favorite)
                    @php($product = $favorite->product)
                    <article class="flex flex-col rounded-2xl bg-warm-white p-5 shadow-soft">
                        @if ($product->image)
                            <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="h-40 w-full rounded-xl object-cover">
                        @else
                            <div class="flex h-40 items-center justify-center rounded-xl bg-warm-50 text-sm font-bold text-sage-400">Tidak ada foto</div>
                        @endif
                        <h2 class="mt-4 line-clamp-2 font-bold"><a href="{{ route('products.show', $product) }}" class="hover:text-forest-700 hover:underline">{{ $product->name }}</a></h2>
                        <p class="mt-1 text-sm text-sage-500"><a href="{{ route('stores.show', $product->sellerProfile) }}" class="hover:underline">oleh {{ $product->sellerProfile->store_name }}</a></p>
                        <p class="mt-2 font-black text-forest-700">@if ($product->hasDiscount())<span class="mr-1 text-sm font-semibold text-sage-400 line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span>@endif Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</p>
                        <div class="mt-4 flex gap-2">
                            @if ($product->status === 'active' && $product->stock > 0)
                                <form action="{{ route('cart.store', $product) }}" method="POST" class="flex-1">@csrf<input type="hidden" name="quantity" value="1" /><button class="w-full rounded-lg bg-forest-700 px-3 py-2 text-sm font-bold text-white hover:bg-forest-800">Beli</button></form>
                            @else
                                <span class="flex-1 rounded-lg bg-warm-50 px-3 py-2 text-center text-sm font-bold text-sage-400">{{ $product->stock === 0 ? 'Stok habis' : 'Nonaktif' }}</span>
                            @endif
                            <form action="{{ route('favorites.destroy', $product) }}" method="POST">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50" title="Hapus dari favorit">Hapus</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $favorites->links() }}</div>
        @endif
    </main>
</body>
</html>


