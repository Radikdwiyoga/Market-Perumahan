<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat dengan {{ $counterpart->name }} - Market UMKM Perumahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f3ed] text-slate-900">
    <header class="border-b border-slate-900/10 bg-white">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-5">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                <x-brand-logo />
                <a href="{{ route('chat.index') }}" class="text-sm font-bold text-emerald-700">&larr; Daftar chat</a>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-lg font-black text-lime-200">{{ strtoupper(substr($counterpart->name, 0, 1)) }}</span>
                <div class="min-w-0"><h1 class="truncate text-lg font-black sm:text-xl">{{ $counterpart->name }}</h1><p class="text-xs text-slate-500">@if ($conversation->sellerProfile->user_id === auth()->id()) Pembeli · Blok {{ $counterpart->block }} No. {{ $counterpart->house_number }}@else Toko {{ $conversation->sellerProfile->store_name }}@endif</p></div>
            </div>
            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener" class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700">WhatsApp</a>
        </div>
    </header>
    <main class="mx-auto max-w-4xl px-6 py-8">
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="h-[28rem] space-y-4 overflow-y-auto bg-emerald-50/60 p-6">
                @forelse ($conversation->messages as $message)
                    @php($mine = $message->sender_id === auth()->id())
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] rounded-2xl px-4 py-3 {{ $mine ? 'rounded-br-sm bg-emerald-800 text-white' : 'rounded-bl-sm border border-slate-200 bg-white' }}">
                            <p class="text-sm font-semibold {{ $mine ? 'text-lime-200' : 'text-slate-400' }}">{{ $mine ? 'Anda' : $message->sender->name }}</p>
                            <p class="mt-0.5 whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>
                            <small class="mt-1 block text-right text-[11px] {{ $mine ? 'text-emerald-200' : 'text-slate-400' }}">{{ $message->created_at->format('d M H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center text-center">
                        <div><p class="text-lg font-bold text-slate-500">Belum ada pesan</p><p class="mt-1 text-sm text-slate-400">Tulis pesan pertama untuk memulai percakapan.</p></div>
                    </div>
                @endforelse
            </div>
            <form action="{{ route('chat.messages.store', $conversation) }}" method="POST" class="border-t border-slate-100 p-4">
                @csrf
                @error('body')<p class="mb-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                <div class="flex items-end gap-3">
                    <textarea name="body" rows="2" required maxlength="2000" placeholder="Tulis pesan..." class="min-h-14 flex-1 rounded-2xl border-slate-300 resize-none"></textarea>
                    <button class="rounded-xl bg-emerald-800 px-6 py-3 font-bold text-white hover:bg-emerald-900">Kirim</button>
                </div>
            </form>
        </section>
        <p class="mt-4 text-center text-xs text-slate-400">Lebih cepat? Lanjutkan percakapan di WhatsApp lewat tombol hijau di atas.</p>
    </main>
</body>
</html>