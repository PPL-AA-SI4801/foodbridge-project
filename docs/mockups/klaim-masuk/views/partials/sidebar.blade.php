@php
    $user = config('foodbridge.user');
    $navigation = config('foodbridge.navigation');
@endphp

<aside class="sidebar">
    <a class="brand" href="{{ route('claims.index') }}">
        <x-icon name="flame" class="brand-mark" :size="26" />
        <span class="brand-name">FoodBridge</span>
    </a>

    <form class="sidebar-search" action="{{ route('claims.index') }}" method="GET" role="search">
        <x-icon name="search" :size="15" />
        <input type="search" name="q" placeholder="Pencarian..." aria-label="Pencarian">
        <span class="kbd">⌘ K</span>
    </form>

    <p class="nav-label">MENU UTAMA</p>

    <nav class="nav" aria-label="Menu utama">
        @foreach ($navigation as $item)
            <a
                href="{{ route('claims.index') }}"
                @class(['nav-item', 'is-active' => $item['active'] ?? false])
                @if ($item['active'] ?? false) aria-current="page" @endif
            >
                <x-icon :name="$item['icon']" :size="17" />
                <span>{{ $item['label'] }}</span>
                @isset($item['badge'])
                    <span class="nav-badge">{{ $item['badge'] }}</span>
                @endisset
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="user-row">
            <span class="avatar">
                <x-icon name="star" :size="16" />
            </span>
            <div class="user-meta">
                <p class="user-name">{{ $user['name'] }}</p>
                <p class="user-email">{{ $user['email'] }}</p>
            </div>
        </div>
        <button type="button" class="logout">
            <x-icon name="logout" :size="15" />
            Logout
        </button>
    </div>
</aside>
