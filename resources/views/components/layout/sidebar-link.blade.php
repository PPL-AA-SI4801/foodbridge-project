@props(['active' => false, 'href' => '#'])

@php
$classes = ($active ?? false)
            ? 'group flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md bg-gray-50 text-gray-900'
            : 'group flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if (isset($icon))
        <span class="text-gray-400 group-hover:text-gray-500">
            {{ $icon }}
        </span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
