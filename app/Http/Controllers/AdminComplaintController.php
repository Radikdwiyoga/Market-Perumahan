<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminComplaintController extends Controller
{
    private const STATUSES = ['open', 'in_review', 'resolved', 'rejected'];

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $status = $request->string('status')->value();

        return view('admin.complaints.index', [
            'complaints' => Complaint::query()
                ->with(['order', 'buyer'])
                ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->get(),
            'status' => $status,
            'statusCounts' => Complaint::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->ensureAdmin();
        $status = $request->validate(['status' => ['required', 'in:open,in_review,resolved,rejected']])['status'];
        $complaint->update(['status' => $status]);

        AuditLogger::log('COMPLAINT_STATUS_UPDATED', 'Complaint', $complaint->id, ['status' => $status]);

        if (in_array($status, ['resolved', 'rejected'], true)) {
            UserNotification::send($complaint->buyer_id, 'Komplain ditindaklanjuti', "Komplain untuk pesanan {$complaint->order->order_number} berstatus ".ucfirst($status).'.', 'complaint_status');
        }

        return back()->with('status', 'Status komplain berhasil diperbarui.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
