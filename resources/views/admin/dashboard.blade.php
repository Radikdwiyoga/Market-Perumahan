<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Admin - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 pb-20 text-forest-950 sm:pb-0">
    <x-notification-stream />
    <x-app-header :unread-notifications="$unreadNotifications" :unread-chats="0" />
    <main class="mx-auto max-w-6xl px-6 py-10">
        <h1 class="text-2xl font-black">Panel admin</h1>
        <p class="text-sage-500">Ringkasan aktivitas Market UMKM Perumahan</p>
        @if (session('status'))<div class="mt-6 rounded-lg bg-forest-100 px-4 py-3 text-sm font-semibold text-forest-800">{{ session('status') }}</div>@endif
        @if ($pendingSellerApprovals > 0)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-300 bg-amber-50 p-5">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-amber-700">Verifikasi toko</p>
                    <p class="mt-1 text-warm-800">{{ $pendingSellerApprovals }} pengajuan toko menunggu tinjauan Anda.</p>
                </div>
                <a href="{{ route('admin.users.index', ['verification' => 'pending']) }}" class="rounded-xl bg-amber-600 px-5 py-3 font-bold text-white hover:bg-amber-700">Tinjau pengajuan</a>
            </div>
        @endif
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ([['Pengguna', $stats['users']], ['Seller', $stats['sellers']], ['Toko', $stats['stores']], ['Produk', $stats['products']], ['Pesanan', $stats['orders']], ['Kategori', $stats['categories']]] as [$label, $value])<article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm text-sage-500">{{ $label }}</p><p class="mt-2 text-3xl font-black text-forest-700">{{ $value }}</p></article>@endforeach</div>
        <section class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Manajemen pengguna</p><h2 class="mt-1 text-2xl font-black">Buat buyer atau seller</h2><p class="mt-2 text-sage-500">Seller baru langsung dibuatkan profil toko.</p><a href="{{ route('admin.users.create') }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Buat akun</a></article>
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Manajemen katalog</p><h2 class="mt-1 text-2xl font-black">Kategori produk</h2><p class="mt-2 text-sage-500">Atur kategori aktif dan nonaktif.</p><a href="{{ route('admin.categories.index') }}" class="mt-5 inline-block rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700 hover:bg-warm-50">Kelola kategori</a></article>
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Komplain buyer</p><h2 class="mt-1 text-2xl font-black">Tindak lanjut komplain</h2><p class="mt-2 text-sage-500">Lihat semua laporan warga beserta bukti foto dan perbarui statusnya.</p><div class="mt-5 flex flex-wrap items-center gap-3"><a href="{{ route('admin.complaints.index') }}" class="inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Kelola komplain</a>@if ($openComplaints > 0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ $openComplaints }} menunggu</span>@endif</div></article>
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Laporan</p><h2 class="mt-1 text-2xl font-black">Statistik penjualan</h2><p class="mt-2 text-sage-500">GMV, penjualan per pedagang/kategori, dan metode pembayaran. Ekspor CSV tersedia.</p><a href="{{ route('admin.reports.index') }}" class="mt-5 inline-block rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700 hover:bg-warm-50">Buka laporan</a></article>
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Audit log</p><h2 class="mt-1 text-2xl font-black">Riwayat aktivitas</h2><p class="mt-2 text-sage-500">Pantau aktivitas penting seperti login, produk, dan pembayaran.</p><a href="{{ route('admin.audit-logs.index') }}" class="mt-5 inline-block rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700 hover:bg-warm-50">Lihat audit log</a></article>
            <article class="rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Promosi & Sponsor</p><h2 class="mt-1 text-2xl font-black">Iklan Katalog</h2><p class="mt-2 text-sage-500">Pasang banner foto atau video promosi sponsor di halaman utama.</p><a href="{{ route('admin.promotions.index') }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Kelola iklan</a></article>
        </section>
        <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft"><h2 class="text-xl font-black">Pesanan terbaru</h2><div class="mt-4 divide-y divide-warm-100">@forelse ($recentOrders as $order)<div class="flex flex-wrap justify-between gap-3 py-4"><div><p class="font-bold">{{ $order->order_number }}</p><p class="text-sm text-sage-500">{{ $order->buyer->name }}</p></div><div class="text-right"><p class="font-bold text-forest-700">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</p><p class="text-sm text-sage-500">{{ ucfirst($order->status) }}</p></div></div>@empty<p class="py-6 text-sage-500">Belum ada pesanan.</p>@endforelse</div></section>
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



