<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 pb-20 text-forest-950 sm:pb-0">
    <x-notification-stream />
    <x-app-header :unread-notifications="$unreadNotifications" :unread-chats="$unreadChats" />
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <section class="rounded-2xl bg-forest-700 p-8 text-white shadow-lg">
            <p class="text-sm text-forest-100">Akun aktif</p>
            <h2 class="mt-2 text-3xl font-bold">Halo, {{ $user->name }}</h2>
            <p class="mt-3 text-forest-100">{{ ucfirst($user->role) }} Â· Blok {{ $user->block }} No. {{ $user->house_number }}</p>
        </section>
        <section class="mt-8 grid gap-5 md:grid-cols-3">
            <article class="rounded-xl bg-warm-white p-6 shadow-soft"><p class="text-sm text-sage-500">Role</p><p class="mt-2 text-xl font-bold">{{ ucfirst($user->role) }}</p></article>
            <article class="rounded-xl bg-warm-white p-6 shadow-soft"><p class="text-sm text-sage-500">Status akun</p><p class="mt-2 text-xl font-bold text-forest-700">{{ ucfirst($user->status) }}</p></article>
            <article class="rounded-xl bg-warm-white p-6 shadow-soft"><p class="text-sm text-sage-500">Alamat</p><p class="mt-2 text-xl font-bold">{{ $user->address }}</p></article>
        </section>
        @if ($user->isSeller() && $store?->isVerificationPending())
            <section class="mt-8 rounded-2xl border border-amber-300 bg-amber-50 p-6 shadow-soft">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-amber-700">Verifikasi toko</p>
                        <h2 class="mt-2 text-2xl font-black">Toko {{ $store->store_name }} sedang ditinjau pengelola</h2>
                        <p class="mt-2 text-warm-700">Pengajuan dikirim {{ $store->submitted_at?->format('d M Y H:i') }}. Toko belum tampil di katalog sampai disetujui. Anda tetap bisa menyiapkan produk di meantime.</p>
                    </div>
                    <a href="{{ route('seller.dashboard') }}" class="rounded-xl bg-amber-600 px-5 py-3 font-bold text-white hover:bg-amber-700">Buka dashboard toko</a>
                </div>
            </section>
        @elseif ($user->isSeller() && $store?->isVerificationRejected())
            <section class="mt-8 rounded-2xl border border-red-300 bg-red-50 p-6 shadow-soft">
                <p class="text-sm font-bold uppercase tracking-wider text-red-700">Verifikasi ditolak</p>
                <h2 class="mt-2 text-2xl font-black">Perbaiki data toko Anda lalu kirim ulang</h2>
                <p class="mt-2 text-warm-800">Alasan dari pengelola: {{ $store->rejection_reason }}</p>
                <a href="{{ route('seller.application.create') }}" class="mt-5 inline-block rounded-xl bg-red-700 px-5 py-3 font-bold text-white hover:bg-red-800">Perbaiki &amp; kirim ulang</a>
            </section>
        @elseif (! $user->isAdmin() && ! $user->isSeller())
            <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft">
                <p class="text-sm font-bold uppercase tracking-wider text-forest-700">Jual produk Anda</p>
                <h2 class="mt-2 text-2xl font-black">Daftar jadi pedagang</h2>
                <p class="mt-2 text-sage-500">Lengkapi data toko, tunggu verifikasi pengelola, lalu produk Anda tampil di katalog warga sekitar.</p>
                <a href="{{ route('seller.application.create') }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Ajukan jadi pedagang</a>
            </section>
        @endif
        @if ($user->isSeller() && $store?->isVerificationApproved())
            <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft">
                <p class="text-sm font-bold uppercase tracking-wider text-forest-700">Area pedagang</p>
                <h2 class="mt-2 text-2xl font-black">Kelola toko dan produk</h2>
                <p class="mt-2 text-sage-500">Tambahkan produk UMKM Anda agar mudah ditemukan oleh warga sekitar.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Buka dashboard toko</a>
                    <a href="{{ route('seller.products.index') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Kelola produk</a>
                    <a href="{{ route('seller.shipping.edit') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Pengaturan pengiriman</a>
                    <a href="{{ route('seller.payment.edit') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Pengaturan pembayaran</a>
                    
                    <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Pesanan masuk</a>
                    <a href="{{ route('seller.reports.index') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Laporan penjualan</a>
                    <a href="{{ route('chat.index') }}" class="inline-flex items-center rounded-xl border border-warm-200 px-5 py-3 font-bold text-sage-700">Chat pembeli</a>
                </div>
            </section>
        @endif
        @if ($user->isAdmin())
            <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft"><p class="text-sm font-bold uppercase tracking-wider text-forest-700">Pengelola marketplace</p><h2 class="mt-2 text-2xl font-black">Kelola data marketplace</h2><p class="mt-2 text-sage-500">Pantau statistik, transaksi, dan kategori dari satu panel.</p><a href="{{ route('admin.dashboard') }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Buka panel admin</a></section>
        @endif
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

    <x-mobile-bottom-nav :unread-notifications="$unreadNotifications" :unread-chats="$unreadChats" />
</body>
</html>



