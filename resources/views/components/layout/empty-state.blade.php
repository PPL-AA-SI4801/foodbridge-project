@props(['icon' => null, 'title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    @if (isset($icon))
        <div class="mx-auto h-12 w-12 text-gray-400">
            {{ $icon }}
        </div>
    @endif

    @if ($title)
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $title }}</h3>
    @endif

    @if ($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="mt-6 flex items-center justify-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
