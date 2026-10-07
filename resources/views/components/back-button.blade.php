@props(['fallback' => route('marketplace.index')])

<a href="{{ $fallback }}" data-back-button title="Kembali" aria-label="Kembali" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-warm-200 bg-warm-white text-forest-700 transition hover:bg-forest-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-700">
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
    </svg>
</a>