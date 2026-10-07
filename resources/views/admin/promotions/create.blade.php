<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Iklan Sponsor - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('admin.promotions.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-forest-700 hover:underline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke daftar iklan
        </a>

        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Promosi & Sponsor</p>
            <h1 class="mt-2 text-3xl font-black">Tambah Iklan Baru</h1>
            <p class="mt-1 text-sm text-sage-500">Iklan ini akan tampil di bagian atas halaman katalog utama bagi seluruh pengunjung/warga.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="title" class="block text-sm font-semibold">Judul Iklan / Sponsor <span class="text-red-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title') }}" required placeholder="Contoh: Promo Spesial Toko Berkah" class="mt-2 w-full rounded-lg border-warm-200" />
                </div>

                <div>
                    <label class="block text-sm font-semibold">Tipe Media Promosi <span class="text-red-500">*</span></label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-warm-200 p-3.5 transition hover:border-forest-400 has-checked:border-forest-700 has-checked:bg-forest-50 has-checked:font-bold">
                            <input type="radio" name="type" value="image" class="accent-forest-700" {{ old('type', 'image') === 'image' ? 'checked' : '' }} onchange="updateMediaType('image')">
                            <span>🖼️ Foto / Banner</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-warm-200 p-3.5 transition hover:border-forest-400 has-checked:border-forest-700 has-checked:bg-forest-50 has-checked:font-bold">
                            <input type="radio" name="type" value="video" class="accent-forest-700" {{ old('type') === 'video' ? 'checked' : '' }} onchange="updateMediaType('video')">
                            <span>🎬 Video Singkat</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="media" class="block text-sm font-semibold">File Media <span class="text-red-500">*</span></label>
                    <input id="media" type="file" name="media" required class="mt-2 w-full rounded-lg border-warm-200 file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-forest-700" />
                    <small id="media-hint" class="mt-1 block text-xs text-sage-400">
                        Format: JPG, PNG, atau WebP. Maksimal 30 MB. Disarankan rasio landscape (16:9 atau 21:9).
                    </small>
                </div>

                <div>
                    <label for="link_url" class="block text-sm font-semibold">Tautan / Link Tujuan (Opsional)</label>
                    <input id="link_url" type="url" name="link_url" value="{{ old('link_url') }}" placeholder="https://wa.me/... atau link toko/sosmed sponsor" class="mt-2 w-full rounded-lg border-warm-200" />
                    <small class="mt-1 block text-xs text-sage-400">Jika diisi, pengunjung yang mengklik iklan ini akan diarahkan ke tautan tersebut.</small>
                </div>

                <div>
                    <label for="caption" class="block text-sm font-semibold">Keterangan / Slogan Singkat (Opsional)</label>
                    <textarea id="caption" name="caption" rows="2" placeholder="Teks singkat penjelas sponsor atau penawaran menarik..." class="mt-2 w-full rounded-lg border-warm-200">{{ old('caption') }}</textarea>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="order" class="block text-sm font-semibold">Nomor Urutan Tampil</label>
                        <input id="order" type="number" min="0" name="order" value="{{ old('order', 0) }}" class="mt-2 w-full rounded-lg border-warm-200" />
                        <small class="mt-1 block text-xs text-sage-400">Nilai lebih kecil tampil lebih dulu (0, 1, 2...).</small>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-semibold">Status Tayang</label>
                        <select id="status" name="status" class="mt-2 w-full rounded-lg border-warm-200">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Langsung Aktif</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Simpan sebagai Draf (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-xl bg-forest-700 px-5 py-3.5 font-bold text-white hover:bg-forest-800 shadow-soft transition">
                        Simpan & Terbitkan Iklan
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        function updateMediaType(type) {
            const input = document.getElementById('media');
            const hint = document.getElementById('media-hint');
            if (type === 'video') {
                input.accept = 'video/mp4,video/webm,video/ogg,video/quicktime';
                hint.textContent = 'Format video: MP4, WebM, atau MOV. Maksimal 30 MB. Disarankan durasi 5-30 detik.';
            } else {
                input.accept = 'image/jpeg,image/png,image/webp';
                hint.textContent = 'Format gambar: JPG, PNG, atau WebP. Maksimal 30 MB. Disarankan rasio landscape (16:9 atau 21:9).';
            }
        }
        // Initialize based on default
        updateMediaType("{{ old('type', 'image') }}");
    </script>
</body>
</html>

