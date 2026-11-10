@props([])

@php
    $user = auth()->user();
    $donorProfile = $user->donorProfile;
    $pendingClaims = $donorProfile
        ? \App\Models\Claim::query()
            ->whereHas('post', fn ($query) => $query->where('donor_id', $donorProfile->id))
            ->whereIn('status', ['pending', 'menunggu'])
            ->count()
        : 0;

    $navItems = [
        [
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'route' => 'dashboard.donor',
            'active' => request()->routeIs('dashboard.donor'),
        ],
        [
            'label' => 'Posting Makanan',
            'icon' => 'utensils-crossed',
            'route' => 'donor.food-posts.index',
            'active' => request()->routeIs('donor.food-posts.*'),
        ],
        [
            'label' => 'Klaim Masuk',
            'icon' => 'clipboard-check',
            'route' => 'donor.claims.index',
            'active' => request()->routeIs('donor.claims.*'),
            'badge' => $pendingClaims,
        ],
        [
            'label' => 'Request Board',
            'icon' => 'message-square',
            'route' => 'donor.requests.index',
            'active' => request()->routeIs('donor.requests.*'),
        ],
        [
            'label' => 'Ulasan dan Laporan',
            'icon' => 'file-text',
            'route' => 'donor.reviews.index',
            'active' => request()->routeIs('donor.reviews.*'),
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
                    :badge="$item['badge'] ?? null"
                />
            @endif
        @endforeach
    </x-layout.sidebar-section>
</nav>

<x-layout.sidebar-footer />
