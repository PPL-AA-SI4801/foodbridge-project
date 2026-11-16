@props([
    'href' => null,
    'sidebarId' => 'app-sidebar',
])

@php
    $homeUrl = $href ?? route(auth()->user()->dashboardRouteName());
@endphp

<div {{ $attributes->class(['flex items-center justify-between gap-3 p-4']) }}>
    <a href="{{ $homeUrl }}" class="flex min-w-0 items-center">
        <img
            src="{{ asset(rawurlencode('Logo Foodbridge.webp')) }}"
            alt="{{ config('app.name', 'FoodBridge') }}"
            class="h-[55px] w-[194px] max-w-full object-contain"
        >
    </a>

    <button
        type="button"
        @click="$dispatch('close-sidebar')"
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sidebar-muted transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
        aria-label="Tutup menu"
        aria-controls="{{ $sidebarId }}"
    >
        <x-icon name="x" class="h-5 w-5" />
    </button>
</div>
