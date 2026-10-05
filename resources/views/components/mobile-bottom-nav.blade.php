@props([
    'cartCount' => 0,
    'unreadNotifications' => 0,
    'unreadChats' => 0,
])

@php
    $tab = 'relative flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[10px] font-semibold transition';
    $idle = 'text-sage-500';
    $active = 'text-forest-700';
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-warm-200/60 bg-warm-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur sm:hidden">
    <div class="flex w-full items-stretch">
        @auth
            @if (auth()->user()->role === 'buyer')
                <a href="{{ route('marketplace.index') }}" class="{{ $tab }} {{ request()->routeIs('marketplace.*') ? $active : $idle }}">
                    @if (request()->routeIs('marketplace.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </span>
                    <span>Beranda</span>
                </a>

                <a href="{{ route('cart.index') }}" class="{{ $tab }} {{ request()->routeIs('cart.*') ? $active : $idle }}">
                    @if (request()->routeIs('cart.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                        </svg>
                        @if ($cartCount > 0)
                            <span class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                        @endif
                    </span>
                    <span>Keranjang</span>
                </a>

                <a href="{{ route('orders.index') }}" class="{{ $tab }} {{ request()->routeIs('orders.*') ? $active : $idle }}">
                    @if (request()->routeIs('orders.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                        </svg>
                    </span>
                    <span>Pesanan</span>
                </a>

                <a href="{{ route('chat.index') }}" class="{{ $tab }} {{ request()->routeIs('chat.*') ? $active : $idle }}">
                    @if (request()->routeIs('chat.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                        </svg>
                        @if ($unreadChats > 0)
                            <span class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-green-600 px-1 text-[9px] font-bold text-white">{{ $unreadChats > 9 ? '9+' : $unreadChats }}</span>
                        @endif
                    </span>
                    <span>Chat</span>
                </a>

                <a href="{{ route('dashboard') }}" class="{{ $tab }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
                    @if (request()->routeIs('dashboard'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A1.125 1.125 0 0 1 18.375 21.75H5.625a1.125 1.125 0 0 1-1.124-1.632Z"/>
                        </svg>
                    </span>
                    <span>Profil</span>
                </a>
            @elseif (auth()->user()->isSeller())
                <a href="{{ route('marketplace.index') }}" class="{{ $tab }} {{ request()->routeIs('marketplace.*') ? $active : $idle }}">
                    @if (request()->routeIs('marketplace.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </span>
                    <span>Katalog</span>
                </a>

                <a href="{{ route('seller.dashboard') }}" class="{{ $tab }} {{ request()->routeIs('seller.dashboard') ? $active : $idle }}">
                    @if (request()->routeIs('seller.dashboard'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('seller.orders.index') }}" class="{{ $tab }} {{ request()->routeIs('seller.orders.*') ? $active : $idle }}">
                    @if (request()->routeIs('seller.orders.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                        </svg>
                    </span>
                    <span>Pesanan</span>
                </a>

                <a href="{{ route('seller.products.index') }}" class="{{ $tab }} {{ request()->routeIs('seller.products.*') ? $active : $idle }}">
                    @if (request()->routeIs('seller.products.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                        </svg>
                    </span>
                    <span>Produk</span>
                </a>

                <a href="{{ route('dashboard') }}" class="{{ $tab }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
                    @if (request()->routeIs('dashboard'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A1.125 1.125 0 0 1 18.375 21.75H5.625a1.125 1.125 0 0 1-1.124-1.632Z"/>
                        </svg>
                    </span>
                    <span>Profil</span>
                </a>
            @elseif (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="{{ $tab }} {{ request()->routeIs('admin.dashboard') ? $active : $idle }}">
                    @if (request()->routeIs('admin.dashboard'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="{{ $tab }} {{ request()->routeIs('admin.users.*') ? $active : $idle }}">
                    @if (request()->routeIs('admin.users.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 21c-2.614 0-5.06-.87-7.025-2.339M15 14.25a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                        </svg>
                    </span>
                    <span>User</span>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="{{ $tab }} {{ request()->routeIs('admin.categories.*') ? $active : $idle }}">
                    @if (request()->routeIs('admin.categories.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128v.461M9.53 16.122a3 3 0 0 0 5.94 0M9.53 16.122a15.998 15.998 0 0 0 3.988.466M12 3.75c-1.036 0-1.875.692-1.875 1.55 0 .858.839 1.55 1.875 1.55s1.875-.692 1.875-1.55c0-.858-.839-1.55-1.875-1.55Z"/>
                        </svg>
                    </span>
                    <span>Kategori</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" class="{{ $tab }} {{ request()->routeIs('admin.reports.*') ? $active : $idle }}">
                    @if (request()->routeIs('admin.reports.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                        </svg>
                    </span>
                    <span>Laporan</span>
                </a>

                <a href="{{ route('dashboard') }}" class="{{ $tab }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
                    @if (request()->routeIs('dashboard'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A1.125 1.125 0 0 1 18.375 21.75H5.625a1.125 1.125 0 0 1-1.124-1.632Z"/>
                        </svg>
                    </span>
                    <span>Profil</span>
                </a>
            @endif
        @else
            <a href="{{ route('marketplace.index') }}" class="{{ $tab }} {{ request()->routeIs('marketplace.*') ? $active : $idle }}">
                @if (request()->routeIs('marketplace.*'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                <span class="relative flex h-6 w-6 items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                </span>
                <span>Beranda</span>
            </a>

            <a href="{{ route('login') }}" class="{{ $tab }} {{ request()->routeIs('login') ? $active : $idle }}">
                @if (request()->routeIs('login'))<span class="absolute inset-x-6 top-0 h-0.5 rounded-b-full bg-forest-700"></span>@endif
                <span class="relative flex h-6 w-6 items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                </span>
                <span>Masuk</span>
            </a>
        @endauth
    </div>
</nav>
