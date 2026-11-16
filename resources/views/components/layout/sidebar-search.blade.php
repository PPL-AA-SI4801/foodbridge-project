@props([
    'placeholder' => 'Pencarian...',
])

<div {{ $attributes->class(['px-4 pb-4 pt-0']) }}>
    <label class="relative block">
        <span class="sr-only">Cari menu navigasi</span>

        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-sidebar-placeholder" />

        <input
            type="search"
            x-ref="navSearch"
            x-model="navQuery"
            @keydown.escape="navQuery = ''"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="h-10 w-full rounded-[8px] border border-[#e8e9e8] bg-white pl-9 pr-16 text-[12px] text-foreground placeholder:text-sidebar-placeholder focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30 shadow-[0px_1px_3px_0px_rgba(0,0,0,0.02)]"
        >

        <span class="pointer-events-none absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-[5px]">
            <kbd class="inline-flex h-[24px] w-[22px] items-center justify-center rounded-[5px] border border-[#e8e9e8] bg-sidebar text-[12px] font-normal text-sidebar-muted">
                ⌘
            </kbd>
            <kbd class="inline-flex h-[24px] w-[22px] items-center justify-center rounded-[5px] border border-[#e8e9e8] bg-sidebar text-[12px] font-normal text-sidebar-muted">
                K
            </kbd>
        </span>
    </label>
</div>
