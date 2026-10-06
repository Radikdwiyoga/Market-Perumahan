<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Masuk - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('seller.dashboard') }}" class="text-sm font-bold text-forest-700">&larr; Dashboard toko</a>
                <h1 class="mt-2 text-3xl font-black">Pesanan masuk</h1>
                <p class="mt-1 text-sage-500">{{ $store->store_name }}</p>
            </div>
            <a href="{{ route('seller.products.index') }}" class="rounded-xl border border-warm-200 bg-warm-white px-4 py-3 text-sm font-bold">Produk toko</a>
        </div>
        @if (session('status'))
            <div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('seller.orders.index') }}" method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl bg-warm-white p-5 shadow-soft">
            <label class="text-sm font-semibold">
                Cari
                <input type="search" name="q" value="{{ $search }}" placeholder="Nomor pesanan atau nama pembeli" class="mt-1 block w-64 rounded-lg border-warm-200 text-sm" />
            </label>
            <label class="text-sm font-semibold">
                Status
                <select name="status" class="mt-1 block rounded-lg border-warm-200 text-sm">
                    <option value="">Semua</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="processing" @selected($status === 'processing')>Processing</option>
                    <option value="completed" @selected($status === 'completed')>Completed</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
            </label>
            <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Terapkan</button>
            @if ($search || $status)
                <a href="{{ route('seller.orders.index') }}" class="rounded-lg border border-warm-200 px-4 py-2 text-sm font-bold">Reset</a>
            @endif
        </form>
        <div class="mt-6 space-y-5">
            @forelse ($sellerOrders as $sellerOrder)
                <article class="rounded-2xl bg-warm-white p-6 shadow-soft">
                    <div class="flex flex-wrap justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wider text-sage-400">{{ $sellerOrder->order->order_number }}</p>
                            <h2 class="mt-1 text-xl font-black">{{ $sellerOrder->order->buyer->name }}</h2>
                            <p class="mt-1 text-sm text-sage-500">{{ $sellerOrder->order->buyer->phone }} &middot; {{ $sellerOrder->order->buyer->address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-forest-700">Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</p>
                            <p class="mt-1 text-sm text-sage-500">Pesanan: {{ ucfirst($sellerOrder->status) }}</p>
                            <p class="mt-1 text-sm font-bold text-forest-700">Metode pengiriman: {{ $sellerOrder->shipping_method === 'seller_delivery' ? 'Diantar oleh seller' : 'Diambil langsung oleh buyer' }}</p>
                            <p class="mt-1 text-sm text-sage-500">Status pengiriman: {{ ucfirst(str_replace('_', ' ', $sellerOrder->shipping_status)) }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-warm-100 pt-5">
                        <a href="{{ route('seller.orders.show', $sellerOrder) }}" class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white hover:bg-forest-800">Detail pesanan</a>
                        @if ($sellerOrder->shipping_method === 'store_pickup' && $sellerOrder->pickup_code)
                            <p class="text-sm text-sage-500">Kode pengambilan: <strong class="tracking-widest">{{ $sellerOrder->pickup_code }}</strong></p>
                        @endif
                        @if ($sellerOrder->payments->contains(fn ($payment): bool => $payment->status === 'pending'))
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Menunggu verifikasi pembayaran</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-warm-white p-12 text-center shadow-soft">
                    <h2 class="text-xl font-black">{{ $search || $status ? 'Tidak ada pesanan yang cocok' : 'Belum ada pesanan' }}</h2>
                    <p class="mt-2 text-sage-500">{{ $search || $status ? 'Coba ubah kata kunci atau filter status.' : 'Pesanan pembeli akan muncul di sini.' }}</p>
                </div>
            @endforelse
        </div>
        @if ($sellerOrders->hasPages())
            <div class="mt-6">{{ $sellerOrders->links() }}</div>
        @endif
    </main>
</body>
</html>



