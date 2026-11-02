@props([
    'title' => 'Katalog Peta Pangan - FoodBridge',
    'recipient' => null,
    'dailyClaimedPortions' => 0,
])

<x-layout.app-shell
    :title="$title"
    :display-name="$recipient?->nama_lembaga"
    max-width="max-w-[1600px]"
    :sticky-header="true"
>
    <x-slot name="headerActions">
        <div class="hidden items-center gap-2.5 rounded-xl border border-border bg-card px-3 py-1.5 xl:flex">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-accent text-primary">
                <x-icon name="package" class="h-4 w-4" />
            </span>
            <div class="leading-tight">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Klaim hari ini</p>
                <p class="text-sm font-semibold text-foreground">{{ number_format($dailyClaimedPortions, 0, ',', '.') }} porsi</p>
            </div>
        </div>

        <span class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-medium text-muted-foreground xl:hidden">
            <x-icon name="package" class="h-3.5 w-3.5" />
            {{ number_format($dailyClaimedPortions, 0, ',', '.') }} porsi
        </span>
    </x-slot>

    {{ $slot }}
</x-layout.app-shell>
