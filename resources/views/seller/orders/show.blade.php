<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan {{ $sellerOrder->order->order_number }} - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-4xl px-6 py-10">
        <x-back-button :fallback="route('seller.orders.index')" />
        @if (session('status'))
            <div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <section class="mt-5 rounded-2xl bg-forest-950 p-7 text-white">
            <p class="text-sm text-forest-200">{{ $store->store_name }}</p>
            <h1 class="mt-2 text-3xl font-black">{{ $sellerOrder->order->order_number }}</h1>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">Pesanan: {{ ucfirst($sellerOrder->status) }}</span>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">Pembayaran: {{ ucfirst($sellerOrder->payment_status) }}</span>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">Pengiriman: {{ ucfirst(str_replace('_', ' ', $sellerOrder->shipping_status)) }}</span>
            </div>
            <p class="mt-5 text-3xl font-black">Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</p>
            <p class="mt-2 text-sm text-forest-200">
                Subtotal Rp{{ number_format($sellerOrder->subtotal, 0, ',', '.') }} &middot; Ongkos kirim Rp{{ number_format($sellerOrder->shipping_fee, 0, ',', '.') }} &middot;
                {{ $sellerOrder->shipping_method === 'seller_delivery' ? 'Diantar oleh penjual' : 'Ambil sendiri di toko' }}
            </p>
        </section>
        <section class="mt-6 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-lg font-black">Pembeli</h2>
            <p class="mt-2 font-bold">{{ $sellerOrder->order->buyer->name }}</p>
            <p class="mt-1 text-sm text-sage-600">{{ $sellerOrder->order->buyer->phone }} &middot; {{ $sellerOrder->order->buyer->address }}, Blok {{ $sellerOrder->order->buyer->block }} No. {{ $sellerOrder->order->buyer->house_number }}</p>
            <p class="mt-3 text-sm text-sage-600">
                {{ $sellerOrder->shipping_method === 'seller_delivery' ? 'Dikirim ke' : 'Lokasi pengambilan' }}:
                {{ $sellerOrder->shipment->first()?->address ?? $store->address }}
            </p>
            <a href="{{ route('chat.start') }}" class="mt-4 inline-block rounded-xl border border-forest-700 px-4 py-2 text-sm font-bold text-forest-700">Chat pembeli</a>
        </section>
        <section class="mt-6 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-lg font-black">Detail yang dibeli</h2>
            <div class="mt-4 divide-y divide-warm-100">
                @foreach ($items as $item)
                    <div class="flex items-start justify-between gap-4 py-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('products.show', $item->product) }}" class="font-bold hover:text-forest-700">{{ $item->product_name }}</a>
                            <span class="text-sage-500"> x{{ $item->quantity }}</span>
                            <p class="mt-1 text-xs text-sage-500">Rp{{ number_format($item->price, 0, ',', '.') }} / item</p>
                            @if ($item->note)
                                <p class="mt-1 text-xs font-semibold text-amber-700">Catatan pembeli: {{ $item->note }}</p>
                            @endif
                        </div>
                        <strong class="shrink-0">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="mt-6 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-lg font-black">Pembayaran</h2>
            <div class="mt-4 space-y-4">
                @forelse ($sellerOrder->payments as $payment)
                    <div class="rounded-xl border border-warm-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-bold">{{ $payment->methodLabel() }} &middot; Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $payment->status === 'paid' ? 'bg-forest-100 text-forest-700' : ($payment->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst($payment->status) }}</span>
                        </div>
                        @if ($payment->proof_image)
                            <a href="{{ Storage::disk('public')->url($payment->proof_image) }}" target="_blank" rel="noopener" class="mt-3 inline-block text-xs font-semibold text-forest-700 underline">Lihat bukti pembayaran</a>
                        @endif
                        @if ($payment->rejection_reason)
                            <p class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700">Pembayaran ditolak: {{ $payment->rejection_reason }}.</p>
                        @endif
                        @if ($payment->isVerifiable() && $payment->hasRequiredProof())
                            <form action="{{ route('seller.orders.payments.verify', $payment) }}" method="POST" class="mt-4">@csrf @method('PATCH')<button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">{{ $payment->method === 'cod' ? 'Terima pembayaran COD' : 'Verifikasi pembayaran' }}</button></form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-sage-500">Belum ada data pembayaran.</p>
                @endforelse
            </div>
        </section>
        <section class="mt-6 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-lg font-black">Pengiriman</h2>
            @if ($sellerOrder->shipping_status === 'completed' || $sellerOrder->status === 'cancelled')
                <p class="mt-3 text-sm text-sage-500">Pesanan ini sudah selesai atau dibatalkan, status pengiriman tidak dapat diubah.</p>
            @else
                <form action="{{ route('seller.orders.shipping.update', $sellerOrder) }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3">@csrf @method('PATCH')
                    <label class="text-sm font-semibold">
                        Status pengiriman
                        <select name="shipping_status" class="mt-1 block rounded-lg border-warm-200 text-sm">
                            @foreach ($allowedShippingStatuses as $allowed)
                                <option value="{{ $allowed }}" @selected($sellerOrder->shipping_status === $allowed)>{{ ucfirst(str_replace('_', ' ', $allowed)) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="rounded-lg border border-warm-200 px-4 py-2 text-sm font-bold">Simpan status</button>
                </form>
            @endif
            @if ($sellerOrder->shipping_method === 'store_pickup')
                <div class="mt-5 rounded-xl bg-warm-50 p-4">
                    <p class="text-sm text-sage-500">Kode pengambilan</p>
                    <p class="mt-1 text-2xl font-black tracking-widest">{{ $sellerOrder->pickup_code ?? '-' }}</p>
                </div>
                @if ($sellerOrder->shipping_status === 'ready' && $sellerOrder->status !== 'completed')
                    <form action="{{ route('seller.orders.pickup', $sellerOrder) }}" method="POST" class="mt-5 flex flex-wrap items-end gap-3">@csrf
                        <label class="text-sm font-semibold">
                            Kode pengambilan dari pembeli
                            <input type="text" name="pickup_code" required maxlength="6" placeholder="482915" class="mt-1 block rounded-lg border-warm-200 text-sm tracking-widest" />
                        </label>
                        <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Verifikasi pengambilan</button>
                    </form>
                @endif
            @endif
            @if ($sellerOrder->shipping_status === 'delivered' && $sellerOrder->shipping_method === 'seller_delivery' && $sellerOrder->status !== 'completed')
                <p class="mt-4 text-sm text-sage-500">Menunggu pembeli mengonfirmasi pesanan diterima.</p>
            @endif
            @if ($sellerOrder->payment_due_at && $sellerOrder->payment_status === 'pending')
                <p class="mt-4 text-sm font-semibold text-amber-700">Batas pembayaran: {{ $sellerOrder->payment_due_at->format('d M Y H:i') }}</p>
            @endif
        </section>
    </main>
</body>
</html>



