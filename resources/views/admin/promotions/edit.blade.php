<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Iklan Sponsor - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('admin.promotions.index') }}" class="text-sm font-bold text-forest-700 hover:underline">&larr; Kembali ke daftar iklan</a>

        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Promosi & Sponsor</p>
            <h1 class="mt-2 text-3xl font-black">Edit Iklan</h1>
            <p class="mt-1 text-sm text-sage-500">Perbarui informasi, tautan, atau ganti file media iklan sponsor ini.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.promotions.update', $promotion) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="block text-sm font-semibold">Judul Iklan / Sponsor <span class="text-red-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $promotion->title) }}" required class="mt-2 w-full rounded-lg border-warm-200" />
                </div>

                <div>
                    <label class="block text-sm font-semibold">Tipe Media Promosi <span class="text-red-500">*</span></label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-warm-200 p-3.5 transition hover:border-forest-400 has-checked:border-forest-700 has-checked:bg-forest-50 has-checked:font-bold">
                            <input type="radio" name="type" value="image" class="accent-forest-700" {{ old('type', $promotion->type) === 'image' ? 'checked' : '' }} onchange="updateMediaType('image')">
                            <span>🖼️ Foto / Banner</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-warm-200 p-3.5 transition hover:border-forest-400 has-checked:border-forest-700 has-checked:bg-forest-50 has-checked:font-bold">
                            <input type="radio" name="type" value="video" class="accent-forest-700" {{ old('type', $promotion->type) === 'video' ? 'checked' : '' }} onchange="updateMediaType('video')">
                            <span>🎬 Video Singkat</span>
                        </label>
                    </div>
                </div>

                <!-- Media Preview -->
                <div class="rounded-xl border border-warm-200 bg-warm-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-sage-500">Media Saat Ini:</p>
                    <div class="mt-2 flex items-center gap-4">
                        @if ($promotion->isVideo())
                            <video src="{{ asset('storage/'.$promotion->media_path) }}" controls class="max-h-40 max-w-full rounded-lg bg-black"></video>
                        @else
                            <img src="{{ asset('storage/'.$promotion->media_path) }}" alt="{{ $promotion->title }}" class="max-h-36 rounded-lg object-contain border border-warm-200 bg-white">
                        @endif
                    </div>
                </div>

                <div>
                    <label for="media" class="block text-sm font-semibold">Ganti File Media (Opsional)</label>
                    <input id="media" type="file" name="media" class="mt-2 w-full rounded-lg border-warm-200 file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-forest-700" />
                    <small id="media-hint" class="mt-1 block text-xs text-sage-400">
                        Kosongkan bila tidak ingin mengganti file media yang sudah tersimpan.
                    </small>
                </div>

                <div>
                    <label for="link_url" class="block text-sm font-semibold">Tautan / Link Tujuan (Opsional)</label>
                    <input id="link_url" type="url" name="link_url" value="{{ old('link_url', $promotion->link_url) }}" placeholder="https://..." class="mt-2 w-full rounded-lg border-warm-200" />
                </div>

                <div>
                    <label for="caption" class="block text-sm font-semibold">Keterangan / Slogan Singkat (Opsional)</label>
                    <textarea id="caption" name="caption" rows="2" class="mt-2 w-full rounded-lg border-warm-200">{{ old('caption', $promotion->caption) }}</textarea>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="order" class="block text-sm font-semibold">Nomor Urutan Tampil</label>
                        <input id="order" type="number" min="0" name="order" value="{{ old('order', $promotion->order) }}" class="mt-2 w-full rounded-lg border-warm-200" />
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-semibold">Status Tayang</label>
                        <select id="status" name="status" class="mt-2 w-full rounded-lg border-warm-200">
                            <option value="active" {{ old('status', $promotion->status) === 'active' ? 'selected' : '' }}>Aktif (Tayang)</option>
                            <option value="inactive" {{ old('status', $promotion->status) === 'inactive' ? 'selected' : '' }}>Nonaktif (Sembunyikan)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-xl bg-forest-700 px-5 py-3.5 font-bold text-white hover:bg-forest-800 shadow-soft transition">
                        Simpan Perubahan
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
                hint.textContent = 'Kosongkan jika tidak diganti. Format baru: MP4, WebM, MOV. Maksimal 30 MB.';
            } else {
                input.accept = 'image/jpeg,image/png,image/webp';
                hint.textContent = 'Kosongkan jika tidak diganti. Format baru: JPG, PNG, WebP. Maksimal 30 MB.';
            }
        }
        updateMediaType("{{ old('type', $promotion->type) }}");
    </script>
</body>
</html>
