<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Admin - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-emerald-700">&larr; Panel admin</a>
                <h1 class="mt-2 text-3xl font-black">Laporan</h1>
                <p class="mt-1 text-slate-500">Statistik penjualan marketplace · {{ $label }}</p>
            </div>
        </div>
        <form action="{{ route('admin.reports.index') }}" method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-sm">
            <label class="text-sm font-semibold">
                Periode
                <select name="period" class="mt-1 block rounded-lg border-slate-300 text-sm">
                    <option value="semua" @selected(($period ?: 'bulan') === 'semua')>Semua waktu</option>
                    <option value="hari" @selected(($period ?: 'bulan') === 'hari')>Hari ini</option>
                    <option value="minggu" @selected(($period ?: 'bulan') === 'minggu')>Minggu ini</option>
                    <option value="bulan" @selected(($period ?: 'bulan') === 'bulan')>Bulan ini</option>
                    <option value="custom" @selected(($period ?: 'bulan') === 'custom')>Rentang custom</option>
                </select>
            </label>
            <label class="text-sm font-semibold">
                Dari
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1 block rounded-lg border-slate-300 text-sm" />
            </label>
            <label class="text-sm font-semibold">
                Sampai
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1 block rounded-lg border-slate-300 text-sm" />
            </label>
            <button class="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-bold text-white">Tampilkan</button>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Export CSV</a>
        </form>
        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['Total GMV', 'Rp'.number_format($summary['gmv'], 0, ',', '.')], ['Total Orders', $summary['orders']], ['Total Pedagang', $summary['sellers']], ['Total Pembeli', $summary['buyers']], ['Total Produk', $summary['products']]] as [$label, $value])
                <article class="rounded-2xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-emerald-800">{{ $value }}</p>
                </article>
            @endforeach
        </div>

        <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Penjualan per hari</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="py-3 pr-4">Tanggal</th><th class="py-3 pr-4">Jumlah order</th><th class="py-3">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($summary['salesPerDay'] as $day)
                            <tr><td class="py-3 pr-4">{{ $day->date }}</td><td class="py-3 pr-4">{{ $day->orders }}</td><td class="py-3 font-semibold">Rp{{ number_format($day->revenue, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-slate-500">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Penjualan per pedagang</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="py-3 pr-4">Toko</th><th class="py-3 pr-4">Jumlah order</th><th class="py-3">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($summary['salesPerSeller'] as $seller)
                            <tr><td class="py-3 pr-4 font-semibold">{{ $seller['store_name'] }}</td><td class="py-3 pr-4">{{ $seller['orders'] }}</td><td class="py-3 font-semibold">Rp{{ number_format($seller['revenue'], 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-slate-500">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Penjualan per kategori</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="py-3 pr-4">Kategori</th><th class="py-3 pr-4">Jumlah terjual</th><th class="py-3">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($summary['salesPerCategory'] as $category)
                            <tr><td class="py-3 pr-4 font-semibold">{{ $category->category_name }}</td><td class="py-3 pr-4">{{ $category->qty }}</td><td class="py-3 font-semibold">Rp{{ number_format($category->revenue, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-slate-500">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-8 grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Metode pembayaran</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                            <tr><th class="py-3 pr-4">Metode</th><th class="py-3 pr-4">Jumlah</th><th class="py-3 pr-4">Pendapatan</th><th class="py-3">Pangsa</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($summary['paymentMethods'] as $method)
                                <tr><td class="py-3 pr-4 font-semibold">{{ $method['method'] }}</td><td class="py-3 pr-4">{{ $method['count'] }}</td><td class="py-3 pr-4">Rp{{ number_format($method['amount'], 0, ',', '.') }}</td><td class="py-3">{{ $method['percent'] }}%</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-slate-500">Belum ada pembayaran pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (count($summary['paymentMethods']) > 0)
                    <div class="mt-6 space-y-3" aria-label="Grafik metode pembayaran">
                        @foreach ($summary['paymentMethods'] as $method)
                            <div class="flex items-center gap-3">
                                <span class="w-32 shrink-0 text-sm font-semibold">{{ $method['method'] }}</span>
                                <div class="h-4 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-700" style="width: {{ $method['percent'] }}%"></div>
                                </div>
                                <span class="w-12 shrink-0 text-right text-sm font-bold">{{ $method['percent'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Metode pengiriman</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                            <tr><th class="py-3 pr-4">Metode</th><th class="py-3 pr-4">Jumlah order</th><th class="py-3">Pendapatan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($summary['shippingMethods'] as $shipping)
                                <tr><td class="py-3 pr-4 font-semibold">{{ $shipping->shipping_method === 'seller_delivery' ? 'Diantar seller' : 'Diambil langsung' }}</td><td class="py-3 pr-4">{{ $shipping->total }}</td><td class="py-3">Rp{{ number_format($shipping->revenue, 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="py-6 text-slate-500">Belum ada pengiriman pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>