<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 pb-20 text-forest-950 sm:pb-0">
    <x-app-header :cart-count="$cartCount" :unread-notifications="$unreadNotifications ?? 0" :unread-chats="$unreadChats ?? 0" />

    <main>
        @if (session('status'))
            <div id="flash-status" class="fixed left-4 right-4 top-4 z-50 flex items-center gap-3 rounded-2xl bg-forest-700 px-5 py-4 text-sm font-semibold text-white shadow-xl sm:left-auto sm:right-5 sm:top-5 sm:max-w-md">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-lime-300 text-forest-900">&#10003;</span>
                <span class="min-w-0 flex-1">{{ session('status') }}</span>
                <a href="{{ route('cart.index') }}" class="shrink-0 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold hover:bg-white/25">Lihat keranjang</a>
                <button type="button" onclick="this.parentElement.remove()" class="shrink-0 text-white/70 hover:text-white">&times;</button>
            </div>
        @endif
        @if ($errors->any())
            <div id="flash-error" class="fixed left-4 right-4 top-4 z-50 flex items-center gap-3 rounded-2xl bg-red-700 px-5 py-4 text-sm font-semibold text-white shadow-xl sm:left-auto sm:right-5 sm:top-5 sm:max-w-md">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/25">!</span>
                <span class="min-w-0 flex-1">{{ $errors->first() }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="shrink-0 text-white/70 hover:text-white">&times;</button>
            </div>
        @endif
        <script>
            document.querySelectorAll('#flash-status, #flash-error').forEach((toast) => setTimeout(() => toast.remove(), 4000));
        </script>
        <section class="relative overflow-hidden bg-forest-950 px-5 py-16 text-white sm:px-6 lg:px-10 lg:py-24">
            <div class="absolute -right-24 -top-32 h-96 w-96 rounded-full border-48 border-lime-300/20"></div>
            <div class="relative mx-auto grid max-w-7xl items-end gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <p class="mb-5 text-sm font-bold uppercase tracking-[0.28em] text-lime-300">Dukung UMKM, hidup makin hangat</p>
                    <h1 class="max-w-3xl text-4xl font-black leading-[1.05] tracking-tight sm:text-6xl lg:text-7xl">Kebutuhan warga, dari UMKM tetangga sendiri.</h1>
                    <p class="mt-7 max-w-xl text-base leading-7 text-forest-100 sm:text-lg sm:leading-8">Temukan produk UMKM &mdash; makanan, sembako, dan kebutuhan harian &mdash; dari usaha kecil di sekitar perumahan. Pesan mudah, dukung langsung pedagang lokal.</p>
                    <form action="{{ route('marketplace.index') }}" method="GET" class="mt-8 flex max-w-xl flex-col gap-2 rounded-2xl bg-warm-white p-2 sm:flex-row sm:gap-3">
                        <input name="q" value="{{ $search }}" placeholder="Cari produk UMKM atau kebutuhan..." class="min-w-0 flex-1 rounded-xl border-0 bg-transparent px-4 py-3.5 text-forest-950 outline-none focus:ring-0" />
                        <button class="shrink-0 rounded-xl bg-lime-300 px-5 py-3.5 font-bold text-forest-950 hover:bg-lime-200">Cari</button>
                    </form>
                </div>
                <div class="hidden justify-self-end lg:block">
                    <div class="w-72 rotate-3 rounded-4xl bg-lime-300 p-7 text-forest-950 shadow-2xl">
                        <p class="text-sm font-bold uppercase tracking-widest">Produk UMKM hari ini</p>
                        <p class="mt-16 text-5xl font-black">{{ $products->count() }}</p>
                        <p class="mt-2 text-lg font-semibold">siap dipesan</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Jelajahi</p><h2 class="mt-2 text-2xl font-black sm:text-3xl">Belanja sesuai kebutuhan</h2></div>
                <span class="hidden text-sm text-sage-500 sm:block">{{ $products->count() }} produk UMKM tersedia</span>
            </div>
            <div class="-mx-4 mt-6 flex gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                <a href="{{ route('marketplace.index') }}" class="shrink-0 rounded-full px-5 py-3 text-sm font-bold {{ !$selectedCategory ? 'bg-forest-700 text-white' : 'bg-warm-white text-sage-600' }}">Semua</a>
                @foreach ($categories as $category)
                    <a href="{{ route('marketplace.index', ['category' => $category->id]) }}" class="shrink-0 rounded-full px-5 py-3 text-sm font-bold {{ $selectedCategory === $category->id ? 'bg-forest-700 text-white' : 'bg-warm-white text-sage-600' }}">{{ $category->name }}</a>
                @endforeach
            </div>

            @if ($products->isEmpty())
                <div class="mt-10 rounded-2xl border border-dashed border-warm-200 bg-warm-white p-8 text-center sm:p-12"><h3 class="text-lg font-bold sm:text-xl">Produk belum ditemukan</h3><p class="mt-2 text-sm text-sage-500 sm:text-base">Coba kata kunci atau kategori lain.</p></div>
            @else
                <div class="mt-8 grid gap-4 sm:mt-10 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <article class="group flex flex-col overflow-hidden rounded-2xl bg-warm-white shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                            <a href="{{ route('products.show', $product) }}" class="relative flex aspect-4/3 items-end justify-between bg-linear-to-br from-lime-100 via-forest-100 to-forest-200 p-5">
                                @if ($product->image)
                                    <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 h-full w-full object-cover" />
                                    <span class="absolute left-4 top-4 rounded-full bg-warm-white/90 px-3 py-1 text-xs font-bold text-forest-900">{{ $product->category->name }}</span>
                                    @if ($product->hasDiscount())<span class="absolute right-4 top-4 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">-{{ $product->discount_percent }}%</span>@endif
                                @else
                                    <span class="rounded-full bg-warm-white/80 px-3 py-1 text-xs font-bold text-forest-900">{{ $product->category->name }}</span>
                                    <span class="text-5xl font-black text-forest-900/20">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-4 sm:p-5">
                                <p class="text-xs font-semibold uppercase tracking-wider text-sage-400"><a href="{{ route('stores.show', $product->sellerProfile) }}" class="hover:text-forest-700">{{ $product->sellerProfile->store_name }}</a></p>
                                <h3 class="mt-2 text-lg font-bold sm:text-xl"><a href="{{ route('products.show', $product) }}" class="hover:text-forest-700">{{ $product->name }}</a></h3>
                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-sage-500">{{ $product->description }}</p>
                                @if ($product->reviews_count > 0)<p class="mt-3 text-sm font-bold text-amber-600">{{ number_format($product->reviews_avg_rating, 1, ',', '.') }}/5 &middot; {{ $product->reviews_count }} review</p>@endif
                                <div class="mt-4 flex items-center justify-between">
                                    <div>
                                        @if ($product->hasDiscount())
                                            <span class="block text-xs text-sage-400 line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                                            <strong class="text-lg text-red-600">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>
                                        @else
                                            <strong class="text-lg text-forest-700">Rp{{ number_format($product->price, 0, ',', '.') }}</strong>
                                        @endif
                                    </div>
                                    <span class="text-xs text-sage-400">Stok {{ $product->stock }}</span>
                                </div>
                                @auth
                                    @if (auth()->user()->role === 'buyer')
                                        <div class="mt-4 space-y-2">
                                            <form action="{{ route('cart.store', $product) }}" method="POST">@csrf<button class="flex w-full items-center justify-center gap-2 rounded-xl bg-forest-700 px-4 py-3 text-sm font-bold text-white hover:bg-forest-800"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>Tambah ke keranjang</button></form>
                                            <div class="grid grid-cols-2 gap-2">
                                                <form action="{{ route('chat.start') }}" method="POST">@csrf<input type="hidden" name="seller_profile_id" value="{{ $product->seller_profile_id }}" /><button class="w-full rounded-xl border border-forest-700 px-4 py-3 text-sm font-bold text-forest-700 hover:bg-forest-50">Chat penjual</button></form>
                                                <a href="{{ \App\Support\WhatsApp::chatLink($product->sellerProfile->phone, 'Halo '.$product->sellerProfile->store_name.', saya tertarik dengan produk "'.$product->name.'" seharga Rp'.number_format($product->effectivePrice(), 0, ',', '.').'.') }}" target="_blank" rel="noopener" class="w-full rounded-xl bg-green-600 px-4 py-3 text-center text-sm font-bold text-white hover:bg-green-700">WhatsApp</a>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="mt-4 block rounded-xl border border-forest-700 px-4 py-3 text-center text-sm font-bold text-forest-700">Masuk untuk membeli</a>
                                @endauth
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($stores->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-10">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Tetangga berjualan</p>
                    <h2 class="mt-2 text-2xl font-black sm:text-3xl">Toko di perumahan</h2>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stores as $store)
                        <a href="{{ route('stores.show', $store) }}" class="group flex items-center gap-4 rounded-2xl bg-warm-white p-5 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-forest-100 text-xl font-black text-forest-900">
                                @if ($store->image)
                                    <img src="{{ asset('storage/'.$store->image) }}" alt="{{ $store->store_name }}" class="h-full w-full object-cover" />
                                @else
                                    {{ strtoupper(substr($store->store_name, 0, 1)) }}
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-black group-hover:text-forest-700">{{ $store->store_name }}</span>
                                <span class="mt-1 block text-sm text-sage-500">{{ $store->products_count }} produk &middot; {{ $store->businessHoursLabel() }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <x-mobile-bottom-nav :cart-count="$cartCount" :unread-notifications="$unreadNotifications ?? 0" :unread-chats="$unreadChats ?? 0" />
</body>
</html>



