@props(['header' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-border bg-card']) }}>
    @if (isset($header))
        <div class="border-b border-border px-4 py-5 sm:px-6">
            {{ $header }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="border-t border-border bg-muted px-4 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endif
</div>
