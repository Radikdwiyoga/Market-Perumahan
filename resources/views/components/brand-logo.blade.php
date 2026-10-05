@props([
    'route' => route('marketplace.index'),
    'tone' => 'dark',
    'compact' => true,
    'large' => false,
])

@php
    $tile = $large ? 'h-11 w-11' : 'h-9 w-9 sm:h-10 sm:w-10';
    $main = $tone === 'light' ? 'text-white' : 'text-forest-950';
    $sub = $tone === 'light' ? 'text-lime-200' : 'text-sage-600';
    $wordmark = $compact ? '' : '';  // selalu tampilkan teks, baik desktop maupun mobile
    $ring = $tone === 'light' ? 'ring-1 ring-white/25' : '';
@endphp

<a href="{{ $route }}" {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-2.5']) }}>
    <span class="{{ $tile }} {{ $ring }} flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-warm-white shadow-soft" aria-hidden="true">
        <img src="{{ asset('images/umkm-logo.png') }}" alt="Market UMKM" class="h-full w-full object-cover" loading="eager" fetchpriority="high" />
    </span>
    <span class="min-w-0 {{ $wordmark }}">
        <strong class="block truncate text-base leading-none {{ $main }}">Market UMKM</strong>
        <small class="mt-1 block truncate text-[11px] leading-none sm:text-xs {{ $sub }}">Perumahan ABC</small>
    </span>
</a>


