<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Iklan & Sponsor - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-forest-700 hover:underline">&larr; Panel admin</a>
                <h1 class="mt-2 text-3xl font-black">Iklan & Sponsor Katalog</h1>
                <p class="mt-1 text-sm text-sage-500">Kelola promosi sponsor berupa foto atau video singkat yang tampil di halaman katalog utama.</p>
            </div>
            <a href="{{ route('admin.promotions.create') }}" class="rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800 shadow-soft transition">
                + Tambah Iklan Sponsor
            </a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8 overflow-hidden rounded-2xl bg-warm-white shadow-soft">
            <div class="hidden grid-cols-[120px_1fr_100px_120px_180px] gap-4 border-b border-warm-200 px-6 py-4 text-xs font-bold uppercase tracking-wider text-sage-400 sm:grid">
                <span>Media</span>
                <span>Judul & Informasi</span>
                <span>Urutan</span>
                <span>Status</span>
                <span class="text-right">Aksi</span>
            </div>

            @forelse ($promotions as $promo)
                <div class="grid gap-4 border-b border-warm-100 px-6 py-5 last:border-0 sm:grid-cols-[120px_1fr_100px_120px_180px] sm:items-center">
                    <div>
                        @if ($promo->isVideo())
                            <div class="relative h-20 w-28 overflow-hidden rounded-xl bg-black">
                                <video src="{{ asset('storage/'.$promo->media_path) }}" muted playsinline class="h-full w-full object-cover"></video>
                                <span class="absolute bottom-1 right-1 rounded bg-black/70 px-1 py-0.5 text-[10px] font-bold text-white">🎬 Video</span>
                            </div>
                        @else
                            <div class="relative h-20 w-28 overflow-hidden rounded-xl border border-warm-200 bg-warm-100">
                                <img src="{{ asset('storage/'.$promo->media_path) }}" alt="{{ $promo->title }}" class="h-full w-full object-cover">
                                <span class="absolute bottom-1 right-1 rounded bg-black/70 px-1 py-0.5 text-[10px] font-bold text-white">🖼️ Foto</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-base text-forest-950">{{ $promo->title }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $promo->isVideo() ? 'bg-indigo-100 text-indigo-700' : 'bg-lime-100 text-forest-800' }}">
                                {{ $promo->type === 'video' ? 'Video' : 'Foto' }}
                            </span>
                        </div>
                        @if ($promo->caption)
                            <p class="mt-1 text-xs text-sage-500">{{ $promo->caption }}</p>
                        @endif
                        @if ($promo->link_url)
                            <p class="mt-1 text-xs">
                                <a href="{{ $promo->link_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-forest-700 hover:underline">
                                    <span>Tautan: {{ Str::limit($promo->link_url, 45) }}</span>
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </p>
                        @endif
                    </div>

                    <div class="text-sm font-semibold text-sage-600">
                        Ke-{{ $promo->order }}
                    </div>

                    <div>
                        @if ($promo->isActive())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-forest-100 px-3 py-1 text-xs font-bold text-forest-800">
                                <span class="h-2 w-2 rounded-full bg-forest-600"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-warm-200 px-3 py-1 text-xs font-bold text-sage-600">
                                <span class="h-2 w-2 rounded-full bg-sage-400"></span>
                                Nonaktif
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('admin.promotions.edit', $promo) }}" class="rounded-lg border border-warm-200 bg-white px-3 py-1.5 text-xs font-bold text-sage-700 hover:bg-warm-50">
                            Edit
                        </a>
                        <form action="{{ route('admin.promotions.toggle', $promo) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-warm-200 bg-white px-3 py-1.5 text-xs font-bold text-sage-700 hover:bg-warm-50">
                                {{ $promo->isActive() ? 'Matikan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.promotions.destroy', $promo) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus iklan sponsor ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-warm-100 text-2xl">
                        📢
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-forest-950">Belum ada iklan sponsor</h2>
                    <p class="mt-1 text-sm text-sage-500">Tambahkan iklan gambar atau video untuk mempromosikan sponsor/toko warga di halaman katalog.</p>
                    <a href="{{ route('admin.promotions.create') }}" class="mt-5 inline-block rounded-xl bg-forest-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-forest-800">
                        + Tambah Iklan Sekarang
                    </a>
                </div>
            @endforelse
        </div>
    </main>
</body>
</html>
