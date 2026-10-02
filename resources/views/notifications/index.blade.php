<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifikasi - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-4xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-emerald-700">&larr; Dashboard</a>
                <h1 class="mt-2 text-3xl font-black">Notifikasi</h1>
                <p class="mt-1 text-slate-500"><strong class="text-emerald-700">{{ $unreadCount }}</strong> belum dibaca</p>
            </div>
            @if ($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button class="rounded-xl bg-emerald-800 px-4 py-3 text-sm font-bold text-white">Tandai semua sudah dibaca</button>
                </form>
            @endif
        </div>
        @if (session('status'))
            <div class="mt-6 rounded-lg bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif
        <div class="mt-8 space-y-4">
            @forelse ($notifications as $notification)
                <article class="rounded-2xl bg-white p-5 shadow-sm {{ $notification->read_at ? 'opacity-70' : 'border-l-4 border-emerald-600' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-black">{{ $notification->title }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        @if (! $notification->read_at)
                            <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-white p-12 text-center shadow-sm">
                    <h2 class="text-xl font-black">Belum ada notifikasi</h2>
                    <p class="mt-2 text-slate-500">Aktivitas pesanan dan akun akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $notifications->links() }}</div>
    </main>
</body>
</html>