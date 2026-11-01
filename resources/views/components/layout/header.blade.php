@props([
    'maxWidth' => 'max-w-7xl',
    'showLogo' => true,
    'hideLogoOnDesktop' => false,
    'sticky' => false,
    'title' => null,
    'roleLabel' => null,
])

<header @class(['border-b border-border bg-card/95 backdrop-blur', 'sticky top-0 z-30' => $sticky])>
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                @isset($sidebarToggle)
                    {{ $sidebarToggle }}
                @endisset

                @if ($showLogo)
                    <a
                        href="{{ route(auth()->user()->dashboardRouteName()) }}"
                        @class([
                            'flex shrink-0 items-center gap-2.5',
                            'lg:hidden' => $hideLogoOnDesktop,
                        ])
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <x-icon name="leaf" class="h-5 w-5" />
                        </span>
                        <span class="text-base font-semibold tracking-tight">{{ config('app.name', 'FoodBridge') }}</span>
                    </a>
                @endif

                @if ($title || $roleLabel || isset($left))
                    <div class="hidden min-w-0 items-center gap-3 sm:flex">
                        @if ($roleLabel)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-border bg-card px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                {{ $roleLabel }}
                            </span>
                        @endif

                        @if ($title)
                            <h1 class="truncate text-base font-semibold">{{ $title }}</h1>
                        @endif

                        @isset($left)
                            <div class="flex min-w-0 items-center gap-4">{{ $left }}</div>
                        @endisset
                    </div>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                @isset($center)
                    <div class="hidden items-center md:flex">{{ $center }}</div>
                @endisset

                <div class="flex items-center gap-2 sm:gap-3">
                    {{ $slot }}
                </div>

                @isset($right)
                    <div class="hidden items-center sm:flex">{{ $right }}</div>
                @endisset
            </div>
        </div>
    </div>
</header>
