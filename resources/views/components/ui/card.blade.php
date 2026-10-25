@props(['header' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg']) }}>
    @if (isset($header))
        <div class="px-4 py-5 sm:px-6 border-b border-gray-100">
            {{ $header }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-4 py-4 sm:px-6 border-t border-gray-100 bg-gray-50">
            {{ $footer }}
        </div>
    @endif
</div>
