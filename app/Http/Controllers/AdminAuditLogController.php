<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $search = $request->string('q')->trim()->value();

        return view('admin.audit_logs.index', [
            'auditLogs' => AuditLog::query()
                ->with('user')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('action', 'like', "%{$search}%")
                            ->orWhere('entity_type', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                    });
                })
                ->latest('created_at')
                ->paginate(25),
            'search' => $search,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
