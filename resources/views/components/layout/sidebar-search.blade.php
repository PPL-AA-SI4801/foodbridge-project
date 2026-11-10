@props([
    'placeholder' => 'Cari menu...',
])

<div {{ $attributes->class(['px-4 pb-2 pt-3 lg:px-5']) }}>
    <label class="relative block">
        <span class="sr-only">Cari menu navigasi</span>

        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

        <input
            type="search"
            x-ref="navSearch"
            x-model="navQuery"
            @keydown.escape="navQuery = ''"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="h-10 w-full rounded-lg border border-sidebar-border bg-card pl-9 pr-14 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30"
        >

        <kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 items-center rounded border border-sidebar-border bg-sidebar px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground sm:inline-flex">
            &#8984;K
        </kbd>
    </label>
</div>
