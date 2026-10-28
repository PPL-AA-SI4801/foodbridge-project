{{-- Sidebar admin: menu, grup manajemen data, profil, dan logout. --}}
@props(['pendingVerifications' => 0])

@php
$navItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'layout-dashboard',
        'route' => 'admin.dashboard',
        'active' => request()->routeIs('admin.dashboard'),
    ],
    [
        'label' => 'Verifikasi Akun',
        'icon' => 'user-check',
        'route' => 'admin.verifications.index',
        'active' => request()->routeIs('admin.verifications.*'),
        'badge' => $pendingVerifications,
    ],
    [
        'label' => 'Laporan Makanan',
        'icon' => 'triangle-alert',
        'route' => 'admin.reports.index',
        'active' => request()->routeIs('admin.reports.*'),
    ],
    [
        'label' => 'Request Board',
        'icon' => 'message-square',
        'route' => 'admin.requests.index',
        'active' => request()->routeIs('admin.requests.*'),
    ],
];

$dataItems = [
    ['label' => 'Pengguna', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*')],
    ['label' => 'Postingan Makanan', 'route' => 'admin.food-posts.index', 'active' => request()->routeIs('admin.food-posts.*')],
    ['label' => 'Klaim', 'route' => 'admin.claims.index', 'active' => request()->routeIs('admin.claims.*')],
    ['label' => 'Kategori Gizi', 'route' => 'admin.categories.index', 'active' => request()->routeIs('admin.categories.*')],
];

$link = fn (string $route): string => \Illuminate\Support\Facades\Route::has($route) ? route($route) : '#';
@endphp

<aside {{ $attributes->class(['fixed inset-y-0 left-0 z-50 w-[19rem] shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:static lg:z-auto lg:flex']) }}
    id="admin-sidebar"
    aria-label="Navigasi administrator"
    x-bind:class="sidebarOpen ? 'flex' : 'hidden lg:flex'"
    x-cloak>
    {{-- Brand --}}
    <div class="flex items-center gap-3 border-b border-sidebar-border p-5">
        <img
            src="{{ asset(rawurlencode('Logo Foodbridge.webp')) }}"
            alt="FoodBridge"
            class="h-12 w-auto max-w-full object-contain"
        >

        <button
            type="button"
            @click="$dispatch('close-sidebar')"
            class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
            aria-label="Tutup menu"
        >
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <div class="flex flex-col gap-1">
            @foreach ($navItems as $item)
                <a
                    href="{{ $link($item['route']) }}"
                    @class([
                        'relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors',
                        'bg-accent font-medium text-accent-foreground' => $item['active'],
                        'text-muted-foreground hover:bg-accent hover:text-accent-foreground' => ! $item['active'],
                    ])
                >
                    @if ($item['active'])
                        <span class="absolute bottom-2 left-0 top-2 w-0.5 rounded-full bg-secondary" aria-hidden="true"></span>
                    @endif

                    <x-icon :name="$item['icon']" class="h-4 w-4" />
                    <span>{{ $item['label'] }}</span>

                    @if (! empty($item['badge']))
                        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-secondary px-1.5 text-xs font-medium text-white">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Manajemen Data --}}
        <div class="mt-5 border-t border-border pt-5" x-data="{ dataOpen: true }">
            <button
                type="button"
                @click="dataOpen = ! dataOpen"
                class="flex w-full items-center gap-3 px-3 pb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground transition-colors hover:text-foreground"
                :aria-expanded="dataOpen"
            >
                <x-icon name="database" class="h-3.5 w-3.5" />
                <span>Manajemen Data</span>
                <x-icon name="chevron-down" class="ml-auto h-3.5 w-3.5 transition-transform" x-bind:class="dataOpen ? 'rotate-180' : ''" />
            </button>

            <div
                x-show="dataOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="ml-[1.4rem] mt-1 flex flex-col gap-0.5 border-l border-border pl-4"
            >
                @foreach ($dataItems as $item)
                    <a
                        href="{{ $link($item['route']) }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm transition-colors',
                            'bg-accent font-medium text-accent-foreground' => $item['active'],
                            'text-muted-foreground hover:bg-accent hover:text-accent-foreground' => ! $item['active'],
                        ])
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

    {{-- Pengguna dan logout --}}
    <div class="border-t border-sidebar-border p-4">
        <div class="mb-3 flex items-center gap-3 px-1">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                {{ str(auth()->user()->nama)->substr(0, 1)->upper() }}
            </span>
            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm font-medium">{{ auth()->user()->nama }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md border border-input px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                <x-icon name="log-out" class="h-4 w-4" />
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
