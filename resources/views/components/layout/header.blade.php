<header class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-4">
                @if (isset($sidebarToggle))
                    {{ $sidebarToggle }}
                @endif

                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                @if (isset($left))
                    <div class="hidden sm:flex sm:items-center sm:gap-6">
                        {{ $left }}
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                @if (isset($center))
                    <div class="hidden md:flex items-center">
                        {{ $center }}
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    {{ $slot }}
                </div>

                @if (isset($right))
                    <div class="hidden sm:flex sm:items-center">
                        {{ $right }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>
