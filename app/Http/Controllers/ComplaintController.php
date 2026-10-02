<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(Order $order): View
    {
        $this->authorizeBuyerOrder($order);

        return view('complaints.create', ['order' => $order]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeBuyerOrder($order);
        $validated = $request->validate([
            'category' => ['required', 'in:item_not_as_described,damaged_item,missing_item,not_received,payment,other'],
            'description' => ['required', 'string', 'max:2000'],
            'evidence_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('evidence_image')) {
            $validated['evidence_image'] = $request->file('evidence_image')->store('complaints', 'public');
        }

        $complaint = Complaint::create(['order_id' => $order->id, 'buyer_id' => auth()->id(), ...$validated]);

        AuditLogger::log('COMPLAINT_CREATED', 'Complaint', $complaint->id, [
            'order_number' => $order->order_number,
            'category' => $complaint->category,
        ]);

        UserNotification::sendToAdmins('Komplain baru', "Komplain {$complaint->category} untuk pesanan {$order->order_number} masuk dari pembeli.", 'complaint_created');

        $order->load('sellerOrders.sellerProfile.user');
        foreach ($order->sellerOrders as $sellerOrder) {
            UserNotification::send($sellerOrder->sellerProfile->user_id, 'Komplain baru', "Pesanan {$order->order_number} Anda menerima komplain dari pembeli.", 'complaint_created');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Komplain berhasil diajukan.');
    }

    private function authorizeBuyerOrder(Order $order): void
    {
        abort_unless(auth()->user()->role === 'buyer' && $order->buyer_id === auth()->id(), 403);
        abort_if($order->status === 'cancelled', 422, 'Order yang dibatalkan tidak dapat dikomplain.');
    }
}
