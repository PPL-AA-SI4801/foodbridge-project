@props([])

@php
    $navItems = [
        [
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'route' => 'dashboard.recipient',
            'active' => request()->routeIs('dashboard.recipient'),
        ],
        [
            'label' => 'Katalog Makanan',
            'icon' => 'package',
            'route' => 'recipient.catalog.index',
            'active' => request()->routeIs('recipient.catalog.*'),
        ],
        [
            'label' => 'Klaim Saya',
            'icon' => 'clipboard-check',
            'route' => 'recipient.claims.index',
            'active' => request()->routeIs('recipient.claims.*'),
        ],
        [
            'label' => 'Request Board',
            'icon' => 'message-square',
            'route' => 'recipient.requests.index',
            'active' => request()->routeIs('recipient.requests.*'),
        ],
        [
            'label' => 'Ulasan dan Laporan',
            'icon' => 'file-text',
            'route' => 'recipient.reviews.index',
            'active' => request()->routeIs('recipient.reviews.*'),
        ],
        [
            'label' => 'Profil Saya',
            'icon' => 'user',
            'route' => 'profile.edit',
            'active' => request()->routeIs('profile.*'),
        ],
    ];
@endphp

<x-layout.sidebar-brand sidebar-id="app-sidebar" />

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
