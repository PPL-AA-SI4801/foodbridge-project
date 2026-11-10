@props([
    'label',
    'icon' => null,
    'href' => null,
    'active' => false,
    'badge' => null,
    'disabled' => false,
])

@php
    $isDisabled = $disabled || blank($href) || $href === '#';
@endphp

@if ($isDisabled)
    <span
        {{ $attributes->class(['flex min-h-[52px] cursor-not-allowed items-center gap-3 rounded-lg px-3 text-sm font-medium text-muted-foreground/60']) }}
        aria-disabled="true"
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif

        <span class="truncate">{{ $label }}</span>
    </span>
@else
    <a
        href="{{ $href }}"
        @if ($active) aria-current="page" @endif
        data-nav-item
        data-nav-label="{{ strtolower($label) }}"
        x-show="navMatches($el.dataset.navLabel)"
        @click="$dispatch('close-sidebar')"
        {{ $attributes->class([
            'flex min-h-[52px] items-center gap-3 rounded-lg px-3 text-sm transition-colors',
            'bg-sidebar-primary font-semibold text-sidebar-primary-foreground shadow-sm hover:bg-primary-600' => $active,
            'font-medium text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' => ! $active,
        ]) }}
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif

        <span class="truncate">{{ $label }}</span>

        @if ($badge)
            <span @class([
                'ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                'bg-white/20 text-white' => $active,
                'bg-secondary text-white' => ! $active,
            ])>
                {{ $badge }}
            </span>
        @endif
    </a>
@endif
