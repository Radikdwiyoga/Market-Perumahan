<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <div class="absolute left-4 top-4 z-20 sm:left-6 sm:top-6">
        <x-back-button :fallback="route('marketplace.index')" />
    </div>
    <div class="lg:grid lg:min-h-screen lg:grid-cols-2">
        {{-- Panel kiri: brand & value proposition --}}
        <aside class="relative hidden overflow-hidden bg-forest-950 lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
            <div class="absolute -right-24 -top-28 h-80 w-80 rounded-full border-48 border-lime-300/10"></div>
            <div class="absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-forest-700/70 blur-3xl"></div>
            <x-brand-logo tone="light" :large="true" :compact="false" class="relative" />
            <div class="relative">
                <p class="text-sm font-bold uppercase tracking-[0.28em] text-lime-300">Dukung UMKM di sekitar rumah</p>
                <h2 class="mt-5 max-w-md text-3xl font-black leading-tight text-white xl:text-5xl">Kebutuhan warga, dari UMKM tetangga sendiri.</h2>
                <ul class="mt-9 space-y-4">
                    <li class="flex items-center gap-3 text-forest-50">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-lime-300 text-forest-950"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span>
                        Pesan kebutuhan harian dari UMKM sekitar rumah
                    </li>
                    <li class="flex items-center gap-3 text-forest-50">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-lime-300 text-forest-950"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span>
                        Chat langsung ke pedagang atau via WhatsApp
                    </li>
                    <li class="flex items-center gap-3 text-forest-50">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-lime-300 text-forest-950"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span>
                        Tumbuhkan usaha kecil di lingkungan Anda
                    </li>
                </ul>
            </div>
            <div class="relative rounded-3xl bg-white/10 p-6 backdrop-blur">
                <p class="text-sm leading-6 text-forest-100">&ldquo;Pesan kebutuhan tanpa keluar rumah. UMKM tetangga jadi pilihan utama keluarga kami.&rdquo;</p>
                <p class="mt-3 text-sm font-bold text-white">Warga Blok C, Perumahan ABC</p>
            </div>
        </aside>

        {{-- Panel kanan: form masuk --}}
        <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-10">
            <div class="w-full max-w-md">
                <x-brand-logo :large="true" :compact="false" class="mb-8 lg:hidden" />
                <div class="rounded-3xl bg-warm-white p-7 shadow-xl shadow-forest-900/5 ring-1 ring-warm-200/50 sm:p-9">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Selamat datang kembali</p>
                    <h1 class="mt-2 text-3xl font-black">Masuk ke akun Anda</h1>
                    <p class="mt-2 text-sm leading-6 text-sage-600">Lanjutkan belanja dari UMKM di sekitar perumahan.</p>

                    @if ($errors->any())
                        <div class="mt-6 flex items-start gap-3 rounded-2xl bg-red-50 p-4 text-sm font-semibold text-red-700">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-5">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-bold text-sage-700">Nomor HP atau email</span>
                            <span class="mt-2 flex items-center gap-3 rounded-xl border border-warm-200 bg-warm-white px-4 py-3 transition focus-within:border-forest-600 focus-within:ring-2 focus-within:ring-forest-600/20">
                                <svg class="h-5 w-5 shrink-0 text-sage-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <input name="login" value="{{ old('login') }}" required autofocus placeholder="contoh: 0812xxxxxxx atau email" class="min-w-0 flex-1 border-0 bg-transparent text-sm text-forest-950 outline-none placeholder:text-sage-400 focus:ring-0" />
                            </span>
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-sage-700">Password</span>
                            <span class="mt-2 flex items-center gap-3 rounded-xl border border-warm-200 bg-warm-white px-4 py-3 transition focus-within:border-forest-600 focus-within:ring-2 focus-within:ring-forest-600/20">
                                <svg class="h-5 w-5 shrink-0 text-sage-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                <input type="password" name="password" required placeholder="********" class="min-w-0 flex-1 border-0 bg-transparent text-sm text-forest-950 outline-none placeholder:text-sage-400 focus:ring-0" />
                            </span>
                        </label>
                        <button class="w-full rounded-xl bg-forest-700 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-forest-800/25 transition hover:bg-forest-800 active:scale-[0.99]">Masuk</button>
                    </form>
                    <div class="my-6 flex items-center gap-3 text-xs font-semibold uppercase tracking-wider text-sage-400"><span class="h-px flex-1 bg-warm-200"></span>atau<span class="h-px flex-1 bg-warm-200"></span></div>
                    <a href="{{ route('register') }}" class="block w-full rounded-xl border-2 border-forest-700/15 px-5 py-3 text-center text-sm font-bold text-forest-700 transition hover:border-forest-700/30 hover:bg-forest-50">Buat akun baru</a>
                </div>
                <p class="mt-6 text-center text-sm text-sage-500">Belum punya akun? <a class="font-semibold text-forest-700 hover:text-forest-700" href="{{ route('register') }}">Daftar sebagai warga</a></p>
            </div>
        </main>
    </div>
</body>
</html>


