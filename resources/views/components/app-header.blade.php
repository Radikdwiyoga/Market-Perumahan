@props([
    'cartCount' => 0,
    'unreadNotifications' => 0,
    'unreadChats' => 0,
])

<header class="sticky top-0 z-40 border-b border-warm-200/60 bg-warm-white/95 backdrop-blur">
    <nav class="mx-auto flex w-full max-w-6xl items-center justify-between gap-1 px-4 py-3 sm:gap-3 sm:px-6 sm:py-3.5">
        <x-brand-logo />

        <div class="flex items-center gap-1 sm:gap-2 lg:gap-3">
            @auth
                @if (auth()->user()->role === 'buyer')
                    <a href="{{ route('cart.index') }}" class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-warm-50 text-forest-700 shadow-soft ring-1 ring-warm-200 transition hover:bg-forest-700 hover:text-white sm:h-10 sm:w-10" title="Keranjang" aria-label="Keranjang">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                        </svg>
                        @if ($cartCount > 0)
                            <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                        @endif
                    </a>
                @endif

                @if (!auth()->user()->isAdmin())
                    <a href="{{ route('notifications.index') }}" data-notification-bell class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-warm-50 text-forest-700 shadow-soft ring-1 ring-warm-200 transition hover:bg-forest-700 hover:text-white sm:h-10 sm:w-10" title="Notifikasi" aria-label="Notifikasi">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75v-.7V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <span class="badge-count absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white {{ $unreadNotifications > 0 ? '' : 'hidden' }}" data-count="{{ $unreadNotifications }}">{{ $unreadNotifications }}</span>
                    </a>
                @endif

                @if (!auth()->user()->isAdmin())
                    <a href="{{ route('chat.index') }}" data-chat-bell class="relative hidden h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warm-50 text-forest-700 shadow-soft ring-1 ring-warm-200 transition hover:bg-forest-700 hover:text-white sm:flex" title="Chat" aria-label="Chat">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                        </svg>
                        <span class="badge-count absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1 text-[11px] font-bold text-white {{ $unreadChats > 0 ? '' : 'hidden' }}" data-count="{{ $unreadChats }}">{{ $unreadChats }}</span>
                    </a>
                @endif

                <a href="{{ route('dashboard') }}" class="flex h-9 items-center gap-2 rounded-full bg-forest-700 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-800 sm:h-10 sm:px-4">
                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A1.125 1.125 0 0 1 18.375 21.75H5.625a1.125 1.125 0 0 1-1.124-1.632Z"/>
                    </svg>
                </a>

                <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="flex h-10 items-center justify-center rounded-full border border-warm-200 bg-warm-50 px-3 text-sm font-semibold text-sage-600 transition hover:bg-red-50 hover:text-red-700" title="Keluar" aria-label="Keluar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                        </svg>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-full px-2 py-2.5 text-sm font-semibold text-sage-600 hover:text-forest-700 sm:px-4">Masuk</a>
                <a href="{{ route('register') }}" class="rounded-full bg-forest-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-forest-800 sm:px-5">Daftar</a>
            @endauth
        </div>
    </nav>
</header>
