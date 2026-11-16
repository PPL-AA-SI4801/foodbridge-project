@props([
    'label' => null,
    'icon' => null,
])

<section
    {{ $attributes->class(['flex flex-col gap-[10px] px-4 pt-4']) }}
    x-show="navQuery.trim() === '' || navSectionHasMatches($el)"
    @if ($label) aria-label="{{ $label }}" @endif
>
    @if ($label)
        <p class="w-full text-[12px] font-normal leading-[normal] text-sidebar-muted">
            {{ $label }}
        </p>
    @endif

    <div class="flex flex-col gap-[2px]">
        {{ $slot }}
    </div>
</section>
