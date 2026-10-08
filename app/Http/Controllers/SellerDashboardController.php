<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use Illuminate\View\View;

class SellerDashboardController extends Controller
{
    public function index(): View
    {
        $store = $this->sellerStore();
        $storeId = $store->id;

        return view('seller.dashboard', [
            'store' => $store,
            'stats' => [
                'revenueToday' => (int) SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->sum('total_amount'),
                'newOrders' => SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('status', 'pending')
                    ->count(),
                'processing' => SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('status', 'processing')
                    ->count(),
                'readyForPickup' => SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('shipping_method', 'store_pickup')
                    ->where('shipping_status', 'ready')
                    ->whereNot('status', 'cancelled')
                    ->count(),
                'outForDelivery' => SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('shipping_method', 'seller_delivery')
                    ->whereIn('shipping_status', ['ready', 'out_for_delivery'])
                    ->whereNot('status', 'cancelled')
                    ->count(),
                'completed' => SellerOrder::query()
                    ->where('seller_profile_id', $storeId)
                    ->where('status', 'completed')
                    ->count(),
            ],
            'recentOrders' => SellerOrder::query()
                ->with('order.buyer')
                ->where('seller_profile_id', $storeId)
                ->latest()
                ->limit(5)
                ->get(),
            'lowStockProducts' => Product::query()
                ->where('seller_profile_id', $storeId)
                ->where('status', 'active')
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->limit(5)
                ->get(),
            'activeProductCount' => Product::query()
                ->where('seller_profile_id', $storeId)
                ->where('status', 'active')
                ->count(),
            'unreadNotifications' => auth()->user()->userNotifications()->unread()->count(),
        ]);
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
