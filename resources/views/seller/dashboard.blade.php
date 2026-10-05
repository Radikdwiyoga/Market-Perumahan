<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Toko - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 pb-20 text-forest-950 sm:pb-0">
    <x-notification-stream />
    <x-app-header :unread-notifications="$unreadNotifications" :unread-chats="0" />
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-forest-100 px-4 py-3 text-sm font-semibold text-forest-800">{{ session('status') }}</div>
        @endif
        <div class="mb-6">
            <h1 class="text-2xl font-black">Dashboard Toko</h1>
            <p class="text-sm text-sage-500">{{ $store->store_name }}</p>
        </div>
        <section class="rounded-2xl bg-forest-950 p-7 text-white shadow-lg sm:p-8">
            <p class="text-sm text-forest-200">Penjualan hari ini</p>
            <p class="mt-2 text-4xl font-black sm:text-5xl">Rp{{ number_format($stats['revenueToday'], 0, ',', '.') }}</p>
            <p class="mt-3 text-forest-100">{{ $activeProductCount }} produk aktif Â· {{ $store->isOpenNow() ? 'Toko sedang buka' : 'Toko sedang tutup' }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('seller.products.index') }}" class="rounded-xl bg-warm-white px-5 py-3 font-bold text-forest-900">Kelola produk</a>
                <a href="{{ route('seller.orders.index') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pesanan masuk</a>
                <a href="{{ route('seller.shipping.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pengaturan pengiriman</a>
                <a href="{{ route('seller.payment.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pengaturan pembayaran</a>
                
                <a href="{{ route('seller.store.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Profil toko</a>
                <a href="{{ route('seller.reports.index') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Laporan penjualan</a>
            </div>
        </section>
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Pesanan baru</p>
                <p class="mt-2 text-3xl font-black text-forest-700">{{ $stats['newOrders'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Sedang diproses</p>
                <p class="mt-2 text-3xl font-black text-forest-700">{{ $stats['processing'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Siap diambil</p>
                <p class="mt-2 text-3xl font-black text-forest-700">{{ $stats['readyForPickup'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Sedang diantar</p>
                <p class="mt-2 text-3xl font-black text-forest-700">{{ $stats['outForDelivery'] }}</p>
            </a>
            <a href="{{ route('seller.reports.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Selesai</p>
                <p class="mt-2 text-3xl font-black text-forest-700">{{ $stats['completed'] }}</p>
            </a>
            <a href="{{ route('seller.products.index') }}" class="rounded-2xl bg-warm-white p-6 shadow-soft transition hover:-translate-y-1 hover:shadow-card">
                <p class="text-sm text-sage-500">Stok menipis</p>
                <p class="mt-2 text-3xl font-black text-amber-600">{{ $lowStockProducts->count() }}</p>
            </a>
        </section>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl bg-warm-white p-6 shadow-soft">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Pesanan terbaru</h2>
                    <a href="{{ route('seller.orders.index') }}" class="text-sm font-bold text-forest-700">Lihat semua</a>
                </div>
                @if ($recentOrders->isEmpty())
                    <p class="mt-4 text-sm text-sage-500">Belum ada pesanan masuk.</p>
                @else
                    <div class="mt-4 divide-y divide-warm-100">
                        @foreach ($recentOrders as $sellerOrder)
                            <a href="{{ route('seller.orders.show', $sellerOrder) }}" class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $sellerOrder->order->buyer->name }}</p>
                                    <p class="text-sm text-sage-500">{{ $sellerOrder->order->order_number }} Â· {{ ucfirst($sellerOrder->status) }}</p>
                                </div>
                                <strong class="shrink-0 text-forest-700">Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</strong>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
            <section class="rounded-2xl bg-warm-white p-6 shadow-soft">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Stok menipis</h2>
                    <a href="{{ route('seller.products.index') }}" class="text-sm font-bold text-forest-700">Kelola produk</a>
                </div>
                @if ($lowStockProducts->isEmpty())
                    <p class="mt-4 text-sm text-sage-500">Semua produk aktif masih memiliki stok yang cukup.</p>
                @else
                    <div class="mt-4 divide-y divide-warm-100">
                        @foreach ($lowStockProducts as $product)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $product->name }}</p>
                                    <p class="text-sm text-sage-500">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $product->stock === 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">Sisa {{ $product->stock }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
        <section class="mt-10 sm:hidden">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-warm-200 bg-warm-white px-5 py-3.5 font-bold text-sage-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    Keluar akun
                </button>
            </form>
        </section>
    </main>

    <x-mobile-bottom-nav :unread-notifications="$unreadNotifications" :unread-chats="0" />
</body>
</html>



