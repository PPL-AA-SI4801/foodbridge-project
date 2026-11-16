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
        {{ $attributes->class(['flex h-10 cursor-not-allowed items-center gap-3 rounded-[8px] border border-[#fafafa] bg-[#fafafa] px-3 text-[14px] font-normal text-sidebar-muted/60']) }}
        aria-disabled="true"
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0" />
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
            'flex h-10 items-center gap-3 rounded-[8px] border px-3 text-[14px] transition-colors',
            'border-[rgba(255,255,255,0.4)] bg-[#2a9263] font-medium text-white shadow-[inset_0px_4px_6px_0px_rgba(255,255,255,0.24)]' => $active,
            'border-[#fafafa] bg-[#fafafa] font-normal text-sidebar-muted hover:bg-white' => ! $active,
        ]) }}
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0" />
        @endif

        <span class="truncate">{{ $label }}</span>

        @if ($badge)
            <span class="ml-auto inline-flex items-center justify-center rounded-[99px] bg-[#d26c29] px-[8px] py-[4px] text-[12px] font-bold leading-[normal] text-white">
                {{ $badge }}
            </span>
        @endif
    </a>
@endif
