@props([
    'placeholder' => 'Pencarian...',
])

<div {{ $attributes->class(['px-4 pb-4 pt-0']) }}>
    <label class="relative block">
        <span class="sr-only">Cari menu navigasi</span>

        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-sidebar-placeholder" />

        <input
            type="search"
            x-ref="navSearch"
            x-model="navQuery"
            @keydown.escape="navQuery = ''"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="h-10 w-full rounded-lg border border-border bg-card pl-10 pr-16 text-xs text-foreground placeholder:text-sidebar-placeholder focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30"
        >

        <span class="pointer-events-none absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-1.5">
            <kbd class="inline-flex h-6 w-5 items-center justify-center rounded border border-border bg-sidebar text-xs font-normal text-sidebar-muted">
                ⌘
            </kbd>
            <kbd class="inline-flex h-6 w-5 items-center justify-center rounded border border-border bg-sidebar text-xs font-normal text-sidebar-muted">
                K
            </kbd>
        </span>
    </label>
</div>
