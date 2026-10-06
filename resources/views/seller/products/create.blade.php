<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tambah Produk - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/product-description-ai.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('seller.products.index') }}" class="text-sm font-bold text-forest-700">&larr; Kembali ke produk</a>
        <section class="mt-5 rounded-2xl bg-warm-white p-6 shadow-soft sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-forest-700">Kelola toko</p><h1 class="mt-2 text-3xl font-black">Tambah produk</h1><p class="mt-2 text-sage-500">Isi informasi produk yang akan tampil di katalog warga.</p>
            @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-5" data-ai-description data-url="{{ route('seller.products.ai-description') }}">@csrf
                <label class="block text-sm font-semibold">Nama produk<input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-lg border-warm-200" data-ai-field-name /></label>
                <label class="block text-sm font-semibold">Kategori<select name="category_id" required class="mt-2 w-full rounded-lg border-warm-200" data-ai-field-category><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <div class="grid gap-5 sm:grid-cols-2"><label class="block text-sm font-semibold">Harga<input type="number" min="0" name="price" value="{{ old('price') }}" required class="mt-2 w-full rounded-lg border-warm-200" /></label><label class="block text-sm font-semibold">Diskon (%)<input type="number" min="0" max="100" name="discount_percent" value="{{ old('discount_percent') }}" placeholder="0" class="mt-2 w-full rounded-lg border-warm-200" /></label><label class="block text-sm font-semibold">Stok<input type="number" min="0" name="stock" value="{{ old('stock', 0) }}" required class="mt-2 w-full rounded-lg border-warm-200" /></label></div>
                <label class="block text-sm font-semibold">Foto produk<input type="file" name="image" accept="image/*" class="mt-2 w-full rounded-lg border-warm-200 file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-forest-700" data-ai-input-image /><small class="mt-1 block text-xs text-sage-400">JPG/PNG/WebP, maksimal 5 MB. Kosongkan jika belum ada foto.</small></label>
                <div class="rounded-xl border border-forest-100 bg-forest-50 p-4">
                    <p class="text-sm font-bold text-forest-900">Butuh bantuan menulis deskripsi?</p>
                    <p class="mt-1 text-xs text-forest-700/80">AI membaca foto produk lalu menyusun draf deskripsi. Hasilnya tetap bisa kamu sunting sebelum disimpan.</p>
                    <button type="button" data-ai-trigger data-ai-label="Buat dengan AI" class="mt-3 rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white hover:bg-forest-800 disabled:opacity-50">Buat dengan AI</button>
                    <small data-ai-status class="mt-1 block text-xs text-sage-400"></small>
                </div>
                <label class="block text-sm font-semibold">Deskripsi<textarea name="description" rows="4" class="mt-2 w-full rounded-lg border-warm-200" data-ai-field-description>{{ old('description') }}</textarea><small class="mt-1 block text-xs text-sage-400">Maksimal 2.000 karakter.</small></label>
                <button class="w-full rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Simpan produk</button>
            </form>
        </section>
    </main>
</body>
</html>



