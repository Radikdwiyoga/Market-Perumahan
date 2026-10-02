<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f3ed] text-slate-900">
    <header class="sticky top-0 z-40 border-b border-slate-900/10 bg-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
            <x-brand-logo />
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-full bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900 sm:px-5">Dashboard</a>
            </div>
        </nav>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <div><p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">Belanja Anda</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">Keranjang</h1></div>
        @if (session('status'))<div class="mt-6 rounded-lg bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        @if ($groups->isEmpty())
            <section class="mt-10 rounded-2xl bg-white p-8 text-center shadow-sm sm:p-12"><h2 class="text-2xl font-black">Keranjang masih kosong</h2><p class="mt-2 text-sm text-slate-500 sm:text-base">Temukan kebutuhan Anda dari UMKM sekitar.</p><a href="{{ route('marketplace.index') }}" class="mt-6 inline-block rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white">Mulai belanja</a></section>
        @else
            <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_320px] lg:gap-8">
                <div class="space-y-6">
                    @foreach ($groups as $group)
                        @php($store = $group->first()->sellerProfile)
                        <section class="overflow-hidden rounded-2xl bg-white shadow-sm"><div class="border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Toko</p><h2 class="mt-1 text-lg font-black sm:text-xl">{{ $store->store_name }}</h2></div>
                            <div class="divide-y divide-slate-100">
                                @foreach ($group as $product)
                                    <div class="border-b border-slate-100 px-4 py-4 last:border-0 sm:px-6 sm:py-5">
                                        <div class="flex flex-wrap items-start gap-3 sm:gap-4">
                                            <div class="min-w-0 flex-1"><h3 class="flex items-center gap-3 font-bold">@if ($product->image)<img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="h-10 w-10 shrink-0 rounded-lg object-cover">@endif<span class="text-sm sm:text-base">{{ $product->name }}</span></h3><p class="mt-1 text-sm text-slate-500">@if ($product->hasDiscount())<span class="line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</span> <strong class="text-red-600">Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}</strong>@else Rp{{ number_format($product->effectivePrice(), 0, ',', '.') }}@endif / item</p></div>
                                            <strong class="w-24 text-right text-emerald-800 sm:w-28">Rp{{ number_format($product->effectivePrice() * ($cartQuantities[$product->id] ?? 0), 0, ',', '.') }}</strong>
                                            <form action="{{ route('cart.destroy', $product) }}" method="POST">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700">Hapus</button></form>
                                        </div>
                                        <form action="{{ route('cart.update', $product) }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3">
                                            @csrf @method('PUT')
                                            <label class="block text-xs font-semibold text-slate-500">Jumlah<input type="number" name="quantity" min="1" max="{{ $product->stock }}" value="{{ $cartQuantities[$product->id] ?? 1 }}" class="mt-1 w-20 rounded-lg border-slate-300 text-center" /></label>
                                            <label class="block min-w-52 flex-1 text-xs font-semibold text-slate-500">Catatan untuk penjual<textarea name="note" rows="1" maxlength="500" placeholder="mis. potong tipis, tanpa plastik..." class="mt-1 w-full resize-none rounded-lg border-slate-300 text-sm">{{ $cartNotes[$product->id] ?? '' }}</textarea></label>
                                            <button class="rounded-lg bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-900">Simpan</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                <aside class="h-fit rounded-2xl bg-emerald-950 p-6 text-white lg:sticky lg:top-24"><p class="text-sm text-emerald-200">Ringkasan</p><div class="mt-5 flex items-center justify-between border-b border-white/20 pb-5"><span>Subtotal</span><strong>Rp{{ number_format($total, 0, ',', '.') }}</strong></div><a href="{{ route('checkout.create') }}" class="mt-6 block w-full rounded-xl bg-lime-300 px-4 py-3 text-center font-bold text-emerald-950 hover:bg-lime-200">Lanjut checkout</a></aside>
            </div>
        @endif
    </main>
</body>
</html>