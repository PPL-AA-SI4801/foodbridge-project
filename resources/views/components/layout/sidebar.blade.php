<aside
    {{ $attributes->merge(['class' => 'fixed inset-y-0 left-0 z-50 flex w-[19rem] max-w-[85vw] shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:bottom-auto lg:z-auto lg:h-screen lg:w-64 lg:max-w-none']) }}
    x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    id="app-sidebar"
    aria-label="Navigasi utama"
    x-cloak
>
    <div class="flex min-h-16 items-center gap-3 border-b border-sidebar-border px-4">
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="flex min-w-0 items-center">
            <img
                src="{{ asset(rawurlencode('Logo Foodbridge.webp')) }}"
                alt="{{ config('app.name', 'FoodBridge') }}"
                class="h-10 w-auto max-w-[10rem] object-contain"
            >
        </a>

        <button
            type="button"
            @click="$dispatch('close-sidebar')"
            class="ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
            aria-label="Tutup menu"
            aria-controls="app-sidebar"
        >
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        {{ $slot }}
    </nav>

    @if (isset($footer))
        <div class="border-t border-sidebar-border px-3 py-4">
            {{ $footer }}
        </div>
    @endif
</aside>
