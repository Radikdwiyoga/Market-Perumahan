<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $this->ensureAdmin();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'sellers' => User::query()->where('role', 'seller')->count(),
                'stores' => SellerProfile::query()->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
                'categories' => Category::query()->count(),
                'complaints' => Complaint::query()->count(),
            ],
            'recentOrders' => Order::query()->with('buyer')->latest()->limit(8)->get(),
            'openComplaints' => Complaint::query()->where('status', 'open')->count(),
            'pendingSellerApprovals' => SellerProfile::query()->where('verification_status', 'pending')->count(),
            'unreadNotifications' => auth()->user()->userNotifications()->unread()->count(),
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
