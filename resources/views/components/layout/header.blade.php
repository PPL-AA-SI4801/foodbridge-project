@props([
    'title' => null,
    'breadcrumb' => null,
    'roleLabel' => null,
    'maxWidth' => null,
    'sticky' => false,
    'displayName' => null,
])

@php
    $items = $breadcrumb;

    if (is_string($items)) {
        $items = [['label' => $items]];
    } elseif (is_null($items)) {
        $items = [];
    } else {
        $items = collect($items)
            ->map(fn ($item) => is_array($item) ? $item : ['label' => $item])
            ->all();
    }

    if (blank($items)) {
        $defaultCrumb = $roleLabel ?? $title;

        if (filled($defaultCrumb)) {
            $items = [['label' => $defaultCrumb]];
        }
    }
@endphp

<header @class(['border-b border-border bg-card', 'sticky top-0 z-20' => $sticky])>
    <div class="{{ filled($maxWidth) ? $maxWidth.' mx-auto' : '' }} px-4 py-3 sm:px-6 lg:px-7 lg:py-4">
        <div class="flex min-h-[56px] flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                @isset($sidebarToggle)
                    {{ $sidebarToggle }}
                @endisset

                <div class="min-w-0">
                    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
                        <a
                            href="{{ route(auth()->user()->dashboardRouteName()) }}"
                            class="shrink-0 transition-colors hover:text-foreground"
                        >
                            FoodBridge
                        </a>

                        @foreach ($items as $item)
                            <x-icon name="chevron-right" class="h-3 w-3 shrink-0" />

                            @if (filled($item['href'] ?? null))
                                <a
                                    href="{{ $item['href'] }}"
                                    class="truncate transition-colors hover:text-foreground"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @else
                                <span class="truncate font-medium text-foreground">{{ $item['label'] }}</span>
                            @endif
                        @endforeach
                    </nav>

                    @if ($title)
                        <h1 class="mt-0.5 truncate text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                            {{ $title }}
                        </h1>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @isset($status)
                    {{ $status }}
                @endisset

                @isset($actions)
                    {{ $actions }}
                @endisset

                <x-header.notifications />

                <div class="h-6 w-px bg-border" aria-hidden="true"></div>

                <x-header.profile-menu :display-name="$displayName" />
            </div>
        </div>
    </div>
</header>
