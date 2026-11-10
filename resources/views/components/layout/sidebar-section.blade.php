@props([
    'label' => null,
    'icon' => null,
])

<section
    {{ $attributes->class(['flex flex-col gap-1 px-3 pt-3 lg:px-4']) }}
    @if ($label) aria-label="{{ $label }}" @endif
>
    @if ($label)
        <div class="flex items-center gap-2 px-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">
            @if ($icon)
                <x-icon :name="$icon" class="h-3.5 w-3.5" />
            @endif

            <span>{{ $label }}</span>

            @isset($action)
                <span class="ml-auto">{{ $action }}</span>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
