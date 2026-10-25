@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'py-8']) }}>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($title || isset($actions))
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    @if ($title)
                        <h2 class="text-lg font-medium text-gray-900">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-1 text-sm text-gray-600">{{ $description }}</p>
                    @endif
                </div>

                @if (isset($actions))
                    <div class="flex items-center gap-2">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</section>
