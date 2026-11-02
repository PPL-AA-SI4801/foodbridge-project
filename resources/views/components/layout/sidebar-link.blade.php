@props(['active' => false, 'href' => '#'])

@php
$classes = ($active ?? false)
            ? 'group relative flex items-center gap-3 rounded-md bg-sidebar-accent px-3 py-2.5 text-sm font-medium text-sidebar-accent-foreground'
            : 'group relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($active) aria-current="page" @endif
>
    @if ($active)
        <span class="absolute bottom-2 left-0 top-2 w-0.5 rounded-full bg-secondary" aria-hidden="true"></span>
    @endif

    @if (isset($icon))
        <span class="shrink-0 text-muted-foreground group-hover:text-sidebar-accent-foreground">
            {{ $icon }}
        </span>
    @endif

    <span class="truncate">{{ $slot }}</span>
</a>
