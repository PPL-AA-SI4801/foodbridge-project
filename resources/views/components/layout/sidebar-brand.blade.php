@props([
    'href' => null,
    'sidebarId' => 'app-sidebar',
])

@php
    $homeUrl = $href ?? route(auth()->user()->dashboardRouteName());
@endphp

<div {{ $attributes->class(['flex min-h-20 items-center gap-3 px-5 lg:min-h-24 lg:px-6']) }}>
    <a href="{{ $homeUrl }}" class="flex min-w-0 items-center gap-2.5">
        <img
            src="{{ asset(rawurlencode('Logo Foodbridge.webp')) }}"
            alt="{{ config('app.name', 'FoodBridge') }}"
            class="h-9 w-auto max-w-[9.5rem] object-contain"
        >
    </a>

    <button
        type="button"
        @click="$dispatch('close-sidebar')"
        class="ml-auto inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
        aria-label="Tutup menu"
        aria-controls="{{ $sidebarId }}"
    >
        <x-icon name="x" class="h-5 w-5" />
    </button>
</div>
