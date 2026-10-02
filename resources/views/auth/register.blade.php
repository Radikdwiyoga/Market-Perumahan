<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f3ed] px-4 py-10 text-slate-900 sm:px-6">
    <main class="mx-auto max-w-2xl">
        <x-brand-logo :large="true" :compact="false" class="mb-8" />
        <div class="rounded-3xl bg-white p-7 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-10">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">Dukung UMKM, dari rumah Anda</p>
            <h1 class="mt-2 text-3xl font-black">Buat akun baru</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Daftar untuk mulai berbelanja dari UMKM di sekitar perumahan.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <label class="block sm:col-span-2">
                    <span class="text-sm font-bold text-slate-700">Nama lengkap</span>
                    <input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Nomor HP</span>
                    <input name="phone" value="{{ old('phone') }}" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Email <span class="font-normal text-slate-400">(opsional)</span></span>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm font-bold text-slate-700">Alamat perumahan</span>
                    <input name="address" value="{{ old('address') }}" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Blok</span>
                    <input name="block" value="{{ old('block') }}" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Nomor rumah</span>
                    <input name="house_number" value="{{ old('house_number') }}" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Password</span>
                    <input type="password" name="password" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Ulangi password</span>
                    <input type="password" name="password_confirmation" required class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" />
                </label>
                <button class="sm:col-span-2 w-full rounded-xl bg-emerald-800 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-800/25 transition hover:bg-emerald-900 active:scale-[0.99]">Daftar</button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </main>
</body>
</html>