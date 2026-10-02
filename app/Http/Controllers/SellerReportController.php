<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerReportController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->sellerStore();
        [$label, $from, $to] = array_values(ReportPeriod::resolve($request, 'hari'));

        return view('seller.reports.index', [
            'store' => $store,
            'periodLabel' => $label,
            'period' => $request->string('period')->value(),
            'dailyGlance' => [
                'today' => $this->revenue($store->id, now()->startOfDay(), now()),
                'week' => $this->revenue($store->id, now()->startOfWeek(), now()),
                'month' => $this->revenue($store->id, now()->startOfMonth(), now()),
            ],
            'summary' => $this->summary($store->id, $label, $from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $store = $this->sellerStore();
        [$label, $from, $to] = array_values(ReportPeriod::resolve($request, 'hari'));
        $summary = $this->summary($store->id, $label, $from, $to);

        $rows = [
            ['Laporan Penjualan Toko - '.$store->store_name],
            ['Periode', $label],
            [],
            ['# Ringkasan'],
            ['Pendapatan', $summary['revenue']],
            ['Jumlah Order', $summary['orders']],
            ['Produk Terjual', $summary['items']],
            [],
            ['# Metode Pembayaran'],
            ['Metode', 'Jumlah', 'Pendapatan', 'Persentase'],
            ...$summary['paymentMethods']->map(fn ($method): array => [
                $method['method'],
                $method['count'],
                $method['amount'],
                $method['percent'].'%',
            ])->all(),
            [],
            ['# Produk Terlaris'],
            ['Produk', 'Jumlah Terjual', 'Pendapatan'],
            ...$summary['topProducts']->map(fn ($product): array => [
                $product->product_name,
                $product->total_qty,
                $product->total_revenue,
            ])->all(),
        ];

        return $this->downloadCsv($rows, "laporan-penjualan-{$store->id}-".now()->format('Ymd-His').'.csv');
    }

    /**
     * @return array{revenue: int, orders: int, items: int, paymentMethods: Collection<int, Payment>, topProducts: Collection<int, OrderItem>}
     */
    private function summary(int $storeId, string $label, ?Carbon $from, ?Carbon $to): array
    {
        $paidOrders = SellerOrder::query()
            ->where('seller_profile_id', $storeId)
            ->where('payment_status', 'paid')
            ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [$from, $to]));

        $paidOrderIds = (clone $paidOrders)->pluck('order_id');

        $payments = Payment::query()
            ->where('seller_profile_id', $storeId)
            ->where('status', 'paid')
            ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [$from, $to]))
            ->selectRaw('method, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('method')
            ->orderByDesc('amount')
            ->get();
        $paymentBase = max((int) $payments->sum('amount'), 1);

        return [
            'label' => $label,
            'revenue' => (int) (clone $paidOrders)->sum('total_amount'),
            'orders' => (int) (clone $paidOrders)->count(),
            'items' => (int) OrderItem::query()->where('seller_profile_id', $storeId)->whereIn('order_id', $paidOrderIds)->sum('quantity'),
            'paymentMethods' => $payments->map(fn ($row): array => [
                'method' => Payment::methodLabels()[$row->method] ?? ucfirst(str_replace('_', ' ', $row->method)),
                'count' => (int) $row->total,
                'amount' => (int) $row->amount,
                'percent' => round((int) $row->amount / $paymentBase * 100),
            ]),
            'topProducts' => OrderItem::query()
                ->where('seller_profile_id', $storeId)
                ->whereIn('order_id', $paidOrderIds)
                ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
                ->groupBy('product_name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get(),
        ];
    }

    private function revenue(int $storeId, Carbon $from, Carbon $to): int
    {
        return (int) SellerOrder::query()
            ->where('seller_profile_id', $storeId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
