<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 pb-20 text-forest-950 sm:pb-0">
    <x-app-header :unread-notifications="$unreadNotifications ?? 0" :unread-chats="$unreadChats ?? 0" />
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <div><p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Konfirmasi pesanan</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">Checkout</h1></div>
        @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px] lg:gap-8">
            <form action="{{ route('checkout.store') }}" method="POST" class="space-y-6">@csrf
                <section class="rounded-2xl bg-warm-white p-4 shadow-soft sm:p-6"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Informasi pembeli</p><h2 class="mt-2 text-lg font-black sm:text-xl">{{ $buyer->name }}</h2><p class="mt-1 text-sm text-sage-600">{{ $buyer->phone }} &middot; {{ $buyer->address }}, Blok {{ $buyer->block }} No. {{ $buyer->house_number }}</p></section>
                <section class="rounded-2xl bg-warm-white p-4 shadow-soft sm:p-6"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Alamat pengiriman</p><label class="mt-4 block text-sm font-semibold">Alamat lengkap<input name="shipping_address" value="{{ old('shipping_address', $buyer->address.', Blok '.$buyer->block.' No. '.$buyer->house_number) }}" class="mt-2 w-full rounded-lg border-warm-200" /></label><label class="mt-4 block text-sm font-semibold">Catatan untuk penjual (opsional)<textarea name="shipping_note" rows="2" class="mt-2 w-full rounded-lg border-warm-200">{{ old('shipping_note') }}</textarea></label><p class="mt-2 text-xs text-sage-400">Alamat ini dipakai untuk pesanan yang diantar. Pesanan ambil di toko memakai lokasi toko.</p></section>
                <section class="rounded-2xl bg-warm-white p-4 shadow-soft sm:p-6">
                    <p class="text-sm font-bold uppercase tracking-wider text-forest-700">Pengiriman per toko</p>
                    <div class="mt-4 space-y-5">
                        @foreach ($sellerGroups as $group)
                            @php
                                $store = $group['store'];
                            @endphp
                            <div class="rounded-xl border border-warm-200 p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-bold">{{ $store->store_name }}</p>
                                    <p class="text-sm text-sage-500">Subtotal Rp{{ number_format($group['subtotal'], 0, ',', '.') }}</p>
                                </div>
                                @if (empty($group['methods']))
                                    <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Toko ini belum membuka metode pengiriman.</p>
                                @else
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach ($group['methods'] as $method)
                                            <label class="flex cursor-pointer gap-3 rounded-xl border border-warm-200 p-4">
                                                <input type="radio" name="shipping_methods[{{ $store->id }}]" value="{{ $method['value'] }}" required @checked($loop->first)>
                                                <span>
                                                    <strong class="block">{{ $method['label'] }}</strong>
                                                    <small class="text-sage-500">{{ $method['fee'] > 0 ? 'Ongkir Rp'.number_format($method['fee'], 0, ',', '.') : 'Gratis ongkir' }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($store->min_order_amount > 0)
                                    <p class="mt-3 text-xs text-sage-500">Minimal pembelian untuk diantar: Rp{{ number_format($store->min_order_amount, 0, ',', '.') }}@if ($store->free_shipping_threshold) &middot; gratis ongkir mulai Rp{{ number_format($store->free_shipping_threshold, 0, ',', '.') }}@endif</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
                <section class="rounded-2xl bg-warm-white p-4 shadow-soft sm:p-6">
                    <p class="text-sm font-bold uppercase tracking-wider text-forest-700">Pembayaran</p>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <label title="Transfer bank" class="group flex min-h-24 cursor-pointer items-center justify-center rounded-xl border border-warm-200 p-3 transition hover:border-forest-300 has-checked:border-forest-700 has-checked:bg-forest-50">
                            <input class="peer sr-only" type="radio" name="payment_method" value="bank_transfer" aria-label="Transfer bank" required @checked(old('payment_method', 'bank_transfer') === 'bank_transfer')>
                            <span class="flex h-12 w-12 items-center justify-center rounded-lg text-forest-700 peer-focus-visible:ring-2 peer-focus-visible:ring-forest-700" aria-hidden="true">
                                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7M4.5 10.5h15M5.25 20.25h13.5M7.5 10.5v7.5m4.5-7.5v7.5m4.5-7.5v7.5M3 20.25h18"/></svg>
                            </span>
                            <span class="sr-only">Transfer bank</span>
                        </label>
                        <label title="QRIS" class="group flex min-h-24 cursor-pointer items-center justify-center rounded-xl border border-warm-200 p-3 transition hover:border-forest-300 has-checked:border-forest-700 has-checked:bg-forest-50 has-disabled:cursor-not-allowed has-disabled:opacity-50">
                            <input class="peer sr-only" type="radio" name="payment_method" value="qris" aria-label="QRIS" required @checked(old('payment_method') === 'qris') @disabled(! $qrisAvailable)>
                            <span class="flex h-12 w-12 items-center justify-center rounded-lg text-forest-700 peer-focus-visible:ring-2 peer-focus-visible:ring-forest-700" aria-hidden="true">
                                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6zm10.5 0h6v6h-6zm-10.5 10.5h6v6h-6zm10.5 0h2.25v2.25h-2.25zm3.75 0h2.25v2.25H18zm-3.75 3.75h2.25v2.25h-2.25zm3.75 0h2.25v2.25H18z"/></svg>
                            </span>
                            <span class="sr-only">QRIS</span>
                        </label>
                        <label title="COD" class="group flex min-h-24 cursor-pointer items-center justify-center rounded-xl border border-warm-200 p-3 transition hover:border-forest-300 has-checked:border-forest-700 has-checked:bg-forest-50">
                            <input class="peer sr-only" type="radio" name="payment_method" value="cod" aria-label="COD" required @checked(old('payment_method') === 'cod')>
                            <span class="flex h-12 w-12 items-center justify-center rounded-lg text-forest-700 peer-focus-visible:ring-2 peer-focus-visible:ring-forest-700" aria-hidden="true">
                                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h11.25v9H3zm11.25 3h3.3l3.45 3.6v2.4h-6.75zm-7.5 7.5a1.875 1.875 0 1 0 0 .001zm10.5 0a1.875 1.875 0 1 0 0 .001zM5.25 10.5h6.75"/></svg>
                            </span>
                            <span class="sr-only">COD</span>
                        </label>
                    </div>
                    @unless ($qrisAvailable)
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">QRIS belum tersedia karena ada toko yang belum mengunggah QRIS-nya.</p>
                    @endunless
                    <div id="bank-transfer-details" class="mt-5 space-y-3 rounded-xl bg-forest-50 p-4">
                        <p class="text-sm font-bold text-forest-900">Rekening transfer per toko</p>
                        @foreach ($sellerGroups as $group)
                            @php
                                $store = $group['store'];
                            @endphp
                            <div class="border-t border-forest-200 pt-3 text-sm">
                                <p class="font-bold text-forest-900">{{ $store->store_name }}</p>
                                @if ($store->paymentSetting?->bank_name && $store->paymentSetting?->bank_account_number)
                                    @php
                                        $bankName = $store->paymentSetting->bank_name;
                                        $bankNameNormalized = Str::lower($bankName);
                                        $bankLogo = match (true) {
                                            Str::contains($bankNameNormalized, ['bri', 'rakyat indonesia']) => 'https://upload.wikimedia.org/wikipedia/commons/5/59/BRI_2025.svg',
                                            Str::contains($bankNameNormalized, ['bca', 'central asia']) => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg',
                                            Str::contains($bankNameNormalized, ['bni', 'negara indonesia']) => 'https://commons.wikimedia.org/wiki/Special:FilePath/Bank_Negara_Indonesia_logo_%282004%29.svg',
                                            Str::contains($bankNameNormalized, 'mandiri') => 'https://upload.wikimedia.org/wikipedia/commons/5/5d/Bank_Mandiri_logo_2016_%28with_English_slogan%29.svg',
                                            default => null,
                                        };
                                    @endphp
                                    <div class="mt-2 flex flex-wrap items-center gap-3">
                                        <span role="img" aria-label="{{ $bankName }}" title="{{ $bankName }}" class="relative inline-flex h-10 min-w-16 items-center justify-center overflow-hidden rounded-md border border-warm-200 bg-white px-2 text-xs font-black text-forest-800">
                                            <span aria-hidden="true">{{ Str::upper(Str::substr($bankName, 0, 4)) }}</span>
                                            @if ($bankLogo)
                                                <img src="{{ $bankLogo }}" alt="" data-bank-logo class="absolute inset-0 h-full w-full bg-white object-contain p-1">
                                            @endif
                                        </span>
                                        <p class="font-semibold text-forest-800">{{ $store->paymentSetting->bank_account_number }}</p>
                                        <button type="button" data-copy-text="{{ $store->paymentSetting->bank_account_number }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-forest-200 text-forest-700 transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-700" title="Salin nomor rekening" aria-label="Salin nomor rekening {{ $bankName }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25V5.625c0-.621.504-1.125 1.125-1.125h9c.621 0 1.125.504 1.125 1.125v9c0 .621-.504 1.125-1.125 1.125H15.75M5.625 8.25h9c.621 0 1.125.504 1.125 1.125v9c0 .621-.504 1.125-1.125 1.125h-9A1.125 1.125 0 0 1 4.5 18.375v-9c0-.621.504-1.125 1.125-1.125Z"/></svg>
                                        </button>
                                        <span data-copy-status aria-live="polite" class="text-xs text-forest-700"></span>
                                    </div>
                                    <p class="mt-1 text-forest-700">a.n. {{ $store->paymentSetting->bank_account_name }}</p>
                                @else
                                    <p class="mt-1 text-red-700">Rekening seller belum tersedia.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
                <button class="w-full rounded-xl bg-forest-700 px-5 py-4 font-bold text-white hover:bg-forest-800">Buat pesanan</button>
            </form>
            <aside class="h-fit rounded-2xl bg-forest-950 p-6 text-white lg:sticky lg:top-24"><p class="text-sm text-forest-200">Pesanan Anda</p><div class="mt-5 space-y-4">@foreach ($products as $product)<div class="flex justify-between gap-4 text-sm"><span class="min-w-0">{{ $product->name }} x {{ $quantities[$product->id] }}@if ($product->hasDiscount())<small class="block text-forest-300">diskon {{ $product->discount_percent }}%</small>@endif</span><strong class="shrink-0">Rp{{ number_format($product->effectivePrice() * $quantities[$product->id], 0, ',', '.') }}</strong></div>@endforeach</div><div class="mt-6 space-y-2 border-t border-white/20 pt-5 text-sm"><div class="flex justify-between"><span>Subtotal</span><span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span></div><div class="flex justify-between"><span>Ongkos kirim</span><span>Rp{{ number_format($shippingFee, 0, ',', '.') }}</span></div></div><div class="mt-4 flex justify-between border-t border-white/20 pt-5 text-lg"><span>Total</span><strong>Rp{{ number_format($subtotal + $shippingFee, 0, ',', '.') }}</strong></div></aside>
        </div>
    </main>

    <x-mobile-bottom-nav :unread-notifications="$unreadNotifications ?? 0" :unread-chats="$unreadChats ?? 0" />
    <script>
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const bankDetails = document.getElementById('bank-transfer-details');
        paymentMethods.forEach((method) => method.addEventListener('change', () => {
            bankDetails.hidden = document.querySelector('input[name="payment_method"]:checked').value !== 'bank_transfer';
        }));
    </script>
</body>
</html>



