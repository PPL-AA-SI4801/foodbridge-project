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

<header @class(['flex min-h-[79px] items-center border-b border-[#e8e9e8] bg-white', 'sticky top-0 z-20' => $sticky])>
    <div class="w-full {{ filled($maxWidth) ? $maxWidth.' mx-auto' : '' }} px-4 py-4">
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @isset($sidebarToggle)
                        {{ $sidebarToggle }}
                    @endisset
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    @isset($status)
                        {{ $status }}
                    @endisset

                    @isset($actions)
                        {{ $actions }}
                    @endisset

                    <div class="relative flex h-10 w-[42px] items-center justify-center rounded-[8px] border border-[#e8e9e8] bg-white">
                        <x-header.notifications />
                    </div>
                </div>
            </div>

            <div class="flex min-w-0 flex-col gap-2">
                <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ route(auth()->user()->dashboardRouteName()) }}"
                        class="shrink-0 text-[12px] font-normal leading-[normal] text-sidebar-muted transition-colors hover:text-foreground"
                    >
                        FoodBridge
                    </a>

                    @foreach ($items as $item)
                        <x-icon name="chevron-right" class="h-3 w-3 shrink-0 text-sidebar-muted" />

                        @if (filled($item['href'] ?? null))
                            <a
                                href="{{ $item['href'] }}"
                                class="truncate text-[12px] font-normal leading-[normal] text-[#0d1c2e] transition-colors hover:text-foreground"
                            >
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="truncate text-[12px] font-normal leading-[normal] text-[#0d1c2e]">
                                {{ $item['label'] }}
                            </span>
                        @endif
                    @endforeach
                </nav>

                @if ($title)
                    <h1 class="truncate text-[20px] font-medium leading-[1.2] tracking-[-0.6px] text-[#0d1c2e]">
                        {{ $title }}
                    </h1>
                @endif
            </div>
        </div>
    </div>
</header>
