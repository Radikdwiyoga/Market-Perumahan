<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }} - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="sticky top-0 z-40 border-b border-warm-200/60 bg-warm-50/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-10">
            <x-brand-logo />
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

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-10">
        @if (session('status'))
            <div class="mb-6 flex items-center justify-between gap-3 rounded-2xl bg-forest-700 px-5 py-4 text-sm font-semibold text-white">
                <span>{{ session('status') }}</span>
                <a href="{{ route('cart.index') }}" class="shrink-0 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold hover:bg-white/25">Lihat keranjang</a>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-red-700 px-5 py-4 text-sm font-semibold text-white">{{ $errors->first() }}</div>
        @endif

        <nav class="text-sm text-sage-500">
            <a href="{{ route('marketplace.index') }}" class="font-semibold hover:text-forest-700">Katalog</a>
            <span class="mx-2">/</span>
            <a href="{{ route('stores.show', $product->sellerProfile) }}" class="font-semibold hover:text-forest-700">{{ $product->sellerProfile->store_name }}</a>
            <span class="mx-2">/</span>
            <span class="text-sage-700">{{ $product->name }}</span>
        </nav>

        <section class="mt-6 grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="relative flex aspect-4/3 items-end overflow-hidden rounded-3xl bg-linear-to-br from-lime-100 via-forest-100 to-forest-200 p-6">
                @if ($product->image)
                    <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 h-full w-full object-cover" />
                    <span class="absolute left-5 top-5 rounded-full bg-warm-white/90 px-3 py-1 text-xs font-bold text-forest-900">{{ $product->category->name }}</span>
                    @if ($product->hasDiscount())
                        <span class="absolute right-5 top-5 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">-{{ $product->discount_percent }}%</span>
                    @endif
                @else
                    <span class="rounded-full bg-warm-white/80 px-3 py-1 text-xs font-bold text-forest-900">{{ $product->category->name }}</span>
                    <span class="text-7xl font-black text-forest-900/20">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-sage-400">{{ $product->category->name }}</p>
                <h1 class="mt-2 text-3xl font-black leading-tight sm:text-4xl">{{ $product->name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                    @if ($reviews->isNotEmpty())
                        <span class="font-bold text-amber-600">{{ number_format($reviews->avg('rating'), 1, ',', '.') }}/5</span>
                        <span class="text-sage-500">{{ $reviews->count() }} review</span>
                    @else
                        <span class="text-sage-500">Belum ada review</span>
                    @endif
                    <span class="text-sage-400">Stok {{ $product->stock }}</span>
                </div>

                <div class="mt-6 flex items-end gap-3">
                    @if ($product->hasDiscount())
                        <span class="text-lg text-sage-400 line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                        <strong class="text-3xl text-red-600">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>
                    @else
                        <strong class="text-3xl text-forest-700">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>
                    @endif
                </div>

                @if ($product->description)
                    <p class="mt-6 leading-7 text-sage-600">{{ $product->description }}</p>
                @endif

                <div class="mt-6 rounded-2xl bg-warm-white p-5 shadow-soft">
                    <p class="text-sm font-bold">Dikirim oleh</p>
                    <a href="{{ route('stores.show', $product->sellerProfile) }}" class="mt-2 block text-lg font-black text-forest-700 hover:underline">{{ $product->sellerProfile->store_name }}</a>
                    <p class="mt-1 text-sm text-sage-500">{{ $product->sellerProfile->address }}</p>
                    <p class="mt-1 text-sm text-sage-500">Jam operasional: {{ $product->sellerProfile->businessHoursLabel() }}</p>
                </div>

                <div class="mt-4 rounded-2xl bg-warm-white p-5 shadow-soft">
                    <p class="text-sm font-bold">Metode pengiriman</p>
                    @php($methods = $product->sellerProfile->availableShippingMethods())
                    @if (empty($methods))
                        <p class="mt-2 text-sm text-sage-500">Toko ini belum membuka metode pengiriman.</p>
                    @else
                        <ul class="mt-2 space-y-1 text-sm text-sage-600">
                            @foreach ($methods as $method)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2"><span class="text-forest-700">&#10003;</span> {{ $method === 'seller_delivery' ? 'Diantar oleh penjual' : 'Ambil sendiri di toko' }}</span>
                                    @if ($method === 'seller_delivery' && $product->sellerProfile->delivery_fee > 0)
                                        <span class="text-sage-500">Ongkir Rp{{ number_format($product->sellerProfile->delivery_fee, 0, ',', '.') }} per pesanan</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($product->sellerProfile->min_order_amount > 0)
                            <p class="mt-2 text-xs text-sage-500">Minimal pembelian diantar: Rp{{ number_format($product->sellerProfile->min_order_amount, 0, ',', '.') }}@if ($product->sellerProfile->free_shipping_threshold) &middot; gratis ongkir mulai Rp{{ number_format($product->sellerProfile->free_shipping_threshold, 0, ',', '.') }}@endif</p>
                        @endif
                    @endif
                    <p class="mt-4 text-sm font-bold">Metode pembayaran</p>
                    <ul class="mt-2 space-y-1 text-sm text-sage-600">
                        @if ($product->sellerProfile->paymentSetting?->bank_name && $product->sellerProfile->paymentSetting?->bank_account_number)
                            <li class="flex items-center gap-2"><span class="text-forest-700">&#10003;</span> Transfer bank ({{ $product->sellerProfile->paymentSetting->bank_name }})</li>
                        @endif
                        @if ($product->sellerProfile->paymentSetting?->qris_image)
                            <li class="flex items-center gap-2"><span class="text-forest-700">&#10003;</span> QRIS toko</li>
                        @endif
                        <li class="flex items-center gap-2"><span class="text-forest-700">&#10003;</span> COD (bayar saat diterima)</li>
                    </ul>
                </div>

                @auth
                    @if (auth()->user()->role === 'buyer')
                        @if ($product->stock > 0)
                            <form action="{{ route('cart.store', $product) }}" method="POST" class="mt-6 flex flex-wrap items-center gap-3">
                                @csrf
                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="w-28 rounded-xl border-warm-200" />
                                <button class="flex-1 rounded-xl bg-forest-700 px-5 py-3.5 font-bold text-white hover:bg-forest-800">Tambah ke keranjang</button>
                            </form>
                        @else
                            <p class="mt-6 rounded-xl bg-amber-100 px-5 py-4 text-sm font-bold text-amber-800">Stok produk ini sedang habis.</p>
                        @endif
                        <form action="{{ $isFavorite ? route('favorites.destroy', $product) : route('favorites.store', $product) }}" method="POST" class="mt-3">
                            @csrf
                            @if ($isFavorite)
                                @method('DELETE')
                            @endif
                            <button class="flex w-full items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-bold {{ $isFavorite ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-warm-200 text-sage-700 hover:bg-white' }}">
                                @if ($isFavorite)
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.75.75 0 01-.75 0l-.003-.001z" /></svg>
                                    Tersimpan di Favorit
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                    Simpan ke Favorit
                                @endif
                            </button>
                        </form>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <form action="{{ route('chat.start') }}" method="POST">@csrf<input type="hidden" name="seller_profile_id" value="{{ $product->seller_profile_id }}" /><button class="w-full rounded-xl border border-forest-700 px-4 py-3 text-sm font-bold text-forest-700 hover:bg-forest-50">Chat penjual</button></form>
                            <a href="{{ \App\Support\WhatsApp::chatLink($product->sellerProfile->phone, 'Halo '.$product->sellerProfile->store_name.', saya tertarik dengan produk "'.$product->name.'" seharga Rp'.number_format($product->effectivePrice(), 0, ',', '.').'.') }}" target="_blank" rel="noopener" class="w-full rounded-xl bg-green-600 px-4 py-3 text-center text-sm font-bold text-white hover:bg-green-700">WhatsApp</a>
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="mt-6 block rounded-xl bg-forest-700 px-5 py-3.5 text-center font-bold text-white">Masuk untuk membeli</a>
                @endauth
            </div>
        </section>

        <section class="mt-12">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Review warga</p>
            <h2 class="mt-2 text-2xl font-black">Apa kata pembeli</h2>
            @if ($reviews->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-warm-200 bg-warm-white p-8 text-center text-sm text-sage-500">Belum ada review untuk produk ini.</p>
            @else
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($reviews as $review)
                        <article class="rounded-2xl bg-warm-white p-5 shadow-soft">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold">{{ Str::before($review->buyer->name, ' ') }}</p>
                                <p class="text-sm font-bold text-amber-600">{{ $review->rating }}/5</p>
                            </div>
                            @if ($review->review)
                                <p class="mt-2 text-sm leading-6 text-sage-600">{{ $review->review }}</p>
                            @endif
                            <p class="mt-3 text-xs text-sage-400">{{ $review->created_at->format('d M Y') }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</body>
</html>



