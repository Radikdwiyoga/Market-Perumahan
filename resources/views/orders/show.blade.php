<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan {{ $order->order_number }} - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-10">
        <div class="flex items-center gap-2">
            <x-back-button :fallback="route('orders.index')" />
            <x-brand-logo />
        </div>
        @if (session('status'))
            <div class="mt-6 rounded-lg bg-forest-100 p-4 text-sm font-semibold text-forest-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <section class="mt-6 rounded-2xl bg-forest-950 p-7 text-white">
            <p class="text-sm text-forest-200">Detail pesanan</p>
            <h1 class="mt-2 text-3xl font-black">{{ $order->order_number }}</h1>
            <p class="mt-3 text-forest-100">Status: {{ ucfirst($order->status) }}</p>
        </section>
        <section class="mt-8 space-y-5">
            @foreach ($order->sellerOrders as $sellerOrder)
                <article class="overflow-hidden rounded-2xl bg-warm-white shadow-soft">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-warm-100 px-6 py-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-sage-400">Toko</p>
                            <h2 class="mt-1 text-xl font-black">{{ $sellerOrder->sellerProfile->store_name }}</h2>
                            <p class="mt-1 text-sm text-sage-500">{{ $sellerOrder->shipping_method === 'seller_delivery' ? 'Dikirim ke' : 'Diambil di' }}: {{ $sellerOrder->shipment->first()?->address }}</p>
                            @if ($sellerOrder->shipping_fee > 0)
                                <p class="mt-1 text-sm text-sage-500">Ongkos kirim: Rp{{ number_format($sellerOrder->shipping_fee, 0, ',', '.') }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ ucfirst(str_replace('_', ' ', $sellerOrder->shipping_method)) }}</span>
                            <p class="mt-2 text-sm font-bold text-forest-700">Pengiriman: {{ ucfirst(str_replace('_', ' ', $sellerOrder->shipping_status)) }}</p>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-sage-500">Total sub-order</p>
                        <p class="mt-1 text-xl font-black text-forest-700">Rp{{ number_format($sellerOrder->total_amount, 0, ',', '.') }}</p>
                        @if ($sellerOrder->pickup_code)
                            <p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm">
                                Kode pengambilan: <strong class="text-lg tracking-widest">{{ $sellerOrder->pickup_code }}</strong>
                                @if ($sellerOrder->shipping_status === 'ready')
                                    <span class="mt-1 block text-amber-700">Tunjukkan kode ini kepada penjual saat mengambil pesanan.</span>
                                @endif
                            </p>
                        @endif
                        @foreach ($sellerOrder->payments as $payment)
                            <div class="mt-5 border-t border-warm-100 pt-5">
                                <p class="text-sm font-bold">{{ $payment->methodLabel() }}</p>
                                <p class="mt-1 text-sm text-sage-500">Status: {{ ucfirst($payment->status) }} &middot; Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                                @if ($payment->method === 'qris' && $payment->qris_image_snapshot)
                                    <img src="{{ Storage::disk('public')->url($payment->qris_image_snapshot) }}" alt="QRIS {{ $sellerOrder->sellerProfile->store_name }}" class="mt-3 h-40 w-40 rounded-lg object-cover" />
                                @endif
                                @if ($payment->method === 'bank_transfer')
                                    @php($setting = $sellerOrder->sellerProfile->paymentSetting)
                                    @if ($setting?->bank_name && $setting?->bank_account_number)
                                        <div class="mt-3 rounded-xl bg-forest-50 p-4 text-sm">
                                            <p class="font-bold text-forest-900">Transfer ke rekening toko</p>
                                            <p class="mt-1 text-forest-700">{{ $setting->bank_name }} &middot; {{ $setting->bank_account_number }}</p>
                                            <p class="text-forest-700">a.n. {{ $setting->bank_account_name }}</p>
                                        </div>
                                    @endif
                                @endif
                                @if ($payment->proof_image)
                                    <a href="{{ Storage::disk('public')->url($payment->proof_image) }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm font-semibold text-forest-700 underline">Lihat bukti pembayaran</a>
                                @endif
                                @if ($payment->requiresProof() && ! $payment->proof_image && $payment->status !== 'paid' && ! in_array($sellerOrder->status, ['completed', 'cancelled'], true))
                                    <form action="{{ route('payments.proof.store', $payment) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3">
                                        @csrf
                                        <input type="file" name="proof_image" accept="image/jpeg,image/png" required class="max-w-full rounded-lg border border-warm-200 p-2 text-sm" />
                                        <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Unggah bukti</button>
                                    </form>
                                @endif
                                @if ($payment->rejection_reason)
                                    <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm">
                                        <p class="font-bold text-red-700">Pembayaran ditolak</p>
                                        <p class="mt-1 text-red-700">Alasan: {{ $payment->rejection_reason }}</p>
                                        @if ($payment->requiresProof() && $payment->status !== 'paid' && ! in_array($sellerOrder->status, ['completed', 'cancelled'], true))
                                            <form action="{{ route('payments.proof.store', $payment) }}" method="POST" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3">
                                                @csrf
                                                <input type="file" name="proof_image" accept="image/jpeg,image/png" required class="max-w-full rounded-lg border border-warm-200 p-2 text-sm" />
                                                <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Unggah ulang bukti</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        @if ($sellerOrder->shipping_method === 'seller_delivery' && $sellerOrder->shipping_status === 'delivered' && $sellerOrder->status !== 'completed')
                            <form action="{{ route('orders.confirm', $sellerOrder) }}" method="POST" class="mt-5">
                                @csrf
                                <button class="rounded-xl bg-forest-700 px-5 py-3 text-sm font-bold text-white">Konfirmasi pesanan telah diterima</button>
                            </form>
                        @endif
                        <div class="mt-5 border-t border-warm-100 pt-5"><h3 class="font-bold text-sm">Detail yang dibeli</h3><div class="mt-3 divide-y divide-warm-100">@foreach ($sellerOrder->order->items->where('seller_profile_id', $sellerOrder->seller_profile_id) as $item)<div class="py-3 text-sm"><div class="flex justify-between gap-4"><span>{{ $item->product_name }} <strong class="text-sage-500">x{{ $item->quantity }}</strong></span><strong>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong></div>@if ($item->note)<p class="mt-1 text-xs text-sage-400">Catatan: {{ $item->note }}</p>@endif</div>@endforeach</div></div>
                    </div>
                </article>
            @endforeach
        </section>
        <div class="mt-8 flex justify-between rounded-2xl bg-warm-white p-6 shadow-soft">
            <span class="font-bold">Total pesanan</span>
            <strong class="text-xl text-forest-700">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</strong>
        </div>
        @if ($canCancel)
            <form action="{{ route('orders.cancel', $order) }}" method="POST" class="mt-5">
                @csrf
                <button class="rounded-xl border border-red-200 px-5 py-3 text-sm font-bold text-red-700 hover:bg-red-50">Batalkan pesanan</button>
            </form>
        @endif
        @if ($order->status !== 'cancelled')
            <a href="{{ route('complaints.create', $order) }}" class="mt-5 inline-block rounded-xl border border-red-200 px-5 py-3 text-sm font-bold text-red-700">Ajukan komplain</a>
        @endif
        @if ($order->status === 'completed')
            <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft">
                <p class="text-sm font-bold uppercase tracking-wider text-forest-700">Bagikan pengalaman</p>
                <h2 class="mt-1 text-xl font-black">Rating dan review produk</h2>
                <div class="mt-5 space-y-5">
                    @foreach ($order->items as $item)
                        @if ($reviewsByProductId->has($item->product_id))
                            @php($review = $reviewsByProductId->get($item->product_id))
                            <article class="border-t border-warm-100 pt-5 first:border-0 first:pt-0">
                                <p class="font-bold">{{ $item->product_name }}</p>
                                <p class="mt-2 text-sm font-bold text-amber-600">Review Anda: {{ $review->rating }}/5</p>
                                @if ($review->review)
                                    <p class="mt-2 text-sm leading-6 text-sage-600">{{ $review->review }}</p>
                                @endif
                            </article>
                        @else
                            <form action="{{ route('orders.reviews.store', $order) }}" method="POST" class="border-t border-warm-100 pt-5 first:border-0 first:pt-0">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                <p class="font-bold">{{ $item->product_name }}</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <label class="text-sm font-semibold">Rating<select name="rating" class="ml-2 rounded-lg border-warm-200"><option value="5">5 - Sangat puas</option><option value="4">4 - Puas</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang</option><option value="1">1 - Tidak puas</option></select></label>
                                </div>
                                <textarea name="review" rows="2" placeholder="Tulis review..." class="mt-3 w-full rounded-lg border-warm-200"></textarea>
                                <button class="mt-3 rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Kirim review</button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</body>
</html>


