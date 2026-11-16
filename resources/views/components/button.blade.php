@props(['href' => null, 'size' => 'md', 'disabled' => false, 'type' => 'button'])

@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
        @if ($disabled)
            aria-disabled="true"
            tabindex="-1"
        @endif
    @else
        type="{{ $type }}"
        @disabled($disabled)
    @endif
    {{ $attributes->merge(['class' => 'btn btn-' . $size]) }}
>
    @if (isset($icon) && ! $icon->isEmpty())
        <span class="btn__icon" aria-hidden="true">{{ $icon }}</span>
    @endif

    {{ $slot }}
</{{ $tag }}>
