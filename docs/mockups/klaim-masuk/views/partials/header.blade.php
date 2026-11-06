@php
    $organization = config('foodbridge.organization');
@endphp

<header class="topbar">
    <div class="topbar-left">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <span>FoodBridge</span>
            <span class="breadcrumb-sep">›</span>
            <span class="breadcrumb-current">Klaim Masuk</span>
        </nav>
        <h2 class="greeting">Selamat Datang, {{ $organization['name'] }} 👋</h2>
    </div>

    <div class="top-actions">
        <button type="button" class="icon-btn" aria-label="Notifikasi">
            <x-icon name="bell" :size="18" />
            <span class="dot" aria-hidden="true"></span>
        </button>

        <button type="button" class="org" aria-haspopup="true">
            <span class="avatar">
                <x-icon name="star" :size="16" />
            </span>
            <span class="org-meta">
                <span class="org-name">{{ $organization['name'] }}</span>
                <span class="org-email">{{ $organization['email'] }}</span>
            </span>
            <x-icon name="chevron-down" :size="14" class="org-chevron" />
        </button>
    </div>
</header>
