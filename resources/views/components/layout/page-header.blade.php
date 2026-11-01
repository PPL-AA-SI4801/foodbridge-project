@props(['maxWidth' => 'max-w-7xl'])

<header {{ $attributes->merge(['class' => 'border-b border-border bg-card']) }}>
    <div class="{{ $maxWidth }} mx-auto px-4 py-5 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</header>
