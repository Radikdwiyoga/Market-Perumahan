<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-forest-700">&larr; Panel admin</a>
                <h1 class="mt-2 text-3xl font-black">Audit log</h1>
                <p class="mt-1 text-sage-500">Riwayat aktivitas penting dalam sistem.</p>
            </div>
            <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari aksi / pengguna..." class="rounded-lg border-warm-200 text-sm" />
                <button class="rounded-lg bg-forest-700 px-4 py-2 text-sm font-bold text-white">Cari</button>
            </form>
        </div>
        <div class="mt-8 overflow-x-auto rounded-2xl bg-warm-white shadow-soft">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-warm-200 bg-warm-50 text-xs uppercase tracking-wider text-sage-500">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">Aksi</th>
                        <th class="px-5 py-3">Entitas</th>
                        <th class="px-5 py-3">IP</th>
                        <th class="px-5 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100">
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td class="px-5 py-4 whitespace-nowrap text-sage-500">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="px-5 py-4 font-semibold">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-forest-100 px-3 py-1 text-xs font-bold text-forest-700">{{ $log->action }}</span></td>
                            <td class="px-5 py-4 text-sage-500">{{ $log->entity_type }}@if ($log->entity_id) #{{ $log->entity_id }}@endif</td>
                            <td class="px-5 py-4 text-sage-500">{{ $log->ip_address }}</td>
                            <td class="px-5 py-4 text-xs text-sage-500">{{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sage-500">Belum ada aktivitas tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-8">{{ $auditLogs->links() }}</div>
    </main>
</body>
</html>


