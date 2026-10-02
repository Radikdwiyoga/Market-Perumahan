<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin();

        return view('admin.reports.index', $this->reportData($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureAdmin();
        ['label' => $label, 'summary' => $summary] = $this->reportData($request);

        $rows = [
            ['Laporan Admin Marketplace Perumahan'],
            ['Periode', $label],
            [],
            ['# Ringkasan'],
            ['Total GMV', $summary['gmv']],
            ['Total Orders', $summary['orders']],
            ['Total Pedagang', $summary['sellers']],
            ['Total Pembeli', $summary['buyers']],
            ['Total Produk', $summary['products']],
            [],
            ['# Penjualan per Hari'],
            ['Tanggal', 'Jumlah Order', 'Pendapatan'],
            ...$summary['salesPerDay']->map(fn ($row): array => [$row->date, $row->orders, $row->revenue])->all(),
            [],
            ['# Penjualan per Pedagang'],
            ['Toko', 'Jumlah Order', 'Pendapatan'],
            ...$summary['salesPerSeller']->map(fn ($row): array => [$row['store_name'], $row['orders'], $row['revenue']])->all(),
            [],
            ['# Penjualan per Kategori'],
            ['Kategori', 'Jumlah Terjual', 'Pendapatan'],
            ...$summary['salesPerCategory']->map(fn ($row): array => [$row->category_name, $row->qty, $row->revenue])->all(),
            [],
            ['# Metode Pembayaran'],
            ['Metode', 'Jumlah', 'Pendapatan', 'Persentase'],
            ...$summary['paymentMethods']->map(fn ($row): array => [$row['method'], $row['count'], $row['amount'], $row['percent'].'%'])->all(),
            [],
            ['# Metode Pengiriman'],
            ['Metode', 'Jumlah Order', 'Pendapatan'],
            ...$summary['shippingMethods']->map(fn ($row): array => [$row->shipping_method, $row->total, $row->revenue])->all(),
        ];

        return $this->downloadCsv($rows, 'laporan-admin-'.now()->format('Ymd-His').'.csv');
    }

    /**
     * @return array{label: string, summary: array<string, mixed>}
     */
    private function reportData(Request $request): array
    {
        [$label, $from, $to] = array_values(ReportPeriod::resolve($request, 'bulan'));

        $inPeriod = fn ($query) => $from && $to ? $query->whereBetween('created_at', [$from, $to]) : $query;

        $paidOrders = SellerOrder::query()
            ->where('payment_status', 'paid')
            ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [$from, $to]));

        $paidOrderIds = (clone $paidOrders)->pluck('order_id');

        $payments = Payment::query()
            ->where('status', 'paid')
            ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [$from, $to]))
            ->selectRaw('method, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('method')
            ->orderByDesc('amount')
            ->get();
        $paymentBase = max((int) $payments->sum('amount'), 1);

        return [
            'label' => $label,
            'period' => $request->string('period')->value(),
            'summary' => [
                'gmv' => (int) (clone $paidOrders)->sum('total_amount'),
                'orders' => (int) Order::query()->when($from && $to, $inPeriod)->count(),
                'sellers' => (int) SellerProfile::query()->when($from && $to, $inPeriod)->count(),
                'buyers' => (int) User::query()->where('role', 'buyer')->when($from && $to, $inPeriod)->count(),
                'products' => (int) Product::query()->when($from && $to, $inPeriod)->count(),
                'salesPerDay' => (clone $paidOrders)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'salesPerSeller' => (clone $paidOrders)
                    ->with('sellerProfile')
                    ->selectRaw('seller_profile_id, COUNT(*) as orders, SUM(total_amount) as revenue')
                    ->groupBy('seller_profile_id')
                    ->orderByDesc('revenue')
                    ->get()
                    ->map(fn ($row): array => [
                        'store_name' => $row->sellerProfile->store_name,
                        'orders' => (int) $row->orders,
                        'revenue' => (int) $row->revenue,
                    ]),
                'salesPerCategory' => OrderItem::query()
                    ->whereIn('order_id', $paidOrderIds)
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->selectRaw('categories.name as category_name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as revenue')
                    ->groupBy('categories.id', 'categories.name')
                    ->orderByDesc('revenue')
                    ->get(),
                'paymentMethods' => $payments->map(fn ($row): array => [
                    'method' => Payment::methodLabels()[$row->method] ?? ucfirst(str_replace('_', ' ', $row->method)),
                    'count' => (int) $row->total,
                    'amount' => (int) $row->amount,
                    'percent' => round((int) $row->amount / $paymentBase * 100),
                ]),
                'shippingMethods' => (clone $paidOrders)
                    ->selectRaw('shipping_method, COUNT(*) as total, SUM(total_amount) as revenue')
                    ->groupBy('shipping_method')
                    ->orderByDesc('revenue')
                    ->get(),
            ],
        ];
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
