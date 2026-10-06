<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-forest-700">&larr; Dashboard</a>
                <h1 class="mt-2 text-3xl font-black">Laporan penjualan</h1>
                <p class="mt-1 text-sage-500">{{ $store->store_name }} &middot; {{ $periodLabel }}</p>
            </div>
            <a href="{{ route('seller.products.index') }}" class="rounded-xl border border-warm-200 bg-warm-white px-4 py-3 text-sm font-bold">Produk toko</a>
        </div>
        <form action="{{ route('seller.reports.index') }}" method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl bg-warm-white p-4 shadow-soft">
            <label class="text-sm font-semibold">
                Periode
                <select name="period" class="mt-1 block rounded-lg border-warm-200 text-sm">
                    <option value="hari" @selected(($period ?: 'hari') === 'hari')>Hari ini</option>
                    <option value="minggu" @selected(($period ?: 'hari') === 'minggu')>Minggu ini</option>
                    <option value="bulan" @selected(($period ?: 'hari') === 'bulan')>Bulan ini</option>
                    <option value="custom" @selected(($period ?: 'hari') === 'custom')>Rentang custom</option>
                </select>
            </label>
            <label class="text-sm font-semibold">
                Dari
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1 block rounded-lg border-warm-200 text-sm" />
            </label>
            <label class="text-sm font-semibold">
                Sampai
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1 block rounded-lg border-warm-200 text-sm" />
            </label>
            <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Tampilkan</button>
            <a href="{{ route('seller.reports.export', request()->query()) }}" class="rounded-lg border border-warm-200 px-4 py-2 text-sm font-bold">Export CSV</a>
        </form>
        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ([['Penjualan hari ini', $dailyGlance['today']], ['Penjualan minggu ini', $dailyGlance['week']], ['Penjualan bulan ini', $dailyGlance['month']]] as [$label, $value])
                <article class="rounded-2xl bg-forest-700 p-6 text-white shadow-soft">
                    <p class="text-sm text-forest-100">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black">Rp{{ number_format($value, 0, ',', '.') }}</p>
                </article>
            @endforeach
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ([['Pendapatan (periode)', 'Rp'.number_format($summary['revenue'], 0, ',', '.')], ['Jumlah order (periode)', $summary['orders']], ['Produk terjual (periode)', $summary['items']]] as [$label, $value])
                <article class="rounded-2xl bg-warm-white p-6 shadow-soft">
                    <p class="text-sm text-sage-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-forest-700">{{ $value }}</p>
                </article>
            @endforeach
        </div>
        <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-xl font-black">Produk terlaris ({{ $periodLabel }})</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-warm-200 text-xs uppercase tracking-wider text-sage-500">
                        <tr><th class="py-3 pr-4">Produk</th><th class="py-3 pr-4">Jumlah terjual</th><th class="py-3">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-warm-100">
                        @forelse ($summary['topProducts'] as $product)
                            <tr><td class="py-3 pr-4 font-semibold">{{ $product->product_name }}</td><td class="py-3 pr-4">{{ $product->total_qty }}</td><td class="py-3 font-semibold">Rp{{ number_format($product->total_revenue, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-sage-500">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="mt-8 rounded-2xl bg-warm-white p-6 shadow-soft">
            <h2 class="text-xl font-black">Statistik pembayaran ({{ $periodLabel }})</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-warm-200 text-xs uppercase tracking-wider text-sage-500">
                        <tr><th class="py-3 pr-4">Metode</th><th class="py-3 pr-4">Jumlah</th><th class="py-3 pr-4">Pendapatan</th><th class="py-3">Pangsa</th></tr>
                    </thead>
                    <tbody class="divide-y divide-warm-100">
                        @forelse ($summary['paymentMethods'] as $method)
                            <tr><td class="py-3 pr-4 font-semibold">{{ $method['method'] }}</td><td class="py-3 pr-4">{{ $method['count'] }}</td><td class="py-3 pr-4">Rp{{ number_format($method['amount'], 0, ',', '.') }}</td><td class="py-3">{{ $method['percent'] }}%</td></tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-sage-500">Belum ada pembayaran pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (count($summary['paymentMethods']) > 0)
                <div class="mt-6 space-y-3" aria-label="Grafik metode pembayaran">
                    @foreach ($summary['paymentMethods'] as $method)
                        <div class="flex items-center gap-3">
                            <span class="w-32 shrink-0 text-sm font-semibold">{{ $method['method'] }}</span>
                            <div class="h-4 flex-1 overflow-hidden rounded-full bg-warm-50">
                                <div class="h-full rounded-full bg-forest-600" style="width: {{ $method['percent'] }}%"></div>
                            </div>
                            <span class="w-12 shrink-0 text-right text-sm font-bold">{{ $method['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</body>
</html>


