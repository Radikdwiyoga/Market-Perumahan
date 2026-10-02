<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Toko - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <x-notification-stream />
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-5">
            <div class="flex items-center gap-3">
                <x-brand-logo />
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">Dashboard Toko</h1>
                    <p class="text-sm text-slate-500">{{ $store->store_name }} · {{ $store->businessHoursLabel() }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Dashboard</a>
                @if ($store->status === 'open')
                    <a href="{{ route('stores.show', $store) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Lihat halaman toko</a>
                @endif
                <a href="{{ route('notifications.index') }}" data-notification-bell class="relative rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
                    Notifikasi
                    <span class="badge-count absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-bold text-white {{ $unreadNotifications > 0 ? '' : 'hidden' }}" data-count="{{ $unreadNotifications }}">{{ $unreadNotifications }}</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Keluar</button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif
        <section class="rounded-2xl bg-emerald-950 p-7 text-white shadow-lg sm:p-8">
            <p class="text-sm text-emerald-200">Penjualan hari ini</p>
            <p class="mt-2 text-4xl font-black sm:text-5xl">Rp{{ number_format($stats['revenueToday'], 0, ',', '.') }}</p>
            <p class="mt-3 text-emerald-100">{{ $activeProductCount }} produk aktif · {{ $store->isOpenNow() ? 'Toko sedang buka' : 'Toko sedang tutup' }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('seller.products.index') }}" class="rounded-xl bg-white px-5 py-3 font-bold text-emerald-900">Kelola produk</a>
                <a href="{{ route('seller.orders.index') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pesanan masuk</a>
                <a href="{{ route('seller.shipping.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pengaturan pengiriman</a>
                <a href="{{ route('seller.payment.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Pengaturan pembayaran</a>
                
                <a href="{{ route('seller.store.edit') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Profil toko</a>
                <a href="{{ route('seller.reports.index') }}" class="rounded-xl bg-white/15 px-5 py-3 font-bold text-white hover:bg-white/25">Laporan penjualan</a>
            </div>
        </section>
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Pesanan baru</p>
                <p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['newOrders'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Sedang diproses</p>
                <p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['processing'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Siap diambil</p>
                <p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['readyForPickup'] }}</p>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Sedang diantar</p>
                <p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['outForDelivery'] }}</p>
            </a>
            <a href="{{ route('seller.reports.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Selesai</p>
                <p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['completed'] }}</p>
            </a>
            <a href="{{ route('seller.products.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm text-slate-500">Stok menipis</p>
                <p class="mt-2 text-3xl font-black text-amber-600">{{ $lowStockProducts->count() }}</p>
            </a>
        </section>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Pesanan terbaru</h2>
                    <a href="{{ route('seller.orders.index') }}" class="text-sm font-bold text-emerald-700">Lihat semua</a>
                </div>
                @if ($recentOrders->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Belum ada pesanan masuk.</p>
                @else
                    <div class="mt-4 divide-y divide-slate-100">
                        @foreach ($recentOrders as $sellerOrder)
                            <a href="{{ route('seller.orders.show', $sellerOrder) }}" class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $sellerOrder->order->buyer->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $sellerOrder->order->order_number }} · {{ ucfirst($sellerOrder->status) }}</p>
                                </div>
                                <strong class="shrink-0 text-emerald-800">Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</strong>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Stok menipis</h2>
                    <a href="{{ route('seller.products.index') }}" class="text-sm font-bold text-emerald-700">Kelola produk</a>
                </div>
                @if ($lowStockProducts->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Semua produk aktif masih memiliki stok yang cukup.</p>
                @else
                    <div class="mt-4 divide-y divide-slate-100">
                        @foreach ($lowStockProducts as $product)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $product->name }}</p>
                                    <p class="text-sm text-slate-500">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $product->stock === 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">Sisa {{ $product->stock }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
