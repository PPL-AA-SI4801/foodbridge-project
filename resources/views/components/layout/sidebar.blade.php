<aside {{ $attributes->merge(['class' => 'hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-100']) }}>
    <div class="flex items-center h-16 px-6 border-b border-gray-100">
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="flex items-center gap-2">
            <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
            <span class="text-sm font-semibold text-gray-900">{{ config('app.name', 'FoodBridge') }}</span>
        </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        {{ $slot }}
    </nav>

    @if (isset($footer))
        <div class="px-3 py-4 border-t border-gray-100">
            {{ $footer }}
        </div>
    @endif
</aside>
