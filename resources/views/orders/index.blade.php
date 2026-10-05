<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Saya - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="border-b border-warm-200/60 bg-warm-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 sm:py-5"><x-brand-logo /><div class="flex gap-3"><a href="{{ route('cart.index') }}" class="rounded-lg border border-warm-200 px-4 py-2 text-sm font-semibold">Keranjang</a><a href="{{ route('dashboard') }}" class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-semibold text-white">Dashboard</a></div></div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-10">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Aktivitas belanja</p>
        <h1 class="mt-2 text-4xl font-black">Pesanan saya</h1>
        @if ($orders->isEmpty())
            <section class="mt-10 rounded-2xl bg-warm-white p-12 text-center shadow-soft"><h2 class="text-2xl font-black">Belum ada pesanan</h2><p class="mt-2 text-sage-500">Pesanan yang Anda buat akan muncul di sini.</p><a href="{{ route('marketplace.index') }}" class="mt-6 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white">Mulai belanja</a></section>
        @else
            <div class="mt-8 space-y-5">
                @foreach ($orders as $order)
                    <article class="rounded-2xl bg-warm-white p-6 shadow-soft">
                        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-sage-400">{{ $order->order_number }}</p><p class="mt-1 text-sm text-sage-500">{{ $order->created_at->format('d M Y, H:i') }}</p></div><div class="text-right"><p class="text-xl font-black text-forest-700">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</p><span class="mt-1 inline-block rounded-full bg-forest-100 px-3 py-1 text-xs font-bold text-forest-700">{{ ucfirst($order->status) }}</span></div></div>
                        <div class="mt-5 grid gap-3 border-t border-warm-100 pt-5 sm:grid-cols-2">@foreach ($order->sellerOrders as $sellerOrder)<div class="rounded-xl bg-warm-50 p-4"><div class="flex items-center justify-between gap-3"><strong>{{ $sellerOrder->sellerProfile->store_name }}</strong><span class="text-xs font-bold text-sage-500">{{ ucfirst($sellerOrder->shipping_status) }}</span></div><p class="mt-2 text-sm text-sage-500">{{ ucfirst(str_replace('_', ' ', $sellerOrder->shipping_method)) }} Â· Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</p></div>@endforeach</div>
                        <a href="{{ route('orders.show', $order) }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-3 text-sm font-bold text-white hover:bg-forest-800">Lihat tracking pesanan</a>
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>



