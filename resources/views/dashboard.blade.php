<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <x-notification-stream />
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <x-brand-logo />
                <h1 class="text-lg font-bold sm:text-xl">Dashboard</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('marketplace.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Katalog</a>
                @if ($user->isSeller())
                    <a href="{{ route('seller.dashboard') }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Kelola toko</a>
                @elseif ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Panel admin</a>
                @else
                    <a href="{{ route('cart.index') }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Keranjang</a>
                    <a href="{{ route('orders.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Pesanan</a>
                    <a href="{{ route('favorites.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Favorit</a>
                @endif
                <a href="{{ route('notifications.index') }}" data-notification-bell class="relative rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
                    Notifikasi
                    <span class="badge-count absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-bold text-white {{ $unreadNotifications > 0 ? '' : 'hidden' }}" data-count="{{ $unreadNotifications }}">{{ $unreadNotifications }}</span>
                </a>
                @if (!$user->isAdmin())
                    <a href="{{ route('chat.index') }}" data-chat-bell class="relative rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
                        Chat
                        <span class="badge-count absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1.5 text-xs font-bold text-white {{ $unreadChats > 0 ? '' : 'hidden' }}" data-count="{{ $unreadChats }}">{{ $unreadChats }}</span>
                    </a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Keluar</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <section class="rounded-2xl bg-emerald-800 p-8 text-white shadow-lg">
            <p class="text-sm text-emerald-100">Akun aktif</p>
            <h2 class="mt-2 text-3xl font-bold">Halo, {{ $user->name }}</h2>
            <p class="mt-3 text-emerald-100">{{ ucfirst($user->role) }} · Blok {{ $user->block }} No. {{ $user->house_number }}</p>
        </section>
        <section class="mt-8 grid gap-5 md:grid-cols-3">
            <article class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Role</p><p class="mt-2 text-xl font-bold">{{ ucfirst($user->role) }}</p></article>
            <article class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Status akun</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ ucfirst($user->status) }}</p></article>
            <article class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Alamat</p><p class="mt-2 text-xl font-bold">{{ $user->address }}</p></article>
        </section>
        @if ($user->isSeller() && $store?->isVerificationPending())
            <section class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-amber-700">Verifikasi toko</p>
                        <h2 class="mt-2 text-2xl font-black">Toko {{ $store->store_name }} sedang ditinjau pengelola</h2>
                        <p class="mt-2 text-slate-600">Pengajuan dikirim {{ $store->submitted_at?->format('d M Y H:i') }}. Toko belum tampil di katalog sampai disetujui. Anda tetap bisa menyiapkan produk di meantime.</p>
                    </div>
                    <a href="{{ route('seller.dashboard') }}" class="rounded-xl bg-amber-600 px-5 py-3 font-bold text-white hover:bg-amber-700">Buka dashboard toko</a>
                </div>
            </section>
        @elseif ($user->isSeller() && $store?->isVerificationRejected())
            <section class="mt-8 rounded-2xl border border-red-300 bg-red-50 p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wider text-red-700">Verifikasi ditolak</p>
                <h2 class="mt-2 text-2xl font-black">Perbaiki data toko Anda lalu kirim ulang</h2>
                <p class="mt-2 text-slate-700">Alasan dari pengelola: {{ $store->rejection_reason }}</p>
                <a href="{{ route('seller.application.create') }}" class="mt-5 inline-block rounded-xl bg-red-700 px-5 py-3 font-bold text-white hover:bg-red-800">Perbaiki &amp; kirim ulang</a>
            </section>
        @elseif (! $user->isAdmin() && ! $user->isSeller())
            <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wider text-emerald-700">Jual produk Anda</p>
                <h2 class="mt-2 text-2xl font-black">Daftar jadi pedagang</h2>
                <p class="mt-2 text-slate-500">Lengkapi data toko, tunggu verifikasi pengelola, lalu produk Anda tampil di katalog warga sekitar.</p>
                <a href="{{ route('seller.application.create') }}" class="mt-5 inline-block rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white hover:bg-emerald-900">Ajukan jadi pedagang</a>
            </section>
        @endif
        @if ($user->isSeller() && $store?->isVerificationApproved())
            <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wider text-emerald-700">Area pedagang</p>
                <h2 class="mt-2 text-2xl font-black">Kelola toko dan produk</h2>
                <p class="mt-2 text-slate-500">Tambahkan produk UMKM Anda agar mudah ditemukan oleh warga sekitar.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white hover:bg-emerald-900">Buka dashboard toko</a>
                    <a href="{{ route('seller.products.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Kelola produk</a>
                    <a href="{{ route('seller.shipping.edit') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Pengaturan pengiriman</a>
                    <a href="{{ route('seller.payment.edit') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Pengaturan pembayaran</a>
                    
                    <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Pesanan masuk</a>
                    <a href="{{ route('seller.reports.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Laporan penjualan</a>
                    <a href="{{ route('chat.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700">Chat pembeli</a>
                </div>
            </section>
        @endif
        @if ($user->isAdmin())
            <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm font-bold uppercase tracking-wider text-emerald-700">Pengelola marketplace</p><h2 class="mt-2 text-2xl font-black">Kelola data marketplace</h2><p class="mt-2 text-slate-500">Pantau statistik, transaksi, dan kategori dari satu panel.</p><a href="{{ route('admin.dashboard') }}" class="mt-5 inline-block rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white">Buka panel admin</a></section>
        @endif
    </main>
</body>
</html>
