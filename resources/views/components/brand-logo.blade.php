@props([
    'route' => route('marketplace.index'),
    'tone' => 'dark',
    'compact' => true,
    'large' => false,
])

@php
    $tile = $large ? 'h-11 w-11' : 'h-9 w-9 sm:h-10 sm:w-10';
    $main = $tone === 'light' ? 'text-white' : 'text-slate-900';
    $sub = $tone === 'light' ? 'text-emerald-200' : 'text-slate-500';
    $wordmark = $compact ? 'hidden sm:block' : '';
    $ring = $tone === 'light' ? 'ring-1 ring-white/25' : '';
@endphp

<a href="{{ $route }}" {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-2.5']) }}>
    <span class="{{ $tile }} {{ $ring }} flex shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm" aria-hidden="true">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
            <defs>
                <linearGradient id="brand-tile" x1="12" y1="8" x2="36" y2="40" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#065f46"/>
                    <stop offset="1" stop-color="#047857"/>
                </linearGradient>
            </defs>
            <rect width="48" height="48" rx="13" fill="url(#brand-tile)"/>
            <circle cx="38" cy="10" r="14" fill="#bef264" opacity="0.14"/>
            <path d="M11 22.5 24 10.5 37 22.5" stroke="#bef264" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15 23v12h18V23" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" opacity="0.92"/>
            <g stroke="#bef264" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 28.5c.4-3 4.6-3 5 0"/>
                <path d="M20.7 28.5h6.6l-.7 6.6c-.1.9-.9 1.6-1.8 1.6h-1.6c-.9 0-1.7-.7-1.8-1.6z"/>
            </g>
        </svg>
    </span>
    <span class="min-w-0 {{ $wordmark }}">
        <strong class="block truncate text-base leading-none {{ $main }}">Market UMKM</strong>
        <small class="mt-1 block truncate text-[11px] leading-none sm:text-xs {{ $sub }}">Perumahan ABC</small>
    </span>
</a>