<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store->store_name }} - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="sticky top-0 z-40 border-b border-warm-200/60 bg-warm-50/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-10">
            <div class="flex min-w-0 items-center gap-2">
                <x-back-button :fallback="route('marketplace.index')" />
                <x-brand-logo />
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    @if (auth()->user()->role === 'buyer')
                        <a href="{{ route('cart.index') }}" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warm-white text-forest-700 shadow-soft ring-1 ring-warm-200 transition hover:bg-forest-700 hover:text-white" title="Keranjang" aria-label="Keranjang">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-forest-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-800 sm:px-5">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden px-3 py-2 text-sage-700 hover:text-forest-700 sm:inline">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-forest-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-800 sm:px-5">Daftar</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        <section class="bg-forest-950 px-5 py-12 text-white sm:px-6 lg:px-10 lg:py-16">
            <div class="mx-auto max-w-7xl">
                <div class="mt-6 flex flex-wrap items-start justify-between gap-6">
                    <div class="flex items-start gap-5">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-lime-300 text-3xl font-black text-forest-950">
                            @if ($store->image)
                                <img src="{{ asset('storage/'.$store->image) }}" alt="{{ $store->store_name }}" class="h-full w-full object-cover" />
                            @else
                                {{ strtoupper(substr($store->store_name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <h1 class="text-3xl font-black sm:text-4xl">{{ $store->store_name }}</h1>
                            @if ($store->description)
                                <p class="mt-3 max-w-2xl leading-7 text-forest-100">{{ $store->description }}</p>
                            @endif
                            <p class="mt-4 text-sm text-forest-200">{{ $store->address }}</p>
                            <p class="mt-1 text-sm text-forest-200">Jam operasional: {{ $store->businessHoursLabel() }}</p>
                            <p class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $store->isOpenNow() ? 'bg-lime-300 text-forest-950' : 'bg-red-600 text-white' }}">
                                {{ $store->isOpenNow() ? 'Sedang buka' : 'Sedang tutup' }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ \App\Support\WhatsApp::chatLink($store->phone, 'Halo '.$store->store_name.', saya ingin bertanya tentang produk yang dijual.') }}" target="_blank" rel="noopener" class="rounded-full bg-green-600 px-5 py-3 text-sm font-bold text-white hover:bg-green-700">Hubungi via WhatsApp</a>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Katalog toko</p>
                    <h2 class="mt-2 text-2xl font-black sm:text-3xl">{{ $products->count() }} produk tersedia</h2>
                </div>
                @if ($categories->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $category)
                            <span class="rounded-full bg-warm-white px-4 py-2 text-sm font-bold text-sage-600">{{ $category->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($products->isEmpty())
                <p class="mt-8 rounded-2xl border border-dashed border-warm-200 bg-warm-white p-10 text-center text-sm text-sage-500">Toko ini belum memiliki produk aktif.</p>
            @else
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <article class="group flex flex-col overflow-hidden rounded-2xl bg-warm-white shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                            <a href="{{ route('products.show', $product) }}" class="relative flex aspect-4/3 items-end bg-linear-to-br from-lime-100 via-forest-100 to-forest-200 p-5">
                                @if ($product->image)
                                    <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 h-full w-full object-cover" />
                                @else
                                    <span class="text-5xl font-black text-forest-900/20">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                @endif
                                @if ($product->hasDiscount())
                                    <span class="absolute right-4 top-4 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">-{{ $product->discount_percent }}%</span>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-xs font-semibold uppercase tracking-wider text-sage-400">{{ $product->category->name }}</p>
                                <h3 class="mt-2 text-lg font-bold"><a href="{{ route('products.show', $product) }}" class="hover:text-forest-700">{{ $product->name }}</a></h3>
                                @if ($product->reviews_count > 0)
                                    <p class="mt-2 text-sm font-bold text-amber-600">{{ number_format($product->reviews_avg_rating, 1, ',', '.') }}/5 &middot; {{ $product->reviews_count }} review</p>
                                @endif
                                <div class="mt-4 flex items-center justify-between">
                                    @if ($product->hasDiscount())
                                        <div>
                                            <span class="block text-xs text-sage-400 line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                                            <strong class="text-lg text-red-600">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>
                                        </div>
                                    @else
                                        <strong class="text-lg text-forest-700">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>
                                    @endif
                                    <span class="text-xs text-sage-400">Stok {{ $product->stock }}</span>
                                </div>
                                <a href="{{ route('products.show', $product) }}" class="mt-4 block rounded-xl border border-forest-700 px-4 py-3 text-center text-sm font-bold text-forest-700 hover:bg-forest-50">Lihat detail</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</body>
</html>



