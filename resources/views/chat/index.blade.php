<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-warm-50 text-forest-950">
    <header class="border-b border-warm-200/60 bg-warm-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3"><x-brand-logo /><div><a href="{{ route('dashboard') }}" class="text-sm font-bold text-forest-700">&larr; Dashboard</a><h1 class="mt-1 text-lg font-black sm:text-2xl">Chat</h1></div></div>
            <a href="{{ route('marketplace.index') }}" class="text-sm font-semibold text-sage-600">Katalog</a>
        </div>
    </header>
    <main class="mx-auto max-w-3xl px-6 py-10">
        @if ($conversations->isEmpty())
            <section class="rounded-2xl bg-warm-white p-12 text-center shadow-soft">
                <h2 class="text-2xl font-black">Belum ada percakapan</h2>
                <p class="mt-2 text-sage-500">@if (auth()->user()->role === 'buyer') Ajak ngobrol dengan UMKM dari halaman produk terlebih dahulu.@else Percakapan akan muncul saat pembeli mulai mengobrol dengan toko Anda.@endif</p>
                <a href="{{ route('marketplace.index') }}" class="mt-6 inline-block rounded-xl bg-forest-700 px-5 py-3 font-bold text-white hover:bg-forest-800">Buka katalog</a>
            </section>
        @else
            <div class="space-y-3">
                @foreach ($conversations as $conversation)
                    @php($counterpart = $conversation->counterpart(auth()->user()))
                    @php($unread = $unreadCounts[$conversation->id] ?? 0)
                    @php($lastMessage = $conversation->latestMessage)
                    <a href="{{ route('chat.show', $conversation) }}" class="flex items-center gap-4 rounded-2xl bg-warm-white p-5 shadow-soft transition hover:bg-forest-50">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-forest-700 text-lg font-black text-lime-200">{{ strtoupper(substr($counterpart->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-3">
                                <strong class="truncate">{{ auth()->user()->role === 'buyer' ? $conversation->sellerProfile->store_name : $counterpart->name }}</strong>
                                @if ($lastMessage)<small class="shrink-0 text-xs text-sage-400">{{ $lastMessage->created_at->format('d M H:i') }}</small>@endif
                            </span>
                            <span class="mt-1 flex items-center justify-between gap-3">
                                <span class="truncate text-sm text-sage-500">{{ $lastMessage?->body ?? 'Belum ada pesan' }}</span>
                                @if ($unread > 0)<span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-bold text-white">{{ $unread }}</span>@endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>


