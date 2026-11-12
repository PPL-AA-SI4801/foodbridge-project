{{-- Sidebar admin: menu, grup manajemen data, profil, dan logout. --}}
@props(['pendingVerifications' => 0])

@php
    $navItems = [
        [
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'route' => 'dashboard.admin',
            'active' => request()->routeIs('dashboard.admin'),
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
        ['label' => 'Pengguna', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Postingan Makanan', 'route' => 'admin.food-posts.index', 'active' => request()->routeIs('admin.food-posts.*')],
        ['label' => 'Klaim', 'route' => 'admin.claims.index', 'active' => request()->routeIs('admin.claims.*')],
        ['label' => 'Kategori Gizi', 'route' => 'admin.categories.index', 'active' => request()->routeIs('admin.categories.*')],
    ];
@endphp

<x-layout.sidebar id="admin-sidebar" label="Navigasi administrator">
    <x-layout.sidebar-brand sidebar-id="admin-sidebar" />

    <x-layout.sidebar-search />

    <nav class="flex-1 overflow-y-auto pb-4">
        <x-layout.sidebar-section label="MENU UTAMA">
            @foreach ($navItems as $item)
                @if (\Illuminate\Support\Facades\Route::has($item['route']))
                    <x-layout.sidebar-link
                        :label="$item['label']"
                        :icon="$item['icon']"
                        :href="route($item['route'])"
                        :active="$item['active']"
                        :badge="$item['badge'] ?? null"
                    />
                @endif
            @endforeach
        </x-layout.sidebar-section>

        <x-layout.sidebar-section label="MANAJEMEN DATA" icon="database" class="mt-3 border-t border-sidebar-border pt-4">
            @foreach ($dataItems as $item)
                @if (\Illuminate\Support\Facades\Route::has($item['route']))
                    <x-layout.sidebar-link
                        :label="$item['label']"
                        :href="route($item['route'])"
                        :active="$item['active']"
                    />
                @endif
            @endforeach
        </x-layout.sidebar-section>

        <p
            x-show="navQuery.trim() !== '' && !navHasMatches($el)"
            x-cloak
            class="px-4 py-6 text-center text-sm text-muted-foreground"
        >
            Menu tidak ditemukan.
        </p>
    </nav>

    <x-layout.sidebar-footer />
</x-layout.sidebar>
